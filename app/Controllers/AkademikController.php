<?php

class AkademikController
{
    public function index(): void
    {
        $data = [
            'title' => 'Program Akademik | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/akademik/index.php';
    }
}
