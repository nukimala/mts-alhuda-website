<?php

require_once __DIR__ . '/../Support/Database.php';

class GaleriController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->query("SELECT * FROM galeri WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC, created_at DESC");
        $photos = $stmt->fetchAll();

        $data = [
            'title' => 'Galeri Foto & Dokumentasi Kegiatan | MTs Al-Huda Gondang',
            'photos' => $photos,
        ];

        require_once __DIR__ . '/../Views/galeri/index.php';
    }
}
