<?php
/**
 * API v1 — autentikasi dan profil.
 *
 * Semua endpoint mengembalikan JSON. Tidak pernah HTML, tidak pernah
 * stack trace. Kode galat selalu berbentuk:
 *   { "success": false, "error": { "code": "...", "message": "..." } }
 */

class ApiAuthController
{
    /**
     * POST /api/v1/auth/login
     * Body: { identifier, password, device_name?, platform? }
     */
    public function login(): void
    {
        $input = ApiResponse::input();

        $identifier = ApiResponse::str($input, 'identifier');
        $password = (string) ($input['password'] ?? '');
        $deviceName = ApiResponse::str($input, 'device_name');
        $platform = ApiResponse::str($input, 'platform', 'unknown');

        if ($identifier === '' || $password === '') {
            ApiResponse::validation([
                'identifier' => $identifier === '' ? 'Wajib diisi.' : null,
                'password'   => $password === '' ? 'Wajib diisi.' : null,
            ]);
            return;
        }

        // Batasi 10 percobaan per 5 menit per IP. Mencegah brute force.
        $ip = RateLimiter::clientIp();
        $limit = RateLimiter::hit('api-login:' . $ip, 10, 300);

        if (!$limit['allowed']) {
            header('Retry-After: ' . $limit['retry_after']);
            ApiResponse::tooMany('Terlalu banyak percobaan masuk. Coba lagi dalam ' . $limit['retry_after'] . ' detik.');
            return;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, name, email, username, password_hash, role, role_label,
                    is_active, must_change_password, failed_login_attempts, locked_until
             FROM users
             WHERE (email = ? OR username = ?) AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user === false) {
            // Waktu respons disamakan supaya tidak membocorkan apakah akun ada.
            password_verify($password, '$2y$10$usesomesillystringforsaltXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
            AuditLog::write(null, 'api.login.failed', 'users', null, 'Login API gagal — akun tidak dikenal', $ip);
            ApiResponse::error('INVALID_CREDENTIALS', 'Nama pengguna atau kata sandi salah.', 401);
            return;
        }

        $userId = (int) $user['id'];

        if ($user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time()) {
            $sisa = (int) ceil((strtotime((string) $user['locked_until']) - time()) / 60);
            AuditLog::write($userId, 'api.login.locked', 'users', $userId, 'Login API ditolak — akun terkunci', $ip);
            ApiResponse::error('ACCOUNT_LOCKED', 'Akun dikunci sementara. Coba lagi dalam ' . $sisa . ' menit.', 423);
            return;
        }

        if ((int) $user['is_active'] !== 1) {
            AuditLog::write($userId, 'api.login.inactive', 'users', $userId, 'Login API ditolak — akun nonaktif', $ip);
            ApiResponse::error('ACCOUNT_INACTIVE', 'Akun ini dinonaktifkan. Hubungi Operator Madrasah.', 403);
            return;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $maxAttempts = (int) (ApiAuth::setting('security_max_login_attempts', '5'));
            $lockMinutes = (int) (ApiAuth::setting('security_lockout_minutes', '15'));
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lockUntil = null;

            if ($attempts >= $maxAttempts) {
                $lockUntil = (new DateTimeImmutable('+' . $lockMinutes . ' minutes'))->format('Y-m-d H:i:s');
            }

            $pdo->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?')
                ->execute([$attempts, $lockUntil, $userId]);

            AuditLog::write(
                $userId,
                'api.login.failed',
                'users',
                $userId,
                'Login API gagal — percobaan ke-' . $attempts . ' dari ' . $maxAttempts,
                $ip
            );

            if ($lockUntil !== null) {
                ApiResponse::error('ACCOUNT_LOCKED', 'Terlalu banyak percobaan gagal. Akun dikunci ' . $lockMinutes . ' menit.', 423);
                return;
            }

            $sisa = $maxAttempts - $attempts;
            ApiResponse::error(
                'INVALID_CREDENTIALS',
                'Nama pengguna atau kata sandi salah. Sisa ' . $sisa . ' percobaan sebelum akun dikunci sementara.',
                401
            );
            return;
        }

        // Berhasil.
        $pdo->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL,
                    last_login_at = NOW(), last_login_ip = ?
             WHERE id = ?'
        )->execute([$ip, $userId]);

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        }

        $tokens = ApiAuth::issue(
            $userId,
            $deviceName,
            $platform,
            $ip,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        RateLimiter::clear('api-login:' . $ip);

        AuditLog::write(
            $userId,
            'api.login.success',
            'users',
            $userId,
            'Masuk lewat API (' . $platform . ': ' . ($deviceName !== '' ? $deviceName : 'tanpa nama') . ')',
            $ip
        );

        ApiResponse::ok([
            'tokens' => [
                'access_token'       => $tokens['access_token'],
                'refresh_token'      => $tokens['refresh_token'],
                'token_type'         => 'Bearer',
                'access_expires_at'  => $tokens['access_expires_at'],
                'refresh_expires_at' => $tokens['refresh_expires_at'],
            ],
            'user' => $this->userPayload($user, true),
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     * Body: { refresh_token }
     */
    public function refresh(): void
    {
        $input = ApiResponse::input();
        $refresh = ApiResponse::str($input, 'refresh_token');

        if ($refresh === '') {
            ApiResponse::validation(['refresh_token' => 'Wajib diisi.']);
            return;
        }

        $ip = RateLimiter::clientIp();
        $limit = RateLimiter::hit('api-refresh:' . $ip, 30, 300);

        if (!$limit['allowed']) {
            header('Retry-After: ' . $limit['retry_after']);
            ApiResponse::tooMany();
            return;
        }

        $tokens = ApiAuth::rotate($refresh, $ip, $_SERVER['HTTP_USER_AGENT'] ?? null);

        if ($tokens === null) {
            ApiResponse::error(
                'INVALID_REFRESH_TOKEN',
                'Sesi sudah berakhir. Silakan masuk kembali.',
                401
            );
            return;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, username, role, role_label, must_change_password
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $tokens['user_id']]);
        $user = $stmt->fetch();

        AuditLog::write(
            (int) $tokens['user_id'],
            'api.token.refresh',
            'users',
            (int) $tokens['user_id'],
            'Refresh token dipakai, pasangan token berotasi',
            $ip
        );

        ApiResponse::ok([
            'tokens' => [
                'access_token'       => $tokens['access_token'],
                'refresh_token'      => $tokens['refresh_token'],
                'token_type'         => 'Bearer',
                'access_expires_at'  => $tokens['access_expires_at'],
                'refresh_expires_at' => $tokens['refresh_expires_at'],
            ],
            'user' => $user === false ? null : $this->userPayload($user, false),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     * Header: Authorization: Bearer <access_token>
     */
    public function logout(): void
    {
        $user = ApiAuth::authenticate();

        if ($user === null) {
            ApiResponse::unauthorized();
            return;
        }

        AuditLog::write(
            (int) $user['user_id'],
            'api.logout',
            'users',
            (int) $user['user_id'],
            'Keluar dari API — token dicabut',
            RateLimiter::clientIp()
        );

        ApiAuth::revokeCurrent();

        ApiResponse::ok(['message' => 'Berhasil keluar.']);
    }

    /**
     * GET /api/v1/me
     */
    public function me(): void
    {
        $user = ApiAuth::authenticate();

        if ($user === null) {
            ApiResponse::unauthorized();
            return;
        }

        ApiResponse::ok(['user' => $this->userPayload($user, true)]);
    }

    /**
     * GET /api/v1/settings/public
     * Pengaturan yang boleh dibaca publik: nama madrasah, kontak, jam operasional.
     */
    public function publicSettings(): void
    {
        try {
            $rows = Database::connection()->query(
                "SELECT setting_key, setting_value
                 FROM settings
                 WHERE is_public = 1
                 ORDER BY group_name, setting_key"
            )->fetchAll();
        } catch (PDOException $e) {
            ApiResponse::serverError();
            return;
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = $row['setting_value'];
        }

        ApiResponse::ok(['settings' => $settings]);
    }

    /**
     * GET /api/v1/health
     * Dipakai tim mobile untuk memastikan backend hidup sebelum melapor bug.
     */
    public function health(): void
    {
        $info = [
            'status'   => 'ok',
            'time'     => date('c'),
            'timezone' => date_default_timezone_get(),
        ];

        try {
            $pdo = Database::connection();
            $info['database'] = 'terhubung';
            $info['server'] = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $info['migrasi'] = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
        } catch (PDOException $e) {
            $info['status'] = 'degraded';
            $info['database'] = 'tidak terhubung';
        }

        ApiResponse::ok($info);
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    private function userPayload(array $user, bool $withFlags): array
    {
        $payload = [
            'id'       => (int) $user['id'],
            'name'     => (string) $user['name'],
            'email'    => (string) $user['email'],
            'username' => $user['username'] !== null ? (string) $user['username'] : null,
            'role'     => (string) $user['role'],
            'role_label' => $user['role_label'] !== null && $user['role_label'] !== ''
                ? (string) $user['role_label']
                : ucfirst((string) $user['role']),
        ];

        if ($withFlags) {
            $payload['must_change_password'] = (int) ($user['must_change_password'] ?? 0) === 1;
        }

        return $payload;
    }
}
