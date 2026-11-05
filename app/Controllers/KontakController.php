<?php

require_once __DIR__ . '/../Support/Database.php';

class KontakController
{
    public function index(): void
    {
        Auth::start();
        $pdo = Database::connection();

        $rows = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name = 'kontak' OR is_public = 1")->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        $data = [
            'title' => 'Kontak & Layanan Informasi | MTs Al-Huda Gondang',
            'settings' => $settings,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_old_input'] ?? [],
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error'], $_SESSION['_old_input']);

        require_once __DIR__ . '/../Views/kontak/index.php';
    }

    public function send(): void
    {
        Auth::start();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token sesi tidak valid. Silakan muat ulang halaman.';
            header('Location: ' . base_url('kontak'));
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        $_SESSION['_old_input'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ];

        // Validasi
        if ($name === '' || mb_strlen($name) < 2) {
            $_SESSION['_flash_error'] = 'Nama lengkap wajib diisi minimal 2 karakter.';
            header('Location: ' . base_url('kontak'));
            exit;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash_error'] = 'Alamat email tidak valid.';
            header('Location: ' . base_url('kontak'));
            exit;
        }

        if ($message === '' || mb_strlen($message) < 5) {
            $_SESSION['_flash_error'] = 'Isi pesan wajib diisi minimal 5 karakter.';
            header('Location: ' . base_url('kontak'));
            exit;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("
                INSERT INTO pesan_kontak (name, email, phone, subject, message, status, created_at, updated_at)
                VALUES (:name, :email, :phone, :subject, :message, 'baru', NOW(), NOW())
            ");
            $stmt->execute([
                ':name' => mb_substr($name, 0, 120),
                ':email' => mb_substr($email, 0, 160),
                ':phone' => $phone !== '' ? mb_substr($phone, 0, 24) : null,
                ':subject' => $subject !== '' ? mb_substr($subject, 0, 200) : null,
                ':message' => $message,
            ]);

            unset($_SESSION['_old_input']);
            $_SESSION['_flash_success'] = 'Terima kasih, pesan Anda telah berhasil dikirim ke pihak MTs Al-Huda. Kami akan segera menindaklanjuti pesan Anda.';
        } catch (\PDOException $e) {
            $_SESSION['_flash_error'] = 'Terjadi kesalahan sistem saat mengirim pesan. Silakan hubungi kami via telepon atau WhatsApp.';
        }

        header('Location: ' . base_url('kontak'));
        exit;
    }
}
