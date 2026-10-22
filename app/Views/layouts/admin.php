<?php require_once __DIR__ . '/../../helpers.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($data['title'] ?? 'Panel Admin | MTs Al-Huda Gondang') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body>
<div class="shell">

    <aside class="sidebar">
        <div class="side-brand">
            <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTs Al-Huda">
            <div>
                <b>MTs Al-Huda</b>
                <span>Panel Admin</span>
            </div>
        </div>

        <nav class="side-nav">
            <div class="side-group">Utama</div>
            <a class="side-link on" href="<?= base_url('admin') ?>">
                <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
                Beranda
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5M13 16V8M18 16v-3"/></svg>
                Laporan
            </a>

            <div class="side-group">Konten Website</div>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
                Berita
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg>
                Galeri
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M6 21V8l6-4 6 4v13"/><path d="M10 21v-5h4v5"/></svg>
                Fasilitas
            </a>

            <div class="side-group">Layanan</div>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11h5M19.5 8.5v5"/></svg>
                Pengguna &amp; Role
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/></svg>
                Info &amp; Dokumen PPDB
            </a>

            <div class="side-group">Akademik</div>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                Jadwal Pelajaran
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Presensi Guru
            </a>

            <div class="side-group">Akreditasi</div>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="5.5"/><path d="m8.5 14-1.5 7 5-2.5 5 2.5-1.5-7"/></svg>
                Dashboard Kesiapan
            </a>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/></svg>
                Bank Bukti
            </a>

            <div class="side-group">Sistem</div>
            <a class="side-link disabled" href="#" aria-disabled="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                Pengaturan Situs
            </a>
        </nav>

        <div class="side-foot">Panel Admin v1.0</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="top-user">
                <div class="avatar"><?= htmlspecialchars(strtoupper(mb_substr((string) ($data['user']['name'] ?? 'A'), 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                    <b><?= htmlspecialchars((string) ($data['user']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></b>
                    <span><?= htmlspecialchars((string) ($data['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="spacer"></div>

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
