<?php

class ProfilController
{
    public function index(): void
    {
        $data = [
            'title'      => 'Profil Madrasah | MTs Al-Huda Gondang',
            'page'       => Content::page('profil'),
            'stats'      => Content::stats(),
            'facilities' => Content::facilities(3),
        ];

        require_once __DIR__ . '/../Views/profil/index.php';
    }
}
