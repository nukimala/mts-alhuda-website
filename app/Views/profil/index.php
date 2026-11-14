<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$guru = $data['guru'] ?? [];
$settings = $data['settings'] ?? [];

function get_initials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_substr($w, 0, 1);
    }
    return strtoupper($initials ?: 'M');
}
?>

<main>
    <!-- HERO PROFIL RESMI -->
    <section class="page-hero-clean">
        <div class="container">
            <div class="hero-label">Identitas &amp; Dokumen Resmi Madrasah</div>
            <h1>Profil Lengkap <span>MTS Al-Huda Gondang</span></h1>
            <p>
                Membina generasi yang berilmu amaliah, cerdas dalam ilmu pengetahuan, dan teguh dalam akhlakul karimah di bawah naungan Yayasan Pendidikan Islam Al-Huda Pandean, Gondang, Nganjuk.
            </p>
        </div>
    </section>

    <!-- SECTION 1: DATA POKOK PENDIDIKAN MADRASAH -->
    <section style="padding: 56px 0 32px; background: #FFFFFF;">
        <div class="container">
            <div class="doc-card">
                <div class="doc-card-header">
                    <div>
                        <div class="section-pretitle">Satuan Pendidikan</div>
                        <h2 class="doc-card-title">Data Pokok Kelembagaan Madrasah</h2>
                    </div>
                    <span class="doc-status-badge">
                        Status: Terakreditasi "A" (Unggul) BAN-PDM
                    </span>
                </div>

                <div class="doc-grid">
                    <div class="doc-item">
                        <span class="doc-label">Nama Lembaga</span>
                        <div class="doc-value">MTs Al-Huda Gondang</div>
                    </div>

                    <div class="doc-item">
                        <span class="doc-label">NPSN / NSM</span>
                        <div class="doc-value">20582910 / 121235180039</div>
                    </div>

                    <div class="doc-item">
                        <span class="doc-label">Kepala Madrasah</span>
                        <div class="doc-value">M. Abdul Haris Jauhari, S.Pd.I</div>
                    </div>

                    <div class="doc-item">
                        <span class="doc-label">Naungan Yayasan</span>
                        <div class="doc-value">Yayasan Pendidikan Islam Al-Huda</div>
                    </div>

                    <div class="doc-item">
                        <span class="doc-label">Alamat Lengkap</span>
                        <div class="doc-value" style="font-size: 13.5px; font-weight: 700;">
                            Jl. Letjend Suprapto No. 16, Desa Pandean, Kec. Gondang, Kab. Nganjuk
                        </div>
                    </div>

                    <div class="doc-item">
                        <span class="doc-label">Izin Operasional &amp; Afiliasi</span>
                        <div class="doc-value" style="font-size: 13.5px; font-weight: 700;">
                            Kementerian Agama Republik Indonesia
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISI, MISI, & TUJUAN STRATEGIS -->
            <div class="visi-misi-v2-grid">
                <!-- VISI -->
                <article class="visi-v2-card">
                    <span class="vm-tag green">Visi Madrasah</span>
                    <h3 class="visi-v2-quote">
                        "Terbentuknya Generasi Cerdas, Berilmu Amaliah, Beramal Ilmiah, dan Berakhlakul Karimah."
                    </h3>
                    <p class="visi-v2-desc">
                        Visi ini mencerminkan tekad madrasah untuk melahirkan lulusan yang seimbang antara kematangan intelektual, kecakapan hidup, dan keluhuran pekerti di tengah kehidupan masyarakat.
                    </p>
                </article>

                <!-- MISI -->
                <article class="misi-v2-card">
                    <span class="vm-tag blue">Misi Madrasah</span>
                    <ol class="misi-v2-list">
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">1</span>
                            <span>Menyelenggarakan pendidikan formal dan kepesantrenan yang berkualitas dan terjangkau bagi masyarakat.</span>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">2</span>
                            <span>Membina kemampuan baca tulis, tahsin tartil, dan tahfidz Al-Qur'an secara berkesinambungan.</span>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">3</span>
                            <span>Membiasakan nilai akhlakul karimah dan ibadah harian seperti sholat dhuha dan sholat dhuhur berjamaah.</span>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">4</span>
                            <span>Mengembangkan potensi akademik dan non-akademik siswa melalui bimbingan intensif dan kegiatan kepanduan.</span>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">5</span>
                            <span>Mewujudkan tata kelola madrasah yang akuntabel, transparan, dan berbasis teknologi informasi.</span>
                        </li>
                    </ol>
                </article>
            </div>
        </div>
    </section>

    <!-- SECTION 2: DATA PENDIDIK & TENAGA KEPENDIDIKAN (GTK) -->
    <section style="padding: 56px 0 72px; background: #F8FAFC; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-head centered">
                <div class="section-pretitle">Tenaga Pendidik</div>
                <h2 class="section-title">Guru &amp; Tenaga Kependidikan</h2>
                <p class="section-subtitle">
                    Dewan asatidz dan tenaga kependidikan berdedikasi membimbing dan mendampingi santri MTs Al-Huda Gondang.
                </p>
            </div>

            <div class="teachers-v2-grid">
                <?php if (empty($guru)): ?>
                    <p style="color: #64748B; text-align: center; grid-column: 1 / -1; padding: 32px; background: #FFFFFF; border-radius: 12px; border: 1px solid var(--border);">
                        Data tenaga pendidik sedang diperbarui oleh bagian tata usaha madrasah.
                    </p>
                <?php else: ?>
                    <?php foreach ($guru as $g): ?>
                        <div class="teacher-v2-card">
                            <?php if (!empty($g['avatar_path'])): ?>
                                <img src="<?= base_url($g['avatar_path']) ?>"
                                     alt="<?= htmlspecialchars($g['name']) ?>"
                                     class="teacher-v2-avatar"
                                     style="object-fit: cover;">
                            <?php else: ?>
                                <div class="teacher-v2-avatar">
                                    <?= htmlspecialchars(get_initials($g['name'])) ?>
                                </div>
                            <?php endif; ?>

                            <div style="min-width: 0; flex: 1;">
                                <h3 class="teacher-v2-name">
                                    <?= htmlspecialchars($g['name']) ?>
                                </h3>
                                <div class="teacher-v2-role">
                                    <?= htmlspecialchars($g['subject_specialty'] ?? 'Guru / Pendidik') ?>
                                </div>
                                <?php if (!empty($g['nip'])): ?>
                                    <div class="teacher-v2-nip">
                                        NIP: <?= htmlspecialchars($g['nip']) ?>
                                    </div>
                                <?php elseif (!empty($g['employment_status'])): ?>
                                    <div class="teacher-v2-nip" style="text-transform: uppercase;">
                                        Status: <?= htmlspecialchars($g['employment_status']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
