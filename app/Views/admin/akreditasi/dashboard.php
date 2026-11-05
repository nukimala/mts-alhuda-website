<?php
ob_start();
$period = $data['period'] ?? [];
$components = $data['components'] ?? [];
$gapButirs = $data['gap_butirs'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Akreditasi IA2024 / <b>Dashboard Kesiapan</b>
</div>

<div class="page-head">
    <div>
        <h1>Dashboard Kesiapan Akreditasi</h1>
        <p>Instrumen Akreditasi 2024 (IA2024) &middot; 4 Komponen &middot; 33 Butir Penilaian</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('admin/akreditasi/bukti') ?>">Bank Bukti</a>
        <a class="btn btn-primary btn-sm" href="<?= base_url('admin/akreditasi/dka') ?>">Lanjut Isi DKA &rarr;</a>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom:18px">
    <div class="card stat">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5"/><path d="m8.5 13.5-1.5 7 5-2.6 5 2.6-1.5-7"/></svg>
        </div>
        <div>
            <small>Progres Keseluruhan</small>
            <strong><?= (int) $data['overall_progress'] ?>%</strong>
            <div class="hint"><?= (int) $data['completed_butirs'] ?> dari <?= (int) $data['total_butirs'] ?> butir siap</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <div>
            <small>Butir Lengkap</small>
            <strong><?= (int) $data['completed_butirs'] ?></strong>
            <div class="hint">DKA &amp; Bukti terpenuhi</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/></svg>
        </div>
        <div>
            <small>Bukti Terunggah</small>
            <strong><?= (int) $data['total_evidence'] ?></strong>
            <div class="hint">Di Bank Bukti</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        </div>
        <div>
            <small>Butir Belum Terisi</small>
            <strong><?= (int) $data['empty_butirs'] ?></strong>
            <div class="hint">Perlu perhatian tim</div>
        </div>
    </div>
</div>

<?php if ((int) $data['empty_butirs'] > 0): ?>
    <div class="notice warn" style="margin-bottom:18px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div>
            <b>Masih terdapat <?= (int) $data['empty_butirs'] ?> butir yang belum memiliki bukti atau DKA.</b> Buka <b>Bank Bukti</b> untuk mengunggah dokumen SK/SOP, lalu lengkapi narasi kondisi nyata pada <b>Editor DKA</b>.
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="margin-bottom:18px">
    <?php foreach ($components as $c): ?>
        <?php
        $compTotal = (int) $c['total_butir'];
        $compDone = (int) $c['completed_butir'];
        $compEmpty = (int) $c['empty_butir'];
        $pCent = $compTotal > 0 ? (int) round(($compDone / $compTotal) * 100) : 0;
        $barClass = $pCent >= 75 ? '' : ($pCent >= 50 ? 'amber' : 'red');
        ?>
        <div class="card comp-card">
            <div class="ring" style="--p:<?= $pCent ?>;--size:78px">
                <b><?= $pCent ?><small>%</small></b>
            </div>
            <div style="flex:1">
                <b>Komponen <?= htmlspecialchars($c['number'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></b>
                <span><?= $compTotal ?> butir &middot; <?= $compDone ?> selesai &middot; <?= $compEmpty ?> belum</span>
                <div class="progress <?= $barClass ?>" style="margin-top:9px">
                    <span style="width:<?= $pCent ?>%"></span>
                </div>
                <div class="meta">
                    <span>Bukti tertaut: <?= (int) $c['total_evidence'] ?></span>
                    <span>DKA terisi: <?= $compDone ?>/<?= $compTotal ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-head">
        <h3>Gap Analysis — Butir yang Belum Lengkap</h3>
        <div class="actions">
            <a class="btn btn-outline btn-sm" href="<?= base_url('admin/akreditasi/dka') ?>">Buka Editor DKA &rarr;</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:80px">Butir</th>
                    <th>Komponen</th>
                    <th>Pernyataan Butir IA2024</th>
                    <th style="width:100px">Bukti</th>
                    <th style="width:100px">DKA</th>
                    <th style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($gapButirs)): ?>
                <tr>
                    <td colspan="6" style="padding:32px 16px;text-align:center;color:#15803D">
                        <b>Alhamdulillah! Seluruh 33 butir akreditasi telah memiliki DKA dan bukti lengkap.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($gapButirs as $gb): ?>
                    <tr>
                        <td><span class="pill green plain"><?= htmlspecialchars($gb['code'] ?? ('#' . $gb['number']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><b><?= htmlspecialchars($gb['component_code'], ENT_QUOTES, 'UTF-8') ?></b> &middot; <?= htmlspecialchars($gb['component_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($gb['butir_text'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ((int) $gb['evidence_count'] > 0): ?>
                                <span class="pill green"><?= $gb['evidence_count'] ?> bukti</span>
                            <?php else: ?>
                                <span class="pill red">0 bukti</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $gb['dka_count'] > 0): ?>
                                <span class="pill blue">Ada Draf</span>
                            <?php else: ?>
                                <span class="pill gray">Kosong</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-outline btn-sm" href="<?= base_url('admin/akreditasi/dka?butir_id=' . $gb['id']) ?>">Isi DKA</a>
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
