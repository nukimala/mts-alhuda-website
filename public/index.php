<?php

require_once __DIR__ . '/../app/helpers.php';

spl_autoload_register(function (string $class): void {
    $directories = [
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Controllers/Api/',
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Support/',
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

// ---------------------------------------------------------------------------
// API v1 — dipakai aplikasi mobile dan integrasi lain
// ---------------------------------------------------------------------------

if ($page === 'api') {
    (new ApiRouter())->dispatch($segments);
    return;
}

// ---------------------------------------------------------------------------
// Panel admin
// ---------------------------------------------------------------------------

if ($page === 'admin') {
    $action = $segments[1] ?? '';
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    $auth = new AuthController();

    switch ($action) {
        case '':
            (new AdminController())->dashboard();
            return;

        case 'login':
            if ($method === 'POST') {
                $auth->login();
            } else {
                $auth->loginForm();
            }
            return;

        case 'logout':
            if ($method === 'POST') {
                $auth->logout();
            } else {
                header('Location: ' . base_url('admin'));
            }
            return;

        case 'ganti-sandi':
            if ($method === 'POST') {
                $auth->changePassword();
            } else {
                $auth->changePasswordForm();
            }
            return;

        default:
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
    }
}

// ---------------------------------------------------------------------------
// Website publik
// ---------------------------------------------------------------------------

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
