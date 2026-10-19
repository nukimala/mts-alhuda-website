<?php

class BeritaController
{
    public function index(): void
    {
        $data = [
            'title' => 'Berita & Artikel | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/berita/index.php';
    }

    public function detail(string $slug = ''): void
    {
        $data = [
            'title' => 'Detail Berita | MTs Al-Huda Gondang',
            'slug' => $slug,
        ];

        require_once __DIR__ . '/../Views/berita/detail.php';
    }
}
