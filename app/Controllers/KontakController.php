<?php

class KontakController
{
    public function index(): void
    {
        $data = [
            'title' => 'Kontak | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/kontak/index.php';
    }
}
