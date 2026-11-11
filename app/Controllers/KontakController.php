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
            // Skema produksi memakai kolom Bahasa Indonesia: nama, telepon, subjek, pesan.
            // Panjang mengikuti kolom: nama 100, email 100, telepon 30, subjek 150.
            $stmt = $pdo->prepare("
                INSERT INTO pesan_kontak (nama, email, telepon, subjek, pesan, status, ip_address, user_agent, created_at, updated_at)
                VALUES (:nama, :email, :telepon, :subjek, :pesan, 'baru', :ip, :ua, NOW(), NOW())
            ");
            $stmt->execute([
                ':nama' => mb_substr($name, 0, 100),
                ':email' => mb_substr($email, 0, 100),
                ':telepon' => $phone !== '' ? mb_substr($phone, 0, 30) : null,
                ':subjek' => mb_substr($subject !== '' ? $subject : 'Pesan dari situs', 0, 150),
                ':pesan' => $message,
                ':ip' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                ':ua' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
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
