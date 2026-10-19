<?php

class GaleriController
{
    public function index(): void
    {
        $data = [
            'title' => 'Galeri | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/galeri/index.php';
    }
}
