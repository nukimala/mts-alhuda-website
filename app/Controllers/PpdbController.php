<?php

require_once __DIR__ . '/../Support/Database.php';

class PpdbController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $settings = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name = 'ppdb' OR is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
        $documents = $pdo->query("SELECT * FROM dokumen_ppdb WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll();

        $data = [
            'title' => 'Penerimaan Peserta Didik Baru (PPDB) | MTs Al-Huda Gondang',
            'settings' => $settings,
            'documents' => $documents,
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }

    public function informasi(): void
    {
        $this->index();
    }
}
