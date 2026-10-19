<?php
$current = current_route();
$activeRoot = $current === '' ? '' : explode('/', $current)[0];

$navItems = [
    '' => 'Beranda',
    'profil' => 'Profil',
    'akademik' => 'Akademik',
    'fasilitas' => 'Fasilitas',
    'galeri' => 'Galeri',
    'berita' => 'Berita',
    'informasi' => 'Informasi',
    'kontak' => 'Kontak',
];
?>
<header class="site-header">
    <div class="container navbar">
        <a href="<?= url('/') ?>" class="brand">
            <img src="<?= asset('assets/images/LOGO_MTS 1.png') ?>"
                 alt="Logo MTs Al-Huda"
                 class="brand-logo">
            <span>MTs Al-Huda</span>
        </a>

        <nav class="nav-menu" aria-label="Navigasi utama">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="<?= url($route) ?>"
                   class="<?= $activeRoot === $route ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <a href="<?= url('ppdb') ?>" class="nav-cta">Info PPDB</a>
        <button class="nav-toggle" type="button" aria-label="Buka menu">&#9776;</button>
    </div>
</header>
