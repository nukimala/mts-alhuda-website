<?php

class GaleriController
{
    public function index(): void
    {
        $items = Content::galleries(48);

        // Dikelompokkan per album supaya tampil rapi kalau albumnya lebih dari satu.
        $albums = [];
        foreach ($items as $item) {
            $albums[$item['album'] ?: 'Kegiatan Madrasah'][] = $item;
        }

        $data = [
            'title'  => 'Galeri | MTs Al-Huda Gondang',
            'albums' => $albums,
            'total'  => count($items),
        ];

        require_once __DIR__ . '/../Views/galeri/index.php';
    }
}
