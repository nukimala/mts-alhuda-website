<?php

class AkademikController
{
    public function index(): void
    {
        $data = [
            'title'    => 'Program Akademik | MTs Al-Huda Gondang',
            'page'     => Content::page('akademik'),
            'subjects' => Content::subjects(),
            'stats'    => Content::stats(),
        ];

        require_once __DIR__ . '/../Views/akademik/index.php';
    }
}
