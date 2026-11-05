<?php
ob_start();
$allButirs = $data['all_butirs'] ?? [];
$currButir = $data['curr_butir'] ?? null;
$dkaEntry = $data['dka_entry'] ?? null;
$linkedEvidence = $data['linked_evidence'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Akreditasi IA2024 / <a href="<?= base_url('admin/akreditasi') ?>">Dashboard</a> / <b>Editor DKA</b>
</div>

<div class="page-head">
    <div>
        <h1>Editor DKA — Deskripsi Kondisi Aktual</h1>
        <p>Penyusunan narasi reflektif kinerja madrasah per butir instrumen IA2024</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/akreditasi/bukti') ?>">Bank Bukti &rarr;</a>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="dka-grid">
    <!-- Kolom Kiri: Daftar Butir -->
    <div class="card">
        <div class="card-head">
            <h3>Daftar 33 Butir IA2024</h3>
            <div class="actions">
                <span class="pill plain gray"><?= count($allButirs) ?> butir</span>
            </div>
        </div>
        <div class="butir-list">
            <?php foreach ($allButirs as $b): ?>
                <?php
                $isOn = (int) $b['id'] === (int) ($currButir['id'] ?? 0);
                $isDone = in_array($b['status'], ['lengkap', 'terverifikasi'], true);
                $isGap = $b['status'] === 'kosong';
                $classState = $isOn ? 'on' : ($isDone ? 'done' : ($isGap ? 'gap' : ''));
                ?>
                <a href="<?= base_url('admin/akreditasi/dka?butir_id=' . $b['id']) ?>" class="butir-item <?= $classState ?>">
                    <span class="code"><?= htmlspecialchars($b['comp_code'] . '.' . $b['code'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="txt">
                        <b><?= htmlspecialchars(mb_substr($b['butir_text'], 0, 52)) ?>...</b>
                        <small><?= $b['evidence_count'] ?> bukti &middot; <?= $b['status'] ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kolom Tengah: Form Narasi DKA -->
    <div>
        <?php if ($currButir): ?>
            <form method="post" action="<?= base_url('admin/akreditasi/save-dka') ?>" class="card card-pad">
                <?= Csrf::field() ?>
                <input type="hidden" name="butir_id" value="<?= $currButir['id'] ?>">

                <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span class="pill green plain">Butir <?= htmlspecialchars($currButir['comp_code'] . '.' . $currButir['code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="pill gray plain"><?= htmlspecialchars($currButir['comp_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h2 style="font-size:15px;line-height:1.45;color:var(--text)">
                        <?= htmlspecialchars($currButir['butir_text'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>

                <div class="field">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <label for="dka_content" style="margin-bottom:0">Narasi Deskripsi Kondisi Aktual (DKA)</label>
                        <div class="char-meter">
                            <span id="charCount">0</span> / 1000 karakter
                            <div class="bar"><span id="charBar" style="width:0%"></span></div>
                        </div>
                    </div>
                    <textarea id="dka_content" name="content" class="dka-text" required placeholder="Tuliskan narasi refleksi faktual kondisi madrasah terkait butir ini (maksimal 1.000 karakter)..." oninput="updateCharCount()"><?= htmlspecialchars($dkaEntry['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="hint">Tuliskan bukti autentik, proses pelaksanaan, capaian hasil, dan kendala nyata secara jujur serta terukur.</div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;border-top:1px solid var(--border);padding-top:14px">
                    <div>
                        <select name="status" class="btn btn-outline btn-sm">
                            <option value="draft" <?= ($dkaEntry['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Simpan Draf</option>
                            <option value="final" <?= ($dkaEntry['status'] ?? '') === 'final' ? 'selected' : '' ?>>Tandai Selesai / Final</option>
                            <option value="revisi" <?= ($dkaEntry['status'] ?? '') === 'revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Narasi DKA</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="card card-pad" style="text-align:center;padding:48px 20px">
                <b>Pilih butir akreditasi di sebelah kiri untuk mulai mengisi DKA.</b>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Bukti Tertaut -->
    <div class="card">
        <div class="card-head">
            <h3>Bukti Tertaut</h3>
            <div class="actions">
                <span class="pill plain gray"><?= count($linkedEvidence) ?> berkas</span>
            </div>
        </div>
        <div style="padding:10px 0">
            <?php if (empty($linkedEvidence)): ?>
                <div style="padding:24px 16px;text-align:center;color:#6B7280;font-size:12px">
                    Belum ada bukti yang ditautkan ke butir ini.
                    <div style="margin-top:8px">
                        <a href="<?= base_url('admin/akreditasi/bukti') ?>" class="btn btn-outline btn-sm" style="font-size:11px">+ Tautkan Bukti</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($linkedEvidence as $lev): ?>
                    <div class="evidence-item">
                        <div class="ico">DOC</div>
                        <div style="flex:1;min-width:0">
                            <b><?= htmlspecialchars($lev['title'], ENT_QUOTES, 'UTF-8') ?></b>
                            <span><?= !empty($lev['file_path']) ? 'Berkas PDF' : 'Link Web' ?></span>
                        </div>
                        <?php if (!empty($lev['file_path'])): ?>
                            <a href="<?= base_url($lev['file_path']) ?>" target="_blank" class="icon-btn" style="width:26px;height:26px" title="Buka">
                                <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateCharCount() {
    var txt = document.getElementById('dka_content');
    if (!txt) return;
    var len = txt.value.length;
    var max = 1000;
    var countEl = document.getElementById('charCount');
    var barEl = document.getElementById('charBar');
    if (countEl) countEl.innerText = len;
    if (barEl) {
        var pct = Math.min(100, Math.round((len / max) * 100));
        barEl.style.width = pct + '%';
        if (len > max) {
            barEl.style.backgroundColor = '#DC2626';
            countEl.style.color = '#DC2626';
        } else {
            barEl.style.backgroundColor = '#15803D';
            countEl.style.color = '#6B7280';
        }
    }
}
document.addEventListener('DOMContentLoaded', updateCharCount);
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
