<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label">Program Akademik</div>
            <h1>Pendidikan <span>Seimbang</span> Ilmu Agama &amp; Umum</h1>
            <p>MTs Al-Huda Gondang mengintegrasikan kurikulum nasional dan nilai-nilai pesantren untuk membekali siswa menghadapi tantangan global.</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="info-grid">
                <article class="info-card">
                    <h3>Kurikulum Terpadu</h3>
                    <p>Menggabungkan kurikulum nasional (Merdeka) dan nilai-nilai pesantren secara harmonis.</p>
                </article>
                <article class="info-card">
                    <h3>Program Sholawat</h3>
                    <p>Bimbingan hafalan Al-Qur'an intensif dengan target capaian terukur setiap semester.</p>
                </article>
                <article class="info-card">
                    <h3>Ekstrakurikuler</h3>
                    <p>Dari pramuka, seni banjari, hingga klub sains untuk menggali bakat siswa.</p>
                </article>
                <article class="info-card">
                    <h3>Prestasi</h3>
                    <p>Pembinaan lomba akademik dan non-akademik di tingkat kecamatan hingga kabupaten.</p>
                </article>
            </div>

            <div class="program-cta">
                <a href="<?= url('ppdb') ?>" class="btn btn-primary">Daftar PPDB Sekarang</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
