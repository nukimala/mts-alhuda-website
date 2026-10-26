<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<main>
    <section class="section" style="padding:var(--sp-12) 0">
        <div class="container" style="text-align:center;max-width:560px">
            <div style="font-size:64px;font-weight:800;letter-spacing:-.04em;color:var(--green);line-height:1">404</div>
            <h1 style="font-size:var(--fs-sec);margin:var(--sp-3) 0 var(--sp-2)">Halaman tidak ditemukan</h1>
            <p style="font-size:var(--fs-sm);color:var(--gray);margin-bottom:var(--sp-6)">
                Alamat yang Anda buka tidak tersedia, sudah dipindahkan, atau salah ketik.
            </p>
            <div style="display:flex;gap:var(--sp-2);justify-content:center;flex-wrap:wrap">
                <a class="btn btn-primary" href="<?= base_url('') ?>">
                    <span class="mi mi-sm">home</span> Kembali ke Beranda
                </a>
                <a class="btn btn-secondary" href="<?= base_url('berita') ?>">Lihat Berita</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
