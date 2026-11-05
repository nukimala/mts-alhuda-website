<?php
ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Berita</b>
</div>

<div class="page-head">
    <div>
        <h1>Kelola Berita</h1>
        <p><?= (int) $data['stat_published'] ?> berita terpublikasi &middot; <?= (int) $data['stat_draft'] ?> draft &middot; Total <?= (int) $data['total'] ?> berita</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary btn-sm" href="<?= base_url('admin/berita/create') ?>">+ Tambah Berita</a>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="get" action="<?= base_url('admin/berita') ?>" class="toolbar">
        <div class="field-inline" style="min-width:240px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari judul berita...">
        </div>

        <div class="field-inline">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="published" <?= ($data['status'] ?? '') === 'published' ? 'selected' : '' ?>>Terbit</option>
                <option value="draft" <?= ($data['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="archived" <?= ($data['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Arsip</option>
            </select>
        </div>

        <div class="field-inline">
            <select name="sort" onchange="this.form.submit()">
                <option value="latest" <?= ($data['sort'] ?? '') === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                <option value="oldest" <?= ($data['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                <option value="title_asc" <?= ($data['sort'] ?? '') === 'title_asc' ? 'selected' : '' ?>>Judul A–Z</option>
            </select>
        </div>

        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        <?php if (!empty($data['q']) || !empty($data['status']) || ($data['sort'] ?? '') !== 'latest'): ?>
            <a href="<?= base_url('admin/berita') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <span class="pill plain gray"><?= (int) $data['total'] ?> baris</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Judul &amp; Tautan</th>
                    <th>Penulis</th>
                    <th>Status</th>
                    <th>Tanggal Terbit</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['posts'])): ?>
                <tr>
                    <td colspan="6" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <svg viewBox="0 0 24 24" style="width:32px;height:32px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 8px;display:block"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
                        <b>Belum ada data berita yang sesuai filter.</b>
                        <div style="margin-top:6px">
                            <a href="<?= base_url('admin/berita/create') ?>" class="btn btn-primary btn-sm">+ Tambah Berita Sekarang</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php $i = 1 + (($data['page'] - 1) * 10); foreach ($data['posts'] as $p): ?>
                    <tr>
                        <td class="muted"><?= $i++ ?></td>
                        <td>
                            <div class="cell-flex">
                                <?php if (!empty($p['cover_path'])): ?>
                                    <img class="thumb" src="<?= base_url($p['cover_path']) ?>" alt="">
                                <?php else: ?>
                                    <div class="thumb" style="display:grid;place-items:center;color:#15803D;font-weight:700">MTs</div>
                                <?php endif; ?>
                                <div>
                                    <div class="t-title"><?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><a href="<?= base_url('berita/' . $p['slug']) ?>" target="_blank" style="color:#6B7280">/berita/<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?> ↗</a></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($p['author_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($p['status'] === 'published'): ?>
                                <span class="pill green">Terbit</span>
                            <?php elseif ($p['status'] === 'draft'): ?>
                                <span class="pill gray">Draft</span>
                            <?php else: ?>
                                <span class="pill amber">Arsip</span>
                            <?php endif; ?>
                            <?php if ((int) ($p['is_featured'] ?? 0) === 1): ?>
                                <span class="pill blue plain" title="Berita Unggulan">★ Unggulan</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '-' ?></td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-btn" href="<?= base_url('berita/' . $p['slug']) ?>" target="_blank" title="Lihat Halaman Publik">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6"/><circle cx="12" cy="12" r="2.6"/></svg>
                                </a>
                                <a class="icon-btn" href="<?= base_url('admin/berita/edit/' . $p['id']) ?>" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                                <form method="post" action="<?= base_url('admin/berita/delete/' . $p['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berita ini?');" style="display:inline">
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

    <?php if ($data['total_pages'] > 1): ?>
        <div class="pagination">
            <span class="spacer">Halaman <?= $data['page'] ?> dari <?= $data['total_pages'] ?> (Total <?= $data['total'] ?> berita)</span>
            <?php for ($p = 1; $p <= $data['total_pages']; $p++): ?>
                <?php
                $query = $_GET;
                $query['page'] = $p;
                $pageUrl = base_url('admin/berita') . '?' . http_build_query($query);
                ?>
                <a href="<?= $pageUrl ?>" class="<?= $p === $data['page'] ? 'on' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
