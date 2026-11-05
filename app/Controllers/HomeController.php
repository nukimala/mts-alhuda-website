<?php

require_once __DIR__ . '/../Support/Database.php';

class HomeController
{
    public function index(): void
    {
        $pdo = Database::connection();

        // 1. Berita terbaru terbit
        $beritaStmt = $pdo->query("SELECT p.*, u.name as author_name 
                                  FROM berita p 
                                  LEFT JOIN pengguna u ON u.id = p.author_id 
                                  WHERE p.status = 'published' AND p.deleted_at IS NULL 
                                  ORDER BY p.is_featured DESC, p.published_at DESC 
                                  LIMIT 3");
        $latestPosts = $beritaStmt->fetchAll();

        // 2. Fasilitas unggulan
        $facStmt = $pdo->query("SELECT * FROM fasilitas WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4");
        $facilities = $facStmt->fetchAll();

        // 3. Pengaturan madrasah
        $settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

        $data = [
            'title' => 'MTs Al-Huda Gondang — Madrasah Berakhlak Mulia & Berprestasi',
            'latest_posts' => $latestPosts,
            'facilities' => $facilities,
            'settings' => $settingsRaw,
        ];

        require_once __DIR__ . '/../Views/home/index.php';
    }
}
