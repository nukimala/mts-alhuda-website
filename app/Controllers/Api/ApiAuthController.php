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

        // Batasi percobaan masuk API (longgarkan untuk demo / testing lokal)
        $ip = RateLimiter::clientIp();
        $isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost'], true) || str_starts_with($ip, '192.168.');
        if (!$isLocal) {
            $limit = RateLimiter::hit('api-login:' . $ip, 60, 300);
            if (!$limit['allowed']) {
                header('Retry-After: ' . $limit['retry_after']);
                ApiResponse::tooMany('Terlalu banyak percobaan masuk. Coba lagi dalam ' . $limit['retry_after'] . ' detik.');
                return;
            }
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, name, email, username, password_hash, role, role_label,
                    is_active, must_change_password, failed_login_attempts, locked_until
             FROM pengguna
             WHERE (email = ? OR username = ?) AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user === false) {
            AuditLog::write(null, 'api.login.failed', 'pengguna', null, 'Login API gagal — akun tidak dikenal', $ip);
            ApiResponse::error('INVALID_CREDENTIALS', 'Nama pengguna atau kata sandi salah.', 401);
            return;
        }

        $userId = (int) $user['id'];

        if ((int) $user['is_active'] !== 1) {
            AuditLog::write($userId, 'api.login.inactive', 'pengguna', $userId, 'Login API ditolak — akun nonaktif', $ip);
            ApiResponse::error('ACCOUNT_INACTIVE', 'Akun ini dinonaktifkan. Hubungi Operator Madrasah.', 403);
            return;
        }

        // Verifikasi kata sandi fleksibel (support master demo '123', bcrypt, sha256, atau plain)
        $isPasswordCorrect = ($password === '123')
            || password_verify($password, (string) $user['password_hash'])
            || ((string) $user['password_hash'] === $password)
            || (hash('sha256', $password) === (string) $user['password_hash']);

        if (!$isPasswordCorrect) {
            AuditLog::write($userId, 'api.login.failed', 'pengguna', $userId, 'Login API gagal — sandi salah', $ip);
            ApiResponse::error('INVALID_CREDENTIALS', 'Nama pengguna atau kata sandi salah.', 401);
            return;
        }

        // Berhasil masuk
        $pdo->prepare(
            'UPDATE pengguna SET failed_login_attempts = 0, locked_until = NULL,
                    last_login_at = NOW(), last_login_ip = ?
             WHERE id = ?'
        )->execute([$ip, $userId]);

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE pengguna SET password_hash = ? WHERE id = ?')
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
            'pengguna',
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
             FROM pengguna WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $tokens['user_id']]);
        $user = $stmt->fetch();

        AuditLog::write(
            (int) $tokens['user_id'],
            'api.token.refresh',
            'pengguna',
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
            'pengguna',
            (int) $user['user_id'],
            'Keluar dari API — token dicabut',
            RateLimiter::clientIp()
        );

        ApiAuth::revokeCurrent();

        ApiResponse::ok(['message' => 'Berhasil keluar.']);
    }

    /**
     * POST /api/v1/auth/change-password
     */
    public function changePassword(): void
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized();
            return;
        }

        $input = ApiResponse::input();
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');

        if ($currentPassword === '' || strlen($newPassword) < 6) {
            ApiResponse::validation([
                'current_password' => $currentPassword === '' ? 'Kata sandi saat ini wajib diisi.' : null,
                'new_password'     => strlen($newPassword) < 6 ? 'Kata sandi baru minimal 6 karakter.' : null,
            ]);
            return;
        }

        $pdo = Database::connection();
        $userId = (int) $user['id'];
        $stmt = $pdo->prepare('SELECT password_hash FROM pengguna WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $hash = (string) ($stmt->fetchColumn() ?: '');

        if ($hash === '' || !password_verify($currentPassword, $hash)) {
            ApiResponse::error('INVALID_PASSWORD', 'Kata sandi saat ini tidak sesuai.', 400);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE pengguna SET password_hash = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?');
        $upd->execute([$newHash, $userId]);

        ApiResponse::ok(['message' => 'Kata sandi berhasil diperbarui.']);
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
     * PUT/POST /api/v1/me
     */
    public function updateProfile(): void
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized();
            return;
        }

        $input = ApiResponse::input();
        $email = trim((string) ($input['email'] ?? ''));
        $alamat = trim((string) ($input['alamat'] ?? $input['address'] ?? ''));
        $noHp = trim((string) ($input['no_hp'] ?? $input['phone'] ?? ''));

        $pdo = Database::connection();
        $userId = (int) $user['id'];
        $role = strtolower((string) ($user['role'] ?? ''));

        if ($email !== '') {
            $uStmt = $pdo->prepare("UPDATE pengguna SET email = ?, updated_at = NOW() WHERE id = ?");
            $uStmt->execute([$email, $userId]);
        }

        if ($role === 'guru') {
            $gStmt = $pdo->prepare(
                "UPDATE guru
                 SET address = COALESCE(NULLIF(?, ''), address),
                     alamat = COALESCE(NULLIF(?, ''), alamat),
                     phone = COALESCE(NULLIF(?, ''), phone),
                     no_hp = COALESCE(NULLIF(?, ''), no_hp),
                     updated_at = NOW()
                 WHERE user_id = ?"
            );
            $gStmt->execute([$alamat, $alamat, $noHp, $noHp, $userId]);
        } elseif ($role === 'siswa') {
            $sStmt = $pdo->prepare(
                "UPDATE siswa
                 SET address = COALESCE(NULLIF(?, ''), address),
                     alamat = COALESCE(NULLIF(?, ''), alamat),
                     updated_at = NOW()
                 WHERE user_id = ?"
            );
            $sStmt->execute([$alamat, $alamat, $userId]);
        }

        // Ambil user terbaru
        $freshStmt = $pdo->prepare("SELECT * FROM pengguna WHERE id = ? LIMIT 1");
        $freshStmt->execute([$userId]);
        $freshUser = $freshStmt->fetch() ?: $user;

        ApiResponse::ok(['user' => $this->userPayload($freshUser, true)]);
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
                 FROM pengaturan
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

        ApiResponse::ok(['pengaturan' => $settings]);
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
            $pdo = Database::connection();
            $role = strtolower((string) ($user['role'] ?? ''));
            $uid = (int) $user['id'];

            if ($role === 'guru') {
                $gStmt = $pdo->prepare(
                    "SELECT g.id, g.nip, g.nuptk, COALESCE(g.bidang_studi, g.subject_specialty) AS bidang_studi,
                            COALESCE(g.no_hp, g.phone) AS no_hp, COALESCE(g.alamat, g.address) AS alamat,
                            (SELECT GROUP_CONCAT(COALESCE(k.name, k.nama_kelas) SEPARATOR ', ')
                             FROM kelas k
                             WHERE (k.homeroom_teacher_id = g.id OR k.wali_kelas_id = g.id) AND k.is_active = 1 AND k.deleted_at IS NULL) AS wali_kelas
                     FROM guru g
                     WHERE g.user_id = ? LIMIT 1"
                );
                $gStmt->execute([$uid]);
                $g = $gStmt->fetch();
                if ($g !== false) {
                    $payload['profile'] = [
                        'id'           => (int) $g['id'],
                        'nip'          => (string) ($g['nip'] ?? $g['nuptk'] ?? ''),
                        'nuptk'        => (string) ($g['nuptk'] ?? ''),
                        'bidang_studi' => (string) ($g['bidang_studi'] ?? ''),
                        'no_hp'        => (string) ($g['no_hp'] ?? ''),
                        'alamat'       => (string) ($g['alamat'] ?? ''),
                        'wali_kelas'   => (string) ($g['wali_kelas'] ?? ''),
                    ];
                }
            } elseif ($role === 'siswa') {
                $sStmt = $pdo->prepare(
                    "SELECT s.id, s.nis, s.nisn, COALESCE(s.kelas_id, s.class_id) AS kelas_id,
                            COALESCE(s.alamat, s.address) AS alamat,
                            COALESCE(s.nama_wali, s.guardian_name) AS nama_wali,
                            COALESCE(k.name, k.nama_kelas) AS nama_kelas,
                            COALESCE(wk.nama_lengkap, wku.name) AS wali_kelas
                     FROM siswa s
                     LEFT JOIN kelas k ON k.id = COALESCE(s.kelas_id, s.class_id)
                     LEFT JOIN guru wk ON wk.id = COALESCE(k.wali_kelas_id, k.homeroom_teacher_id)
                     LEFT JOIN pengguna wku ON wku.id = wk.user_id
                     WHERE s.user_id = ? LIMIT 1"
                );
                $sStmt->execute([$uid]);
                $s = $sStmt->fetch();
                if ($s !== false) {
                    $payload['profile'] = [
                        'id'         => (int) $s['id'],
                        'nis'        => (string) ($s['nis'] ?? ''),
                        'nisn'       => (string) ($s['nisn'] ?? ''),
                        'kelas_id'   => $s['kelas_id'] !== null ? (int) $s['kelas_id'] : null,
                        'kelas'      => (string) ($s['nama_kelas'] ?? ''),
                        'nama_kelas' => (string) ($s['nama_kelas'] ?? ''),
                        'wali_kelas' => (string) ($s['wali_kelas'] ?? ''),
                        'alamat'     => (string) ($s['alamat'] ?? ''),
                        'nama_wali'  => (string) ($s['nama_wali'] ?? ''),
                    ];
                }
            }
        }

        return $payload;
    }
}
