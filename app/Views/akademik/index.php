<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$subjects = $data['subjects'] ?? [];
$stats = $data['stats'] ?? [];
$page = $data['page'] ?? null;
$content = trim((string) ($page['content'] ?? ''));

$groupLabel = [
    'agama' => 'Kelompok Keagamaan',
    'umum' => 'Kelompok Umum',
    'muatan_lokal' => 'Muatan Lokal',
];

$ekskul = [
    ['icon' => 'hiking', 'name' => 'Pramuka', 'text' => 'Pembinaan kemandirian, kepemimpinan, dan kedisiplinan.'],
    ['icon' => 'music_note', 'name' => 'Drumband', 'text' => 'Latihan musik baris untuk upacara dan lomba.'],
    ['icon' => 'auto_stories', 'name' => 'Sholawat & Hadrah', 'text' => 'Seni musik Islami dan pembiasaan sholawat.'],
    ['icon' => 'menu_book', 'name' => "Tahfidzul Qur'an", 'text' => 'Setoran hafalan rutin dengan pendampingan guru.'],
    ['icon' => 'sports_soccer', 'name' => 'Futsal', 'text' => 'Olahraga tim untuk kebugaran dan sportivitas.'],
    ['icon' => 'brush', 'name' => 'Seni Kaligrafi', 'text' => 'Keterampilan menulis indah khat Arab.'],
    ['icon' => 'science', 'name' => 'KIR', 'text' => 'Karya ilmiah remaja dan pembinaan riset dasar.'],
    ['icon' => 'flag', 'name' => 'Paskibra', 'text' => 'Latihan baris-berbaris dan tugas pengibaran bendera.'],
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Akademik</span>
            </nav>
            <h1>Program Akademik</h1>
            <p>Kurikulum nasional yang dipadukan dengan penguatan ilmu keagamaan, pembiasaan ibadah, dan pengembangan bakat santri.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Kurikulum</span>
                <h2>Kurikulum &amp; Pola Pembelajaran</h2>
            </div>

            <?php if ($content !== ''): ?>
                <div class="article"><?= $content ?></div>
            <?php else: ?>
                <div class="article">
                    <p>
                        Pembelajaran disusun mengikuti kurikulum nasional dengan tambahan porsi yang kuat
                        pada rumpun ilmu keagamaan. Setiap kelas didampingi wali kelas yang memantau
                        perkembangan akademik maupun adab santri.
                    </p>
                    <p>
                        Penilaian tidak hanya berhenti pada angka rapor: hafalan, kedisiplinan ibadah,
                        dan keaktifan di kegiatan madrasah ikut menjadi bagian dari pembinaan.
                    </p>
                </div>
            <?php endif; ?>

            <div class="grid-3" style="margin-top:var(--sp-8)">
                <article class="card">
                    <p class="stat-label">Mata Pelajaran</p>
                    <p class="stat-value"><?= (int) ($stats['subjects'] ?? 0) ?></p>
                    <p class="stat-note">Aktif diajarkan</p>
                </article>
                <article class="card">
                    <p class="stat-label">Akreditasi</p>
                    <p class="stat-value"><?= e((string) ($stats['grade'] ?? 'A')) ?></p>
                    <p class="stat-note">Peringkat madrasah</p>
                </article>
                <article class="card">
                    <p class="stat-label">Ekstrakurikuler</p>
                    <p class="stat-value"><?= count($ekskul) ?></p>
                    <p class="stat-note">Pilihan pembinaan</p>
                </article>
            </div>
        </div>
    </section>

    <?php if ($subjects !== []): ?>
    <section class="section section-soft">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Mata Pelajaran</span>
                <h2>Mata Pelajaran Wajib &amp; Keagamaan</h2>
                <p>Daftar di bawah diambil langsung dari data madrasah, jadi selalu sama dengan yang diajarkan.</p>
            </div>

            <?php foreach ($subjects as $group => $items): ?>
                <div style="margin-bottom:var(--sp-6)">
                    <h3 style="font-size:var(--fs-card);margin-bottom:var(--sp-3)">
                        <?= e($groupLabel[$group] ?? ucfirst(str_replace('_', ' ', (string) $group))) ?>
                        <span class="pill" style="margin-left:6px"><?= count($items) ?></span>
                    </h3>
                    <div class="grid-4">
                        <?php foreach ($items as $subject): ?>
                            <div class="card" style="padding:var(--sp-3) var(--sp-4)">
                                <div style="display:flex;align-items:center;gap:var(--sp-2)">
                                    <span class="pill pill-green"><?= e($subject['code']) ?></span>
                                    <b style="font-size:var(--fs-sm)"><?= e($subject['name']) ?></b>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Pengembangan Diri</span>
                <h2>Kegiatan Ekstrakurikuler</h2>
                <p>Ruang bagi santri untuk menemukan bakat dan belajar bekerja dalam tim.</p>
            </div>

            <div class="grid-4">
                <?php foreach ($ekskul as $item): ?>
                    <article class="card card-hover feature">
                        <span class="feature-icon"><span class="mi mi-lg"><?= e($item['icon']) ?></span></span>
                        <h3><?= e($item['name']) ?></h3>
                        <p><?= e($item['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="card card-pad-lg" style="display:flex;align-items:center;gap:var(--sp-6);flex-wrap:wrap">
                <div style="flex:1;min-width:260px">
                    <h2 style="font-size:var(--fs-sec);margin-bottom:6px">Jadwal pelajaran mingguan</h2>
                    <p style="font-size:var(--fs-sm);color:var(--gray);margin:0">
                        Jadwal per kelas dikelola dari panel admin dan dapat dilihat santri melalui aplikasi MADRASAHKU.
                    </p>
                </div>
                <a class="btn btn-primary" href="<?= base_url('kontak') ?>">
                    <span class="mi mi-sm">help</span> Tanya Admin
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
