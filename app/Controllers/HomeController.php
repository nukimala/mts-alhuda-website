<?php

class HomeController
{
    public function index(): void
    {
        $data = [
            'title' => 'Beranda | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/home/index.php';
    }
}
