<?php
/**
 * INDEX.PHP — FRONT CONTROLLER (PUERTA DE ENTRADA)
 *
 * Este archivo es el único punto de entrada de toda la aplicación.
 * Recibe todas las peticiones HTTP, decide qué cargar (vista o controlador)
 * y delega la ejecución. Patrón MVC clásico.
 *
 * Flujo de ejecución:
 * 1. Iniciar sesión PHP ($_SESSION)
 * 2. Obtener y normalizar URI de la petición
 * 3. Servir recursos estáticos directamente (CSS, JS, imágenes)
 * 4. Procesar endpoints API (JSON para AJAX)
 * 5. Mapear rutas a vistas PHP (router simple)
 * 6. Cargar datos específicos según la ruta (/producto, /admin)
 * 7. Incluir la vista/controlador correspondiente
 *
 * Rutas soportadas:
 *   GET /           → index.php (catálogo)
 *   GET /login      → login.php
 *   GET /carrito    → carrito.php (requiere sesión)
 *   GET /perfil     → perfil.php (requiere sesión)
 *   GET /producto?id=X → producto.php + datos del producto
 *   GET /admin      → AdminController (requiere rol admin)
 *
 * Endpoints API:
 *   POST /mis-articulos?action=create → ProductoController->store()
 *   GET/POST /api/saskia[/*]          → SaskiaController (carrito AJAX)
 *   POST /api/eskaerak/procesar       → EskaerakController->procesar()
 *   GET /api/productos?q=&kategoria=&pagina=&limite= → JSON catálogo paginado
 *   GET /api/producto?id=X            → JSON datos producto (solo dueño)
 */

// ================================================================
// 0. INICIALIZACIÓN DE SESIÓN
// ================================================================
// session_start() debe ir al principio para que toda la aplicación
// pueda comprobar si el usuario está logueado mediante $_SESSION
session_start();

// ================================================================
// 0b. CONTADOR DEL CARRITO (SSR)
// ================================================================
// Cargamos el modelo aquí para mostrar el badge en el header
// sin depender de JS. Así el contador se ve inmediatamente al cargar.
if (isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/models/SaskiaModel.php';
    $saskiaModel = new SaskiaModel();
    // getContador devuelve el número de items en la tabla 'saskia'
    // para el usuario logueado
    $num_productos_carrito = $saskiaModel->getContador((int)$_SESSION['usuario_id']);
} else {
    $num_productos_carrito = 0;
}

// ================================================================
// 1. ANÁLISIS DE LA URI
// ================================================================
// Obtiene la ruta solicitada por el usuario en la barra de navegación
// $_SERVER['REQUEST_URI'] contiene todo lo que viene después de 'localhost:8000/'
// parse_url lo separa para quedarnos solo con la ruta, sin ?query=...
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normaliza la ruta: quita la barra final '/' para que
// '/login/' y '/login' sean lo mismo (mejora UX, evita rutas duplicadas)
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/'; // Si el usuario está en la raíz, ponemos /
}

/*
 * 2. RECURSOS ESTÁTICOS
 *
 * Si la URL pide algo de /public/, como /public/css/style.css o /public/img/logo.png,
 * devolvemos false para que el servidor de PHP lo sirva directamente sin pasar por PHP.
 * Esto es más rápido y evita cargar nuestro código para imágenes y CSS.
 */
if (strpos($uri, '/public/') === 0) {
    return false; // Deja que el servidor sirva el archivo estático
}

/*
 * 3. PROCESAR FORMULARIO DE CREACIÓN/ACTUALIZACIÓN DE PRODUCTO
 *
 * Si la URL tiene ?action=create y es una petición POST,
 * ejecutamos el controlador directamente aquí para crear o
 * actualizar un producto antes de cargar cualquier vista.
 *
 * Nota: esta lógica está aquí en lugar de en el modelo de rutas
 * porque es una operación de escritura que no debe pasar por el router normal.
 */
if ($uri === '/mis-articulos' && isset($_GET['action']) && $_GET['action'] === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controllers/ProductoController.php';
    $controller = new ProductoController();
    $controller->create();
}

// ================================================================
// 4. MAPEO DE RUTAS (ROUTE TABLE)
// ================================================================
// Aquí relacionamos cada URL con el archivo PHP que debe mostrarse.
// Es como un diccionario: clave = URL que escribe el usuario,
// valor = archivo real en el servidor.
//
// Algunas rutas son privadas y requieren que el usuario esté logueado.
// En esas rutas comprobamos $_SESSION['usuario_id'] antes de cargar la vista.
$routes = [
    '/'             => 'views/pages/index.php',
    '/index'        => 'views/pages/index.php',
    '/login'        => 'views/pages/login.php',
    '/carrito'      => 'views/pages/carrito.php',
    '/saskia'       => 'views/pages/carrito.php', // Alias basco para carrito
    '/perfil'       => 'views/pages/perfil.php',
    '/mis-articulos' => 'views/pages/perfil.php', // Redirige al panel de venta
    '/admin'        => 'views/pages/admin.php',
    '/mis-compras'  => 'views/pages/mis-compras.php',
    '/producto'     => 'views/pages/producto.php',
    '/logout'       => 'controllers/AuthController.php?action=logout',
];

// Rutas que requieren sesión activa (autenticación obligatoria)
$privateRoutes = ['/perfil', '/carrito', '/mis-articulos', '/saskia', '/admin', '/mis-compras'];

/*
 * 5. RESOLUCIÓN DE RUTA
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

    // Ruta de administración: cargar datos desde el controlador antes de mostrar la vista
    // AdminController->index() carga estadísticas y productos pendientes
    if ($uri === '/admin') {
        if (ob_get_level()) ob_clean();
        require_once __DIR__ . '/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        exit;
    }

    // Ruta de detalle de producto: cargar datos desde el modelo antes de mostrar la vista
    // Busca el producto por ID y lo pasa como variable $producto a la vista
    if ($uri === '/producto') {
        $productoId = (int)($_GET['id'] ?? 0);
        if ($productoId <= 0) {
            header('Location: /');
            exit;
        }
        require_once __DIR__ . '/models/ProductoModel.php';
        $productoModel = new ProductoModel();
        $producto = $productoModel->obtenerPorId($productoId);
        if ($producto === null) {
            header('Location: /');
            exit;
        }
        // $producto queda disponible para que la vista lo use
    }
} elseif ($uri === '/api/productos') {
    // ================================================================
    // API: Catálogo filtrado y paginado (GET)
    // ================================================================
    // Público: cualquier visitante puede ver los productos
    // Parámetros: q (texto), kategoria (int), pagina (int), limite (int)
    if (ob_get_level()) ob_clean();

    require_once __DIR__ . '/models/ProductoModel.php';
    require_once __DIR__ . '/models/KategoriaModel.php';

    // Soporte para búsqueda por IDs múltiple (favoritos del usuario)
    $ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : [];
    if (!empty($ids)) {
        $model = new ProductoModel();
        $result = $model->obtenerPorIds($ids);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Búsqueda con filtros y paginación
    $q = trim($_GET['q'] ?? '');
    $kategoriaId = (int)($_GET['kategoria'] ?? 0);
    $page = (int)($_GET['pagina'] ?? 1);
    $limit = (int)($_GET['limite'] ?? 20);

    $model = new ProductoModel();
    $result = $model->obtenerFiltrados($q, $kategoriaId, $page, $limit);

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
} elseif ($uri === '/api/producto') {
    // ================================================================
    // API: Datos de un producto individual (solo dueño)
    // ================================================================
    // Solo usuarios autenticados pueden editar artículos
    // Devuelve JSON completo del producto para el formulario de edición
    if (ob_get_level()) ob_clean();

    if (!isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    require_once __DIR__ . '/models/ProductoModel.php';
    $productoId = (int)($_GET['id'] ?? 0);
    if ($productoId <= 0) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }
    $model = new ProductoModel();
    $producto = $model->obtenerPorId($productoId);
    if (!$producto || (int)$producto['saltzaile_id'] !== (int)$_SESSION['usuario_id']) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'No autorizado o no encontrado']);
        exit;
    }

    // BLOQUEO DE EDICIÓN: No permitir cargar datos para editar si el artículo ya fue aprobado, rechazado o vendido
    $onarpenEgoera = $producto['onarpen_egoera'] ?? 'pendentea';
    if ($onarpenEgoera !== 'pendentea') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Ezin da jaonartutako, baztertutako edo saldutako artikulurik editatu']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($producto);
    exit;
} elseif (strpos($uri, '/api/saskia') === 0) {
    // ================================================================
    // API: Operaciones del carrito (CRUD via SaskiaController)
    // ================================================================
    // Endpoints:
    //   GET  /api/saskia       → index()    (obtener items del carrito)
    //   POST /api/saskia/gehitu  → gehitu()  (añadir producto al carrito)
    //   POST /api/saskia/kendu   → kendu()   (eliminar producto del carrito)
    if (ob_get_level()) ob_clean();

    require_once __DIR__ . '/controllers/SaskiaController.php';
    $controller = new SaskiaController();

    $action = '';
    if ($uri === '/api/saskia') {
        $action = 'index';
    } elseif (preg_match('#^/api/saskia/(\w+)$#', $uri, $m)) {
        $action = $m[1];
    }

    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'gehitu':
            $controller->gehitu();
            break;
        case 'kendu':
            $controller->kendu();
            break;
        default:
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            exit;
    }
} elseif (strpos($uri, '/api/eskaerak') === 0) {
    // ================================================================
    // API: Checkout / Procesamiento de pedidos
    // ================================================================
    // Endpoint: POST /api/eskaerak/procesar
    // Ejecuta transacción PDO: bloquea productos, resta stock, crea pedido
    if (ob_get_level()) ob_clean();

    require_once __DIR__ . '/controllers/EskaerakController.php';
    $action = '';
    if (preg_match('#^/api/eskaerak/(\w+)$#', $uri, $m)) {
        $action = $m[1];
    }

    if ($action === 'procesar') {
        $controller = new EskaerakController();
        $controller->procesar();
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint no encontrado']);
        exit;
    }
} elseif (strpos($uri, '/api/admin') === 0) {
    // ================================================================
    // API: Operaciones de administración (solo rol admin)
    // ================================================================
    // Endpoints:
    //   POST /api/admin/onartu    → onartuAjax() (aprobar producto)
    //   POST /api/admin/baztertu  → baztertuAjax() (rechazar producto)
    //   POST /api/admin/ezabatu   → ezabatuAjax() (eliminar producto)
    if (ob_get_level()) ob_clean();

    require_once __DIR__ . '/controllers/AdminController.php';
    $action = '';
    if (preg_match('#^/api/admin/(\w+)$#', $uri, $m)) {
        $action = $m[1];
    }

    $controller = new AdminController();
    // Soportar tanto métodos con sufijo Ajax como sin él (onartu, baztertu)
    $methodName = $action;
    if (!method_exists($controller, $methodName)) {
        $methodName = $action . 'Ajax';
    }
    if (method_exists($controller, $methodName)) {
        $controller->{$methodName}();
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint no encontrado']);
        exit;
    }
} elseif ($uri === '/admin/aprobar' || $uri === '/admin/deshabilitar') {
    // ================================================================
    // Legacy routing para Admin (GET)
    // ================================================================
    // Rutas antiguas que aún se soportan por compatibilidad
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/controllers/AdminController.php';
    $controller = new AdminController();
    $action = basename($uri);
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
} else {
    // ================================================================
    // 6. FALLBACK INTELIGENTE
    // ================================================================
    // Intento inteligente: quitar extensión .php de la URL y mapear a archivo
    // Esto permite acceder directamente a archivos PHP dentro de la estructura del proyecto
    $possible = __DIR__ . '/' . ltrim($uri, '/') . '.php';
    if (file_exists($possible)) {
        $file = $possible;
    } else {
        // Fallback a index por 404 simple
        $file = 'views/pages/index.php';
    }
}

/*
 * 7. SEGURIDAD ANTES DE CARGAR
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
