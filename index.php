<?php
/*
 * FRONT CONTROLLER - PUERTA DE ENTRADA DE LA APLICACIÓN
 *
 * El Front Controller es como el recepcionista de un hotel.
 * Toda petición que llega al sitio pasa primero por aquí,
 * y él decide a qué página o recurso enviarla.
 *
 * Con esto evitamos repetir código en todos lados y tenemos
 * un único punto de control para las rutas.
 */

// session_start() debe ir al principio para que toda la aplicación
// pueda comprobar si el usuario está logueado mediante $_SESSION
session_start();

// Contador de carrito desde base de datos
// Si el usuario tiene sesión iniciada, consulta la BD; si no, muestra 0
if (isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/models/CarritoModel.php';
    $carritoModel = new CarritoModel();
    $num_productos_carrito = $carritoModel->getContador((int)$_SESSION['usuario_id']);
} else {
    $num_productos_carrito = 0;
}

// Obtiene la ruta solicitada por el usuario en la barra de navegación
// $_SERVER['REQUEST_URI'] contiene todo lo que viene después de 'localhost:8000/'
// parse_url lo separa para quedarnos solo con la ruta, sin ?query=...
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normaliza la ruta: quita la barra final '/' para que
// '/login/' y '/login' sean lo mismo
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/'; // Si el usuario está en la raíz, ponemos /
}

/*
 * 1. RECURSOS ESTÁTICOS
 *
 * Si la URL pide algo de /public/, como /public/css/style.css o /public/img/logo.png,
 * devolvemos false para que el servidor de PHP lo sirva directamente sin pasar por PHP.
 * Esto es más rápido y evita cargar nuestro código para imágenes y CSS.
 */
if (strpos($uri, '/public/') === 0) {
    return false; // Deja que el servidor sirva el archivo estático
}

/*
 * 2. MAPEO DE RUTAS CON PROTECCIÓN DE SESIÓN
 *
 * Aquí relacionamos cada URL con el archivo PHP que debe mostrarse.
 * Es como un diccionario: clave = URL que escribe el usuario,
 * valor = archivo real en el servidor.
 *
 * Algunas rutas son privadas y requieren que el usuario esté logueado.
 * En esas rutas comprobamos $_SESSION['usuario_id'] antes de cargar la vista.
 */
$routes = [
    '/'             => 'views/pages/index.php',
    '/index'        => 'views/pages/index.php',
    '/login'        => 'views/pages/login.php',
    '/carrito'      => 'views/pages/carrito.php',
    '/perfil'       => 'views/pages/perfil.php',
    '/logout'       => 'controllers/AuthController.php?action=logout',
];

// Rutas que requieren sesión activa
$privateRoutes = ['/perfil', '/carrito'];

/*
 * 3. ¿QUÉ ARCHIVO CARGAR?
 *
 * Primero buscamos la ruta en el array $routes.
 * Si no está, intentamos buscar un archivo con el mismo nombre que la URL.
 * Si tampoco existe, mostramos la página principal como fallback.
 *
 * Protección: si la ruta es privada y no hay sesión, redirigimos a /login
 */
if (array_key_exists($uri, $routes)) {
    $file = $routes[$uri];

    // Comprobar si la ruta necesita sesión activa
    if (in_array($uri, $privateRoutes) && !isset($_SESSION['usuario_id'])) {
        header('Location: /login');
        exit;
    }

    // Si la ruta apunta a un controlador, delegamos la ejecución
    if (strpos($file, 'controllers/') === 0) {
        // Redirigimos directamente al controlador con sus parámetros
        header('Location: /' . $file);
        exit;
    }
} else {
    // Intento inteligente: quitar extensión .php de la URL y mapear a archivo
    $possible = __DIR__ . '/' . ltrim($uri, '/') . '.php';
    if (file_exists($possible)) {
        $file = $possible;
    } else {
        // Fallback a index por 404 simple
        $file = 'views/pages/index.php';
    }
}

/*
 * 4. SEGURIDAD ANTES DE CARGAR
 *
 * Comprobamos que el archivo:
 * - Existe realmente en el disco
 * - Está dentro de nuestra carpeta del proyecto (evita ataques de ruta)
 *
 * Si todo es correcto, lo cargamos con require.
 * Si no, devolvemos error 404 como si la página no existiera.
 */
$realBase = realpath(__DIR__);   // Carpeta raíz del proyecto
$realFile = realpath($file);     // Ruta real del archivo que queremos cargar

if ($realFile && strpos($realFile, $realBase) === 0 && file_exists($realFile)) {
    require $realFile;
} else {
    // Si el archivo no existe como vista, intentamos de forma directa como controlador
    if (strpos($file, 'controllers/') === 0) {
        require $realBase . '/' . $file;
    } else {
        http_response_code(404);
        echo 'Página no encontrada';
    }
}
