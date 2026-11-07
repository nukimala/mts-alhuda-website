<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$photos = $data['photos'] ?? [];
if (empty($photos)) {
    $photos = [
        ['image_path' => 'assets/images/Belajar di Perpustakaan.jpg', 'title' => 'Belajar di Perpustakaan', 'caption' => 'Aktivitas literasi dan belajar santri di perpustakaan madrasah'],
        ['image_path' => 'assets/images/Grup Sholawat  MTS Al-Huda.jpg', 'title' => 'Grup Sholawat MTs Al-Huda', 'caption' => 'Penampilan tim hadrah dan seni religi santri'],
        ['image_path' => 'assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg', 'title' => 'Upacara Pembukaan Jambore Cabang', 'caption' => 'Regu penggalang pramuka MTs Al-Huda'],
        ['image_path' => 'assets/images/Senam Bersama.jpg', 'title' => 'Senam Bersama', 'caption' => 'Senam kebugaran jasmani bersama santri & asatidz'],
        ['image_path' => 'assets/images/Pertemuan Wali Santri.jpg', 'title' => 'Pertemuan Wali Santri', 'caption' => 'Silaturahmi dan koordinasi komite serta paguyuban wali santri'],
        ['image_path' => 'assets/images/Drumband.jpg', 'title' => 'Drumband', 'caption' => 'Korps Drumband Gita Al-Huda tampil memukau pada pawai budaya'],
    ];
}
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Dokumentasi &amp; Galeri</div>
            <h1>Momen &amp; <span>Kegiatan</span> Madrasah</h1>
            <p>Dokumentasi berbagai kegiatan belajar, ibadah, ekstrakurikuler, dan pengembangan diri siswa MTs Al-Huda Gondang.</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="gallery-grid">
                <?php foreach ($photos as $item): 
                    $imgPath = $item['image_path'] ?? $item['gambar'] ?? '';
                    $caption = $item['caption'] ?? $item['title'] ?? $item['judul'] ?? 'Dokumentasi MTs Al-Huda';
                ?>
                    <figure class="gallery-item">
                        <img src="<?= base_url($imgPath) ?>" alt="<?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        <figcaption><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
