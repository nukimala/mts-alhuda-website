<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../Support/Content.php';

$userName = (string) ($data['user']['name'] ?? 'Administrator');
$roleLabel = (string) ($data['role'] ?? 'Admin');
$initial = strtoupper(mb_substr($userName !== '' ? $userName : 'A', 0, 2));
$current = current_route();
$activeRoot = $current === '' ? '' : explode('/', $current)[1] ?? '';

/**
 * Satu item menu sidebar. $enabled=false berarti modulnya belum dibangun:
 * ditampilkan redup dan tidak bisa diklik, supaya tidak jadi tautan mati.
 */
$menuItem = static function (string $key, string $label, string $icon, bool $enabled = false) use ($activeRoot): string {
    $isActive = $enabled && $activeRoot === $key;
    $class = 'side-link' . ($isActive ? ' on' : '') . ($enabled ? '' : ' is-locked');
    $href = $enabled ? base_url('admin/' . $key) : '#';
    $attrs = $enabled ? '' : ' aria-disabled="true" tabindex="-1"';

    return '<a class="' . $class . '" href="' . $href . '"' . $attrs . '>'
        . '<span class="mi">' . $icon . '</span>'
        . '<span>' . e($label) . '</span>'
        . ($enabled ? '' : '<span class="count">Soon</span>')
        . '</a>';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($data['title'] ?? 'Panel Admin | MTs Al-Huda Gondang') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    <link rel="stylesheet" href="<?= base_url('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">

    <aside class="sidebar" id="sidebar">
        <div class="side-brand">
            <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTs Al-Huda">
            <div>
                <b><?= e(Content::setting('site_name', 'MTs Al-Huda')) ?></b>
                <span>Panel Admin</span>
            </div>
        </div>

        <nav class="side-nav">
            <div class="side-group">Utama</div>
            <?= $menuItem('', 'Beranda', 'dashboard', true) ?>

            <div class="side-group">Konten Website</div>
            <?= $menuItem('berita', 'Berita', 'article', true) ?>
            <?= $menuItem('galeri', 'Galeri', 'photo_library') ?>
            <?= $menuItem('fasilitas', 'Fasilitas', 'apartment') ?>
            <?= $menuItem('halaman', 'Halaman Statis', 'description') ?>
            <?= $menuItem('menu', 'Menu Navigasi', 'menu_open') ?>

            <div class="side-group">Layanan</div>
            <?= $menuItem('ppdb', 'Info &amp; Dokumen PPDB', 'folder_shared') ?>
            <?= $menuItem('pesan', 'Pesan Masuk', 'mark_email_unread') ?>
            <?= $menuItem('pengguna', 'Pengguna &amp; Role', 'group', true) ?>

            <div class="side-group">Akademik</div>
            <?= $menuItem('jadwal', 'Jadwal Pelajaran', 'calendar_month') ?>
            <?= $menuItem('presensi', 'Presensi Guru', 'fact_check') ?>
            <?= $menuItem('siswa', 'Data Santri', 'school') ?>

            <div class="side-group">Akreditasi</div>
            <?= $menuItem('akreditasi', 'Kesiapan Akreditasi', 'verified') ?>
            <?= $menuItem('bukti', 'Bank Bukti', 'folder_copy') ?>

            <div class="side-group">Sistem</div>
            <?= $menuItem('pengaturan', 'Pengaturan Situs', 'settings') ?>
        </nav>

        <div class="side-foot">
            <a class="side-link" href="<?= base_url('') ?>" target="_blank" rel="noopener">
                <span class="mi">open_in_new</span>
                <span>Lihat Website</span>
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu" data-sidebar-toggle>
                <span class="mi">menu</span>
            </button>

            <div>
                <h1 class="topbar-title"><?= e($data['heading'] ?? 'Panel Admin') ?></h1>
                <p class="topbar-sub"><?= e(Content::setting('site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang')) ?></p>
            </div>

            <div class="topbar-actions">
                <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/ganti-sandi') ?>">
                    <span class="mi mi-sm">lock_reset</span> Ganti Sandi
                </a>

                <div class="topbar-user">
                    <div class="avatar"><?= e($initial) ?></div>
                    <div>
                        <b><?= e($userName) ?></b>
                        <small><?= e($roleLabel) ?></small>
                    </div>
                </div>

                <form method="post" action="<?= base_url('admin/logout') ?>" style="margin:0">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <span class="mi mi-sm">logout</span> Keluar
                    </button>
                </form>
            </div>
        </header>

        <div class="content">
            <?php if (!empty($data['flash'])): ?>
                <div class="notice notice-success">
                    <span class="mi mi-sm">check_circle</span>
                    <span><?= e((string) $data['flash']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['flashError'])): ?>
                <div class="notice notice-danger">
                    <span class="mi mi-sm">error</span>
                    <span><?= e((string) $data['flashError']) ?></span>
                </div>
            <?php endif; ?>

            <?= $data['content'] ?? '' ?>
        </div>
    </div>
</div>

<script>
    (function () {
        var toggle = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('is-open');
            });
        }
    })();
</script>
</body>
</html>
