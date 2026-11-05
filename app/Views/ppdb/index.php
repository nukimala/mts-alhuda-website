<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$settings = $data['settings'] ?? [];
$documents = $data['documents'] ?? [];
$ppdbTitle = $settings['ppdb_title'] ?? 'Penerimaan Peserta Didik Baru (PPDB)';
$ppdbStatus = $settings['ppdb_status'] ?? 'Buka';
$ppdbQuota = $settings['ppdb_quota'] ?? '3 Rombel (Rasio Efektif)';
$ppdbContact = $settings['ppdb_contact'] ?? '(0358) 123456 / 0812-3456-7890';
?>

<main>
    <section class="page-hero-clean">
        <div class="container">
            <div class="hero-label">Penerimaan Siswa &amp; Santri Baru</div>
            <h1><?= htmlspecialchars($ppdbTitle) ?></h1>
            <p>
                Selamat datang calon santri MTs Al-Huda Gondang. Kami membuka kesempatan belajar bagi putra-putri lulusan SD/MI untuk menimba ilmu agama dan ilmu pengetahuan umum dalam lingkungan yang asri dan berakhlak mulia.
            </p>
        </div>
    </section>

    <section style="padding: 56px 0 72px; background: #FFFFFF;">
        <div class="container">
            <!-- STATUS PPDB BANNER -->
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-left: 4px solid #15803D; border-radius: 12px; padding: 22px 28px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.06em; display: block; margin-bottom: 4px;">
                        Status Pendaftaran
                    </span>
                    <strong style="font-size: 18px; color: #0F172A;">
                        Pendaftaran Sedang Berlangsung &middot; Kuota: <?= htmlspecialchars($ppdbQuota) ?>
                    </strong>
                </div>
                <div>
                    <a href="<?= url('kontak') ?>" class="btn btn-primary" style="font-size: 13.5px;">
                        Hubungi Panitia PPDB
                    </a>
                </div>
            </div>

            <!-- DUA KOLOM: PERSYARATAN & ALUR -->
            <div class="visi-misi-v2-grid">
                <!-- PERSYARATAN -->
                <article class="visi-v2-card">
                    <span class="vm-tag green">Persyaratan Pendaftaran</span>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 16px;">
                        Berkas Kelengkapan Calon Santri
                    </h3>
                    <ul style="padding-left: 20px; margin: 0; font-size: 14px; color: #334155; line-height: 1.75;">
                        <li style="margin-bottom: 8px;">Mengisi formulir pendaftaran resmi madrasah.</li>
                        <li style="margin-bottom: 8px;">Fotokopi Ijazah / Surat Keterangan Lulus (SKL) SD/MI yang telah dilegalisir (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Fotokopi Kartu Keluarga (KK) dan Akta Kelahiran calon siswa (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Fotokopi KTP kedua orang tua / wali santri (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Pas foto resmi ukuran 3x4 berpakaian rapi (3 lembar).</li>
                        <li>Fotokopi Kartu Indonesia Pintar (KIP) / PKH bila memiliki.</li>
                    </ul>
                </article>

                <!-- ALUR PENDAFTARAN -->
                <article class="misi-v2-card">
                    <span class="vm-tag blue">Alur Pendaftaran</span>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 16px;">
                        Tahapan Registrasi Santri Baru
                    </h3>
                    <ol class="misi-v2-list">
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">1</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Pengambilan &amp; Pengisian Formulir</strong>
                                <span>Mengambil formulir langsung di kantor tata usaha madrasah atau mengunduh dokumen formulir di bawah ini.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">2</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Penyerahan &amp; Verifikasi Berkas</strong>
                                <span>Menyerahkan berkas kelengkapan kepada panitia PPDB di MTs Al-Huda Gondang untuk pemeriksaan administrasi.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">3</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Observasi Minat &amp; Bimbingan Baca Quran</strong>
                                <span>Pemetaan awal kemampuan membaca Al-Qur'an dan wawancara minat santri bersama orang tua/wali.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">4</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Daftar Ulang &amp; Masa Ta'aruf Siswa Madrasah (MATSAMA)</strong>
                                <span>Pemberkasan akhir, pembagian seragam, dan pengenalan lingkungan belajar madrasah.</span>
                            </div>
                        </li>
                    </ol>
                </article>
            </div>

            <!-- UNDUHAN DOKUMEN PPDB RESMI -->
            <div class="doc-card" style="margin-top: 40px; margin-bottom: 0;">
                <div class="doc-card-header">
                    <div>
                        <div class="section-pretitle">Berkas Resmi</div>
                        <h2 class="doc-card-title">Unduhan Formulir &amp; Brosur PPDB</h2>
                    </div>
                </div>

                <?php if (empty($documents)): ?>
                    <div style="padding: 24px; background: #F8FAFC; border-radius: 12px; text-align: center; color: #64748B; font-size: 14px;">
                        Formulir cetak dan brosur dapat diambil langsung di sekretariat pendaftaran madrasah setiap hari kerja (07.00 - 14.00 WIB).
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                        <?php foreach ($documents as $doc): ?>
                            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <strong style="color: #0F172A; font-size: 14.5px; display: block; margin-bottom: 4px;">
                                        <?= htmlspecialchars($doc['title']) ?>
                                    </strong>
                                    <span style="font-size: 12px; color: #64748B;">
                                        <?= htmlspecialchars($doc['category'] ?? 'Dokumen Resmi') ?> &middot; <?= (int) ($doc['download_count'] ?? 0) ?>x diunduh
                                    </span>
                                </div>
                                <a href="<?= base_url($doc['file_path']) ?>" target="_blank" download class="btn btn-secondary" style="min-height: 38px; padding: 6px 14px; font-size: 12.5px; white-space: nowrap;">
                                    Unduh Berkas
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
