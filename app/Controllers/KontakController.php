<?php

class KontakController
{
    /** Batas kirim pesan dari satu IP dalam 10 menit. */
    private const MAX_PER_WINDOW = 5;
    private const WINDOW_SECONDS = 600;

    public function index(): void
    {
        // Sesi harus dibuka SEBELUM ada output. View sudah mencetak HTML dari
        // header, jadi kalau token dibuat di view, session_set_cookie_params()
        // gagal ("headers already sent") dan token keluar sebagai warning.
        $csrf = Csrf::token();

        $data = [
            'title' => 'Kontak | MTs Al-Huda Gondang',
            'csrf' => $csrf,
            'sent' => false,
            'errors' => [],
            'old' => [],
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = $this->handle($data);
        }

        require_once __DIR__ . '/../Views/kontak/index.php';
    }

    /**
     * Simpan pesan dari formulir kontak.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function handle(array $data): array
    {
        $name = trim((string) ($_POST['nama'] ?? ''));
        $phone = trim((string) ($_POST['telepon'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $topic = trim((string) ($_POST['topik'] ?? 'lainnya'));
        $message = trim((string) ($_POST['pesan'] ?? ''));

        $data['old'] = compact('name', 'phone', 'email', 'topic', 'message');

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $data['errors'][] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu kirim lagi.';
            return $data;
        }

        $limit = RateLimiter::hit('kontak:' . RateLimiter::clientIp(), self::MAX_PER_WINDOW, self::WINDOW_SECONDS);

        if (!$limit['allowed']) {
            $minutes = max(1, (int) ceil($limit['retry_after'] / 60));
            $data['errors'][] = 'Terlalu banyak pesan dari perangkat ini. Coba lagi dalam ' . $minutes . ' menit.';
            return $data;
        }

        if ($name === '' || mb_strlen($name) < 2) {
            $data['errors'][] = 'Nama minimal 2 karakter.';
        }

        if ($phone === '' || strlen(preg_replace('/\D/', '', $phone) ?? '') < 8) {
            $data['errors'][] = 'Nomor WhatsApp tidak valid.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['errors'][] = 'Format email tidak valid.';
        }

        if ($message === '' || mb_strlen($message) < 10) {
            $data['errors'][] = 'Pesan minimal 10 karakter.';
        }

        if ($data['errors'] !== []) {
            return $data;
        }

        // Tabel mewajibkan email NOT NULL: kalau pengunjung tidak mengisi,
        // disimpan string kosong, bukan NULL.
        $subjects = [
            'ppdb' => 'Pendaftaran / PPDB',
            'akademik' => 'Akademik',
            'fasilitas' => 'Fasilitas',
            'lainnya' => 'Pertanyaan umum',
        ];

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO contact_messages (name, email, phone, subject, message, status)
                 VALUES (:name, :email, :phone, :subject, :message, \'baru\')'
            );
            $stmt->execute([
                ':name' => mb_substr($name, 0, 120),
                ':email' => mb_substr($email, 0, 160),
                ':phone' => $phone !== '' ? mb_substr($phone, 0, 24) : null,
                ':subject' => mb_substr($subjects[$topic] ?? 'Pertanyaan umum', 0, 200),
                ':message' => mb_substr($message, 0, 2000),
            ]);

            $data['sent'] = true;
            $data['old'] = [];
        } catch (Throwable $e) {
            error_log('kontak: gagal menyimpan pesan — ' . $e->getMessage());
            $data['errors'][] = 'Pesan gagal dikirim karena kendala teknis. Silakan coba lagi atau hubungi WhatsApp madrasah.';
        }

        return $data;
    }
}
