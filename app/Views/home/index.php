<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$latestPosts = $data['latest_posts'] ?? [];
$facilities = $data['facilities'] ?? [];
?>

<main>
    <!-- HERO SECTION -->
    <section class="hero-v2">
        <div class="container hero-v2-grid">
            <div class="hero-v2-copy">
                <div class="hero-tag-badge">
                    <span class="hero-tag-dot"></span>
                    <span>Terakreditasi "A" (Unggul) BAN-PDM</span>
                </div>
                <h1 class="hero-v2-title">
                    Membentuk Generasi Cerdas, Berilmu Amaliah, dan <span>Berakhlakul Karimah.</span>
                </h1>
                <p class="hero-v2-desc">
                    MTs Al-Huda Gondang menyelenggarakan pendidikan tingkat menengah pertama terpadu yang memadukan kurikulum nasional dan pembiasaan nilai kepesantrenan di Desa Pandean, Kecamatan Gondang, Kabupaten Nganjuk.
                </p>
                <div class="hero-v2-actions">
                    <a href="<?= url('ppdb') ?>" class="btn btn-primary">
                        Informasi &amp; Pendaftaran PPDB
                    </a>
                    <a href="<?= url('profil') ?>" class="btn btn-secondary">
                        Profil &amp; Visi Madrasah
                    </a>
                </div>
            </div>

            <div class="hero-v2-visual">
                <div class="hero-visual-frame">
                    <img src="<?= base_url('assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg') ?>"
                         alt="Kegiatan Kepramukaan Santri MTs Al-Huda Gondang"
                         width="600"
                         height="380"
                         fetchpriority="high">
                    <div class="hero-visual-caption">
                        <span>🏛️</span>
                        <span>Dokumentasi Kegiatan &amp; Kepanduan Santri MTS Al-Huda Gondang</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SAMBUTAN KEPALA MADRASAH -->
    <section class="speech-section">
        <div class="container speech-grid">
            <div class="speech-photo-wrap">
                <div class="speech-photo-box">
                    <img src="<?= base_url('assets/images/Pertemuan Wali Santri.jpg') ?>"
                         alt="Kepala Madrasah MTs Al-Huda Gondang"
                         width="220"
                         height="270"
                         loading="lazy">
                </div>
                <div class="speech-meta">
                    <h3 class="speech-name">M. Abdul Haris Jauhari, S.Pd.I</h3>
                    <p class="speech-role">Kepala Madrasah</p>
                </div>
            </div>

            <div class="speech-content">
                <span class="speech-quote-tag">Sambutan Pimpinan</span>
                <h2 class="speech-title">
                    Mewujudkan Pendidikan Berkualitas, Terpadu, dan Berakhlak Mulia
                </h2>
                <div class="speech-paragraphs">
                    <p>
                        <em>Assalamu’alaikum Warahmatullahi Wabarakatuh.</em>
                    </p>
                    <p>
                        Selamat datang di portal informasi resmi MTs Al-Huda Gondang. Di tengah perkembangan zaman dan teknologi, madrasah kami berkomitmen menghadirkan iklim belajar yang aman, beradab, serta membekali putra-putri dengan dasar keilmuan yang seimbang antara agama dan pengetahuan umum.
                    </p>
                    <p>
                        Dengan bimbingan para guru yang berdedikasi serta dukungan penuh wali santri dan masyarakat, kami mendidik generasi penerus agar memiliki budi pekerti luhur, kemandirian, dan kesiapan melanjutkan ke jenjang yang lebih tinggi.
                    </p>
                </div>
                <div>
                    <a href="<?= url('profil') ?>" class="btn btn-secondary">
                        Profil Lengkap Madrasah
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- TIGA PILAR PENDIDIKAN MADRASAH -->
    <section class="pillars-section">
        <div class="container">
            <div class="section-head centered">
                <div class="section-pretitle">Pilar Penyelenggaraan</div>
                <h2 class="section-title">Komitmen Mutu &amp; Karakter Santri</h2>
                <p class="section-subtitle">
                    Tiga landasan pembinaan yang menjadi ciri khas dan pedoman pembelajaran di MTs Al-Huda Gondang.
                </p>
            </div>

            <div class="pillars-grid">
                <article class="pillar-item">
                    <span class="pillar-badge">01. Akademik &amp; Kemenag</span>
                    <h3 class="pillar-heading">Kurikulum Nasional &amp; Agama</h3>
                    <p class="pillar-desc">
                        Pembelajaran Kurikulum Merdeka dipadukan dengan penguatan mata pelajaran rumpun PAI (Akidah Akhlak, Fikih, Quran Hadits, dan SKI) serta Bahasa Arab.
                    </p>
                </article>

                <article class="pillar-item">
                    <span class="pillar-badge">02. Spiritual &amp; Karakter</span>
                    <h3 class="pillar-heading">Pembiasaan Ibadah Harian</h3>
                    <p class="pillar-desc">
                        Penanaman disiplin melalui sholat dhuha terpimpin, sholat dhuhur berjamaah di musholla madrasah, bimbingan tahsin tartil Al-Qur'an, dan pembacaan sholawat.
                    </p>
                </article>

                <article class="pillar-item">
                    <span class="pillar-badge">03. Minat &amp; Keterampilan</span>
                    <h3 class="pillar-heading">Eksplorasi Bakat &amp; IT</h3>
                    <p class="pillar-desc">
                        Pengembangan potensi non-akademik meliputi Gerakan Pramuka, grup banjari Al-Huda, drumband, olahraga, serta pengenalan dasar komputer di laboratorium.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- WARTA TERKINI (LIVE DARI DATABASE) -->
    <section style="padding: 64px 0; background: #FFFFFF; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div class="section-pretitle">Warta &amp; Informasi</div>
                    <h2 class="section-title" style="margin: 0;">Kabar &amp; Prestasi Madrasah</h2>
                </div>
                <a href="<?= url('berita') ?>" class="btn btn-secondary">
                    Semua Berita
                </a>
            </div>

            <div class="news-v2-grid">
                <?php if (empty($latestPosts)): ?>
                    <p style="color: #64748B; grid-column: 1 / -1; padding: 24px; background: #F8FAFC; border-radius: 12px; text-align: center;">
                        Belum ada warta berita yang diterbitkan saat ini.
                    </p>
                <?php else: ?>
                    <?php foreach ($latestPosts as $bp): ?>
                        <article class="news-v2-card">
                            <div class="news-v2-thumb">
                                <?php if (!empty($bp['cover_path'])): ?>
                                    <img src="<?= base_url($bp['cover_path']) ?>"
                                         alt="<?= htmlspecialchars($bp['title']) ?>"
                                         width="360"
                                         height="190"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="news-v2-thumb-fallback">
                                        MTs Al-Huda Gondang
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="news-v2-body">
                                <div class="news-v2-date">
                                    <?= !empty($bp['published_at']) ? date('d M Y', strtotime($bp['published_at'])) : date('d M Y') ?>
                                </div>
                                <h3 class="news-v2-title">
                                    <a href="<?= url('berita/' . $bp['slug']) ?>">
                                        <?= htmlspecialchars($bp['title']) ?>
                                    </a>
                                </h3>
                                <p class="news-v2-excerpt">
                                    <?= htmlspecialchars($bp['excerpt'] ?? mb_substr(strip_tags($bp['content']), 0, 110) . '...') ?>
                                </p>
                                <a href="<?= url('berita/' . $bp['slug']) ?>" class="news-v2-cta">
                                    Baca selengkapnya &rarr;
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FASILITAS & SARANA (LIVE DARI DATABASE) -->
    <section style="padding: 64px 0; background: #F8FAFC; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div class="section-pretitle">Sarana &amp; Prasarana</div>
                    <h2 class="section-title" style="margin: 0;">Fasilitas Pendukung Belajar</h2>
                </div>
                <a href="<?= url('fasilitas') ?>" class="btn btn-secondary">
                    Semua Fasilitas
                </a>
            </div>

            <div class="fac-v2-grid">
                <?php if (empty($facilities)): ?>
                    <p style="color: #64748B; grid-column: 1 / -1; padding: 24px; background: #FFFFFF; border-radius: 12px; text-align: center;">
                        Informasi fasilitas sedang diperbarui.
                    </p>
                <?php else: ?>
                    <?php foreach ($facilities as $fac): ?>
                        <div class="fac-v2-card">
                            <div class="fac-v2-thumb">
                                <?php if (!empty($fac['image_path'])): ?>
                                    <img src="<?= base_url($fac['image_path']) ?>"
                                         alt="<?= htmlspecialchars($fac['name']) ?>"
                                         width="280"
                                         height="165"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="news-v2-thumb-fallback">
                                        Sarana Madrasah
                                    </div>
                                <?php endif; ?>
                                <span class="fac-v2-tag">
                                    <?= htmlspecialchars($fac['icon'] ?? 'Fasilitas') ?>
                                </span>
                            </div>
                            <div class="fac-v2-body">
                                <h3 class="fac-v2-name">
                                    <?= htmlspecialchars($fac['name']) ?>
                                </h3>
                                <p class="fac-v2-desc">
                                    <?= htmlspecialchars($fac['description'] ?? 'Sarana penunjang kegiatan santri.') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION RESMI PPDB -->
    <section class="cta-official">
        <div class="container cta-official-inner">
            <h2 class="cta-official-h2">
                Penerimaan Peserta Didik Baru (PPDB) 2026/2027
            </h2>
            <p class="cta-official-p">
                Bergabunglah bersama keluarga besar MTs Al-Huda Gondang. Pendaftaran dibuka untuk lulusan SD/MI dengan kuota terbatas guna menjamin efektivitas pendampingan santri.
            </p>
            <div class="cta-official-btns">
                <a href="<?= url('ppdb') ?>" class="btn btn-primary" style="background: #FFFFFF !important; color: #15803D !important; border-color: #FFFFFF !important;">
                    Daftar Secara Online
                </a>
                <a href="<?= url('kontak') ?>" class="btn btn-secondary" style="background: transparent !important; color: #FFFFFF !important; border-color: rgba(255,255,255,0.4) !important;">
                    Hubungi Panitia PPDB
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
