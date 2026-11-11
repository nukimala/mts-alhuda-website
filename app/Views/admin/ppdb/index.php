<?php
ob_start();
$settings = $data['settings'] ?? [];
$documents = $data['documents'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Info &amp; Dokumen PPDB</b>
</div>

<div class="page-head">
    <div>
        <h1>PPDB: Informasi &amp; Dokumen</h1>
        <p>Halaman publik <b>/ppdb</b> &middot; <?= count($documents) ?> dokumen terdaftar &middot; <?= (int) $data['total_downloads'] ?> total unduhan</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('ppdb') ?>" target="_blank">Pratinjau Halaman Publik ↗</a>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalTambahDoc').style.display='grid'">+ Tambah Dokumen</button>
    </div>
</div>

<div class="notice warn" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
    <div>
        <b>PPDB tanpa formulir online.</b> Halaman publik hanya menampilkan informasi dan berkas unduhan resmi. Pendaftaran calon santri dilakukan secara langsung di madrasah.
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="split">
    <div>
        <form method="post" action="<?= base_url('admin/ppdb/update-info') ?>" class="card">
            <?= Csrf::field() ?>
            <div class="card-head">
                <h3>Informasi PPDB (Tampil di Website)</h3>
                <div class="actions">
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Informasi</button>
                </div>
            </div>
            <div class="card-pad">
                <div class="field">
                    <label for="ppdb_title">Judul Halaman PPDB</label>
                    <input type="text" id="ppdb_title" name="ppdb_title" value="<?= htmlspecialchars($settings['ppdb_title'] ?? 'PPDB MTs Al-Huda Gondang 2027/2028', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="ppdb_schedule">Jadwal &amp; Gelombang Pendaftaran</label>
                    <textarea id="ppdb_schedule" name="ppdb_schedule" style="min-height:86px"><?= htmlspecialchars($settings['ppdb_schedule'] ?? "Gelombang 1: 1 – 30 November 2026\nGelombang 2: 1 – 31 Desember 2026\nDaftar ulang: 2 – 10 Januari 2027", ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="field">
                    <label for="ppdb_requirements">Syarat &amp; Berkas Pendaftaran</label>
                    <textarea id="ppdb_requirements" name="ppdb_requirements" style="min-height:110px"><?= htmlspecialchars($settings['ppdb_requirements'] ?? "1. Fotokopi ijazah SD/MI (2 lembar)\n2. Fotokopi Kartu Keluarga (2 lembar)\n3. Fotokopi Akta Kelahiran (2 lembar)\n4. Pas foto 3x4 (4 lembar)\n5. Mengisi formulir pendaftaran manual di madrasah", ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="ppdb_contact_wa">Kontak Panitia (WhatsApp)</label>
                        <input type="text" id="ppdb_contact_wa" name="ppdb_contact_wa" value="<?= htmlspecialchars($settings['ppdb_contact_wa'] ?? '0812-3456-7890', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label for="ppdb_contact_email">Email Panitia PPDB</label>
                        <input type="email" id="ppdb_contact_email" name="ppdb_contact_email" value="<?= htmlspecialchars($settings['ppdb_contact_email'] ?? 'ppdb@mtsalhuda.sch.id', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="field" style="margin-bottom:0">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="ppdb_is_open" value="1" <?= ($settings['ppdb_is_open'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <b>Status PPDB Sedang Dibuka</b>
                    </label>
                </div>
            </div>
        </form>
    </div>

    <div>
        <div class="card">
            <div class="card-head">
                <h3>Dokumen Unduhan</h3>
                <div class="actions">
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalTambahDoc').style.display='grid'">+ Tambah</button>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Nama Dokumen</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Unduhan</th>
                            <th style="width:80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="5" style="padding:32px 16px;text-align:center;color:#6B7280">
                                <b>Belum ada dokumen unduhan PPDB.</b>
                                <div style="margin-top:6px">
                                    <button class="btn btn-outline btn-sm" onclick="document.getElementById('modalTambahDoc').style.display='grid'">+ Unggah Dokumen</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div class="t-title"><?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><?= htmlspecialchars($doc['file_path'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <span class="pill red"><?= htmlspecialchars($doc['mime_type'] ?? 'FILE', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/ppdb/toggle-doc/' . $doc['id']) ?>" style="display:inline">
                                        <?= Csrf::field() ?>
                                        <button type="submit" style="background:none;border:none;padding:0;cursor:pointer">
                                            <span class="switch <?= (int) $doc['is_active'] === 1 ? 'on' : '' ?>"><span class="track"></span></span>
                                        </button>
                                    </form>
                                </td>
                                <td><b><?= number_format((int) $doc['download_count'], 0, ',', '.') ?></b></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="icon-btn" href="<?= base_url($doc['file_path']) ?>" target="_blank" title="Unduh / Lihat">
                                            <svg viewBox="0 0 24 24"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.6 2.4A2 2 0 0 0 4.5 21h15a2 2 0 0 0 1.9-1.6L22 17"/></svg>
                                        </a>
                                        <form method="post" action="<?= base_url('admin/ppdb/delete-doc/' . $doc['id']) ?>" onsubmit="return confirm('Hapus dokumen ini?');" style="display:inline">
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
    </div>
</div>

<!-- Modal Tambah Dokumen PPDB -->
<div id="modalTambahDoc" class="modal-overlay" style="display:none">
    <div class="modal-mock">
        <div class="modal-head">
            <h3>Unggah Dokumen PPDB</h3>
            <button type="button" class="icon-btn" onclick="document.getElementById('modalTambahDoc').style.display='none'">&times;</button>
        </div>
        <form method="post" action="<?= base_url('admin/ppdb/store-doc') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="modal-body">
                <div class="field">
                    <label for="doc_title">Nama Dokumen <span class="req">*</span></label>
                    <input type="text" id="doc_title" name="title" required placeholder="Contoh: Brosur Resmi PPDB 2027/2028">
                </div>

                <div class="field">
                    <label>Pilih Berkas Dokumen (PDF, DOC, DOCX, XLS, XLSX) <span class="req">*</span></label>
                    <input type="file" name="doc_file" style="margin-bottom:8px">
                    <div class="hint">Atau path relatif berkas jika sudah ada:</div>
                    <input type="text" name="file_path" placeholder="uploads/ppdb/brosur.pdf">
                </div>

                <div class="field">
                    <label for="doc_sort">Nomor Urutan</label>
                    <input type="number" id="doc_sort" name="sort_order" value="1">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('modalTambahDoc').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Dokumen</button>
            </div>
        </form>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
