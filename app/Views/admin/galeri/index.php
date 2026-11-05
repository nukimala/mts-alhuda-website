<?php
ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Galeri</b>
</div>

<div class="page-head">
    <div>
        <h1>Galeri Foto &amp; Dokumentasi</h1>
        <p>Koleksi dokumentasi visual kegiatan, sarana, dan prestasi madrasah</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary btn-sm" href="<?= base_url('admin/galeri/create') ?>">+ Tambah Foto</a>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="get" action="<?= base_url('admin/galeri') ?>" class="toolbar">
        <div class="field-inline" style="min-width:240px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari judul atau caption...">
        </div>

        <?php if (!empty($data['albums'])): ?>
            <div class="field-inline">
                <select name="album" onchange="this.form.submit()">
                    <option value="">Semua Album</option>
                    <?php foreach ($data['albums'] as $alb): ?>
                        <option value="<?= htmlspecialchars($alb, ENT_QUOTES, 'UTF-8') ?>" <?= ($data['album'] ?? '') === $alb ? 'selected' : '' ?>><?= htmlspecialchars($alb, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if (!empty($data['q']) || !empty($data['album'])): ?>
            <a href="<?= base_url('admin/galeri') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <span class="pill plain gray"><?= count($data['items']) ?> foto</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Foto &amp; Judul</th>
                    <th>Album</th>
                    <th>Caption</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['items'])): ?>
                <tr>
                    <td colspan="7" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <svg viewBox="0 0 24 24" style="width:32px;height:32px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 8px;display:block"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg>
                        <b>Belum ada foto galeri.</b>
                        <div style="margin-top:6px">
                            <a href="<?= base_url('admin/galeri/create') ?>" class="btn btn-primary btn-sm">+ Unggah Foto Sekarang</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($data['items'] as $item): ?>
                    <tr>
                        <td class="muted"><?= $i++ ?></td>
                        <td>
                            <div class="cell-flex">
                                <img class="thumb" src="<?= base_url($item['image_path']) ?>" alt="" style="width:60px;height:45px">
                                <div>
                                    <div class="t-title"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><?= htmlspecialchars($item['image_path'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($item['album'])): ?>
                                <span class="pill blue plain"><?= htmlspecialchars($item['album'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="muted">Umum</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($item['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($item['caption'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= (int) $item['sort_order'] ?></td>
                        <td>
                            <?php if ((int) $item['is_active'] === 1): ?>
                                <span class="pill green">Aktif</span>
                            <?php else: ?>
                                <span class="pill red">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="<?= base_url('admin/galeri/edit/' . $item['id']) ?>" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                                <form method="post" action="<?= base_url('admin/galeri/delete/' . $item['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto ini?');" style="display:inline">
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

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
