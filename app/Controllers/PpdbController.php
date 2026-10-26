<?php

class PpdbController
{
    public function index(): void
    {
        $data = [
            'title'     => 'Info PPDB | MTs Al-Huda Gondang',
            'documents' => Content::ppdbDocuments(12),
            'agendas'   => Content::agendas(3),
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }

    public function informasi(): void
    {
        $data = [
            'title'     => 'Informasi & Agenda | MTs Al-Huda Gondang',
            'section'   => 'informasi',
            'documents' => Content::ppdbDocuments(12),
            'announcements' => Content::announcements(5),
            'agendas'   => Content::agendas(8),
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }
}
