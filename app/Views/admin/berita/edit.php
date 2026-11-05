<?php
ob_start();
$post = $data['post'] ?? [];
$old = $data['old'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <a href="<?= base_url('admin/berita') ?>">Berita</a> / <b>Edit Berita</b>
</div>

<div class="page-head">
    <div>
        <h1>Edit Berita: <?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Diperbarui: <?= htmlspecialchars($post['updated_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/berita') ?>">&larr; Kembali</a>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<form method="post" action="<?= base_url('admin/berita/update/' . $post['id']) ?>" enctype="multipart/form-data" class="form-grid">
    <?= Csrf::field() ?>

    <div class="card card-pad">
        <div class="field">
            <label for="title">Judul Berita <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($old['title'] ?? $post['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="field">
            <label for="slug">Slug URL</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($old['slug'] ?? $post['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="hint">Alamat web: /berita/[slug]</div>
        </div>

        <div class="field">
            <label for="excerpt">Ringkasan Singkat (Excerpt)</label>
            <textarea id="excerpt" name="excerpt" style="min-height:70px"><?= htmlspecialchars($old['excerpt'] ?? $post['excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="field">
            <label for="content">Isi Lengkap Berita <span class="req">*</span></label>
            <textarea id="content" name="content" style="min-height:280px" required><?= htmlspecialchars($old['content'] ?? $post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div>
        <div class="card card-pad">
            <div class="card-head" style="margin:-18px -20px 18px;padding:12px 18px">
                <h3 style="font-size:13px">Publikasi</h3>
            </div>

            <div class="field">
                <label for="status">Status Berita</label>
                <?php $currentStatus = $old['status'] ?? $post['status'] ?? 'draft'; ?>
                <select id="status" name="status">
                    <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Terbit</option>
                    <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $currentStatus === 'archived' ? 'selected' : '' ?>>Arsip</option>
                </select>
            </div>

            <div class="field">
                <?php $isFeat = isset($old['is_featured']) ? $old['is_featured'] : ($post['is_featured'] ?? 0); ?>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= (int) $isFeat === 1 ? 'checked' : '' ?>>
                    <span>Jadikan Berita Unggulan (Headline)</span>
                </label>
            </div>

            <div class="field" style="margin-top:18px">
                <label>Foto Sampul (Cover)</label>
                <?php if (!empty($post['cover_path'])): ?>
                    <div style="margin-bottom:10px">
                        <img src="<?= base_url($post['cover_path']) ?>" style="max-height:120px;border-radius:6px;object-fit:cover" alt="Cover saat ini">
                    </div>
                <?php endif; ?>
                <input type="file" name="cover_file" accept="image/png,image/jpeg,image/webp" style="margin-bottom:8px">
                <div class="hint">Atau path/URL:</div>
                <input type="text" name="cover_path" value="<?= htmlspecialchars($old['cover_path'] ?? $post['cover_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:16px;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</form>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
