<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <div class="eyebrow"><span>✦</span> Akreditasi Unggul • Pilihan Tepat Untuk Masa Depan</div>
                <h1>Belajar Lebih<br>Cerdas.<br>Tumbuh Lebih<br>Cepat.<br>Berakhlak <span>Mulia.</span></h1>
                <p class="hero-description">
                    Terbentuknya generasi yang cerdas, berilmu amaliah, beramal ilmiah, dan berakhlakul karimah.
                    Belajar ilmu agama dan teknologi di lingkungan yang terpadu.
                </p>
                <div class="hero-actions">
                    <a href="#akademik" class="btn btn-primary">Lihat Program Akademik <span>›</span></a>
                    <a href="/website-sekolah/public/ppdb" class="btn btn-outline">Info PPDB</a>
                </div>
                <div class="trust-line">
                    <span class="trust-icon">♟</span>
                    <span>Dipercaya <strong>187 siswa aktif</strong> dan keluarganya di Gondang</span>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-photo">
                    <img src="assets/images/Pertemuan Wali Santri.jpg" alt="Pertemuan Wali Santri">
                </div>
            </div>
                <div class="accreditation-card">
                    <span>▰</span>
                    <small>Akreditasi</small>
                    <strong>A</strong>
                    <small>Unggul</small>
                </div>
            </div>
        </div>

        <div class="container partner-card">
            <div class="partner-title">Bekerjasama dan Dipercaya Oleh</div>
            <strong>Kementerian Agama RI</strong>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats-section">
        <div class="container stats-card">
            <h2>Madrasah yang membantu siswa<br>meraih prestasi akademik dan<br>membangun karakter Islami di mana<br>saja.</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">A</div>
                    <div><strong>Akreditasi Unggul.</strong> Memastikan standar pendidikan dan pelayanan terbaik bagi putra-putri Anda.</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">187</div>
                    <div><strong>Siswa Aktif.</strong> Angka agregat jumlah siswa, bukan data pribadi.</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">14</div>
                    <div><strong>Tenaga Pendidik.</strong> Diversifikasi melalui kurikulum Merdeka dan pendekatan experiential learning.</div>
                </div>
            </div>
            <a href="/website-sekolah/public/akademik" class="mini-btn">Lihat Prestasi</a>
        </div>
    </section>

    <!-- PROGRAM -->
    <section id="akademik" class="program-section">
        <div class="container">
            <div class="section-heading">
                <div class="section-label">Kenapa Memilih Kami?</div>
                <h2><span>Pendidikan Seimbang</span> Untuk Masa<br>Depan</h2>
                <p>MTs Al-Huda Gondang mengintegrasikan ilmu agama dan ilmu pengetahuan umum untuk<br>membekali siswa menghadapi tantangan global.</p>
            </div>

            <div class="program-grid">
                <article class="program-card">
                    <div class="program-image image-Perpustakaan">
                    <img src="/website-sekolah/public/assets/images/Belajar di Perpustakaan.jpg" alt="Kurikulum Terpadu">
                    <span>Pondasi Keilmuan</span>
            </div>
                    <div class="program-content">
                        <h3>Kurikulum Terpadu</h3>
                        <p>Menggabungkan kurikulum nasional dan nilai-nilai pesantren secara harmonis.</p>
                    </div>
                </article>

                <article class="program-card">
                    <div class="program-image image-Grup Sholawat  MTS Al-Huda">
                        <img src="/website-sekolah/public/assets/images/Grup Sholawat  MTS Al-Huda.jpg" alt="Program sholawat">
                        <span>Spiritual</span>
                    </div>
                    <div class="program-content">
                        <h3>Program Sholawat</h3>
                        <p>Bimbingan hafalan Al-Quran intensif dengan target capaian terukur setiap semester.</p>
                    </div>
                </article>

                <article class="program-card">
                    <div class="program-image image-Mengikuti Upacara Pembukaan Jambore Cabang">
                        <img src="/website-sekolah/public/assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg" alt="Ekstrakurikuler">
                        <span>Pengembangan Diri</span>
                    </div>
                    <div class="program-content">
                        <h3>Ekstrakurikuler</h3>
                        <p>Dari pramuka, seni banjari, hingga klub sains untuk menggali bakat siswa.</p>
                    </div>
                </article>

                <article class="program-card">
                    <div class="program-image image-Senam Bersama">
                        <img src="/website-sekolah/public/assets/images/Senam Bersama.jpg" alt="Lingkungan Asri">
                        <span>Fasilitas</span>
                    </div>
                    <div class="program-content">
                        <h3>Lingkungan Asri</h3>
                        <p>Ruang kelas nyaman dan fasilitas lengkap di Desa Pandean, Gondang.</p>
                    </div>
                </article>
            </div>

            <div class="program-cta">
                <a href="/website-sekolah/public/profil" class="btn btn-primary">Mulai Belajar Bersama Kami</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
