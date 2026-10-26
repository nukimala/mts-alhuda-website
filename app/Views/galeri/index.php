<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$albums = $data['albums'] ?? [];
$total = (int) ($data['total'] ?? 0);

// Kalau album belum diisi dari panel admin, tampilkan foto bawaan yang memang
// ada di folder assets. Tujuannya supaya halaman tidak kosong melompong saat
// madrasah baru mulai memakai sistem ini.
$fallback = [
    ['file' => 'Senam Bersama.jpg', 'caption' => 'Senam bersama santri'],
    ['file' => 'Belajar di Perpustakaan.jpg', 'caption' => 'Belajar di perpustakaan'],
    ['file' => 'Drumband.jpg', 'caption' => 'Latihan drumband'],
    ['file' => 'Grup Sholawat  MTS Al-Huda.jpg', 'caption' => 'Grup sholawat madrasah'],
    ['file' => 'Mengikuti Upacara Pembukaan Jambore Cabang.jpg', 'caption' => 'Upacara pembukaan jambore'],
    ['file' => 'Pertemuan Wali Santri.jpg', 'caption' => 'Pertemuan wali santri'],
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Galeri</span>
            </nav>
            <h1>Galeri Kegiatan</h1>
            <p>Dokumentasi kegiatan belajar, ibadah, dan pembinaan santri MTs Al-Huda Gondang.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if ($albums === []): ?>
                <div class="section-head-row">
                    <div class="section-head" style="margin-bottom:0">
                        <span class="eyebrow">Dokumentasi</span>
                        <h2>Kegiatan Madrasah</h2>
                        <p>Album foto belum diisi dari panel admin, ini dokumentasi bawaan madrasah.</p>
                    </div>
                </div>

                <div class="gallery-grid">
                    <?php foreach ($fallback as $item): ?>
                        <a class="gallery-item" href="<?= base_url('assets/images/' . rawurlencode($item['file'])) ?>" target="_blank" rel="noopener">
                            <img src="<?= base_url('assets/images/' . rawurlencode($item['file'])) ?>"
                                 alt="<?= e($item['caption']) ?>" loading="lazy">
                            <span class="gallery-caption"><?= e($item['caption']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="section-head-row">
                    <div class="section-head" style="margin-bottom:0">
                        <span class="eyebrow">Dokumentasi</span>
                        <h2><?= $total ?> Foto dalam <?= count($albums) ?> Album</h2>
                    </div>
                </div>

                <?php foreach ($albums as $album => $items): ?>
                    <div style="margin-bottom:var(--sp-8)">
                        <h3 style="font-size:var(--fs-card);margin-bottom:var(--sp-3)">
                            <?= e((string) $album) ?>
                            <span class="pill" style="margin-left:6px"><?= count($items) ?></span>
                        </h3>

                        <div class="gallery-grid">
                            <?php foreach ($items as $item): ?>
                                <?php $src = $item['image_path'] ? base_url($item['image_path']) : ''; ?>
                                <a class="gallery-item" href="<?= e($src) ?>" target="_blank" rel="noopener">
                                    <img src="<?= e($src) ?>" alt="<?= e($item['title'] ?? $item['caption'] ?? 'Foto kegiatan') ?>" loading="lazy">
                                    <?php if (!empty($item['caption']) || !empty($item['title'])): ?>
                                        <span class="gallery-caption"><?= e($item['caption'] ?: $item['title']) ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
