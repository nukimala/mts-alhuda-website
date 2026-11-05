<?php
ob_start();
$evidences = $data['evidences'] ?? [];
$allButirs = $data['all_butirs'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Akreditasi IA2024 / <b>Bank Bukti</b>
</div>

<div class="page-head">
    <div>
        <h1>Bank Bukti Akreditasi</h1>
        <p><?= count($evidences) ?> bukti tersimpan &middot; <?= (int) $data['stat_mapped'] ?> sudah terpetakan &middot; <?= (int) $data['stat_unmapped'] ?> belum dipetakan</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/akreditasi') ?>">&larr; Dashboard</a>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalUnggahBukti').style.display='grid'">+ Unggah Bukti</button>
    </div>
</div>

<div class="notice info" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
    <div>
        <b>Satu bukti dapat digunakan untuk beberapa butir sekaligus.</b> Contoh: SK Pembagian Tugas Guru dapat ditautkan ke butir 1.3, 3.1, dan 4.1. Berkas tersimpan efisien tanpa duplikasi fisik.
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="get" action="<?= base_url('admin/akreditasi/bukti') ?>" class="toolbar">
        <div class="field-inline" style="min-width:240px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama dokumen bukti...">
        </div>

        <div class="field-inline">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="valid" <?= ($data['status'] ?? '') === 'valid' ? 'selected' : '' ?>>Berlaku</option>
                <option value="draft" <?= ($data['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf</option>
                <option value="expired" <?= ($data['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Kadaluarsa</option>
            </select>
        </div>

        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if (!empty($data['q']) || !empty($data['status'])): ?>
            <a href="<?= base_url('admin/akreditasi/bukti') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <span class="pill plain gray"><?= count($evidences) ?> dokumen</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Nama Dokumen Bukti</th>
                    <th>Tipe / Sumber</th>
                    <th>Butir yang Ditautkan</th>
                    <th>Masa Berlaku</th>
                    <th>Status</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($evidences)): ?>
                <tr>
                    <td colspan="6" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <svg viewBox="0 0 24 24" style="width:32px;height:32px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 8px;display:block"><path d="M4 7a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/></svg>
                        <b>Belum ada dokumen bukti yang diunggah ke Bank Bukti.</b>
                        <div style="margin-top:6px">
                            <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalUnggahBukti').style.display='grid'">+ Unggah Bukti Sekarang</button>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($evidences as $ev): ?>
                    <tr>
                        <td>
                            <div class="t-title"><?= htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="t-sub"><?= htmlspecialchars($ev['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?php if (!empty($ev['file_path'])): ?>
                                <span class="pill red plain">PDF / Berkas</span>
                            <?php else: ?>
                                <span class="pill blue plain">Link Eksternal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($ev['mapped_butirs'])): ?>
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    <?php
                                    $butirTokens = explode(', ', $ev['mapped_butirs']);
                                    foreach ($butirTokens as $bt):
                                        $parts = explode(':', $bt);
                                        $label = $parts[0] ?? $bt;
                                    ?>
                                        <span class="pill green plain">Butir <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="pill gray">Belum ditautkan</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted">
                            <?= !empty($ev['valid_until']) ? date('d M Y', strtotime($ev['valid_until'])) : 'Tetap' ?>
                        </td>
                        <td>
                            <?php if ($ev['status'] === 'valid'): ?>
                                <span class="pill green">Valid</span>
                            <?php elseif ($ev['status'] === 'expired'): ?>
                                <span class="pill red">Kadaluarsa</span>
                            <?php else: ?>
                                <span class="pill gray">Draf</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <?php if (!empty($ev['file_path'])): ?>
                                    <a class="icon-btn" href="<?= base_url($ev['file_path']) ?>" target="_blank" title="Unduh">
                                        <svg viewBox="0 0 24 24"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.6 2.4A2 2 0 0 0 4.5 21h15a2 2 0 0 0 1.9-1.6L22 17"/></svg>
                                    </a>
                                <?php elseif (!empty($ev['external_url'])): ?>
                                    <a class="icon-btn" href="<?= htmlspecialchars($ev['external_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" title="Buka Tautan">
                                        <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7L12.5 19"/></svg>
                                    </a>
                                <?php endif; ?>
                                <form method="post" action="<?= base_url('admin/akreditasi/delete-bukti/' . $ev['id']) ?>" onsubmit="return confirm('Hapus dokumen bukti ini?');" style="display:inline">
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

<!-- Modal Unggah Bukti -->
<div id="modalUnggahBukti" class="modal-overlay" style="display:none">
    <div class="modal-mock">
        <div class="modal-head">
            <h3>Unggah Dokumen Bukti Baru</h3>
            <button type="button" class="icon-btn" onclick="document.getElementById('modalUnggahBukti').style.display='none'">&times;</button>
        </div>
        <form method="post" action="<?= base_url('admin/akreditasi/store-bukti') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="period_id" value="<?= (int) ($data['period']['id'] ?? 1) ?>">

            <div class="modal-body">
                <div class="field">
                    <label for="modal_bukti_title">Nama Dokumen Bukti <span class="req">*</span></label>
                    <input type="text" id="modal_bukti_title" name="title" required placeholder="Contoh: SK Tim Pengembang Kurikulum 2026/2027">
                </div>

                <div class="field">
                    <label for="modal_bukti_desc">Deskripsi Singkat</label>
                    <textarea id="modal_bukti_desc" name="description" style="min-height:70px" placeholder="Keterangan isi dan tujuan dokumen..."></textarea>
                </div>

                <div class="field">
                    <label>Pilih Berkas Dokumen (PDF, DOCX, JPG, PNG)</label>
                    <input type="file" name="bukti_file" style="margin-bottom:8px">
                    <div class="hint">Atau masukkan link Cloud / Google Drive resmi madrasah:</div>
                    <input type="url" name="external_url" placeholder="https://drive.google.com/...">
                </div>

                <div class="field">
                    <label for="modal_valid_until">Masa Berlaku Dokumen (opsional)</label>
                    <input type="date" id="modal_valid_until" name="valid_until">
                    <div class="hint">Kosongkan jika dokumen bersifat tetap / tanpa masa kadaluarsa.</div>
                </div>

                <div class="field">
                    <label>Tautkan Langsung ke Butir IA2024</label>
                    <div class="pick-list" style="max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px">
                        <?php foreach ($allButirs as $b): ?>
                            <label style="display:flex;align-items:flex-start;gap:8px;padding:5px 0;font-size:12px;cursor:pointer;border-bottom:1px solid #F1F6F3">
                                <input type="checkbox" name="butir_ids[]" value="<?= $b['id'] ?>" style="margin-top:2px">
                                <span><b>[<?= htmlspecialchars($b['comp_code'] . '.' . $b['code'], ENT_QUOTES, 'UTF-8') ?>]</b> <?= htmlspecialchars(mb_substr($b['butir_text'], 0, 75)) ?>...</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('modalUnggahBukti').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan ke Bank Bukti</button>
            </div>
        </form>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
