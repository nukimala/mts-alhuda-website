<?php

class HomeController
{
    public function index(): void
    {
        $data = [
            'title'       => 'Beranda | MTs Al-Huda Gondang',
            'stats'       => Content::stats(),
            'posts'       => Content::posts(['limit' => 3]),
            'facilities'  => Content::facilities(6),
            'announcements' => Content::announcements(2),
            'agendas'     => Content::agendas(3),
            'subjects'    => Content::subjects(),
        ];

        require_once __DIR__ . '/../Views/home/index.php';
    }
}
