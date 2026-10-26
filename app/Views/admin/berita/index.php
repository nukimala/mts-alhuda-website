<?php
/**
 * Daftar berita panel admin.
 *
 * Mengikuti layar Stitch "Kelola Berita": pencarian, filter status dan
 * kategori, pengurutan, tabel dengan thumbnail, dan paginasi presisi.
 *
 * Kotak centang massal dari desain tidak dipasang: aksinya belum ada, dan
 * kotak centang yang tidak melakukan apa pun lebih buruk daripada tidak ada.
 */

$result = $data['result'] ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 10];
$filters = $data['filters'] ?? [];
$counts = $data['counts'] ?? [];
$canWrite = (bool) ($data['canWrite'] ?? false);

$rows = $result['rows'];
$total = (int) $result['total'];
$page = (int) $result['page'];
$pages = (int) $result['pages'];
$perPage = (int) $result['per_page'];

$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to = min($total, $page * $perPage);

/** Bangun URL halaman ini dengan filter yang sedang aktif. */
$buildUrl = static function (array $override = []) use ($filters): string {
    $params = array_filter([
        'q'        => (string) ($filters['q'] ?? ''),
        'status'   => (string) ($filters['status'] ?? ''),
        'kategori' => (string) ($filters['category'] ?? ''),
        'urutkan'  => (string) ($filters['sort'] ?? 'terbaru'),
        'halaman'  => (string) ($filters['page'] ?? ''),
    ], static fn ($v) => $v !== '' && $v !== null);

    foreach ($override as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    $query = http_build_query($params);

    return base_url('admin/berita') . ($query !== '' ? '?' . $query : '');
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'published' => 'pill pill-green pill-dot',
        'draft'     => 'pill pill-amber pill-dot',
        'archived'  => 'pill pill-dot',
        default     => 'pill',
    };
};

$statusLabel = static fn (string $status): string => Post::STATUSES[$status] ?? ucfirst($status);

$categoryLabel = static fn (string $key): string => Post::CATEGORIES[$key] ?? ucfirst($key);

$isFiltered = ($filters['q'] ?? '') !== ''
    || ($filters['status'] ?? '') !== ''
    || ($filters['category'] ?? '') !== '';

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <span>Konten Website</span>
    <span class="sep">/</span>
    <span class="current">Berita</span>
</div>

<div class="page-head">
    <div>
        <h1>Kelola Berita</h1>
        <p>
            <?= Format::number((int) ($counts['published'] ?? 0)) ?> terbit
            &middot; <?= Format::number((int) ($counts['draft'] ?? 0)) ?> draft
            <?php if ((int) ($counts['archived'] ?? 0) > 0): ?>
                &middot; <?= Format::number((int) $counts['archived']) ?> arsip
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
            <a class="btn btn-primary" href="<?= base_url('admin/berita/create') ?>">
                <span class="mi mi-sm">add</span> Tambah Berita
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card card-flat" style="padding:0;overflow:hidden">
    <form class="table-toolbar" method="get" action="<?= base_url('admin/berita') ?>">
        <div class="search-box">
            <span class="mi mi-sm">search</span>
            <input
                type="search"
                name="q"
                value="<?= e((string) ($filters['q'] ?? '')) ?>"
                placeholder="Cari judul berita..."
                aria-label="Cari judul berita">
        </div>

        <select name="status" aria-label="Filter status">
            <option value="">Semua Status</option>
            <?php foreach (Post::STATUSES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="kategori" aria-label="Filter kategori">
            <option value="">Semua Kategori</option>
            <?php foreach (Post::CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="urutkan" aria-label="Urutkan" class="toolbar-sort">
            <option value="terbaru" <?= ($filters['sort'] ?? '') === 'terbaru' ? 'selected' : '' ?>>Urutkan: Terkini</option>
            <option value="terlama" <?= ($filters['sort'] ?? '') === 'terlama' ? 'selected' : '' ?>>Urutkan: Terlama</option>
            <option value="dibaca" <?= ($filters['sort'] ?? '') === 'dibaca' ? 'selected' : '' ?>>Paling Banyak Dibaca</option>
            <option value="judul" <?= ($filters['sort'] ?? '') === 'judul' ? 'selected' : '' ?>>Judul A-Z</option>
        </select>

        <button class="btn btn-secondary btn-sm" type="submit">Terapkan</button>

        <?php if ($isFiltered): ?>
            <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/berita') ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($rows === []): ?>
        <div style="padding:var(--sp-8) var(--sp-5)">
            <?php if ($isFiltered): ?>
                <div class="empty">
                    <p class="empty-title">Tidak ada berita yang cocok</p>
                    <p class="empty-text">Coba kata kunci lain, atau <a href="<?= base_url('admin/berita') ?>">tampilkan semua berita</a>.</p>
                </div>
            <?php else: ?>
                <div class="empty">
                    <p class="empty-title">Belum ada berita</p>
                    <p class="empty-text">
                        Berita yang ditulis di sini akan tampil di halaman Berita dan Beranda website.
                        <?php if ($canWrite): ?>
                            Mulai dengan <a href="<?= base_url('admin/berita/create') ?>">menulis berita pertama</a>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Penulis</th>
                        <th>Status</th>
                        <th>Tanggal Terbit</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $id = (int) $row['id'];
                    $cover = (string) ($row['cover_path'] ?? '');
                    $hasCover = $cover !== '' && Media::exists($cover);
                    $status = (string) $row['status'];
                    ?>
                    <tr>
                        <td>
                            <div class="cell-title">
                                <?php if ($hasCover): ?>
                                    <img class="cell-thumb" src="<?= base_url($cover) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="cell-thumb is-placeholder">
                                        <span class="mi mi-sm">article</span>
                                    </span>
                                <?php endif; ?>
                                <div class="cell-title-text">
                                    <?php if ($canWrite): ?>
                                        <a href="<?= base_url('admin/berita/' . $id . '/edit') ?>"><?= e((string) $row['title']) ?></a>
                                    <?php else: ?>
                                        <span class="cell-title-static"><?= e((string) $row['title']) ?></span>
                                    <?php endif; ?>
                                    <small class="mono">/berita/<?= e((string) $row['slug']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="nowrap">
                            <span class="pill"><?= e($categoryLabel((string) ($row['category'] ?? 'umum'))) ?></span>
                        </td>
                        <td class="nowrap">
                            <?= e((string) ($row['author_name'] ?? 'Tanpa penulis')) ?>
                        </td>
                        <td class="nowrap">
                            <span class="<?= $statusClass($status) ?>"><?= e($statusLabel($status)) ?></span>
                        </td>
                        <td class="nowrap">
                            <?php if (!empty($row['published_at'])): ?>
                                <?= e(Format::dateShort((string) $row['published_at'])) ?>
                                <?php if ($status === 'draft'): ?>
                                    <span class="muted tiny">(jadwal)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <?php if ($status === 'published'): ?>
                                    <a class="icon-btn" href="<?= base_url('berita/' . $row['slug']) ?>"
                                       target="_blank" rel="noopener" title="Lihat di website" aria-label="Lihat di website">
                                        <span class="mi mi-sm">visibility</span>
                                    </a>
                                <?php endif; ?>

                                <?php if ($canWrite): ?>
                                    <a class="icon-btn" href="<?= base_url('admin/berita/' . $id . '/edit') ?>"
                                       title="Sunting" aria-label="Sunting berita">
                                        <span class="mi mi-sm">edit</span>
                                    </a>

                                    <button class="icon-btn is-danger" type="button"
                                            title="Hapus" aria-label="Hapus berita"
                                            data-confirm-delete="<?= e((string) $row['title']) ?>"
                                            data-form="delete-post-<?= $id ?>">
                                        <span class="mi mi-sm">delete</span>
                                    </button>

                                    <form id="delete-post-<?= $id ?>" method="post"
                                          action="<?= base_url('admin/berita/' . $id . '/delete') ?>" hidden>
                                        <?= Csrf::field() ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <span class="muted">
                Menampilkan <?= $from ?>–<?= $to ?> dari <?= Format::number($total) ?> berita
            </span>

            <?php if ($pages > 1): ?>
                <nav class="pagination-inline" aria-label="Navigasi halaman">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="<?= $buildUrl(['halaman' => $page - 1]) ?>">Sebelumnya</a>
                    <?php else: ?>
                        <span class="page-btn is-disabled">Sebelumnya</span>
                    <?php endif; ?>

                    <?php
                    // Jendela halaman: selalu tampilkan halaman pertama, terakhir,
                    // dan dua tetangga di sekitar halaman aktif.
                    $shown = [];
                    for ($i = 1; $i <= $pages; $i++) {
                        if ($i === 1 || $i === $pages || abs($i - $page) <= 1) {
                            $shown[] = $i;
                        }
                    }
                    $previous = 0;
                    foreach ($shown as $i):
                        if ($previous !== 0 && $i - $previous > 1):
                            ?><span class="page-gap">…</span><?php
                        endif;
                        $previous = $i;
                        ?>
                        <?php if ($i === $page): ?>
                            <span class="page-btn is-active"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-btn" href="<?= $buildUrl(['halaman' => $i]) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($page < $pages): ?>
                        <a class="page-btn" href="<?= $buildUrl(['halaman' => $page + 1]) ?>">Selanjutnya</a>
                    <?php else: ?>
                        <span class="page-btn is-disabled">Selanjutnya</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!$canWrite): ?>
    <div class="notice notice-info" style="margin-top:var(--sp-4)">
        <span class="mi mi-sm">visibility</span>
        <span>
            Role Anda hanya dapat melihat daftar berita. Untuk menulis atau mengubah,
            minta akses <strong>Operator Madrasah</strong> atau <strong>Super Admin</strong>.
        </span>
    </div>
<?php endif; ?>

<script>
    (function () {
        document.querySelectorAll('[data-confirm-delete]').forEach(function (button) {
            button.addEventListener('click', function () {
                var title = button.getAttribute('data-confirm-delete') || 'berita ini';
                if (!window.confirm('Hapus "' + title + '"?\n\nBerita akan hilang dari website. Tindakan ini tidak bisa dibatalkan dari panel.')) {
                    return;
                }
                var form = document.getElementById(button.getAttribute('data-form'));
                if (form) {
                    form.removeAttribute('hidden');
                    form.submit();
                }
            });
        });
    })();
</script>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../../layouts/admin.php';
