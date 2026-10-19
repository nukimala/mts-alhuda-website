<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$slug = $data['slug'] ?? '';
$judul = ucwords(str_replace('-', ' ', $slug));
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Berita</div>
            <h1><?= htmlspecialchars($judul !== '' ? $judul : 'Detail Berita') ?></h1>
        </div>
    </section>

    <section class="page-section">
        <div class="container article-body">
            <p>Ini adalah halaman detail berita dengan alamat <code>/berita/<?= htmlspecialchars($slug) ?></code>.</p>
            <p>Konten berita dapat diisi melalui panel admin atau langsung pada file view
                <code>app/Views/berita/detail.php</code>.</p>
            <a class="btn btn-outline" href="<?= url('berita') ?>">&lsaquo; Kembali ke daftar berita</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
