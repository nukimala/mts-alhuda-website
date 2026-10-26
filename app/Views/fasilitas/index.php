<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$facilities = $data['facilities'] ?? [];

// Ikon per kategori fasilitas; dipetakan dari kolom `icon` di database.
$iconMap = [
    'classroom' => 'meeting_room',
    'book' => 'local_library',
    'mosque' => 'mosque',
    'field' => 'sports_soccer',
    'lab' => 'science',
    'office' => 'badge',
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Fasilitas</span>
            </nav>
            <h1>Fasilitas Madrasah</h1>
            <p>Sarana dan prasarana yang menunjang kegiatan belajar, ibadah, dan pengembangan bakat santri.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if ($facilities === []): ?>
                <div class="empty">
                    <p class="empty-title">Belum ada data fasilitas</p>
                    <p class="empty-text">Data fasilitas akan tampil di sini setelah diisi dari panel admin.</p>
                </div>
            <?php else: ?>
                <div class="section-head-row">
                    <div class="section-head" style="margin-bottom:0">
                        <span class="eyebrow">Sarana Prasarana</span>
                        <h2><?= count($facilities) ?> Fasilitas Tercatat</h2>
                    </div>
                </div>

                <div class="grid-2">
                    <?php foreach ($facilities as $facility): ?>
                        <article class="facility-card">
                            <div class="facility-thumb">
                                <?php if (!empty($facility['image_path'])): ?>
                                    <img src="<?= e(base_url($facility['image_path'])) ?>"
                                         alt="<?= e($facility['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--green-bg);color:var(--green)">
                                        <span class="mi mi-lg"><?= e($iconMap[$facility['icon'] ?? ''] ?? 'apartment') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="facility-body">
                                <h3><?= e($facility['name']) ?></h3>
                                <p><?= e($facility['description'] ?? '') ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="card card-pad-lg" style="display:flex;align-items:center;gap:var(--sp-6);flex-wrap:wrap">
                <div style="flex:1;min-width:260px">
                    <h2 style="font-size:var(--fs-sec);margin-bottom:6px">Lihat kegiatan kami</h2>
                    <p style="font-size:var(--fs-sm);color:var(--gray);margin:0">
                        Dokumentasi kegiatan belajar dan pembinaan santri ada di halaman galeri.
                    </p>
                </div>
                <a class="btn btn-primary" href="<?= base_url('galeri') ?>">
                    <span class="mi mi-sm">photo_library</span> Buka Galeri
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
