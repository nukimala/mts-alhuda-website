<?php
/**
 * Daftar pengguna panel admin.
 *
 * Mengikuti layar Stitch "Kelola Pengguna": pencarian, saringan role berbentuk
 * chip, saringan status, tabel identitas, dan paginasi.
 *
 * Dua hal dari desain sengaja tidak dipasang:
 *   - tombol "Impor dari Excel": belum ada fitur impor, dan tombol yang tidak
 *     melakukan apa pun lebih buruk daripada tidak ada tombol,
 *   - kotak centang massal: belum ada aksi massal.
 */

$result = $data['result'] ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 10];
$filters = $data['filters'] ?? [];
$counts = $data['counts'] ?? [];
$canWrite = (bool) ($data['canWrite'] ?? false);
$currentId = (int) ($data['currentId'] ?? 0);

$rows = $result['rows'];
$total = (int) $result['total'];
$page = (int) $result['page'];
$pages = (int) $result['pages'];
$perPage = (int) $result['per_page'];

$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to = min($total, $page * $perPage);

/** URL halaman ini dengan saringan yang sedang aktif. */
$buildUrl = static function (array $override = []) use ($filters): string {
    $params = array_filter([
        'q'      => (string) ($filters['q'] ?? ''),
        'role'   => (string) ($filters['role'] ?? ''),
        'status' => (string) ($filters['status'] ?? ''),
        'halaman' => (string) ($filters['page'] ?? ''),
    ], static fn ($v) => $v !== '' && $v !== null);

    foreach ($override as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    // Saringan berubah berarti halaman kembali ke satu.
    if (array_key_exists('q', $override) || array_key_exists('role', $override) || array_key_exists('status', $override)) {
        unset($params['halaman']);
    }

    $query = http_build_query($params);

    return base_url('admin/pengguna') . ($query !== '' ? '?' . $query : '');
};

$activeRole = (string) ($filters['role'] ?? '');
$activeStatus = (string) ($filters['status'] ?? '');
$isFiltered = ($filters['q'] ?? '') !== '' || $activeRole !== '' || $activeStatus !== '';

$initials = static function (string $name): string {
    $name = trim($name);

    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);

    if (count($parts) > 1) {
        return mb_strtoupper($first . mb_substr($parts[count($parts) - 1], 0, 1));
    }

    return mb_strtoupper(mb_substr($name, 0, 2));
};

$roleLabel = static fn (string $role): string => User::ROLES[$role] ?? ucfirst($role);

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <span>Layanan</span>
    <span class="sep">/</span>
    <span class="current">Pengguna &amp; Role</span>
</div>

<div class="page-head">
    <div>
        <h1>Kelola Pengguna</h1>
        <p>
            <?= Format::number((int) ($counts['guru'] ?? 0)) ?> guru
            &middot; <?= Format::number((int) ($counts['operator'] ?? 0)) ?> operator
            &middot; <?= Format::number((int) ($counts['kepala'] ?? 0)) ?> kepala madrasah
            &middot; <?= Format::number((int) ($counts['akreditasi'] ?? 0)) ?> tim akreditasi
            <?php if ((int) ($counts['admin'] ?? 0) > 0): ?>
                &middot; <?= Format::number((int) $counts['admin']) ?> admin
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
            <a class="btn btn-primary" href="<?= base_url('admin/pengguna/create') ?>">
                <span class="mi mi-sm">person_add</span> Tambah Pengguna
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="notice notice-info">
    <span class="mi mi-sm">info</span>
    <span>
        Tidak ada pendaftaran mandiri. Semua akun dibuat di sini oleh Operator atau Admin,
        lalu pengguna wajib mengganti kata sandi saat login pertama.
    </span>
</div>

<div class="card card-flat" style="padding:0;overflow:hidden">
    <form class="table-toolbar" method="get" action="<?= base_url('admin/pengguna') ?>">
        <div class="search-box">
            <span class="mi mi-sm">search</span>
            <input
                type="search"
                name="q"
                value="<?= e((string) ($filters['q'] ?? '')) ?>"
                placeholder="Cari nama, email, atau NIP..."
                aria-label="Cari pengguna">
        </div>

        <select name="status" aria-label="Saringan status">
            <option value="">Semua Status</option>
            <option value="aktif" <?= $activeStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $activeStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            <option value="terkunci" <?= $activeStatus === 'terkunci' ? 'selected' : '' ?>>Terkunci</option>
        </select>

        <?php if ($activeRole !== ''): ?>
            <input type="hidden" name="role" value="<?= e($activeRole) ?>">
        <?php endif; ?>

        <button class="btn btn-secondary btn-sm" type="submit">Terapkan</button>

        <?php if ($isFiltered): ?>
            <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/pengguna') ?>">Reset</a>
        <?php endif; ?>
    </form>

    <div class="role-chips">
        <a class="chip <?= $activeRole === '' ? 'is-on' : '' ?>" href="<?= $buildUrl(['role' => '']) ?>">
            Semua <span class="chip-count"><?= Format::number((int) ($counts['semua'] ?? 0)) ?></span>
        </a>
        <?php foreach (User::ROLES as $key => $label): ?>
            <a class="chip <?= $activeRole === $key ? 'is-on' : '' ?>" href="<?= $buildUrl(['role' => $key]) ?>">
                <?= e($label) ?>
                <span class="chip-count"><?= Format::number((int) ($counts[$key] ?? 0)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($rows === []): ?>
        <div style="padding:var(--sp-8) var(--sp-5)">
            <?php if ($isFiltered): ?>
                <div class="empty">
                    <p class="empty-title">Tidak ada pengguna yang cocok</p>
                    <p class="empty-text">Coba kata kunci lain, atau <a href="<?= base_url('admin/pengguna') ?>">tampilkan semua pengguna</a>.</p>
                </div>
            <?php else: ?>
                <div class="empty">
                    <p class="empty-title">Belum ada pengguna</p>
                    <p class="empty-text">
                        <?php if ($canWrite): ?>
                            Mulai dengan <a href="<?= base_url('admin/pengguna/create') ?>">membuat akun pertama</a>.
                        <?php else: ?>
                            Minta Super Admin membuatkan akun untuk Anda.
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
                        <th>Nama &amp; Identitas</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <?php if ($canWrite): ?>
                            <th style="text-align:right">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $id = (int) $row['id'];
                    $isSelf = $id === $currentId;
                    $isActive = (int) $row['is_active'] === 1;
                    $lockedUntil = (string) ($row['locked_until'] ?? '');
                    $isLocked = $lockedUntil !== '' && strtotime($lockedUntil) > time();
                    $role = (string) $row['role'];
                    $nip = trim((string) ($row['nip'] ?? ''));
                    $backTo = 'admin/pengguna' . (($filters['q'] ?? '') !== '' || $activeRole !== '' || $activeStatus !== ''
                        ? '?' . http_build_query(array_filter([
                            'q' => (string) ($filters['q'] ?? ''),
                            'role' => $activeRole,
                            'status' => $activeStatus,
                            'halaman' => $page > 1 ? (string) $page : '',
                        ]))
                        : '');
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <span class="avatar avatar-lg"><?= e($initials((string) $row['name'])) ?></span>
                                <div class="user-cell-text">
                                    <span class="user-name">
                                        <?= e((string) $row['name']) ?>
                                        <?php if ($isSelf): ?>
                                            <span class="pill pill-blue" style="margin-left:6px">Anda</span>
                                        <?php endif; ?>
                                    </span>
                                    <small class="muted">
                                        <?php if ($nip !== ''): ?>
                                            NIP. <?= e($nip) ?>
                                        <?php elseif (trim((string) ($row['username'] ?? '')) !== ''): ?>
                                            <?= e((string) $row['username']) ?>
                                        <?php else: ?>
                                            Belum ada NIP
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td class="nowrap"><?= e((string) $row['email']) ?></td>
                        <td class="nowrap">
                            <span class="pill"><?= e($roleLabel($role)) ?></span>
                        </td>
                        <td class="nowrap">
                            <?php if (!$isActive): ?>
                                <span class="pill pill-red pill-dot">Nonaktif</span>
                            <?php elseif ($isLocked): ?>
                                <span class="pill pill-amber pill-dot">Terkunci</span>
                            <?php else: ?>
                                <span class="pill pill-green pill-dot">Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="nowrap">
                            <?php if (!empty($row['last_login_at'])): ?>
                                <span title="<?= e(Format::dateTime((string) $row['last_login_at'])) ?>">
                                    <?= e(Format::ago((string) $row['last_login_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="muted">Belum pernah</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($canWrite): ?>
                            <td>
                                <div class="cell-actions">
                                    <a class="icon-btn" href="<?= base_url('admin/pengguna/' . $id . '/edit') ?>"
                                       title="Sunting" aria-label="Sunting pengguna">
                                        <span class="mi mi-sm">edit</span>
                                    </a>

                                    <?php if ($isLocked): ?>
                                        <button class="icon-btn" type="button"
                                                title="Buka kunci" aria-label="Buka kunci akun"
                                                data-post-form="unlock-<?= $id ?>">
                                            <span class="mi mi-sm">lock_open</span>
                                        </button>
                                        <form id="unlock-<?= $id ?>" method="post"
                                              action="<?= base_url('admin/pengguna/' . $id . '/buka-kunci') ?>" hidden>
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="back" value="<?= e($backTo) ?>">
                                        </form>
                                    <?php endif; ?>

                                    <button class="icon-btn" type="button"
                                            title="Setel ulang sandi" aria-label="Setel ulang sandi"
                                            data-confirm-reset="<?= e((string) $row['name']) ?>"
                                            data-post-form="reset-<?= $id ?>">
                                        <span class="mi mi-sm">key</span>
                                    </button>
                                    <form id="reset-<?= $id ?>" method="post"
                                          action="<?= base_url('admin/pengguna/' . $id . '/reset') ?>" hidden>
                                        <?= Csrf::field() ?>
                                    </form>

                                    <?php if ($isSelf): ?>
                                        <span class="icon-btn is-off" title="Akun Anda sendiri" aria-hidden="true">
                                            <span class="mi mi-sm"><?= $isActive ? 'toggle_on' : 'toggle_off' ?></span>
                                        </span>
                                    <?php else: ?>
                                        <button class="icon-btn <?= $isActive ? '' : 'is-danger' ?>" type="button"
                                                title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                                aria-label="<?= $isActive ? 'Nonaktifkan akun' : 'Aktifkan akun' ?>"
                                                data-confirm-toggle="<?= e((string) $row['name']) ?>"
                                                data-active="<?= $isActive ? '1' : '0' ?>"
                                                data-post-form="toggle-<?= $id ?>">
                                            <span class="mi mi-sm"><?= $isActive ? 'toggle_on' : 'toggle_off' ?></span>
                                        </button>
                                        <form id="toggle-<?= $id ?>" method="post"
                                              action="<?= base_url('admin/pengguna/' . $id . '/' . ($isActive ? 'nonaktifkan' : 'aktifkan')) ?>" hidden>
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="back" value="<?= e($backTo) ?>">
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!$isSelf): ?>
                                        <button class="icon-btn is-danger" type="button"
                                                title="Hapus" aria-label="Hapus pengguna"
                                                data-confirm-delete="<?= e((string) $row['name']) ?>"
                                                data-post-form="delete-<?= $id ?>">
                                            <span class="mi mi-sm">delete</span>
                                        </button>
                                        <form id="delete-<?= $id ?>" method="post"
                                              action="<?= base_url('admin/pengguna/' . $id . '/delete') ?>" hidden>
                                            <?= Csrf::field() ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <span class="muted">
                Menampilkan <?= $from ?>–<?= $to ?> dari <?= Format::number($total) ?> pengguna
            </span>

            <?php if ($pages > 1): ?>
                <nav class="pagination-inline" aria-label="Navigasi halaman">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="<?= $buildUrl(['halaman' => $page - 1]) ?>">Sebelumnya</a>
                    <?php else: ?>
                        <span class="page-btn is-disabled">Sebelumnya</span>
                    <?php endif; ?>

                    <?php
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

<?php if ($canWrite): ?>
    <div class="card card-flat" style="margin-top:var(--sp-4)">
        <div class="card-head">
            <div>
                <h2 class="card-title">Catatan Keamanan Akun</h2>
                <p class="card-sub">Berlaku otomatis, tidak perlu diatur</p>
            </div>
            <span class="mi" style="color:var(--green)">shield</span>
        </div>
        <ol class="security-notes">
            <li>Menonaktifkan akun langsung memutus sesi web yang sedang berjalan dan mencabut token aplikasi mobile.</li>
            <li>Setel ulang sandi menghasilkan sandi sementara acak yang wajib diperbarui saat login berikutnya.</li>
            <li>Akun terkunci otomatis selama <strong><?= e((string) Auth::setting('security_lockout_minutes', '15')) ?> menit</strong>
                setelah <strong><?= e((string) Auth::setting('security_max_login_attempts', '5')) ?> kali</strong> percobaan login gagal berturut-turut.</li>
        </ol>
    </div>
<?php endif; ?>

<script>
    (function () {
        function submitForm(id) {
            var form = document.getElementById(id);
            if (form) {
                form.removeAttribute('hidden');
                form.submit();
            }
        }

        // Aksi langsung (buka kunci, setel ulang sandi).
        document.querySelectorAll('[data-post-form]').forEach(function (button) {
            if (button.tagName !== 'BUTTON') {
                return;
            }

            button.addEventListener('click', function () {
                var id = button.getAttribute('data-post-form');
                var name = button.getAttribute('data-confirm-reset');

                if (name !== null) {
                    if (!window.confirm('Setel ulang sandi "' + name + '"?\n\nSandi lama langsung tidak berlaku dan sesi pengguna diputus.')) {
                        return;
                    }
                }

                var toggleName = button.getAttribute('data-confirm-toggle');
                if (toggleName !== null) {
                    var willActivate = button.getAttribute('data-active') === '0';
                    var message = willActivate
                        ? 'Aktifkan akun "' + toggleName + '"?'
                        : 'Nonaktifkan akun "' + toggleName + '"?\n\nSesi web dan token aplikasi miliknya langsung dicabut.';
                    if (!window.confirm(message)) {
                        return;
                    }
                }

                submitForm(id);
            });
        });

        document.querySelectorAll('[data-confirm-delete]').forEach(function (button) {
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-confirm-delete') || 'pengguna ini';
                if (!window.confirm('Hapus akun "' + name + '"?\n\nAkun tidak bisa dipakai lagi. Riwayat aktivitasnya tetap tersimpan.')) {
                    return;
                }
                submitForm(button.getAttribute('data-post-form'));
            });
        });
    })();
</script>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../../layouts/admin.php';
