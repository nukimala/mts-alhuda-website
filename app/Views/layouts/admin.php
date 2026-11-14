<?php require_once __DIR__ . '/../../helpers.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($data['title'] ?? 'Panel Admin | MTS Al-Huda Gondang') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin.css?v=' . (file_exists(__DIR__ . '/../../../public/css/admin.css') ? filemtime(__DIR__ . '/../../../public/css/admin.css') : '1.1')) ?>">
</head>
<body>
<div class="shell">

    <aside class="sidebar">
        <div class="side-brand">
            <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTS Al-Huda Gondang">
            <div>
                <b>MTS Al-Huda Gondang</b>
                <span>Panel Administrasi</span>
            </div>
        </div>

        <?php
        $currRoute = current_route();
        $routeParts = $currRoute === '' ? [] : explode('/', $currRoute);
        $currPage = $routeParts[1] ?? 'dashboard';
        $currSub = $routeParts[2] ?? '';
        $activeMenu = $data['active_menu'] ?? ($currPage === '' ? 'dashboard' : $currPage);
        $activeSub = $data['sub_menu'] ?? $currSub;
        ?>
        <nav class="side-nav">
            <div class="side-group">Utama</div>
            <a class="side-link <?= in_array($activeMenu, ['dashboard', 'admin', ''], true) ? 'on' : '' ?>" href="<?= base_url('admin') ?>">
                <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
                Beranda
            </a>
            <a class="side-link <?= $activeMenu === 'laporan' ? 'on' : '' ?>" href="<?= base_url('admin/laporan') ?>">
                <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5M13 16V8M18 16v-3"/></svg>
                Laporan
            </a>

            <div class="side-group">Konten Website</div>
            <a class="side-link <?= $activeMenu === 'berita' ? 'on' : '' ?>" href="<?= base_url('admin/berita') ?>">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
                Berita
            </a>
            <a class="side-link <?= $activeMenu === 'galeri' ? 'on' : '' ?>" href="<?= base_url('admin/galeri') ?>">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg>
                Galeri
            </a>
            <a class="side-link <?= $activeMenu === 'fasilitas' ? 'on' : '' ?>" href="<?= base_url('admin/fasilitas') ?>">
                <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M6 21V8l6-4 6 4v13"/><path d="M10 21v-5h4v5"/></svg>
                Fasilitas
            </a>
            <a class="side-link <?= $activeMenu === 'kontak' ? 'on' : '' ?>" href="<?= base_url('admin/kontak') ?>">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Kotak Masuk
            </a>

            <div class="side-group">Layanan</div>
            <a class="side-link <?= $activeMenu === 'pengguna' ? 'on' : '' ?>" href="<?= base_url('admin/pengguna') ?>">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11h5M19.5 8.5v5"/></svg>
                Pengguna &amp; Role
            </a>
            <a class="side-link <?= $activeMenu === 'ppdb' ? 'on' : '' ?>" href="<?= base_url('admin/ppdb') ?>">
                <svg viewBox="0 0 24 24"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/></svg>
                Info &amp; Dokumen PPDB
            </a>

            <div class="side-group">Akademik</div>
            <a class="side-link <?= $activeMenu === 'jadwal' ? 'on' : '' ?>" href="<?= base_url('admin/jadwal') ?>">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                Jadwal Pelajaran
            </a>
            <a class="side-link <?= $activeMenu === 'presensi' ? 'on' : '' ?>" href="<?= base_url('admin/presensi') ?>">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Presensi Guru
            </a>

            <div class="side-group">Sistem</div>
            <a class="side-link <?= $activeMenu === 'pengaturan' ? 'on' : '' ?>" href="<?= base_url('admin/pengaturan') ?>">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                Pengaturan Situs
            </a>
        </nav>

        <div class="side-foot">Panel Admin v1.0</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="pill green plain" style="font-size: 10.5px; font-weight: 700; padding: 2px 7px;">Madrasah</span>
                <span style="font-weight: 800; font-size: 14.5px; color: var(--text);">MTS Al-Huda Gondang</span>
                <a href="<?= base_url() ?>" target="_blank" class="btn btn-ghost btn-xs" style="color: var(--gray); font-size: 11.5px; padding: 3px 8px; text-decoration: none;" title="Buka Website Publik">
                    Lihat Situs ↗
                </a>
            </div>

            <div class="spacer"></div>

            <div class="top-user">
                <div class="avatar"><?= htmlspecialchars(strtoupper(mb_substr((string) ($data['user']['name'] ?? 'A'), 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                    <b><?= htmlspecialchars((string) ($data['user']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></b>
                    <span><?= htmlspecialchars((string) ($data['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <a class="btn btn-ghost" href="<?= base_url('admin/ganti-sandi') ?>">Ganti Sandi</a>

            <form method="post" action="<?= base_url('admin/logout') ?>" style="margin:0">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-outline">Keluar</button>
            </form>
        </header>

        <div class="content">
            <?php if (!empty($data['flash'])): ?>
                <div class="notice ok" style="margin-bottom:16px">
                    <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
                    <div><?= htmlspecialchars((string) $data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?= $data['content'] ?>
        </div>
    </div>
</div>
</body>
</html>
