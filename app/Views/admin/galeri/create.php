<?php
ob_start();
$old = $data['old'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <a href="<?= base_url('admin/galeri') ?>">Galeri</a> / <b>Tambah Foto</b>
</div>

<div class="page-head">
    <div>
        <h1>Tambah Foto Galeri</h1>
        <p>Unggah dokumentasi foto kegiatan atau sarana madrasah</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/galeri') ?>">&larr; Kembali</a>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<form method="post" action="<?= base_url('admin/galeri/store') ?>" enctype="multipart/form-data" class="form-grid">
    <?= Csrf::field() ?>

    <div class="card card-pad">
        <div class="field">
            <label for="title">Judul Foto / Kegiatan <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required placeholder="Contoh: Upacara Bendera Hari Santri">
        </div>

        <div class="field">
            <label for="album">Album Kegiatan</label>
            <select id="album" name="album" style="margin-bottom:8px">
                <option value="">-- Pilih Album yang Ada atau Ketik Baru di Bawah --</option>
                <?php foreach (($data['albums'] ?? []) as $alb): ?>
                    <option value="<?= htmlspecialchars($alb, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['album'] ?? '') === $alb ? 'selected' : '' ?>><?= htmlspecialchars($alb, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="new_album" value="<?= htmlspecialchars($old['new_album'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Atau tulis nama album baru di sini...">
        </div>

        <div class="field">
            <label for="caption">Keterangan / Caption Foto</label>
            <textarea id="caption" name="caption" style="min-height:90px" placeholder="Penjelasan singkat suasana atau orang dalam foto..."><?= htmlspecialchars($old['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div>
        <div class="card card-pad">
            <div class="card-head" style="margin:-18px -20px 18px;padding:12px 18px">
                <h3 style="font-size:13px">Berkas &amp; Penataan</h3>
            </div>

            <div class="field">
                <label>Berkas Foto <span class="req">*</span></label>
                <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp" style="margin-bottom:8px">
                <div class="hint">Atau masukkan path gambar relatif:</div>
                <input type="text" name="image_path" value="<?= htmlspecialchars($old['image_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="assets/images/foto.jpg">
            </div>

            <div class="field">
                <label for="sort_order">Nomor Urutan Tampil</label>
                <input type="number" id="sort_order" name="sort_order" value="<?= htmlspecialchars($old['sort_order'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" <?= ($old['is_active'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span>Aktif &amp; Tampilkan di Halaman Publik</span>
                </label>
            </div>

            <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:16px">
                <button type="submit" class="btn btn-primary btn-block">Simpan Foto</button>
            </div>
        </div>
    </div>
</form>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
