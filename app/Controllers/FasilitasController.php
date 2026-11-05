<?php

require_once __DIR__ . '/../Support/Database.php';

class FasilitasController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("SELECT * FROM fasilitas WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $facilities = $stmt->fetchAll();

        $data = [
            'title' => 'Fasilitas & Sarana Prasarana | MTs Al-Huda Gondang',
            'facilities' => $facilities,
        ];

        require_once __DIR__ . '/../Views/fasilitas/index.php';
    }
}
