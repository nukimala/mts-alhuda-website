<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$galeri = [
    ['Belajar di Perpustakaan.jpg', 'Belajar di Perpustakaan'],
    ['Grup Sholawat  MTS Al-Huda.jpg', 'Grup Sholawat MTs Al-Huda'],
    ['Mengikuti Upacara Pembukaan Jambore Cabang.jpg', 'Upacara Pembukaan Jambore Cabang'],
    ['Senam Bersama.jpg', 'Senam Bersama'],
    ['Pertemuan Wali Santri.jpg', 'Pertemuan Wali Santri'],
    ['Drumband.jpg', 'Drumband'],
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Galeri</div>
            <h1>Momen &amp; <span>Kegiatan</span> Madrasah</h1>
            <p>Dokumentasi berbagai kegiatan belajar, ibadah, dan pengembangan diri siswa MTs Al-Huda Gondang.</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="gallery-grid">
                <?php foreach ($galeri as [$file, $caption]): ?>
                    <figure class="gallery-item">
                        <img src="<?= asset('assets/images/' . $file) ?>" alt="<?= htmlspecialchars($caption) ?>">
                        <figcaption><?= htmlspecialchars($caption) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
