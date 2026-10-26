<?php
/**
 * Dashboard panel admin.
 * Isi halaman ditangkap ke $data['content'], lalu dirender di dalam layout admin.
 */

$stats = $data['stats'] ?? [];
$migrasi = $data['migrasi'] ?? [];
$pengaturan = $data['pengaturan'] ?? [];

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <span class="current">Beranda</span>
</div>

<div class="page-head">
    <div>
        <h1>Selamat datang, <?= e((string) ($data['user']['name'] ?? 'Administrator')) ?></h1>
        <p>
            <?= e(date('j F Y')) ?> &middot; Sistem informasi madrasah
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="<?= base_url('') ?>" target="_blank" rel="noopener">
            <span class="mi mi-sm">open_in_new</span> Lihat Website
        </a>
        <a class="btn btn-primary" href="<?= base_url('admin/ganti-sandi') ?>">
            <span class="mi mi-sm">lock_reset</span> Ganti Sandi
        </a>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:var(--sp-5)">
    <div class="stat">
        <p class="stat-label">Total Pengguna</p>
        <p class="stat-value"><?= number_format((int) ($stats['pengguna'] ?? 0), 0, ',', '.') ?></p>
        <p class="stat-note">
            <?= (int) ($stats['guru'] ?? 0) ?> guru &middot; <?= (int) ($stats['siswa'] ?? 0) ?> siswa
        </p>
    </div>

    <div class="stat">
        <p class="stat-label">Berita Terbit</p>
        <p class="stat-value"><?= number_format((int) ($stats['berita'] ?? 0), 0, ',', '.') ?></p>
        <p class="stat-note">Tabel <code>posts</code></p>
    </div>

    <div class="stat">
        <p class="stat-label">Presensi Guru Hari Ini</p>
        <p class="stat-value"><?= number_format((int) ($stats['presensi'] ?? 0), 0, ',', '.') ?></p>
        <p class="stat-note">Acuan jam server</p>
    </div>

    <div class="stat">
        <p class="stat-label">Bukti Akreditasi</p>
        <p class="stat-value"><?= number_format((int) ($stats['bukti'] ?? 0), 0, ',', '.') ?></p>
        <p class="stat-note">Tabel <code>evidence</code></p>
    </div>
</div>

<div class="notice notice-info">
    <span class="mi mi-sm">info</span>
    <span>
        <strong>Koneksi database aktif.</strong>
        Angka di atas dibaca langsung dari database. Nilai <strong>0</strong> berarti tabelnya
        sudah ada tetapi masih kosong — bukan error.
    </span>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Status Migrasi Database</h2>
                <p class="card-sub"><?= count($migrasi) ?> migrasi diterapkan</p>
            </div>
            <span class="pill pill-green"><?= count($migrasi) ?></span>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:80px">Versi</th>
                        <th>Nama Migrasi</th>
                        <th style="width:90px">Batch</th>
                        <th style="width:170px">Dijalankan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($migrasi === []): ?>
                    <tr>
                        <td colspan="4" class="muted" style="text-align:center;padding:var(--sp-6)">
                            Belum ada migrasi. Jalankan <code>php tools/migrate.php up</code>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($migrasi as $m): ?>
                        <tr>
                            <td class="mono"><?= e((string) $m['version']) ?></td>
                            <td><b><?= e((string) $m['name']) ?></b></td>
                            <td><span class="pill">batch <?= (int) $m['batch'] ?></span></td>
                            <td class="muted"><?= e((string) $m['executed_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Pengaturan Publik</h2>
                <p class="card-sub"><?= count($pengaturan) ?> baris tampil</p>
            </div>
            <span class="pill"><?= count($pengaturan) ?></span>
        </div>

        <div class="dash-list">
            <?php if ($pengaturan === []): ?>
                <p class="muted" style="font-size:var(--fs-sm)">
                    Belum ada pengaturan. Jalankan <code>php tools/migrate.php seed</code>.
                </p>
            <?php else: ?>
                <?php foreach ($pengaturan as $s): ?>
                    <div class="dash-row">
                        <div style="min-width:0">
                            <b class="mono" style="font-size:var(--fs-label)"><?= e((string) $s['setting_key']) ?></b>
                            <small style="word-break:break-word">
                                <?= $s['setting_value'] === '' || $s['setting_value'] === null
                                    ? '<span class="muted">(kosong)</span>'
                                    : e((string) $s['setting_value']) ?>
                            </small>
                        </div>
                        <span class="pill pill-blue" style="margin-left:auto"><?= e((string) $s['group_name']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../layouts/admin.php';
