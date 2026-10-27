<?php
// Front Controller básico para SoinuTruk MVC
// Obtiene la ruta solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normaliza la ruta quitando query strings y trailing slash
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// 1. Si la petición es a un recurso estático dentro de /public, deja que el servidor lo sirva
if (strpos($uri, '/public/') === 0) {
    return false; // Laravel-ish: devolver false permite que PHP built-in sirva el archivo
}

// 2. Mapeo de rutas a vistas
$routes = [
    '/'             => 'views/pages/index.php',
    '/index'        => 'views/pages/index.php',
    '/login'        => 'views/pages/login.php',
    '/carrito'      => 'views/pages/carrito.php',
    '/perfil'       => 'views/pages/perfil.php',
];

// 3. Determina qué archivo cargar
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

// 4. Seguridad: comprobar que el archivo existe y está dentro del proyecto
$realBase = realpath(__DIR__);
$realFile = realpath($file);
if ($realFile && strpos($realFile, $realBase) === 0 && file_exists($realFile)) {
    require $realFile;
} else {
    http_response_code(404);
    echo 'Página no encontrada';
}
