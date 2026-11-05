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

        // ---------- Berita ----------
        case 'berita':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminBeritaController())->index();
                    return;
                case 'create':
                    (new AdminBeritaController())->create();
                    return;
                case 'store':
                    (new AdminBeritaController())->store();
                    return;
                case 'edit':
                    (new AdminBeritaController())->edit((int) ($segments[3] ?? 0));
                    return;
                case 'update':
                    (new AdminBeritaController())->update((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminBeritaController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Galeri ----------
        case 'galeri':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminGaleriController())->index();
                    return;
                case 'create':
                    (new AdminGaleriController())->create();
                    return;
                case 'store':
                    (new AdminGaleriController())->store();
                    return;
                case 'edit':
                    (new AdminGaleriController())->edit((int) ($segments[3] ?? 0));
                    return;
                case 'update':
                    (new AdminGaleriController())->update((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminGaleriController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Fasilitas ----------
        case 'fasilitas':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminFasilitasController())->index();
                    return;
                case 'create':
                    (new AdminFasilitasController())->create();
                    return;
                case 'store':
                    (new AdminFasilitasController())->store();
                    return;
                case 'edit':
                    (new AdminFasilitasController())->edit((int) ($segments[3] ?? 0));
                    return;
                case 'update':
                    (new AdminFasilitasController())->update((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminFasilitasController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Jadwal ----------
        case 'jadwal':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminJadwalController())->index();
                    return;
                case 'store':
                    (new AdminJadwalController())->store();
                    return;
                case 'delete':
                    (new AdminJadwalController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Presensi ----------
        case 'presensi':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminPresensiController())->index();
                    return;
                case 'correct':
                    (new AdminPresensiController())->correct();
                    return;
            }
            break;

        // ---------- Pengguna ----------
        case 'pengguna':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminPenggunaController())->index();
                    return;
                case 'store':
                    (new AdminPenggunaController())->store();
                    return;
                case 'reset-password':
                    (new AdminPenggunaController())->resetPassword((int) ($segments[3] ?? 0));
                    return;
                case 'toggle-status':
                    (new AdminPenggunaController())->toggleStatus((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminPenggunaController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- PPDB ----------
        case 'ppdb':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminPpdbController())->index();
                    return;
                case 'store':
                    (new AdminPpdbController())->store();
                    return;
                case 'toggle-document':
                    (new AdminPpdbController())->toggleDocument((int) ($segments[3] ?? 0));
                    return;
                case 'delete-document':
                    (new AdminPpdbController())->deleteDocument((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Kontak / Kotak Masuk ----------
        case 'kontak':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminKontakController())->index();
                    return;
                case 'mark-read':
                    (new AdminKontakController())->markRead((int) ($segments[3] ?? 0));
                    return;
                case 'mark-replied':
                    (new AdminKontakController())->markReplied((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminKontakController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Pengaturan ----------
        case 'pengaturan':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminPengaturanController())->index();
                    return;
                case 'update':
                    (new AdminPengaturanController())->update();
                    return;
            }
            break;

        // ---------- Menu ----------
        case 'menu':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminMenuController())->index();
                    return;
                case 'create':
                    (new AdminMenuController())->create();
                    return;
                case 'store':
                    (new AdminMenuController())->store();
                    return;
                case 'edit':
                    (new AdminMenuController())->edit((int) ($segments[3] ?? 0));
                    return;
                case 'update':
                    (new AdminMenuController())->update((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminMenuController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

        // ---------- Laporan ----------
        case 'laporan':
            $sub = $segments[2] ?? '';
            switch ($sub) {
                case '':
                    (new AdminLaporanController())->index();
                    return;
                case 'read':
                    (new AdminLaporanController())->read((int) ($segments[3] ?? 0));
                    return;
                case 'archive':
                    (new AdminLaporanController())->archive((int) ($segments[3] ?? 0));
                    return;
                case 'delete':
                    (new AdminLaporanController())->delete((int) ($segments[3] ?? 0));
                    return;
            }
            break;

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

if ($page === 'kontak' && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    (new KontakController())->send();
    return;
}

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
