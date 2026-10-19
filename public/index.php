<?php

require_once __DIR__ . '/../app/helpers.php';

spl_autoload_register(function (string $class): void {
    $directories = [
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Models/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$route = current_route();
$segments = $route === '' ? [] : explode('/', $route);
$page = $segments[0] ?? '';

$routes = [
    '' => ['HomeController', 'index'],
    'profil' => ['ProfilController', 'index'],
    'akademik' => ['AkademikController', 'index'],
    'fasilitas' => ['FasilitasController', 'index'],
    'galeri' => ['GaleriController', 'index'],
    'berita' => ['BeritaController', 'index'],
    'kontak' => ['KontakController', 'index'],
    'ppdb' => ['PpdbController', 'index'],
    'informasi' => ['PpdbController', 'informasi'],
];

if ($page === 'berita' && isset($segments[1])) {
    (new BeritaController())->detail($segments[1]);
    return;
}

if (isset($routes[$page])) {
    [$controller, $method] = $routes[$page];
    (new $controller())->$method();
    return;
}

http_response_code(404);
(new ErrorController())->notFound();
