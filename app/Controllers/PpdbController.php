<?php

class PpdbController
{
    public function index(): void
    {
        $data = [
            'title' => 'Info PPDB | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }

    public function informasi(): void
    {
        $data = [
            'title' => 'Informasi & Agenda | MTs Al-Huda Gondang',
            'section' => 'informasi',
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }
}
