<?php
$current = current_route();
$activeRoot = $current === '' ? '' : explode('/', $current)[0];

$navItems = [
    '' => 'Beranda',
    'profil' => 'Profil',
    'akademik' => 'Akademik & Jadwal',
    'fasilitas' => 'Fasilitas',
    'berita' => 'Berita',
    'galeri' => 'Galeri',
    'kontak' => 'Kontak',
];
?>
<!-- TOPBAR RESMI MTS AL-HUDA GONDANG -->
<div class="site-topbar">
    <div class="container site-topbar-inner">
        <div class="site-topbar-left">
            <span class="topbar-item topbar-address">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Jln. Letjen Soeprapto No.16, Ds. Pandean, Kec. Gondang, Kab. Nganjuk</span>
            </span>
            <span class="topbar-sep">•</span>
            <span class="topbar-item topbar-akreditasi">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <span>NSM: 121235180039 &middot; Terakreditasi "A" BAN-PDM</span>
            </span>
        </div>
        <div class="site-topbar-right">
            <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="topbar-link">
                <span>💬 WhatsApp</span>
            </a>
            <span class="topbar-sep">•</span>
            <a href="<?= url('admin/login') ?>" class="topbar-portal">Portal Masuk</a>
        </div>
    </div>
</div>

<header class="site-header">
    <div class="container navbar">
        <a href="<?= url('/') ?>" class="brand brand-logo-link">
            <img src="<?= asset('assets/images/LOGO_MTS 1.png') ?>"
                 alt="Logo MTS Al-Huda Gondang"
                 class="brand-logo"
                 width="42"
                 height="42">
            <div>
                <span class="brand-title">MTS Al-Huda Gondang</span>
                <span class="brand-subtitle">Madrasah Tsanawiyah Terakreditasi A &middot; Nganjuk</span>
            </div>
        </a>

        <nav class="nav-menu" aria-label="Navigasi utama">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="<?= url($route) ?>"
                   class="<?= $activeRoot === $route ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-group">
            <a href="<?= url('ppdb') ?>" class="nav-cta nav-cta-custom">
                Daftar PPDB Online
            </a>
            <button class="nav-toggle nav-toggle-custom" type="button" aria-label="Buka menu navigasi">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
</header>