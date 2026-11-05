<?php
ob_start();
$old = $data['old'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <a href="<?= base_url('admin/berita') ?>">Berita</a> / <b>Tambah Baru</b>
</div>

<div class="page-head">
    <div>
        <h1>Tambah Berita Baru</h1>
        <p>Tulis artikel atau berita kegiatan madrasah</p>
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

<form method="post" action="<?= base_url('admin/berita/store') ?>" enctype="multipart/form-data" class="form-grid">
    <?= Csrf::field() ?>

    <div class="card card-pad">
        <div class="field">
            <label for="title">Judul Berita <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required placeholder="Contoh: Peringatan Hari Santri Nasional 2026">
        </div>

        <div class="field">
            <label for="slug">Slug URL (opsional, otomatis dari judul)</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="peringatan-hari-santri-2026">
            <div class="hint">Alamat web akan menjadi: /berita/[slug]</div>
        </div>

        <div class="field">
            <label for="excerpt">Ringkasan Singkat (Excerpt)</label>
            <textarea id="excerpt" name="excerpt" style="min-height:70px" placeholder="Ringkasan 1-2 kalimat untuk kartu berita di beranda..."><?= htmlspecialchars($old['excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="field">
            <label for="content">Isi Lengkap Berita <span class="req">*</span></label>
            <textarea id="content" name="content" style="min-height:280px" required placeholder="Tuliskan isi berita di sini..."><?= htmlspecialchars($old['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div>
        <div class="card card-pad">
            <div class="card-head" style="margin:-18px -20px 18px;padding:12px 18px">
                <h3 style="font-size:13px">Publikasi</h3>
            </div>

            <div class="field">
                <label for="status">Status Berita</label>
                <select id="status" name="status">
                    <option value="published" <?= ($old['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Langsung Terbitkan</option>
                    <option value="draft" <?= ($old['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Simpan sebagai Draf</option>
                    <option value="archived" <?= ($old['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Arsip</option>
                </select>
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
                    <span>Jadikan Berita Unggulan (Headline)</span>
                </label>
            </div>

            <div class="field" style="margin-top:18px">
                <label>Foto Sampul (Cover)</label>
                <input type="file" name="cover_file" accept="image/png,image/jpeg,image/webp" style="margin-bottom:8px">
                <div class="hint">Atau masukkan URL/path relatif gambar:</div>
                <input type="text" name="cover_path" value="<?= htmlspecialchars($old['cover_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="assets/images/foto.jpg">
            </div>

            <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:16px;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary btn-block">Simpan Berita</button>
            </div>
        </div>
    </div>
</form>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
