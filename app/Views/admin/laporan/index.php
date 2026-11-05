<?php
/**
 * View: admin/laporan/index.php
 * Laporan & Audit Trail — panel admin MTs Al-Huda
 */

$s = $data['summary'];
ob_start();
?>

<style>
/* ── Print styles ─────────────────────────────────────── */
@media print {
  .topbar, .side-nav, .side-foot, .no-print { display:none!important; }
  .main { margin:0!important; }
  .content { padding:0!important; }
  .page-break { page-break-before: always; }
  .card { box-shadow: none!important; border:1px solid #ccc!important; }
}

/* ── Halaman-spesifik ─────────────────────────────────── */
.lp-section-title {
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--gray);
    margin: 0 0 14px;
}
.lp-badge {
    display: inline-block;
    padding: 2px 10px;
    background: var(--border);
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 700;
    color: var(--text);
}
.lp-filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.lp-filter select,
.lp-filter input[type="text"],
.lp-filter input[type="date"] {
    padding: 7px 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    background: #fff;
    min-width: 160px;
}
.lp-pag {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 14px;
    font-size: 12.5px;
    color: var(--gray);
}
</style>

<!-- ── Breadcrumb ────────────────────────────────────── -->
<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Laporan &amp; Audit Trail</b>
</div>

<!-- ── Toolbar ────────────────────────────────────────── -->
<div class="page-head">
    <div>
        <h1>Laporan &amp; Audit Trail</h1>
        <p>Ringkasan sistem dan rekam jejak aktivitas operator.</p>
    </div>
    <div class="actions no-print">
        <button class="btn btn-outline btn-sm" onclick="window.print()">
            <svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/></svg>
            Cetak Laporan
        </button>
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice ok" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
        <div><?= htmlspecialchars((string) $data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>
<?php if (!empty($data['error'])): ?>
    <div class="notice err" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        <div><?= htmlspecialchars((string) $data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- ── Kartu Ringkasan Eksekutif ─────────────────────── -->
<p class="lp-section-title">Ringkasan Eksekutif</p>
<div class="grid grid-4" style="margin-bottom:24px">
    <!-- Total Pengguna -->
    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M20 20a4 4 0 0 0-7 0"/></svg>
        </div>
        <div>
            <small>Total Pengguna</small>
            <strong><?= $s['totalSiswa'] + $s['totalGuru'] + $s['totalAdmin'] ?></strong>
            <div class="hint"><?= $s['totalSiswa'] ?> siswa · <?= $s['totalGuru'] ?> guru</div>
        </div>
    </div>

    <!-- Berita Tayang -->
    <div class="card stat">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
        </div>
        <div>
            <small>Berita Tayang</small>
            <strong><?= $s['beritaTayang'] ?></strong>
            <div class="hint"><?= $s['beritaDraft'] ?> draft menunggu</div>
        </div>
    </div>

    <!-- Presensi Hari Ini -->
    <div class="card stat">
        <div class="stat-icon <?= $s['presensiHariIni'] > 0 && $s['presensiHadir'] === $s['presensiHariIni'] ? '' : 'amber' ?>">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <div>
            <small>Hadir Hari Ini</small>
            <strong><?= $s['presensiHadir'] ?></strong>
            <div class="hint">dari <?= $s['presensiHariIni'] ?> tercatat</div>
        </div>
    </div>

    <!-- Akademik & Mobile -->
    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        <div>
            <small>Jadwal Aktif</small>
            <strong><?= $s['jadwalTotal'] ?></strong>
            <div class="hint"><?= $s['totalKelas'] ?> rombel · <?= $s['totalMapel'] ?> mapel</div>
        </div>
    </div>
</div>

<!-- ── Tabel Statistik Per-Modul ─────────────────────── -->
<p class="lp-section-title">Statistik Per-Modul</p>
<div class="grid-2" style="gap:20px;margin-bottom:28px">

    <!-- Konten Website -->
    <div class="card">
        <div class="card-head"><h3 class="card-title">Konten Website</h3></div>
        <table class="data">
            <thead>
                <tr><th>Modul</th><th style="text-align:right">Jumlah</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Berita Tayang</td>
                    <td style="text-align:right"><span class="pill green"><?= $s['beritaTayang'] ?></span></td>
                </tr>
                <tr>
                    <td>Berita Draft</td>
                    <td style="text-align:right"><span class="pill amber"><?= $s['beritaDraft'] ?></span></td>
                </tr>
                <tr>
                    <td>Galeri</td>
                    <td style="text-align:right"><?= $s['totalGaleri'] ?></td>
                </tr>
                <tr>
                    <td>Fasilitas</td>
                    <td style="text-align:right"><?= $s['totalFasilitas'] ?></td>
                </tr>
                <tr>
                    <td>Dokumen PPDB Aktif</td>
                    <td style="text-align:right"><?= $s['ppdbAktif'] ?> / <?= $s['ppdbTotal'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Akademik & Kehadiran -->
    <div class="card">
        <div class="card-head"><h3 class="card-title">Akademik &amp; Kehadiran</h3></div>
        <table class="data">
            <thead>
                <tr><th>Modul</th><th style="text-align:right">Jumlah</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Slot Jadwal Pelajaran</td>
                    <td style="text-align:right"><?= $s['jadwalTotal'] ?></td>
                </tr>
                <tr>
                    <td>Presensi Hari Ini (Total)</td>
                    <td style="text-align:right"><?= $s['presensiHariIni'] ?></td>
                </tr>
                <tr>
                    <td>Guru Hadir Hari Ini</td>
                    <td style="text-align:right"><span class="pill green"><?= $s['presensiHadir'] ?></span></td>
                </tr>
                <tr>
                    <td>Tidak Hadir Hari Ini</td>
                    <td style="text-align:right">
                        <?php $absen = $s['presensiHariIni'] - $s['presensiHadir']; ?>
                        <span class="pill <?= $absen > 0 ? 'red' : 'gray' ?>"><?= $absen ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Akademik & Mobile App -->
    <div class="card">
        <div class="card-head"><h3 class="card-title">Akademik &amp; Madrasahku App</h3></div>
        <table class="data">
            <thead>
                <tr><th>Item</th><th style="text-align:right">Nilai</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Jadwal Terjadwal</td>
                    <td style="text-align:right"><?= $s['jadwalTotal'] ?></td>
                </tr>
                <tr>
                    <td>Total Rombongan Belajar</td>
                    <td style="text-align:right"><?= $s['totalKelas'] ?></td>
                </tr>
                <tr>
                    <td>Mata Pelajaran Aktif</td>
                    <td style="text-align:right"><?= $s['totalMapel'] ?></td>
                </tr>
                <tr>
                    <td>Presensi Hari Ini (Total)</td>
                    <td style="text-align:right"><?= $s['presensiHariIni'] ?></td>
                </tr>
                <tr>
                    <td>Presensi Hadir</td>
                    <td style="text-align:right"><span class="pill green"><?= $s['presensiHadir'] ?></span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Sistem & Log -->
    <div class="card">
        <div class="card-head"><h3 class="card-title">Sistem &amp; Log</h3></div>
        <table class="data">
            <thead>
                <tr><th>Item</th><th style="text-align:right">Jumlah</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Log Aktivitas</td>
                    <td style="text-align:right"><?= $s['logTotal'] ?></td>
                </tr>
                <tr>
                    <td>Log Bulan Ini</td>
                    <td style="text-align:right"><span class="pill blue"><?= $s['logBulanIni'] ?></span></td>
                </tr>
                <tr>
                    <td>Admin &amp; Operator</td>
                    <td style="text-align:right"><?= $s['totalAdmin'] ?></td>
                </tr>
                <tr>
                    <td>Total Pengguna Sistem</td>
                    <td style="text-align:right"><?= $s['totalSiswa'] + $s['totalGuru'] + $s['totalAdmin'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Audit Trail ────────────────────────────────────── -->
<div class="card page-break">
    <div class="card-head" style="justify-content:space-between">
        <h3 class="card-title">Audit Trail</h3>
        <span class="lp-badge"><?= number_format($data['total']) ?> entri</span>
    </div>

    <!-- Filter -->
    <form method="get" class="lp-filter no-print" style="padding:0 20px 4px">
        <select name="aksi">
            <option value="">Semua Aksi</option>
            <?php foreach ($data['aksi_list'] as $aksi): ?>
                <option value="<?= htmlspecialchars($aksi, ENT_QUOTES) ?>"
                    <?= $data['filter_aksi'] === $aksi ? 'selected' : '' ?>>
                    <?= htmlspecialchars($aksi, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="user" placeholder="Nama / email..."
               value="<?= htmlspecialchars($data['filter_user'], ENT_QUOTES) ?>">

        <input type="date" name="tgl"
               value="<?= htmlspecialchars($data['filter_tgl'], ENT_QUOTES) ?>">

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="<?= base_url('admin/laporan') ?>" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <?php if (empty($data['logs'])): ?>
        <div style="padding:48px 20px;text-align:center;color:var(--gray)">
            <svg viewBox="0 0 24 24" style="width:36px;height:36px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 10px;display:block"><path d="M9 12h6M9 16h6M9 8h3"/><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/></svg>
            <b>Belum ada log aktivitas<?= ($data['filter_aksi'] || $data['filter_user'] || $data['filter_tgl']) ? ' yang cocok dengan filter.' : '.' ?></b>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Keterangan</th>
                        <th>IP</th>
                        <th class="no-print">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['logs'] as $log): ?>
                        <tr>
                            <td class="mono" style="white-space:nowrap;font-size:11.5px">
                                <?= htmlspecialchars(
                                    date('d M Y H:i', strtotime((string) ($log['created_at'] ?? ''))),
                                    ENT_QUOTES, 'UTF-8'
                                ) ?>
                            </td>
                            <td>
                                <?php if (!empty($log['user_name'])): ?>
                                    <div style="font-weight:600;font-size:12.8px">
                                        <?= htmlspecialchars((string) $log['user_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="font-size:11px;color:var(--gray)">
                                        <?= htmlspecialchars((string) ($log['user_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php else: ?>
                                    <span class="pill gray">Sistem</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="pill blue plain">
                                    <?= htmlspecialchars((string) ($log['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="max-width:300px;word-break:break-word;font-size:12px">
                                <?= htmlspecialchars((string) ($log['description'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="mono" style="font-size:11px;color:var(--gray)">
                                <?= htmlspecialchars((string) ($log['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="no-print" style="white-space:nowrap">
                                <div class="row-actions">
                                    <form method="post"
                                          action="<?= base_url('admin/laporan/delete/' . (int) $log['id']) ?>"
                                          style="display:inline"
                                          onsubmit="return confirm('Hapus log ini secara permanen?')">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="icon-btn" title="Hapus" style="color:#DC2626">
                                            <svg viewBox="0 0 24 24">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v6M14 11v6"/>
                                                <path d="M9 6V4h6v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($data['total_pages'] > 1): ?>
            <?php
            $buildUrl = function (int $p) use ($data): string {
                $params = array_filter([
                    'page' => $p,
                    'aksi' => $data['filter_aksi'],
                    'user' => $data['filter_user'],
                    'tgl'  => $data['filter_tgl'],
                ]);
                return base_url('admin/laporan') . '?' . http_build_query($params);
            };
            ?>
            <div class="pagination no-print" style="padding:14px 20px">
                <?php if ($data['page'] > 1): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= $buildUrl($data['page'] - 1) ?>">← Sebelumnya</a>
                <?php endif; ?>

                <span class="spacer">Halaman <?= $data['page'] ?> dari <?= $data['total_pages'] ?></span>

                <?php if ($data['page'] < $data['total_pages']): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= $buildUrl($data['page'] + 1) ?>">Berikutnya →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
