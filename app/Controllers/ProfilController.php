<?php

require_once __DIR__ . '/../Support/Database.php';

class ProfilController
{
    public function index(): void
    {
        $pdo = Database::connection();

        // Data guru & tenaga kependidikan
        $guruStmt = $pdo->query("SELECT g.*, u.name, u.email, u.avatar_path 
                                FROM guru g 
                                JOIN pengguna u ON u.id = g.user_id 
                                WHERE u.is_active = 1 AND u.deleted_at IS NULL 
                                ORDER BY u.name ASC");
        $guruList = $guruStmt->fetchAll();

        // Pengaturan identitas
        $settings = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

        $data = [
            'title' => 'Profil Madrasah, Visi Misi & Guru | MTs Al-Huda Gondang',
            'guru' => $guruList,
            'settings' => $settings,
        ];

        require_once __DIR__ . '/../Views/profil/index.php';
    }
}
