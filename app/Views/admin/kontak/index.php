<?php
ob_start();

$counts = $data['counts'] ?? ['semua' => 0, 'baru' => 0, 'dibaca' => 0, 'dibalas' => 0];
$statusCurrent = $data['status'] ?? 'semua';
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Kotak Masuk Pesan</b>
</div>

<div class="page-head">
    <div>
        <h1>Pesan &amp; Kotak Masuk Kontak</h1>
        <p>
            <?= (int) $counts['baru'] ?> pesan baru &middot; <?= (int) $counts['dibaca'] ?> dibaca &middot; <?= (int) $counts['dibalas'] ?> dibalas &middot; Total <?= (int) $counts['semua'] ?> pesan masuk
        </p>
    </div>
</div>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Tab Status Cepat -->
<div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
    <a href="<?= base_url('admin/kontak') ?>" class="btn <?= $statusCurrent === 'semua' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Semua (<?= $counts['semua'] ?>)
    </a>
    <a href="<?= base_url('admin/kontak?status=baru') ?>" class="btn <?= $statusCurrent === 'baru' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Pesan Baru (<?= $counts['baru'] ?>)
    </a>
    <a href="<?= base_url('admin/kontak?status=dibaca') ?>" class="btn <?= $statusCurrent === 'dibaca' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Sudah Dibaca (<?= $counts['dibaca'] ?>)
    </a>
    <a href="<?= base_url('admin/kontak?status=dibalas') ?>" class="btn <?= $statusCurrent === 'dibalas' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Sudah Dibalas (<?= $counts['dibalas'] ?>)
    </a>
</div>

<div class="card">
    <form method="get" action="<?= base_url('admin/kontak') ?>" class="toolbar">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusCurrent, ENT_QUOTES, 'UTF-8') ?>">
        <div class="field-inline" style="min-width:280px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama, email, no telepon, atau pesan...">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        <?php if (!empty($data['q'])): ?>
            <a href="<?= base_url('admin/kontak?status=' . urlencode($statusCurrent)) ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:170px">Pengirim</th>
                    <th style="width:140px">Kontak</th>
                    <th>Subjek &amp; Isi Pesan</th>
                    <th style="width:130px">Waktu</th>
                    <th style="width:100px">Status</th>
                    <th style="width:160px; text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['messages'])): ?>
                <tr>
                    <td colspan="6" class="empty">
                        <svg viewBox="0 0 24 24" style="width:40px;height:40px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 10px;display:block">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        Belum ada pesan kontak dari masyarakat atau pengunjung.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['messages'] as $m): ?>
                    <?php
                    $isNew = $m['status'] === 'baru';
                    $cleanPhone = preg_replace('/[^0-9]/', '', (string)($m['phone'] ?? ''));
                    if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '62' . substr($cleanPhone, 1);
                    }
                    ?>
                    <tr style="<?= $isNew ? 'background: #F0FDF4; font-weight: 500;' : '' ?>">
                        <td>
                            <strong style="color:var(--text); display:block;">
                                <?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                            <small class="muted" style="font-size:11.5px">ID #<?= $m['id'] ?></small>
                        </td>
                        <td>
                            <div style="font-size:12.5px;"><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($m['phone'])): ?>
                                <div style="font-size:12px; color:var(--gray); margin-top:2px;">
                                    ☎ <?= htmlspecialchars($m['phone'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text); margin-bottom:4px; font-size:13.5px;">
                                <?= htmlspecialchars($m['subject'] ?: '(Tanpa Subjek)', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="font-size:12.5px; color:#475569; line-height:1.5;">
                                <?= nl2br(htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                            <?php if (!empty($m['replied_by_name'])): ?>
                                <div style="font-size:11px; color:#15803D; margin-top:6px; font-weight:600;">
                                    ✓ Dibalas oleh <?= htmlspecialchars($m['replied_by_name'], ENT_QUOTES, 'UTF-8') ?> (<?= date('d/m/Y H:i', strtotime($m['dibalas_pada'] ?? '')) ?>)
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="nowrap" style="font-size:12px; color:var(--gray)">
                            <?= date('d M Y', strtotime($m['created_at'])) ?><br>
                            <small><?= date('H:i', strtotime($m['created_at'])) ?> WIB</small>
                        </td>
                        <td>
                            <?php if ($m['status'] === 'baru'): ?>
                                <span class="pill amber">Baru</span>
                            <?php elseif ($m['status'] === 'dibaca'): ?>
                                <span class="pill blue">Dibaca</span>
                            <?php elseif ($m['status'] === 'dibalas'): ?>
                                <span class="pill green">Dibalas</span>
                            <?php else: ?>
                                <span class="pill gray">Arsip</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right">
                            <div style="display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;">
                                <?php if (!empty($cleanPhone)): ?>
                                    <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-outline btn-xs" title="Balas via WhatsApp" style="color:#15803D; border-color:#BBF7D0;">
                                        WhatsApp
                                    </a>
                                <?php endif; ?>

                                <a href="mailto:<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?>?subject=Re:%20<?= urlencode($m['subject'] ?? 'Pesan MTs Al-Huda') ?>" class="btn btn-outline btn-xs" title="Balas via Email">
                                    Email
                                </a>

                                <?php if ($m['status'] === 'baru'): ?>
                                    <form method="post" action="<?= base_url('admin/kontak/mark-read/' . $m['id']) ?>" style="display:inline">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-outline btn-xs" title="Tandai Sudah Dibaca">
                                            Tandai Baca
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($m['status'] !== 'dibalas'): ?>
                                    <form method="post" action="<?= base_url('admin/kontak/mark-replied/' . $m['id']) ?>" style="display:inline">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-primary btn-xs" title="Tandai Sudah Selesai Dibalas">
                                            Tandai Selesai
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="<?= base_url('admin/kontak/delete/' . $m['id']) ?>" onsubmit="return confirm('Hapus pesan dari <?= htmlspecialchars(addslashes($m['name']), ENT_QUOTES, 'UTF-8') ?>?')" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-ghost btn-xs" style="color:var(--red);" title="Hapus Pesan">
                                        Hapus
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
            <span class="spacer">Halaman <?= $data['page'] ?> dari <?= $data['total_pages'] ?> (Total <?= $data['total'] ?> pesan)</span>
            <?php for ($p = 1; $p <= $data['total_pages']; $p++): ?>
                <?php
                $query = $_GET;
                $query['page'] = $p;
                $pageUrl = base_url('admin/kontak') . '?' . http_build_query($query);
                ?>
                <a href="<?= $pageUrl ?>" class="<?= $p === $data['page'] ? 'on' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
?>
