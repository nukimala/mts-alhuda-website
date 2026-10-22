<?php
/**
 * Router API v1.
 *
 * Semua permintaan di bawah /api/ masuk ke sini. Tidak ada HTML yang boleh
 * keluar dari jalur ini — bahkan saat terjadi galat fatal. Klien mobile
 * menerima JSON, atau tidak menerima apa pun. Tidak pernah halaman error PHP.
 */

class ApiRouter
{
    /**
     * @param list<string> $segments hasil explode('/', current_route())
     */
    public function dispatch(array $segments): void
    {
        $this->prepareEnvironment();
        $this->sendCors();

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        // Buffer output. Kalau ada galat fatal di tengah jalan, isi buffer
        // dibuang dan diganti JSON yang bersih.
        ob_start();
        $finished = false;

        register_shutdown_function(static function () use (&$finished): void {
            if ($finished) {
                return;
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }

            echo json_encode([
                'success' => false,
                'error'   => [
                    'code'    => 'SERVER_ERROR',
                    'message' => 'Terjadi kesalahan di server.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        try {
            $this->route($segments, $method);
        } catch (Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            error_log('[api] ' . get_class($e) . ': ' . $e->getMessage()
                . ' @ ' . $e->getFile() . ':' . $e->getLine());

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }

            echo json_encode([
                'success' => false,
                'error'   => [
                    'code'    => 'SERVER_ERROR',
                    'message' => 'Terjadi kesalahan di server.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $finished = true;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * @param list<string> $segments
     */
    private function route(array $segments, string $method): void
    {
        // segments: ['api', 'v1', ...]
        $version = $segments[1] ?? '';

        if ($version === '') {
            ApiResponse::error(
                'VERSION_REQUIRED',
                'Sebutkan versi API. Contoh: /api/v1/health',
                400
            );
            return;
        }

        if ($version !== 'v1') {
            ApiResponse::error(
                'VERSION_UNSUPPORTED',
                'Versi API tidak dikenal. Versi yang tersedia: v1.',
                404
            );
            return;
        }

        $path = implode('/', array_slice($segments, 2));

        // Pola boleh memuat parameter, mis. 'posts/{slug}'. Nama parameter
        // diteruskan ke method controller sebagai array asosiatif.
        $routes = [
            'GET' => [
                'health'                 => ['ApiAuthController', 'health'],
                'me'                     => ['ApiAuthController', 'me'],
                'settings/public'        => ['ApiAuthController', 'publicSettings'],

                'posts'                  => ['ApiContentController', 'posts'],
                'posts/{slug}'           => ['ApiContentController', 'postDetail'],
                'announcements'          => ['ApiContentController', 'announcements'],
                'agendas'                => ['ApiContentController', 'agendas'],
                'facilities'             => ['ApiContentController', 'facilities'],
                'galleries'              => ['ApiContentController', 'galleries'],
                'pages/{slug}'           => ['ApiContentController', 'pageDetail'],

                'schedules'              => ['ApiAcademicController', 'schedules'],
                'assignments'            => ['ApiAcademicController', 'assignments'],
                'assignments/{publicId}' => ['ApiAcademicController', 'assignmentDetail'],
                'materials'              => ['ApiAcademicController', 'materials'],
            ],
            'POST' => [
                'auth/login'   => ['ApiAuthController', 'login'],
                'auth/refresh' => ['ApiAuthController', 'refresh'],
                'auth/logout'  => ['ApiAuthController', 'logout'],
            ],
        ];

        $matched = $this->matchRoute($routes[$method] ?? [], $path);

        if ($matched !== null) {
            [$controller, $action, $params] = $matched;
            (new $controller())->$action($params);
            return;
        }

        // Path ada, tapi metodenya salah → 405 dengan header Allow.
        $allowed = [];
        foreach ($routes as $verb => $patterns) {
            if ($this->matchRoute($patterns, $path) !== null) {
                $allowed[] = $verb;
            }
        }

        if ($allowed !== []) {
            $allowed[] = 'OPTIONS';
            header('Allow: ' . implode(', ', $allowed));
            ApiResponse::error(
                'METHOD_NOT_ALLOWED',
                'Metode ' . $method . ' tidak dipakai di endpoint ini. Pakai: ' . implode(', ', $allowed) . '.',
                405
            );
            return;
        }

        ApiResponse::error(
            'ENDPOINT_NOT_FOUND',
            'Endpoint tidak ditemukan. Lihat daftar di /api/v1/health.',
            404
        );
    }

    /**
     * Cocokkan satu path dengan daftar pola.
     *
     * @param array<string,array{0:string,1:string}> $patterns
     * @return array{0:string,1:string,2:array<string,string>}|null
     */
    private function matchRoute(array $patterns, string $path): ?array
    {
        foreach ($patterns as $pattern => $target) {
            $params = $this->matchPattern((string) $pattern, $path);

            if ($params !== null) {
                return [$target[0], $target[1], $params];
            }
        }

        return null;
    }

    /**
     * Pola 'posts/{slug}' cocok dengan 'posts/contoh-berita' dan menghasilkan
     * ['slug' => 'contoh-berita'].
     *
     * Jumlah segmen harus sama. Tanpa aturan itu, 'posts/{slug}' akan ikut
     * menangkap 'posts/1/komentar' dan controller menerima nilai yang bukan
     * slug.
     *
     * @return array<string,string>|null
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $patternParts = $pattern === '' ? [] : explode('/', $pattern);
        $pathParts = $path === '' ? [] : explode('/', $path);

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];

        foreach ($patternParts as $index => $part) {
            $value = $pathParts[$index];

            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $part, $m) === 1) {
                if ($value === '') {
                    return null;
                }

                $params[$m[1]] = $value;
                continue;
            }

            if ($part !== $value) {
                return null;
            }
        }

        return $params;
    }

    /**
     * Lingkungan API: tanpa HTML, tanpa peringatan PHP di badan respons.
     */
    private function prepareEnvironment(): void
    {
        ini_set('display_errors', '0');
        ini_set('html_errors', '0');
        header_remove('X-Powered-By');

        // Endpoint API tidak memakai cookie sesi, jadi tidak ada risiko CSRF.
        // Token dikirim lewat header Authorization.
        ini_set('session.use_cookies', '0');
    }

    /**
     * CORS untuk API.
     *
     * Origin dibuka lebar karena autentikasi memakai header Authorization,
     * bukan cookie. Tanpa cookie, membuka origin tidak memberi penyerang
     * akses apa pun. Access-Control-Allow-Credentials sengaja TIDAK dikirim.
     */
    private function sendCors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Max-Age: 86400');
        header('Vary: Origin');
    }
}
