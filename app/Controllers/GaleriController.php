
<?php

require_once __DIR__ . '/../Support/Database.php';

class GaleriController
{
    public function index(): void
    {
        $pdo = Database::connection();

        // Ambil foto yang aktif dan belum dihapus
        $stmt = $pdo->query("
            SELECT *
            FROM galeri
            WHERE is_active = 1
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, created_at DESC
        ");

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil kategori/album yang memiliki foto aktif
        $albumStmt = $pdo->query("
            SELECT DISTINCT album
            FROM galeri
            WHERE is_active = 1
              AND deleted_at IS NULL
              AND album IS NOT NULL
              AND album != ''
            ORDER BY album ASC
        ");

        $albums = $albumStmt->fetchAll(PDO::FETCH_COLUMN);

        // Kirim data ke halaman galeri
        $data = [
            'title' => 'Galeri Foto & Dokumentasi Kegiatan | MTs Al-Huda Gondang',
            'items' => $items,
            'albums' => $albums,
        ];

        require_once __DIR__ . '/../Views/galeri/index.php';
    }
}
