<?php
/**
 * Login, logout, dan penggantian kata sandi wajib.
 */

class AuthController
{
    public function loginForm(): void
    {
        Auth::start();

        if (Auth::check()) {
            header('Location: ' . base_url('admin'));
            return;
        }

        $data = [
            'title' => 'Masuk Panel Admin | MTs Al-Huda Gondang',
            'error' => $_SESSION['_flash_error'] ?? null,
            'old'   => $_SESSION['_flash_old'] ?? '',
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/login.php';
    }

    public function login(): void
    {
        Auth::start();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Sesi formulir kedaluwarsa. Silakan coba lagi.';
            header('Location: ' . base_url('admin/login'));
            return;
        }

        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($identifier === '' || $password === '') {
            $_SESSION['_flash_error'] = 'Nama pengguna dan kata sandi wajib diisi.';
            $_SESSION['_flash_old'] = $identifier;
            header('Location: ' . base_url('admin/login'));
            return;
        }

        $error = Auth::attempt($identifier, $password, (string) $ip);

        if ($error !== null) {
            $_SESSION['_flash_error'] = $error;
            $_SESSION['_flash_old'] = $identifier;
            header('Location: ' . base_url('admin/login'));
            return;
        }

        Csrf::rotate();

        $user = Auth::user();
        if ($user !== null && (int) $user['must_change_password'] === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        header('Location: ' . base_url('admin'));
    }

    public function logout(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            header('Location: ' . base_url('admin'));
            return;
        }

        Auth::log('logout', 'users', Auth::id(), 'Keluar dari panel admin', $_SERVER['REMOTE_ADDR'] ?? null);
        Auth::logout();

        header('Location: ' . base_url('admin/login'));
    }

    public function changePasswordForm(): void
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            return;
        }

        $user = Auth::user();
        $wajib = (int) $user['must_change_password'] === 1;

        $data = [
            'title' => 'Ganti Kata Sandi | MTs Al-Huda Gondang',
            'heading' => 'Ganti Kata Sandi',
            'user' => $user,
            'role' => Auth::displayRole(),
            'error' => $_SESSION['_flash_error'] ?? null,
            'forced' => $wajib,
        ];

        unset($_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/ganti-sandi.php';
    }

    public function changePassword(): void
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Sesi formulir kedaluwarsa. Silakan coba lagi.';
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $hash = $stmt->fetchColumn();

        if ($hash === false || !password_verify($current, (string) $hash)) {
            $_SESSION['_flash_error'] = 'Kata sandi saat ini tidak sesuai.';
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        if (strlen($new) < 12) {
            $_SESSION['_flash_error'] = 'Kata sandi baru minimal 12 karakter.';
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        if ($new !== $confirm) {
            $_SESSION['_flash_error'] = 'Konfirmasi kata sandi tidak sama.';
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        if ($new === $current) {
            $_SESSION['_flash_error'] = 'Kata sandi baru tidak boleh sama dengan yang sekarang.';
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        $upd = $pdo->prepare(
            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?'
        );
        $upd->execute([password_hash($new, PASSWORD_DEFAULT), Auth::id()]);

        // Semua sesi lain diputus: token API dan sesi web lain tidak berlaku lagi.
        $pdo->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL')
            ->execute([Auth::id()]);

        Auth::log('password.changed', 'users', Auth::id(), 'Mengganti kata sandi', $_SERVER['REMOTE_ADDR'] ?? null);

        header('Location: ' . base_url('admin'));
    }
}
