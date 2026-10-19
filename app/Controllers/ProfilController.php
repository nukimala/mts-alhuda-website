<?php

class ProfilController
{
    public function index(): void
    {
        $data = [
            'title' => 'Profil Madrasah | MTs Al-Huda Gondang',
        ];

        require_once __DIR__ . '/../Views/profil/index.php';
    }
}
