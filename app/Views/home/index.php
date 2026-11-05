<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$latestPosts = $data['latest_posts'] ?? [];
$facilities = $data['facilities'] ?? [];
?>

<main>
    <!-- HERO SECTION -->
    <section class="hero" style="background: #F8FAF9; padding: 60px 0 55px; border-bottom: 1px solid #E1ECE5;">
        <div class="container hero-grid" style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 40px; align-items: center;">
            <div class="hero-copy">
                <div class="eyebrow" style="display: inline-flex; align-items: center; gap: 8px; background: #DCFCE7; color: #15803D; padding: 5px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; margin-bottom: 16px; border: 1px solid #BBF7D0;">
                    <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#15803D;"></span> Terakreditasi "A" Unggul BAN-PDM
                </div>
                <h1 style="font-size: 38px; line-height: 1.22; font-weight: 800; color: #0F172A; margin-bottom: 18px; letter-spacing: -0.02em;">
                    Membentuk Generasi Cerdas, Berilmu Amaliah, dan <span style="color: #15803D;">Berakhlakul Karimah.</span>
                </h1>
                <p class="hero-description" style="font-size: 15px; color: #475569; line-height: 1.65; margin-bottom: 26px;">
                    MTs Al-Huda Gondang memadukan kurikulum nasional dan nilai-nilai luhur kepesantrenan. Didukung fasilitas digital, bimbingan tahfidz intensif, dan lingkungan asri di Desa Pandean, Gondang, Kab. Nganjuk.
                </p>
                <div class="hero-actions" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 30px;">
                    <a href="<?= url('akademik') ?>" class="btn btn-primary" style="padding: 11px 22px; font-size: 14px; font-weight: 700; border-radius: 10px;">
                        Jelajahi Akademik &amp; Jadwal
                    </a>
                    <a href="<?= url('ppdb') ?>" class="btn btn-outline" style="padding: 11px 22px; font-size: 14px; font-weight: 700; border-radius: 10px; border-color: #CBD5E1;">
                        Informasi PPDB 2026/2027
                    </a>
                </div>
                <div class="trust-line" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #64748B;">
                    <span style="font-size: 18px;">🏛️</span>
                    <span>Dipercaya <strong>187+ siswa aktif</strong> dan wali santri se-Kecamatan Gondang</span>
                </div>
            </div>

            <div class="hero-visual" style="position: relative;">
                <div style="border-radius: 16px; overflow: hidden; box-shadow: 0 16px 36px rgba(15,61,30,.15); border: 4px solid #fff;">
                    <img src="<?= base_url('assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg') ?>"
                         alt="Kegiatan Santri MTs Al-Huda Gondang"
                         width="600"
                         height="400"
                         fetchpriority="high"
                         style="width: 100%; height: auto; object-fit: cover; display: block;">
                </div>
                <div style="position: absolute; bottom: -18px; left: -18px; background: #ffffff; padding: 14px 20px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.12); border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #DCFCE7; color: #15803D; display: grid; place-items: center; font-size: 20px; font-weight: 800;">
                        A
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Akreditasi BAN-PDM</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0F172A;">Peringkat A (Unggul)</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SAMBUTAN KEPALA MADRASAH -->
    <section style="padding: 60px 0; background: #ffffff; border-bottom: 1px solid #E2E8F0;">
        <div class="container" style="display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 40px; align-items: center;">
            <div style="text-align: center;">
                <div style="width: 220px; height: 260px; margin: 0 auto; border-radius: 16px; overflow: hidden; background: #DCFCE7; border: 3px solid #15803D; box-shadow: 0 10px 24px rgba(21,128,61,.12); display: flex; align-items: center; justify-content: center;">
                    <img src="<?= base_url('assets/images/Pertemuan Wali Santri.jpg') ?>"
                         alt="Kepala MTs Al-Huda"
                         width="220"
                         height="260"
                         style="width: 100%; height: 100%; object-fit: cover; display: block;">
                </div>
                <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-top: 14px; margin-bottom: 2px;">
                    Drs. H. M. Sholihin, M.Pd.I
                </h4>
                <p style="font-size: 12px; color: #15803D; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Kepala Madrasah
                </p>
            </div>
            <div>
                <div style="display: inline-block; background: #E9F9EF; color: #15803D; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em;">
                    Sambutan Pimpinan
                </div>
                <h2 style="font-size: 26px; font-weight: 800; color: #0F172A; margin-bottom: 16px; line-height: 1.3;">
                    Mewujudkan Pendidikan Berkualitas, Terpadu, dan Berakhlak Mulia
                </h2>
                <div style="font-size: 14.5px; color: #475569; line-height: 1.75; margin-bottom: 20px;">
                    <p style="margin-bottom: 12px;">
                        <em>Assalamu’alaikum Warahmatullahi Wabarakatuh.</em>
                    </p>
                    <p style="margin-bottom: 12px;">
                        Selamat datang di portal informasi resmi MTs Al-Huda Gondang. Di era keterbukaan informasi dan transformasi digital, madrasah kami berkomitmen menghadirkan layanan pendidikan yang transparan, akuntabel, dan adaptif terhadap perkembangan ilmu pengetahuan dan teknologi tanpa mengesampingkan jati diri pesantren.
                    </p>
                    <p>
                        Melalui perpaduan Kurikulum Merdeka dan keteladanan salafus sholih, kami mendidik santri untuk tidak hanya unggul secara kognitif, namun juga memiliki integritas spiritual dan kesiapan berdaya saing global.
                    </p>
                </div>
                <a href="<?= url('profil') ?>" class="btn btn-outline" style="font-weight: 700; font-size: 13px; border-radius: 10px;">
                    Baca Profil &amp; Visi Misi Lengkap
                </a>
            </div>
        </div>
    </section>

    <!-- STATS SEKOLAH (BUKTI AKREDITASI) -->
    <section class="stats-section" style="padding: 50px 0; background: #F8FAFC;">
        <div class="container stats-card" style="background: #15803D; color: #ffffff; border-radius: 16px; padding: 36px 40px; box-shadow: 0 12px 30px rgba(21,128,61,.2);">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; text-align: center;">
                <div>
                    <div style="font-size: 38px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; color: #BBF7D0;">A</div>
                    <div style="font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 2px;">Akreditasi Unggul</div>
                    <div style="font-size: 12px; color: #E2E8F0;">Standar Mutu Nasional BAN-PDM</div>
                </div>
                <div>
                    <div style="font-size: 38px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; color: #BBF7D0;">187+</div>
                    <div style="font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 2px;">Santri &amp; Siswa Aktif</div>
                    <div style="font-size: 12px; color: #E2E8F0;">Terbina dalam 3 jenjang kelas</div>
                </div>
                <div>
                    <div style="font-size: 38px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; color: #BBF7D0;">14</div>
                    <div style="font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 2px;">Tenaga Pendidik</div>
                    <div style="font-size: 12px; color: #E2E8F0;">Guru tersertifikasi &amp; berpengalaman</div>
                </div>
                <div>
                    <div style="font-size: 38px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; color: #BBF7D0;">100%</div>
                    <div style="font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 2px;">Kelulusan &amp; Pembinaan</div>
                    <div style="font-size: 12px; color: #E2E8F0;">Lanjutan ke MA / SMA / SMK favorit</div>
                </div>
            </div>
        </div>
    </section>

    <!-- BERITA TERBARU (LIVE DARI DATABASE) -->
    <section style="padding: 60px 0; background: #ffffff;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                        Warta Terkini
                    </div>
                    <h2 style="font-size: 26px; font-weight: 800; color: #0F172A; margin: 0;">
                        Kabar &amp; Prestasi Terbaru Madrasah
                    </h2>
                </div>
                <a href="<?= url('berita') ?>" class="btn btn-outline" style="font-weight: 700; font-size: 13px; border-radius: 10px;">
                    Lihat Semua Berita
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                <?php if (empty($latestPosts)): ?>
                    <p style="color: #64748B;">Belum ada berita terbit.</p>
                <?php else: ?>
                    <?php foreach ($latestPosts as $bp): ?>
                        <article style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 8px rgba(0,0,0,.04); transition: transform .2s ease;">
                            <div style="height: 180px; background: #E2E8F0; overflow: hidden; position: relative;">
                                <?php if (!empty($bp['cover_path'])): ?>
                                    <img src="<?= base_url($bp['cover_path']) ?>"
                                         alt="<?= htmlspecialchars($bp['title']) ?>"
                                         width="360"
                                         height="180"
                                         loading="lazy"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg, #15803D, #0B3D1E);display:grid;place-items:center;color:#fff;font-weight:800;font-size:18px">
                                        MTs Al-Huda
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                                <div style="font-size: 12px; color: #64748B; margin-bottom: 6px;">
                                    📅 <?= !empty($bp['published_at']) ? date('d M Y', strtotime($bp['published_at'])) : date('d M Y') ?>
                                </div>
                                <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 8px; line-height: 1.35;">
                                    <a href="<?= url('berita/' . $bp['slug']) ?>" style="text-decoration: none; color: inherit;">
                                        <?= htmlspecialchars($bp['title']) ?>
                                    </a>
                                </h3>
                                <p style="font-size: 13px; color: #475569; line-height: 1.55; margin-bottom: 16px; flex: 1;">
                                    <?= htmlspecialchars($bp['excerpt'] ?? mb_substr(strip_tags($bp['content']), 0, 95) . '...') ?>
                                </p>
                                <a href="<?= url('berita/' . $bp['slug']) ?>" style="color: #15803D; font-weight: 700; font-size: 13px; text-decoration: none;">
                                    Baca Selengkapnya
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FASILITAS UNGGULAN (LIVE DARI DATABASE) -->
    <section style="padding: 60px 0; background: #F8FAFC;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                        Sarana &amp; Prasarana
                    </div>
                    <h2 style="font-size: 26px; font-weight: 800; color: #0F172A; margin: 0;">
                        Fasilitas Lengkap Pendukung Prestasi
                    </h2>
                </div>
                <a href="<?= url('fasilitas') ?>" class="btn btn-outline" style="font-weight: 700; font-size: 13px; border-radius: 10px;">
                    Lihat Semua Fasilitas
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
                <?php foreach ($facilities as $fac): ?>
                    <div style="background: #ffffff; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,.04);">
                        <div style="height: 160px; background: #E2E8F0; position: relative;">
                            <?php if (!empty($fac['image_path'])): ?>
                                <img src="<?= base_url($fac['image_path']) ?>"
                                     alt="<?= htmlspecialchars($fac['name']) ?>"
                                     width="280"
                                     height="160"
                                     loading="lazy"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:linear-gradient(135deg, #15803D 0%, #0B3D1E 100%);display:grid;place-items:center;color:#fff;font-weight:700">
                                    Sarana Madrasah
                                </div>
                            <?php endif; ?>
                            <span style="position: absolute; top: 10px; right: 10px; background: rgba(15,23,42,.75); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px;">
                                <?= htmlspecialchars($fac['icon'] ?? 'Gedung') ?>
                            </span>
                        </div>
                        <div style="padding: 16px;">
                            <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">
                                <?= htmlspecialchars($fac['name']) ?>
                            </h3>
                            <p style="font-size: 12.5px; color: #64748B; line-height: 1.5; margin: 0;">
                                <?= htmlspecialchars($fac['description'] ?? 'Sarana pendukung belajar santri.') ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- PROGRAM UNGGULAN & EKSTRAKURIKULER -->
    <section style="padding: 60px 0; background: #ffffff;">
        <div class="container">
            <div style="text-align: center; max-width: 680px; margin: 0 auto 36px;">
                <div style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">
                    Pengembangan Bakat Santri
                </div>
                <h2 style="font-size: 26px; font-weight: 800; color: #0F172A; margin-bottom: 10px;">
                    Ekstrakurikuler &amp; Pembinaan Karakter
                </h2>
                <p style="font-size: 14px; color: #64748B;">
                    Menyediakan wadah eksplorasi minat, kepemimpinan, dan spiritual santri melalui bimbingan rutin.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📖</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Tahfidz &amp; Seni Sholawat</h3>
                    <p style="font-size: 12.5px; color: #64748B; margin: 0;">Bimbingan tahsin, hafalan juz 30, dan grup banjari Al-Huda.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 10px;">🏕️</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Gerakan Pramuka</h3>
                    <p style="font-size: 12.5px; color: #64748B; margin: 0;">Kedisiplinan, kemandirian, dan kepemimpinan di Jambore Cabang.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 10px;">💻</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Klub Sains &amp; IT Komputer</h3>
                    <p style="font-size: 12.5px; color: #64748B; margin: 0;">Pembelajaran dasar teknologi, internet sehat, dan olimpiade madrasah.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 10px;">⚽</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Olahraga &amp; Drumband</h3>
                    <p style="font-size: 12.5px; color: #64748B; margin: 0;">Futsal, bola voli, senam sehat, dan atraksi drumband madrasah.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION PPDB -->
    <section style="padding: 50px 0; background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; text-align: center;">
        <div class="container" style="max-width: 680px;">
            <h2 style="font-size: 28px; font-weight: 800; color: #ffffff; margin-bottom: 12px;">
                Penerimaan Peserta Didik Baru (PPDB) 2026/2027
            </h2>
            <p style="font-size: 15px; color: #E2E8F0; margin-bottom: 26px; line-height: 1.6;">
                Daftarkan putra-putri Anda segera. Kuota terbatas untuk memastikan rasio pembelajaran yang kondusif dan pendampingan karakter santri yang optimal.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= url('ppdb') ?>" class="btn" style="background: #ffffff; color: #15803D; font-weight: 800; padding: 12px 26px; border-radius: 10px; font-size: 14px;">
                    Daftar Sekarang Secara Online
                </a>
                <a href="<?= url('kontak') ?>" class="btn" style="background: rgba(255,255,255,.15); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 10px; font-size: 14px; border: 1px solid rgba(255,255,255,.3);">
                    Hubungi Panitia PPDB
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
