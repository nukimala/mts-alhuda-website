<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$guru = $data['guru'] ?? [];
$settings = $data['settings'] ?? [];
?>

<main>
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Identitas &amp; Dokumen Resmi Madrasah
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">
                Profil Lengkap <span>MTs Al-Huda</span> Gondang
            </h1>
            <p style="color: rgba(255,255,255,.85); max-width: 680px; font-size: 14.5px; line-height: 1.6;">
                Mewujudkan institusi pendidikan Islam terpadu yang unggul dalam ilmu keagamaan dan ilmu pengetahuan umum di Desa Pandean, Kecamatan Gondang, Kabupaten Nganjuk.
            </p>
        </div>
    </section>

    <!-- SECTION 1: IDENTITAS RESMI (STANDAR AKREDITASI) -->
    <section style="padding: 50px 0 30px; background: #ffffff;">
        <div class="container">
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,.04); margin-bottom: 36px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">
                        🏛️ Data Pokok Pendidikan Madrasah
                    </h2>
                    <span style="background: #DCFCE7; color: #15803D; font-weight: 800; font-size: 12.5px; padding: 4px 14px; border-radius: 20px;">
                        Status: Terakreditasi "A" (Unggul)
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; font-size: 13.5px;">
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">Nama Lembaga</span>
                        <strong style="color: #0F172A; font-size: 14.5px;">MTs Al-Huda Gondang</strong>
                    </div>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">NPSN / NSM</span>
                        <strong style="color: #0F172A; font-size: 14.5px;">20582910 / 121235180039</strong>
                    </div>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">Kepala Madrasah</span>
                        <strong style="color: #0F172A; font-size: 14.5px;">M. Abdul Haris Jauhari, S.Pd.I</strong>
                    </div>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">Alamat Lengkap</span>
                        <strong style="color: #0F172A; font-size: 14px;">Jl. Letjend Suprapto No. 16, Pandean, Gondang, Nganjuk</strong>
                    </div>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">Naungan Yayasan</span>
                        <strong style="color: #0F172A; font-size: 14.5px;">Yayasan Pendidikan Islam Al-Huda</strong>
                    </div>
                    <div style="background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <span style="color: #64748B; font-size: 11.5px; font-weight: 700; display: block; text-transform: uppercase;">SK Izin Operasional</span>
                        <strong style="color: #0F172A; font-size: 14px;">Kemenag Jawa Timur (Terdaftar Resmi)</strong>
                    </div>
                </div>
            </div>

            <!-- VISI, MISI, & TUJUAN STRATEGIS -->
            <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 30px; margin-bottom: 40px; align-items: start;">
                <!-- VISI -->
                <div style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 14px; padding: 28px; box-shadow: 0 4px 12px rgba(0,0,0,.04);">
                    <div style="display: inline-block; background: #DCFCE7; color: #15803D; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; margin-bottom: 10px;">
                        Visi Madrasah
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; color: #0F172A; line-height: 1.4; margin-bottom: 14px;">
                        "Terbentuknya Generasi Cerdas, Berilmu Amaliah, Beramal Ilmiah, dan Berakhlakul Karimah."
                    </h3>
                    <p style="font-size: 13.5px; color: #475569; line-height: 1.65; margin: 0;">
                        Visi ini mencerminkan tekad madrasah untuk melahirkan lulusan yang seimbang antara kematangan intelektual, kedalaman spiritual, dan keluhuran pekerti di tengah masyarakat.
                    </p>
                </div>

                <!-- MISI -->
                <div style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 14px; padding: 28px; box-shadow: 0 4px 12px rgba(0,0,0,.04);">
                    <div style="display: inline-block; background: #EFF6FF; color: #1D4ED8; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; margin-bottom: 10px;">
                        Misi Madrasah
                    </div>
                    <ol style="padding-left: 20px; margin: 0; font-size: 13.5px; color: #334155; line-height: 1.7;">
                        <li style="margin-bottom: 8px;">Menyelenggarakan pendidikan formal dan kepesantrenan yang berkualitas dan terjangkau bagi masyarakat.</li>
                        <li style="margin-bottom: 8px;">Membina kemampuan baca tulis, tahsin, dan tahfidz Al-Qur'an secara berkesinambungan.</li>
                        <li style="margin-bottom: 8px;">Membiasakan nilai-nilai akhlakul karimah dan ibadah harian (sholat dhuha, sholat dhuhur berjamaah, dan sholawat).</li>
                        <li style="margin-bottom: 8px;">Mengembangkan potensi akademik dan non-akademik siswa melalui bimbingan intensif dan ekstrakurikuler.</li>
                        <li>Mewujudkan tata kelola madrasah yang akuntabel, transparan, dan berbasis teknologi informasi.</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: DATA PENDIDIK & TENAGA KEPENDIDIKAN (BUKTI AKREDITASI GURU) -->
    <section style="padding: 50px 0 70px; background: #F8FAFC; border-top: 1px solid #E2E8F0;">
        <div class="container">
            <div style="text-align: center; max-width: 680px; margin: 0 auto 36px;">
                <div style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                    Sumber Daya Manusia (SDM)
                </div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0F172A; margin-bottom: 8px;">
                    Tenaga Pendidik &amp; Kependidikan Profesional
                </h2>
                <p style="font-size: 14px; color: #64748B;">
                    Guru berkompeten dengan kualifikasi S1/S2 dan dedikasi tinggi mendidik santri MTs Al-Huda.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                <?php if (empty($guru)): ?>
                    <p style="color: #64748B; text-align: center; grid-column: 1 / -1;">Data pendidik tersedia di kantor tata usaha madrasah.</p>
                <?php else: ?>
                    <?php foreach ($guru as $g): ?>
                        <div style="background: #ffffff; border-radius: 12px; border: 1px solid #E2E8F0; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,.04); display: flex; align-items: center; gap: 14px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: #DCFCE7; color: #15803D; display: grid; place-items: center; font-size: 20px; font-weight: 800; flex: 0 0 50px;">
                                👤
                            </div>
                            <div>
                                <h4 style="font-size: 14.5px; font-weight: 800; color: #0F172A; margin-bottom: 4px; line-height: 1.3;">
                                    <?= htmlspecialchars($g['name']) ?>
                                </h4>
                                <div style="font-size: 12px; color: #15803D; font-weight: 700;">
                                    Guru Mata Pelajaran
                                </div>
                                <div style="font-size: 11px; color: #64748B; margin-top: 2px;">
                                    NIP: <?= htmlspecialchars($g['nip'] ?? '-') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
