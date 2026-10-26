<?php

class ErrorController
{
    public function notFound(): void
    {
        // Status di-set di sini, bukan di router: halaman 404 bisa dipanggil
        // dari mana saja (route tak dikenal, slug berita yang tidak ada).
        if (!headers_sent()) {
            http_response_code(404);
        }

        $data = [
            'title' => 'Halaman Tidak Ditemukan | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/errors/404.php';
    }
}
