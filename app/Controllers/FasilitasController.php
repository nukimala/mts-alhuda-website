<?php

class FasilitasController
{
    public function index(): void
    {
        $data = [
            'title' => 'Fasilitas | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/fasilitas/index.php';
    }
}
