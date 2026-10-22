<?php
/**
 * Dashboard panel admin.
 * Isi halaman ditangkap ke $data['content'], lalu dirender di dalam layout admin.
 */

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Beranda
</div>

<div class="page-head">
    <div>
        <h1>Selamat datang, <?= htmlspecialchars((string) ($data['user']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="desc"><?= date('l, j F Y') ?> &middot; Tahun Ajaran 2026/2027 &middot; Semester Ganjil</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= base_url() ?>">Lihat Website</a>
        <a class="btn btn-primary" href="<?= base_url('admin/ganti-sandi') ?>">Ganti Kata Sandi</a>
    </div>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <div class="card">
        <div class="stat">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11h5M19.5 8.5v5"/></svg>
            </div>
            <div>
                <small>Total Pengguna</small>
                <strong><?= number_format((int) $data['stats']['pengguna'], 0, ',', '.') ?></strong>
                <div class="hint"><?= (int) $data['stats']['guru'] ?> guru &middot; <?= (int) $data['stats']['siswa'] ?> siswa</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
            </div>
            <div>
                <small>Berita Terpublikasi</small>
                <strong><?= number_format((int) $data['stats']['berita'], 0, ',', '.') ?></strong>
                <div class="hint">Tabel <code>posts</code></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div>
                <small>Presensi Guru Hari Ini</small>
                <strong><?= number_format((int) $data['stats']['presensi'], 0, ',', '.') ?></strong>
                <div class="hint">Acuan jam server</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat">
            <div class="stat-icon red">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5.5"/><path d="m8.5 14-1.5 7 5-2.5 5 2.5-1.5-7"/></svg>
            </div>
            <div>
                <small>Bukti Akreditasi</small>
                <strong><?= number_format((int) $data['stats']['bukti'], 0, ',', '.') ?></strong>
                <div class="hint">Tabel <code>evidence</code></div>
            </div>
        </div>
    </div>
</div>

<div class="notice info" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
    <div>
        <b>Koneksi database aktif.</b>
        Angka di atas dibaca langsung dari MariaDB <span class="mono">mts_alhuda</span>.
        Nilai <b>0</b> berarti tabelnya sudah ada tapi masih kosong — bukan error.
    </div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="card-head">
        <h3>Status Migrasi Database</h3>
        <div class="actions">
            <span class="pill green plain"><?= count($data['migrasi']) ?> migrasi diterapkan</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:90px">Versi</th>
                    <th>Nama Migrasi</th>
                    <th style="width:100px">Batch</th>
                    <th style="width:190px">Dijalankan</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['migrasi'])): ?>
                <tr>
                    <td colspan="4" style="padding:28px 16px;text-align:center;color:#6B7280">
                        Belum ada migrasi yang dijalankan.
                        Jalankan <span class="mono">php tools/migrate.php up</span>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['migrasi'] as $m): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars((string) $m['version'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><b><?= htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8') ?></b></td>
                        <td><span class="pill gray plain">batch <?= (int) $m['batch'] ?></span></td>
                        <td class="muted"><?= htmlspecialchars((string) $m['executed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>Pengaturan Publik</h3>
        <div class="actions">
            <span class="pill gray plain"><?= count($data['pengaturan']) ?> baris</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:240px">Kunci</th>
                    <th>Nilai</th>
                    <th style="width:140px">Grup</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['pengaturan'])): ?>
                <tr>
                    <td colspan="3" style="padding:28px 16px;text-align:center;color:#6B7280">
                        Belum ada pengaturan. Jalankan <span class="mono">php tools/migrate.php seed</span>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['pengaturan'] as $s): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars((string) $s['setting_key'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $value = (string) ($s['setting_value'] ?? '');
                            echo $value === ''
                                ? '<span class="muted">(kosong)</span>'
                                : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                            ?>
                        </td>
                        <td><span class="pill blue plain"><?= htmlspecialchars((string) $s['group_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../layouts/admin.php';
