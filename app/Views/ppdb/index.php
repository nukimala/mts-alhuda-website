<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$documents = $data['documents'] ?? [];
$agendas = $data['agendas'] ?? [];
$announcements = $data['announcements'] ?? [];
$section = $data['section'] ?? 'ppdb';

$tanggal = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($value);
    return $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
};

$steps = [
    ['title' => 'Ambil formulir', 'text' => 'Formulir pendaftaran diambil di sekretariat madrasah atau diunduh dari halaman ini.'],
    ['title' => 'Lengkapi berkas', 'text' => 'Isi formulir dan siapkan fotokopi rapor, akta kelahiran, kartu keluarga, dan pas foto.'],
    ['title' => 'Serahkan berkas', 'text' => 'Serahkan berkas ke panitia PPDB untuk diperiksa kelengkapannya.'],
    ['title' => 'Tes & wawancara', 'text' => 'Calon santri mengikuti tes baca Al-Qur\'an dasar dan wawancara bersama wali.'],
    ['title' => 'Pengumuman', 'text' => 'Hasil seleksi diumumkan melalui papan pengumuman dan kontak yang didaftarkan.'],
    ['title' => 'Daftar ulang', 'text' => 'Santri yang diterima melakukan daftar ulang sesuai jadwal yang ditetapkan.'],
];

$syarat = [
    'Fotokopi akta kelahiran (2 lembar)',
    'Fotokopi kartu keluarga (2 lembar)',
    'Fotokopi rapor kelas 6 semester 1-2',
    'Fotokopi ijazah SD/MI (menyusul bila belum terbit)',
    'Pas foto ukuran 3x4 (4 lembar)',
    'Fotokopi KIP/PKH bila memiliki',
];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">PPDB</span>
            </nav>
            <h1><?= $section === 'informasi' ? 'Informasi &amp; Agenda' : 'Info PPDB' ?></h1>
            <p>Penerimaan Peserta Didik Baru <?= e(Content::setting('site_full_name', 'MTs Al-Huda Gondang')) ?>.</p>
        </div>
    </section>

    <?php if ($section === 'informasi'): ?>
        <section class="section">
            <div class="container">
                <div class="grid-2" style="align-items:start">
                    <div>
                        <div class="section-head">
                            <span class="eyebrow">Pengumuman</span>
                            <h2>Pengumuman Terbaru</h2>
                        </div>

                        <?php if ($announcements === []): ?>
                            <div class="empty">
                                <p class="empty-title">Belum ada pengumuman</p>
                                <p class="empty-text">Pengumuman akan tampil di sini setelah diterbitkan admin.</p>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="dash-list">
                                    <?php foreach ($announcements as $item): ?>
                                        <div class="dash-row">
                                            <div>
                                                <b><?= e($item['title']) ?></b>
                                                <small><?= e($tanggal($item['published_at'] ?? null)) ?></small>
                                            </div>
                                            <?php if ((int) $item['is_pinned'] === 1): ?>
                                                <span class="pill pill-amber" style="margin-left:auto">Disematkan</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="section-head">
                            <span class="eyebrow">Kalender</span>
                            <h2>Agenda Kegiatan</h2>
                        </div>

                        <?php if ($agendas === []): ?>
                            <div class="empty">
                                <p class="empty-title">Belum ada agenda</p>
                                <p class="empty-text">Agenda akan tampil di sini setelah dijadwalkan admin.</p>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="dash-list">
                                    <?php foreach ($agendas as $item): ?>
                                        <div class="dash-row">
                                            <div>
                                                <b><?= e($item['title']) ?></b>
                                                <small>
                                                    <?= e($tanggal($item['start_at'] ?? null)) ?>
                                                    <?php if (!empty($item['location'])): ?>
                                                        &middot; <?= e($item['location']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="section">
            <div class="container">
                <div class="grid-2" style="align-items:start">
                    <div>
                        <span class="eyebrow">Alur Pendaftaran</span>
                        <h2 style="font-size:var(--fs-sec);margin-bottom:var(--sp-4)">Enam Langkah Menjadi Santri</h2>
                        <p style="font-size:var(--fs-sm);line-height:1.7;color:var(--gray);margin-bottom:var(--sp-5)">
                            Alur di bawah adalah gambaran umum. Jadwal resmi tiap tahun diumumkan
                            melalui halaman pengumuman dan papan informasi madrasah.
                        </p>
                        <a class="btn btn-primary" href="<?= base_url('kontak') ?>">
                            <span class="mi mi-sm">call</span> Tanya Panitia PPDB
                        </a>
                    </div>

                    <div class="card card-pad-lg" style="background:var(--green-bg);border-color:var(--pale-green)">
                        <h2 class="card-title" style="margin-bottom:var(--sp-3)">Syarat Berkas</h2>
                        <ul style="font-size:var(--fs-sm);line-height:1.8;color:var(--dark-green);margin:0;padding-left:var(--sp-5)">
                            <?php foreach ($syarat as $s): ?>
                                <li><?= e($s) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="ppdb-steps" style="margin-top:var(--sp-8)">
                    <?php foreach ($steps as $step): ?>
                        <article class="ppdb-step">
                            <h3><?= e($step['title']) ?></h3>
                            <p><?= e($step['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($documents !== []): ?>
        <section class="section section-soft">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Unduhan</span>
                    <h2>Dokumen PPDB</h2>
                    <p>Berkas resmi yang bisa diunduh dan dicetak sendiri.</p>
                </div>

                <div class="doc-list">
                    <?php foreach ($documents as $doc): ?>
                        <a class="doc-item" href="<?= e(base_url($doc['file_path'])) ?>" target="_blank" rel="noopener">
                            <span class="mi">description</span>
                            <span>
                                <?= e($doc['title']) ?>
                                <?php if (!empty($doc['description'])): ?>
                                    <small class="muted" style="display:block;font-weight:500"><?= e($doc['description']) ?></small>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($doc['file_type'])): ?>
                                <span class="pill" style="margin-left:auto"><?= e(strtoupper((string) $doc['file_type'])) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($agendas !== []): ?>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Jadwal</span>
                    <h2>Agenda Terdekat</h2>
                </div>
                <div class="card">
                    <div class="dash-list">
                        <?php foreach ($agendas as $item): ?>
                            <div class="dash-row">
                                <span class="feature-icon" style="margin:0;width:34px;height:34px">
                                    <span class="mi mi-sm">event</span>
                                </span>
                                <div>
                                    <b><?= e($item['title']) ?></b>
                                    <small>
                                        <?= e($tanggal($item['start_at'] ?? null)) ?>
                                        <?php if (!empty($item['location'])): ?>
                                            &middot; <?= e($item['location']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
