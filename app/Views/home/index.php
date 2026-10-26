<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$stats = $data['stats'] ?? [];
$posts = $data['posts'] ?? [];
$facilities = $data['facilities'] ?? [];
$announcements = $data['announcements'] ?? [];
$agendas = $data['agendas'] ?? [];
$subjects = $data['subjects'] ?? [];

$grade = (string) ($stats['grade'] ?? 'A');

// Tanggal Indonesia tanpa perlu ekstensi intl.
$tanggal = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($value);
    return $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
};

$programs = [
    ['icon' => 'menu_book', 'title' => "Tahfidz Al-Qur'an", 'text' => "Bimbingan hafalan Al-Qur'an intensif dengan target capaian tajwid terukur."],
    ['icon' => 'translate', 'title' => 'Bahasa Arab', 'text' => 'Penguasaan muhadatsah harian dan pemahaman kaidah tata bahasa Arab dasar.'],
    ['icon' => 'science', 'title' => 'Sains & Olimpiade', 'text' => 'Pembinaan riset sains madrasah dan persiapan kompetisi olimpiade KSM.'],
    ['icon' => 'palette', 'title' => 'Seni & Budaya', 'text' => 'Pengembangan bakat seni hadrah, kaligrafi Islam, dan pramuka madrasah.'],
];
?>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <span class="eyebrow">Madrasah Tsanawiyah</span>
                <h1>Membentuk Generasi <em>Qur'ani</em>, Berakhlak Mulia, Berprestasi</h1>
                <p class="hero-lead">
                    Madrasah terpadu yang memadukan keunggulan ilmu keislaman, kurikulum nasional,
                    dan penguatan karakter santri berbasis adab di
                    <?= e(Content::setting('site_subtitle', 'Gondang')) ?>.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= base_url('profil') ?>">
                        <span class="mi mi-sm">school</span> Kenali Madrasah
                    </a>
                    <a class="btn btn-secondary btn-lg" href="<?= base_url('berita') ?>">
                        <span class="mi mi-sm">emoji_events</span> Lihat Prestasi
                    </a>
                </div>
            </div>
            <div class="hero-media">
                <img src="<?= base_url('assets/images/Senam Bersama.jpg') ?>"
                     alt="Kegiatan santri MTs Al-Huda Gondang" loading="eager">
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: var(--sp-8)">
        <div class="container">
            <div class="stats-bar">
                <div class="stat">
                    <p class="stat-label">Akreditasi</p>
                    <p class="stat-value"><?= e($grade) ?></p>
                    <p class="stat-note">Peringkat madrasah</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Mata Pelajaran</p>
                    <p class="stat-value"><?= (int) ($stats['subjects'] ?? 0) ?></p>
                    <p class="stat-note">Aktif diajarkan</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Fasilitas</p>
                    <p class="stat-value"><?= (int) ($stats['facilities'] ?? 0) ?></p>
                    <p class="stat-note">Sarana pendukung</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Berita</p>
                    <p class="stat-value"><?= (int) ($stats['posts'] ?? 0) ?></p>
                    <p class="stat-note">Kabar terbit</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow">Kurikulum &amp; Pembinaan</span>
                <h2>Program Unggulan</h2>
                <p>Mempersiapkan santri unggul dalam spiritual, intelektual, dan keterampilan abad 21.</p>
            </div>

            <div class="grid-4">
                <?php foreach ($programs as $program): ?>
                    <article class="card card-hover feature">
                        <span class="feature-icon"><span class="mi mi-lg"><?= e($program['icon']) ?></span></span>
                        <h3><?= e($program['title']) ?></h3>
                        <p><?= e($program['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($posts !== []): ?>
    <section class="section">
        <div class="container">
            <div class="section-head-row">
                <div class="section-head" style="margin-bottom:0">
                    <span class="eyebrow">Kabar Madrasah</span>
                    <h2>Berita Terbaru</h2>
                </div>
                <a class="btn btn-secondary" href="<?= base_url('berita') ?>">
                    Lihat Semua Berita <span class="mi mi-sm">arrow_forward</span>
                </a>
            </div>

            <div class="grid-3">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card">
                        <a class="post-cover" href="<?= base_url('berita/' . $post['slug']) ?>">
                            <img src="<?= e($post['cover_path'] ? base_url($post['cover_path']) : base_url('assets/images/Belajar di Perpustakaan.jpg')) ?>"
                                 alt="<?= e($post['title']) ?>" loading="lazy">
                        </a>
                        <div class="post-body">
                            <div class="post-meta">
                                <span class="mi mi-sm">calendar_today</span>
                                <?= e($tanggal($post['published_at'] ?? null)) ?>
                            </div>
                            <h3 class="post-title">
                                <a href="<?= base_url('berita/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                            </h3>
                            <p class="post-excerpt"><?= e($post['excerpt'] ?? '') ?></p>
                            <a class="post-more" href="<?= base_url('berita/' . $post['slug']) ?>">
                                Baca selengkapnya <span class="mi mi-sm">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($facilities !== []): ?>
    <section class="section section-soft">
        <div class="container">
            <div class="section-head-row">
                <div class="section-head" style="margin-bottom:0">
                    <span class="eyebrow">Sarana Prasarana</span>
                    <h2>Fasilitas Pendukung</h2>
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

    <?php if ($announcements !== [] || $agendas !== []): ?>
    <section class="section">
        <div class="container">
            <div class="grid-2">
                <?php if ($announcements !== []): ?>
                <div>
                    <div class="section-head">
                        <span class="eyebrow">Informasi</span>
                        <h2>Pengumuman</h2>
                    </div>
                    <div class="card">
                        <div class="dash-list">
                            <?php foreach ($announcements as $item): ?>
                                <div class="dash-row">
                                    <span class="feature-icon" style="margin:0;width:34px;height:34px">
                                        <span class="mi mi-sm">campaign</span>
                                    </span>
                                    <div>
                                        <b><?= e($item['title']) ?></b>
                                        <small><?= e($tanggal($item['published_at'] ?? null)) ?></small>
                                    </div>
                                    <?php if ((int) $item['is_pinned'] === 1): ?>
                                        <span class="pill pill-amber" style="margin-left:auto">Disematkan</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($agendas !== []): ?>
                <div>
                    <div class="section-head">
                        <span class="eyebrow">Kalender</span>
                        <h2>Agenda Terdekat</h2>
                    </div>
                    <div class="card">
                        <div class="dash-list">
                            <?php foreach ($agendas as $item): ?>
                                <div class="dash-row">
                                    <span class="feature-icon" style="margin:0;width:34px;height:34px">
                                        <span class="mi mi-sm">event</span>
                                    </span>
                                    <div>
                                        <b><?= e($item['title']) ?></b>
                                        <small>
                                            <?= e($tanggal($item['start_at'] ?? null)) ?>
                                            <?php if (!empty($item['location'])): ?>
                                                &middot; <?= e($item['location']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section section-soft">
        <div class="container">
            <div class="card card-pad-lg" style="display:flex;align-items:center;gap:var(--sp-6);flex-wrap:wrap;background:var(--dark-green);border:0">
                <div style="flex:1;min-width:260px">
                    <h2 style="color:var(--white);font-size:var(--fs-sec);margin-bottom:6px">Penerimaan Peserta Didik Baru</h2>
                    <p style="color:var(--mint);margin:0;font-size:var(--fs-sm)">
                        Pendaftaran santri baru tahun ajaran ini sudah dibuka. Lihat alur, syarat, dan dokumen yang diperlukan.
                    </p>
                </div>
                <a class="btn btn-primary btn-lg" href="<?= base_url('ppdb') ?>">
                    <span class="mi mi-sm">how_to_reg</span> Info PPDB
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
