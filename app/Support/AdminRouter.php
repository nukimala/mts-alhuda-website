<?php
/**
 * Router panel admin.
 *
 * Front controller (public/index.php) hanya meneruskan segmen URL ke sini.
 * Semua aturan bentuk URL admin ada di satu tempat, jadi menambah modul baru
 * tidak menyentuh file front controller.
 *
 * Bentuk URL yang dipakai semua modul konten:
 *
 *   /admin/{modul}                 GET   daftar
 *   /admin/{modul}                 POST  simpan baru
 *   /admin/{modul}/create          GET   formulir baru
 *   /admin/{modul}/{id}/edit       GET   formulir sunting
 *   /admin/{modul}/{id}            POST  simpan perubahan
 *   /admin/{modul}/{id}/delete     POST  hapus
 *   /admin/{modul}/{id}/status     POST  ubah status (modul yang punya status)
 *
 * Modul yang belum dibangun tidak punya entri di sini, sehingga URL-nya
 * mengembalikan 404 — bukan halaman kosong atau tautan mati.
 */

class AdminRouter
{
    /**
     * Modul konten yang memakai pola URL seragam di atas.
     *
     * `actions` mendaftar aksi tambahan khusus modul: nama segmen URL =>
     * nama method controller. Semuanya hanya menerima POST — aksi yang
     * mengubah data tidak boleh bisa dipicu dari tautan atau prefetch browser.
     *
     * @var array<string,array{controller:string,label:string,actions?:array<string,string>}>
     */
    private const CRUD_MODULES = [
        'berita'   => ['controller' => 'AdminBeritaController', 'label' => 'Berita'],
        'pengguna' => [
            'controller' => 'AdminPenggunaController',
            'label'      => 'Pengguna',
            'actions'    => [
                'aktifkan'    => 'activate',
                'nonaktifkan' => 'deactivate',
                'reset'       => 'resetPassword',
                'buka-kunci'  => 'unlock',
            ],
        ],
    ];

    /** @var list<string> */
    private array $segments;

    private string $method;

    /**
     * @param list<string> $segments Hasil explode('/', route) untuk /admin/...
     */
    public function __construct(array $segments)
    {
        $this->segments = $segments;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function dispatch(): void
    {
        $action = $this->segments[1] ?? '';

        switch ($action) {
            case '':
                (new AdminController())->dashboard();
                return;

            case 'login':
                $this->run('AuthController', $this->method === 'POST' ? 'login' : 'loginForm');
                return;

            case 'logout':
                if ($this->method === 'POST') {
                    (new AuthController())->logout();
                } else {
                    header('Location: ' . base_url('admin'));
                }
                return;

            case 'ganti-sandi':
                $this->run('AuthController', $this->method === 'POST' ? 'changePassword' : 'changePasswordForm');
                return;
        }

        if (isset(self::CRUD_MODULES[$action])) {
            $this->dispatchCrud($action, self::CRUD_MODULES[$action]['controller']);
            return;
        }

        $this->notFound();
    }

    /**
     * Pola CRUD seragam: daftar, tambah, sunting, hapus, ubah status.
     */
    private function dispatchCrud(string $module, string $controllerClass): void
    {
        if (!class_exists($controllerClass)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerClass();
        $sub = $this->segments[2] ?? '';
        $tail = $this->segments[3] ?? '';
        $base = base_url('admin/' . $module);

        if ($sub === '') {
            if ($this->method === 'POST') {
                $controller->store();
            } else {
                $controller->index();
            }
            return;
        }

        if ($sub === 'create') {
            if ($this->method === 'POST') {
                $controller->store();
            } else {
                $controller->create();
            }
            return;
        }

        if (!ctype_digit($sub)) {
            $this->notFound();
            return;
        }

        $id = (int) $sub;

        if ($tail === 'edit') {
            $controller->edit($this->segments);
            return;
        }

        if ($tail === 'delete') {
            if ($this->method === 'POST') {
                $controller->destroy($this->segments);
            } else {
                // Hapus tidak boleh lewat GET: prefetch browser atau crawler
                // akan menghapus data tanpa ada orang yang menekan tombol.
                header('Location: ' . $base);
            }
            return;
        }

        if ($tail === 'status') {
            // Tidak semua modul punya konsep status; tanpa pemeriksaan ini
            // URL /admin/<modul>/<id>/status akan memanggil method yang tidak ada.
            if ($this->method === 'POST' && method_exists($controller, 'setStatus')) {
                $controller->setStatus($this->segments);
            } else {
                header('Location: ' . $base);
            }
            return;
        }

        // Aksi tambahan khusus modul (aktifkan, reset sandi, buka kunci, ...).
        $actions = self::CRUD_MODULES[$module]['actions'] ?? [];

        if (isset($actions[$tail])) {
            if ($this->method !== 'POST') {
                header('Location: ' . $base);
                return;
            }

            $method = $actions[$tail];

            if (!method_exists($controller, $method)) {
                $this->notFound();
                return;
            }

            $controller->$method($this->segments);
            return;
        }

        if ($tail === '' && $this->method === 'POST') {
            $controller->update($this->segments);
            return;
        }

        $this->notFound();
    }

    /**
     * Panggil method controller tanpa membuat pemanggil tahu nama kelasnya.
     */
    private function run(string $controllerClass, string $method): void
    {
        (new $controllerClass())->$method();
    }

    private function notFound(): void
    {
        http_response_code(404);
        (new ErrorController())->notFound();
    }
}
