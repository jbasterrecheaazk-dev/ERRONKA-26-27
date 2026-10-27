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
 * 2. MAPEO DE RUTAS
 *
 * Aquí relacionamos cada URL con el archivo PHP que debe mostrarse.
 * Es como un diccionario: clave = URL que escribe el usuario,
 * valor = archivo real en el servidor.
 */
$routes = [
    '/'             => 'views/pages/index.php',
    '/index'        => 'views/pages/index.php',
    '/login'        => 'views/pages/login.php',
    '/carrito'      => 'views/pages/carrito.php',
    '/perfil'       => 'views/pages/perfil.php',
];

/*
 * 3. ¿QUÉ ARCHIVO CARGAR?
 *
 * Primero buscamos la ruta en el array $routes.
 * Si no está, intentamos buscar un archivo con el mismo nombre que la URL.
 * Si tampoco existe, mostramos la página principal como fallback.
 */
if (array_key_exists($uri, $routes)) {
    $file = $routes[$uri];
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
    http_response_code(404);
    echo 'Página no encontrada';
}
