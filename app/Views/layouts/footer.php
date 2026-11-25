<footer class="site-footer footer-custom">
    <div class="container footer-main">
        <!-- Profil Madrasah -->
        <div class="footer-about">
            <a href="<?= url('/') ?>" class="footer-logo-link">
                <img src="<?= asset('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTS Al-Huda Gondang" class="footer-logo">
                <div>
                    <h3 class="footer-brand-title">MTS Al-Huda Gondang</h3>
                    <span class="footer-accreditation">Terakreditasi "A" (Unggul) BAN-PDM</span>
                </div>
            </a>
            <p class="footer-description">
                Madrasah Tsanawiyah terpadu yang memadukan kurikulum nasional dan nilai-nilai kepesantrenan untuk membina generasi cerdas, mandiri, dan berakhlakul karimah di Gondang, Nganjuk.
            </p>
            <div class="footer-info">
                NPSN: <strong>20582234</strong> • NSM: <strong>121235180039</strong>
            </div>
        </div>

        <!-- Navigasi Utama -->
        <div>
            <h4 class="footer-section-title">Navigasi Cepat</h4>
            <div class="footer-links">
                <a href="<?= url('/') ?>" class="footer-link">Beranda</a>
                <a href="<?= url('profil') ?>" class="footer-link">Profil Madrasah</a>
                <a href="<?= url('akademik') ?>" class="footer-link">Kurikulum &amp; Jadwal</a>
                <a href="<?= url('fasilitas') ?>" class="footer-link">Fasilitas &amp; Sarpras</a>
                <a href="<?= url('galeri') ?>" class="footer-link">Galeri Kegiatan Santri</a>
                <a href="<?= url('berita') ?>" class="footer-link">Berita &amp; Prestasi</a>
            </div>
        </div>

        <!-- Layanan & Informasi -->
        <div>
            <h4 class="footer-section-title">Layanan Publik</h4>
            <div class="footer-links">
                <a href="<?= url('ppdb') ?>" class="footer-link footer-link-primary">Pendaftaran PPDB Online</a>
                <a href="<?= url('akademik#jadwal') ?>" class="footer-link">Jadwal Pelajaran Siswa</a>
                <a href="<?= url('kontak') ?>" class="footer-link">Hubungi Tata Usaha</a>
                <a href="<?= url('admin') ?>" target="_blank" rel="noopener noreferrer" class="footer-link">Login Sistem &amp; Guru</a>
            </div>
        </div>

        <!-- Alamat & Jam Kerja -->
        <?php
        $fAlamat = Auth::setting('school_address', 'Jln. Letjen Soeprapto No.16, Ds. Pandean, Kec. Gondang, Kab. Nganjuk, Jawa Timur');
        $fTelp = Auth::setting('school_phone', '(0358) 771234');
        $fWa = Auth::setting('school_whatsapp', '0812-3456-7890');
        $fEmail = Auth::setting('school_email', 'info@mts-alhuda.sch.id');
        $fJam = Auth::setting('school_hours', 'Senin - Sabtu (07.00 - 14.00 WIB)');
        ?>
        <div>
            <h4 class="footer-section-title">Kontak &amp; Alamat</h4>
            <div class="footer-contact">
                <div>📍 <?= e($fAlamat) ?></div>
                <div>☎ Telepon: <?= e($fTelp) ?></div>
                <div>💬 WhatsApp: <?= e($fWa) ?></div>
                <div>✉ Email: <?= e($fEmail) ?></div>
                <div>⏰ Pelayanan: <?= e($fJam) ?></div>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="container footer-bottom-custom">
        <span>© <?= date('Y') ?> MTS Al-Huda Gondang. Terakreditasi "A" BAN-PDM. Seluruh hak cipta dilindungi.</span>
        <span>Kecamatan Gondang, Kabupaten Nganjuk, Jawa Timur</span>
    </div>
</footer>

<script src="<?= base_url('js/script.js') ?>" defer></script>
</body>
</html>