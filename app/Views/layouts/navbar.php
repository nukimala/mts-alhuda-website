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
<header class="site-header">
    <div class="container navbar">
        <a href="<?= url('/') ?>" class="brand" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img src="<?= asset('assets/images/LOGO_MTS 1.png') ?>"
                 alt="Logo MTs Al-Huda"
                 class="brand-logo"
                 width="40"
                 height="40"
                 style="width: 40px; height: 40px; object-fit: contain;">
            <div>
                <span style="font-size: 15px; font-weight: 800; color: #0F172A; display: block; line-height: 1.2;">MTs Al-Huda</span>
                <span style="font-size: 11px; color: #64748B; font-weight: 600; letter-spacing: 0.04em;">Gondang • Nganjuk</span>
            </div>
        </a>

        <nav class="nav-menu" aria-label="Navigasi utama">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="<?= url($route) ?>"
                   class="<?= $activeRoot === $route ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="<?= url('ppdb') ?>" class="nav-cta" style="background: #15803D; color: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12.5px; text-decoration: none; box-shadow: 0 2px 6px rgba(21,128,61,.25);">
                Daftar PPDB Online
            </a>
            <button class="nav-toggle" type="button" aria-label="Buka menu navigasi" style="display: flex; align-items: center; justify-content: center; background: none; border: 1px solid #CBD5E1; border-radius: 6px; padding: 6px; cursor: pointer;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0F172A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
</header>
