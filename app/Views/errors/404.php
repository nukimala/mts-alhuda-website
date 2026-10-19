<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Error 404</div>
            <h1>Halaman <span>Tidak Ditemukan</span></h1>
            <p>Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
            <div class="hero-actions" style="justify-content:center;margin-top:18px;">
                <a class="btn btn-primary" href="<?= url('/') ?>">Kembali ke Beranda</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
