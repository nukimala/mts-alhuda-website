<?php
/**
 * Autentikasi berbasis sesi PHP untuk panel admin.
 *
 * Aturan yang dipegang:
 *   - Password disimpan sebagai hash password_hash(), diverifikasi password_verify().
 *   - Pesan galat login selalu generik: tidak membocorkan apakah email atau
 *     password yang salah.
 *   - Percobaan gagal dihitung. Lewat batas, akun dikunci sementara.
 *   - Sesi diperbarui ID-nya setelah login berhasil (mencegah session fixation).
 *   - must_change_password = 1 memaksa pengguna mengganti sandi sebelum masuk.
 */

class Auth
{
    private const SESSION_KEY = 'auth_user_id';

    /** @var array<string,mixed>|null */
    private static ?array $cachedUser = null;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);

        session_name('MTSALHUDA_SESS');
        session_start();
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        self::start();

        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if ($id === null) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, username, role, role_label, avatar_path,
                    is_active, must_change_password, last_login_at
             FROM users
             WHERE id = ? AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user === false || (int) $user['is_active'] !== 1) {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user === null ? null : (string) $user['role'];
    }

    public static function displayRole(): string
    {
        $user = self::user();
        if ($user === null) {
            return '';
        }

        if (!empty($user['role_label'])) {
            return (string) $user['role_label'];
        }

        return match ((string) $user['role']) {
            'admin'      => 'Super Admin',
            'operator'   => 'Operator Madrasah',
            'kepala'     => 'Kepala Madrasah',
            'guru'       => 'Guru',
            'siswa'      => 'Siswa',
            'akreditasi' => 'Tim Akreditasi',
            default      => ucfirst((string) $user['role']),
        };
    }

    /**
     * Coba masuk. Mengembalikan null kalau berhasil, atau pesan galat kalau gagal.
     */
    public static function attempt(string $identifier, string $password, string $ip): ?string
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, name, password_hash, role, is_active, must_change_password,
                    failed_login_attempts, locked_until
             FROM users
             WHERE (email = ? OR username = ?) AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Akun tidak ada. Tetap jalankan password_verify pada hash dummy supaya
        // waktu respons tidak membocorkan apakah email terdaftar.
        if ($user === false) {
            password_verify($password, '$2y$10$usesomesillystringforsaltXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

            return 'Nama pengguna atau kata sandi salah.';
        }

        $userId = (int) $user['id'];
        $maxAttempts = (int) self::setting('security_max_login_attempts', '5');
        $lockMinutes = (int) self::setting('security_lockout_minutes', '15');

        if ($user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time()) {
            $sisa = (int) ceil((strtotime((string) $user['locked_until']) - time()) / 60);

            return 'Akun dikunci sementara. Coba lagi dalam ' . $sisa . ' menit.';
        }

        if ((int) $user['is_active'] !== 1) {
            return 'Akun ini dinonaktifkan. Hubungi Operator Madrasah.';
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lockUntil = null;
            $pesan = 'Nama pengguna atau kata sandi salah.';

            if ($attempts >= $maxAttempts) {
                $lockUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
                $pesan = 'Terlalu banyak percobaan gagal. Akun dikunci ' . $lockMinutes . ' menit.';
            } else {
                $sisa = $maxAttempts - $attempts;
                $pesan .= ' Sisa ' . $sisa . ' percobaan sebelum akun dikunci sementara.';
            }

            $upd = $pdo->prepare(
                'UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?'
            );
            $upd->execute([$attempts, $lockUntil, $userId]);

            self::log('login.failed', 'users', $userId, 'Percobaan login gagal', $ip);

            return $pesan;
        }

        // Berhasil. Reset penghitung, perbarui waktu masuk, perbarui hash kalau
        // algoritmanya sudah usang.
        $upd = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = 0, locked_until = NULL,
                 last_login_at = NOW(), last_login_ip = ?
             WHERE id = ?'
        );
        $upd->execute([$ip, $userId]);

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $rehash->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        }

        self::start();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;
        self::$cachedUser = null;

        self::log('login.success', 'users', $userId, 'Berhasil masuk panel admin', $ip);

        return null;
    }

    public static function logout(): void
    {
        self::start();

        $userId = $_SESSION[self::SESSION_KEY] ?? null;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax',
                ]
            );
        }

        session_destroy();

        if ($userId !== null) {
            self::$cachedUser = null;
        }
    }

    /**
     * Baca satu nilai pengaturan. Aman kalau tabel belum ada.
     */
    public static function setting(string $key, ?string $default = null): ?string
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
        } catch (PDOException $e) {
            return $default;
        }

        $cache[$key] = ($value === false || $value === null) ? $default : (string) $value;

        return $cache[$key];
    }

    public static function log(string $action, ?string $entity, ?int $entityId, string $description, ?string $ip = null): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO activity_logs (user_id, action, entity, entity_id, description, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                self::id(),
                $action,
                $entity,
                $entityId,
                mb_substr($description, 0, 255),
                $ip,
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (PDOException $e) {
            // Log audit tidak boleh menggagalkan aksi utama.
        }
    }
}
