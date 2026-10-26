<?php

class FasilitasController
{
    public function index(): void
    {
        $data = [
            'title'      => 'Fasilitas | MTs Al-Huda Gondang',
            'facilities' => Content::facilities(24),
        ];

        require_once __DIR__ . '/../Views/fasilitas/index.php';
    }
}
