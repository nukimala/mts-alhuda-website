<?php

class ErrorController
{
    public function notFound(): void
    {
        $data = [
            'title' => 'Halaman Tidak Ditemukan | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/errors/404.php';
    }
}
