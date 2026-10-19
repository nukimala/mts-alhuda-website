<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$berita = [
    [
        'slug' => 'penerimaan-peserta-didik-baru-2026',
        'title' => 'Penerimaan Peserta Didik Baru Tahun Ajaran 2026/2027 Dibuka',
        'date' => '1 Juli 2026',
        'excerpt' => 'MTs Al-Huda Gondang membuka pendaftaran peserta didik baru untuk tahun ajaran 2026/2027.',
    ],
    [
        'slug' => 'peringatan-hari-santri',
        'title' => 'Peringatan Hari Santri Berlangsung Khidmat',
        'date' => '22 Oktober 2026',
        'excerpt' => 'Seluruh siswa dan guru mengikuti rangkaian kegiatan peringatan Hari Santri di lingkungan madrasah.',
    ],
    [
        'slug' => 'juara-lomba-tahfidz',
        'title' => 'Siswa MTs Al-Huda Raih Juara Lomba Tahfidz Tingkat Kabupaten',
        'date' => '10 September 2026',
        'excerpt' => 'Prestasi membanggakan kembali ditorehkan siswa dalam ajang lomba tahfidz tingkat kabupaten.',
    ],
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Berita &amp; Artikel</div>
            <h1>Kabar Terbaru dari <span>Madrasah</span></h1>
            <p>Informasi seputar kegiatan, prestasi, dan agenda MTs Al-Huda Gondang.</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="info-grid">
                <?php foreach ($berita as $item): ?>
                    <article class="info-card">
                        <small class="card-date"><?= htmlspecialchars($item['date']) ?></small>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['excerpt']) ?></p>
                        <a class="card-link" href="<?= url('berita/' . $item['slug']) ?>">Baca selengkapnya &rsaquo;</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
