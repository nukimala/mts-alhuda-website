<?php
ob_start();
$facilitiesList = $data['facilities'] ?? $data['fasilitas'] ?? [];
$totalFac = count($facilitiesList);
$activeFac = count(array_filter($facilitiesList, fn($f) => (int) ($f['is_active'] ?? 0) === 1));
$inactiveFac = $totalFac - $activeFac;
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Fasilitas Madrasah</b>
</div>

<div class="page-head">
    <div>
        <h1>Fasilitas &amp; Sarana Prasarana</h1>
        <p>Manajemen sarana penunjang kegiatan belajar, ibadah, dan ekstrakurikuler santri</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary btn-sm" href="<?= base_url('admin/fasilitas/create') ?>">
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Fasilitas Baru
        </a>
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice ok">
        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        <div><?= htmlspecialchars($data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="grid grid-4" style="margin-bottom:20px">
    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M3 21h18M6 21V8l6-4 6 4v13M10 21v-5h4v5"/></svg>
        </div>
        <div>
            <small>Total Fasilitas</small>
            <strong><?= $totalFac ?></strong>
            <div class="hint">Tercatat di sistem</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon" style="background:#DCFCE7;color:#15803D">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div>
            <small>Fasilitas Aktif</small>
            <strong><?= $activeFac ?></strong>
            <div class="hint">Tampil di web publik</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div>
            <small>Nonaktif / Perbaikan</small>
            <strong><?= $inactiveFac ?></strong>
            <div class="hint">Disembunyikan dari publik</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div>
            <small>Status Publikasi</small>
            <strong>100%</strong>
            <div class="hint">Terintegrasi otomatis</div>
        </div>
    </div>
</div>

<!-- Toolbar & View Switcher -->
<div class="card" style="margin-bottom:20px">
    <form method="get" action="<?= base_url('admin/fasilitas') ?>" class="toolbar">
        <div class="field-inline" style="min-width:280px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama atau deskripsi fasilitas...">
        </div>

        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        <?php if (!empty($data['q'])): ?>
            <a href="<?= base_url('admin/fasilitas') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <div style="display:flex;background:#F1F5F9;padding:3px;border-radius:8px;gap:2px">
                <button type="button" id="btnViewGrid" class="btn btn-sm" onclick="switchView('grid')" style="background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.08);padding:5px 10px;font-weight:700;color:var(--text)">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Grid Visual
                </button>
                <button type="button" id="btnViewTable" class="btn btn-sm btn-ghost" onclick="switchView('table')" style="padding:5px 10px">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Tabel
                </button>
            </div>
            <span class="pill plain gray"><?= $totalFac ?> Fasilitas</span>
        </div>
    </form>
</div>

<!-- VIEW 1: Grid Kartu Visual Berfoto (Default) -->
<div id="viewGrid" class="grid grid-3" style="margin-bottom:24px">
    <?php if (empty($facilitiesList)): ?>
        <div class="card" style="grid-column: 1 / -1; padding: 48px 20px; text-align: center; color: var(--gray)">
            <svg viewBox="0 0 24 24" style="width:40px;height:40px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 10px;display:block"><path d="M3 21h18M6 21V8l6-4 6 4v13M10 21v-5h4v5"/></svg>
            <b style="font-size:15px;color:var(--text)">Belum ada data fasilitas yang sesuai.</b>
            <p style="margin-top:6px;font-size:13px">Silakan tambah fasilitas sarana &amp; prasarana baru.</p>
            <div style="margin-top:16px">
                <a href="<?= base_url('admin/fasilitas/create') ?>" class="btn btn-primary btn-sm">+ Tambah Fasilitas Sekarang</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($facilitiesList as $fac): ?>
            <div class="card" style="display:flex;flex-direction:column;transition:transform .2s ease, box-shadow .2s ease;">
                <!-- Header Banner / Image -->
                <div style="position:relative;height:170px;background:#E2E8F0;overflow:hidden">
                    <?php if (!empty($fac['image_path'])): ?>
                        <img src="<?= base_url($fac['image_path']) ?>" alt="<?= htmlspecialchars($fac['name'], ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg, #15803D 0%, #0B3D1E 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;padding:20px;text-align:center">
                            <svg viewBox="0 0 24 24" style="width:38px;height:38px;stroke:rgba(255,255,255,.8);fill:none;stroke-width:1.8;margin-bottom:6px"><path d="M3 21h18M6 21V8l6-4 6 4v13M10 21v-5h4v5"/></svg>
                            <span style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.7)">Sarana Madrasah</span>
                        </div>
                    <?php endif; ?>

                    <!-- Floating Badges -->
                    <div style="position:absolute;top:10px;left:10px">
                        <?php if ((int) $fac['is_active'] === 1): ?>
                            <span class="pill green" style="box-shadow:0 2px 4px rgba(0,0,0,.12)">Aktif</span>
                        <?php else: ?>
                            <span class="pill red" style="box-shadow:0 2px 4px rgba(0,0,0,.12)">Nonaktif</span>
                        <?php endif; ?>
                    </div>

                    <div style="position:absolute;top:10px;right:10px">
                        <span class="pill plain" style="background:rgba(15,23,42,.75);color:#fff;backdrop-filter:blur(4px);font-size:10.5px">
                            Urutan: #<?= (int) ($fac['sort_order'] ?? 0) ?>
                        </span>
                    </div>
                </div>

                <!-- Body Content -->
                <div style="padding:16px 18px;flex:1;display:flex;flex-direction:column">
                    <div style="font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">
                        <?= htmlspecialchars($fac['category'] ?? $fac['icon'] ?? 'Gedung / Sarpras', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <h3 style="font-size:15px;font-weight:800;color:var(--text);margin-bottom:8px;line-height:1.35">
                        <?= htmlspecialchars($fac['name'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <p style="font-size:12.5px;color:var(--gray);line-height:1.55;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;flex:1">
                        <?= htmlspecialchars($fac['description'] ?? 'Belum ada deskripsi untuk fasilitas ini.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <!-- Footer Action Buttons -->
                <div style="padding:12px 18px;background:#F8FAFC;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:11.5px;color:var(--gray);font-weight:600">ID: #<?= (int) $fac['id'] ?></span>
                    <div class="row-actions">
                        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/fasilitas/edit/' . $fac['id']) ?>" style="padding:5px 10px;gap:5px">
                            <svg viewBox="0 0 24 24" style="width:13px;height:13px"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                            Edit
                        </a>
                        <form method="post" action="<?= base_url('admin/fasilitas/delete/' . $fac['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus fasilitas ini?');" style="margin:0">
                            <?= Csrf::field() ?>
                            <button type="submit" class="icon-btn" title="Hapus Fasilitas" style="color:#DC2626;width:29px;height:29px">
                                <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- VIEW 2: Tabel Data (Toggleable) -->
<div id="viewTable" class="card" style="display:none;margin-bottom:24px">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Nama &amp; Foto Fasilitas</th>
                    <th>Kategori / Ikon</th>
                    <th>Deskripsi</th>
                    <th style="width:80px">Urutan</th>
                    <th style="width:90px">Status</th>
                    <th style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($facilitiesList)): ?>
                <tr>
                    <td colspan="7" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <b>Belum ada data fasilitas.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($facilitiesList as $fac): ?>
                    <tr>
                        <td class="muted"><?= $i++ ?></td>
                        <td>
                            <div class="cell-flex">
                                <?php if (!empty($fac['image_path'])): ?>
                                    <img class="thumb" src="<?= base_url($fac['image_path']) ?>" alt="" style="width:50px;height:38px">
                                <?php else: ?>
                                    <div class="thumb" style="display:grid;place-items:center;color:#15803D;font-weight:700">SAR</div>
                                <?php endif; ?>
                                <div>
                                    <div class="t-title"><?= htmlspecialchars($fac['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub">ID: #<?= $fac['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="pill gray plain"><?= htmlspecialchars($fac['category'] ?? $fac['icon'] ?? 'Gedung', ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($fac['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($fac['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><b>#<?= (int) $fac['sort_order'] ?></b></td>
                        <td>
                            <?php if ((int) $fac['is_active'] === 1): ?>
                                <span class="pill green">Aktif</span>
                            <?php else: ?>
                                <span class="pill red">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="<?= base_url('admin/fasilitas/edit/' . $fac['id']) ?>" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                                <form method="post" action="<?= base_url('admin/fasilitas/delete/' . $fac['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus fasilitas ini?');" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="icon-btn" title="Hapus" style="color:#DC2626">
                                        <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function switchView(mode) {
    const grid = document.getElementById('viewGrid');
    const table = document.getElementById('viewTable');
    const btnGrid = document.getElementById('btnViewGrid');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'grid') {
        grid.style.display = 'grid';
        table.style.display = 'none';
        btnGrid.style.background = '#fff';
        btnGrid.style.boxShadow = '0 1px 2px rgba(0,0,0,.08)';
        btnGrid.style.color = 'var(--text)';
        btnGrid.style.fontWeight = '700';
        btnTable.style.background = 'transparent';
        btnTable.style.boxShadow = 'none';
        btnTable.style.color = 'var(--gray)';
        btnTable.style.fontWeight = '500';
    } else {
        grid.style.display = 'none';
        table.style.display = 'block';
        btnTable.style.background = '#fff';
        btnTable.style.boxShadow = '0 1px 2px rgba(0,0,0,.08)';
        btnTable.style.color = 'var(--text)';
        btnTable.style.fontWeight = '700';
        btnGrid.style.background = 'transparent';
        btnGrid.style.boxShadow = 'none';
        btnGrid.style.color = 'var(--gray)';
        btnGrid.style.fontWeight = '500';
    }
}
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
