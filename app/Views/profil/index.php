<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$stats = $data['stats'] ?? [];
$facilities = $data['facilities'] ?? [];
$page = $data['page'] ?? null;
$grade = (string) ($stats['grade'] ?? 'A');

// Isi halaman: kalau admin sudah mengisi di panel, itu yang dipakai.
$content = trim((string) ($page['content'] ?? ''));
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Profil</span>
            </nav>
            <h1>Profil Madrasah</h1>
            <p>
                <?= e(Content::setting('site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang')) ?> —
                membina generasi cerdas, berilmu amaliah, beramal ilmiah, dan berakhlakul karimah.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="grid-2" style="align-items:start">
                <div>
                    <span class="eyebrow">Sejarah &amp; Dedikasi</span>
                    <h2 style="font-size:var(--fs-sec);margin-bottom:var(--sp-4)">
                        Dedikasi Membangun Generasi Islami Sejak Awal Berdiri
                    </h2>
                    <?php if ($content !== ''): ?>
                        <div class="article"><?= $content ?></div>
                    <?php else: ?>
                        <div class="article">
                            <p>
                                MTs Al-Huda Gondang berdiri sebagai bagian dari ikhtiar
                                <?= e(Content::setting('site_foundation', 'Yayasan Al-Huda Gondang')) ?>
                                dalam menyediakan pendidikan menengah berbasis keislaman bagi masyarakat sekitar.
                            </p>
                            <p>
                                Sejak awal, madrasah memadukan kurikulum nasional dengan penguatan ilmu
                                keagamaan, pembiasaan ibadah, dan pembinaan adab — sehingga santri tumbuh
                                seimbang antara kecerdasan intelektual dan kedalaman spiritual.
                            </p>
                            <p>
                                Hingga kini madrasah terus memperbaiki sarana, tenaga pendidik, dan tata kelola
                                agar setiap santri mendapat pengalaman belajar yang layak.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stats-bar" style="grid-template-columns:repeat(2,1fr);gap:var(--sp-4)">
                    <div class="stat">
                        <p class="stat-label">Akreditasi</p>
                        <p class="stat-value"><?= e($grade) ?></p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Mata Pelajaran</p>
                        <p class="stat-value"><?= (int) ($stats['subjects'] ?? 0) ?></p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Fasilitas</p>
                        <p class="stat-value"><?= (int) ($stats['facilities'] ?? 0) ?></p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Berita</p>
                        <p class="stat-value"><?= (int) ($stats['posts'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow">Arah &amp; Komitmen</span>
                <h2>Arah dan Komitmen Pendidikan</h2>
            </div>

            <div class="grid-2">
                <article class="card card-pad-lg">
                    <span class="feature-icon"><span class="mi mi-lg">flag</span></span>
                    <h3 style="font-size:var(--fs-card);margin-bottom:var(--sp-2)">Visi</h3>
                    <p style="font-size:var(--fs-sm);line-height:1.65;color:var(--gray);margin:0">
                        Terbentuknya generasi yang cerdas, berilmu amaliah, beramal ilmiah,
                        dan berakhlakul karimah.
                    </p>
                </article>

                <article class="card card-pad-lg">
                    <span class="feature-icon"><span class="mi mi-lg">checklist</span></span>
                    <h3 style="font-size:var(--fs-card);margin-bottom:var(--sp-2)">Misi Madrasah</h3>
                    <ul style="font-size:var(--fs-sm);line-height:1.7;color:var(--gray);margin:0;padding-left:var(--sp-5)">
                        <li>Menyelenggarakan pendidikan agama dan umum yang seimbang.</li>
                        <li>Membina hafalan dan pemahaman Al-Qur'an.</li>
                        <li>Mengembangkan karakter Islami dan kedisiplinan santri.</li>
                        <li>Meningkatkan mutu tenaga pendidik dan sarana pembelajaran.</li>
                        <li>Membangun kemitraan dengan wali santri dan masyarakat.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <?php if ($facilities !== []): ?>
    <section class="section">
        <div class="container">
            <div class="section-head-row">
                <div class="section-head" style="margin-bottom:0">
                    <span class="eyebrow">Sarana Prasarana</span>
                    <h2>Fasilitas Pendukung Utama</h2>
                </div>
                <a class="btn btn-secondary" href="<?= base_url('fasilitas') ?>">
                    Semua Fasilitas <span class="mi mi-sm">arrow_forward</span>
                </a>
            </div>

            <div class="grid-3">
                <?php foreach ($facilities as $facility): ?>
                    <article class="card feature">
                        <span class="feature-icon"><span class="mi mi-lg">apartment</span></span>
                        <h3><?= e($facility['name']) ?></h3>
                        <p><?= e($facility['description'] ?? '') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section section-soft">
        <div class="container">
            <div class="card card-pad-lg" style="display:flex;align-items:center;gap:var(--sp-6);flex-wrap:wrap">
                <div style="flex:1;min-width:260px">
                    <h2 style="font-size:var(--fs-sec);margin-bottom:6px">Ingin mengenal lebih dekat?</h2>
                    <p style="font-size:var(--fs-sm);color:var(--gray);margin:0">
                        Silakan berkunjung atau hubungi kami untuk informasi pendaftaran dan program madrasah.
                    </p>
                </div>
                <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap">
                    <a class="btn btn-primary" href="<?= base_url('kontak') ?>">
                        <span class="mi mi-sm">call</span> Hubungi Kami
                    </a>
                    <a class="btn btn-secondary" href="<?= base_url('ppdb') ?>">Info PPDB</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
