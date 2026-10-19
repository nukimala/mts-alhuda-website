<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$isInformasi = ($data['section'] ?? '') === 'informasi';
?>

<main>
    <section class="page-hero">
        <div class="container">
            <div class="section-label"><?= $isInformasi ? 'Informasi & Agenda' : 'Info PPDB' ?></div>
            <h1>
                <?php if ($isInformasi): ?>
                    Pengumuman &amp; <span>Agenda</span> Madrasah
                <?php else: ?>
                    Penerimaan Peserta Didik <span>Baru</span>
                <?php endif; ?>
            </h1>
            <p>
                <?php if ($isInformasi): ?>
                    Jadwal kegiatan dan pengumuman resmi MTs Al-Huda Gondang.
                <?php else: ?>
                    Bergabunglah bersama MTs Al-Huda Gondang. Pendaftaran dibuka untuk lulusan SD/MI.
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="info-grid">
                <?php if ($isInformasi): ?>
                    <article class="info-card">
                        <h3>Agenda Terdekat</h3>
                        <p>Jadwal kegiatan madrasah akan ditampilkan di sini.</p>
                    </article>
                    <article class="info-card">
                        <h3>Pengumuman</h3>
                        <p>Pengumuman resmi untuk siswa, wali santri, dan masyarakat.</p>
                    </article>
                <?php else: ?>
                    <article class="info-card">
                        <h3>Syarat Pendaftaran</h3>
                        <ul>
                            <li>Fotokopi ijazah / SKL SD/MI</li>
                            <li>Fotokopi kartu keluarga</li>
                            <li>Fotokopi akta kelahiran</li>
                            <li>Pas foto 3x4</li>
                        </ul>
                    </article>
                    <article class="info-card">
                        <h3>Alur Pendaftaran</h3>
                        <ul>
                            <li>Ambil / isi formulir pendaftaran</li>
                            <li>Serahkan berkas persyaratan</li>
                            <li>Verifikasi data</li>
                            <li>Pengumuman kelulusan</li>
                        </ul>
                    </article>
                    <article class="info-card">
                        <h3>Informasi</h3>
                        <p>Hubungi panitia PPDB melalui <strong>(0358) 123456</strong> atau datang langsung ke madrasah.</p>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
