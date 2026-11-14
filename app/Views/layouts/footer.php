<footer class="site-footer" style="background: #0B3D1E; color: rgba(255,255,255,.8); padding: 50px 0 24px; font-size: 13.5px; border-top: 4px solid #15803D;">
    <div class="container footer-grid" style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.2fr); gap: 36px; margin-bottom: 40px;">
        <!-- Profil Madrasah -->
        <div class="footer-about">
            <a href="<?= url('/') ?>" style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; text-decoration: none;">
                <img src="<?= asset('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTS Al-Huda Gondang" width="44" height="44" style="width: 44px; height: 44px; object-fit: contain; background: #fff; border-radius: 8px; padding: 4px;">
                <div>
                    <h3 style="color: #ffffff; font-size: 17px; font-weight: 800; margin: 0; line-height: 1.2;">MTS Al-Huda Gondang</h3>
                    <span style="font-size: 11px; color: #4ADE80; font-weight: 700;">Terakreditasi "A" (Unggul) BAN-PDM</span>
                </div>
            </a>
            <p style="color: #E2E8F0; line-height: 1.65; font-size: 13px; margin-bottom: 14px;">
                Madrasah Tsanawiyah terpadu yang memadukan kurikulum nasional dan nilai-nilai kepesantrenan untuk membina generasi cerdas, mandiri, dan berakhlakul karimah di Gondang, Nganjuk.
            </p>
            <div style="font-size: 12px; color: #BBF7D0;">
                NPSN: <strong>20582234</strong> • NSM: <strong>121235180039</strong>
            </div>
        </div>

        <!-- Navigasi Utama -->
        <div>
            <h4 style="color: #ffffff; font-size: 15px; font-weight: 800; margin-bottom: 16px; border-left: 3px solid #4ADE80; padding-left: 8px;">
                Navigasi Cepat
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                <a href="<?= url('/') ?>" style="color: #E2E8F0; text-decoration: none;">Beranda</a>
                <a href="<?= url('profil') ?>" style="color: #E2E8F0; text-decoration: none;">Profil Madrasah</a>
                <a href="<?= url('akademik') ?>" style="color: #E2E8F0; text-decoration: none;">Kurikulum &amp; Jadwal</a>
                <a href="<?= url('fasilitas') ?>" style="color: #E2E8F0; text-decoration: none;">Fasilitas &amp; Sarpras</a>
                <a href="<?= url('galeri') ?>" style="color: #E2E8F0; text-decoration: none;">Galeri Kegiatan Santri</a>
                <a href="<?= url('berita') ?>" style="color: #E2E8F0; text-decoration: none;">Berita &amp; Prestasi</a>
            </div>
        </div>

        <!-- Layanan & Informasi -->
        <div>
            <h4 style="color: #ffffff; font-size: 15px; font-weight: 800; margin-bottom: 16px; border-left: 3px solid #4ADE80; padding-left: 8px;">
                Layanan Publik
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                <a href="<?= url('ppdb') ?>" style="color: #4ADE80; font-weight: 700; text-decoration: none;">Pendaftaran PPDB Online</a>
                <a href="<?= url('akademik#jadwal') ?>" style="color: #E2E8F0; text-decoration: none;">Jadwal Pelajaran Siswa</a>
                <a href="<?= url('kontak') ?>" style="color: #E2E8F0; text-decoration: none;">Hubungi Tata Usaha</a>
                <a href="<?= url('admin') ?>" target="_blank" style="color: #E2E8F0; text-decoration: none;">Login Sistem &amp; Guru</a>
            </div>
        </div>

        <!-- Alamat & Jam Kerja -->
        <div>
            <h4 style="color: #ffffff; font-size: 15px; font-weight: 800; margin-bottom: 16px; border-left: 3px solid #4ADE80; padding-left: 8px;">
                Kontak &amp; Alamat
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 12.5px; color: #E2E8F0;">
                <div>📍 Jln. Letjen Soeprapto No.16, Ds. Pandean, Kec. Gondang, Kab. Nganjuk, Jawa Timur</div>
                <div>☎ Telepon: (0358) 771234</div>
                <div>💬 WhatsApp: 0812-3456-7890</div>
                <div>✉ Email: info@mts-alhuda.sch.id</div>
                <div>⏰ Pelayanan: Senin - Sabtu (07.00 - 14.00 WIB)</div>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="container" style="border-top: 1px solid rgba(255,255,255,.1); padding-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 12px; color: rgba(255,255,255,.6);">
        <span>© <?= date('Y') ?> MTS Al-Huda Gondang. Terakreditasi "A" BAN-PDM. Seluruh hak cipta dilindungi.</span>
        <span>Kecamatan Gondang, Kabupaten Nganjuk, Jawa Timur</span>
    </div>
</footer>

<script src="<?= base_url('js/main.js') ?>" defer></script>
</body>
</html>
