<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../Support/Content.php';

$current = current_route();
$activeRoot = $current === '' ? '' : explode('/', $current)[0];

// Menu diambil dari database. Kalau admin belum mengisi menu sama sekali,
// dipakai daftar bawaan supaya navigasi tidak hilang.
$navItems = [];
foreach (Content::menus('utama') as $menu) {
    $navItems[] = [
        'url' => (string) $menu['url'],
        'label' => (string) $menu['label'],
        'target' => $menu['target'] === 'blank' ? '_blank' : '_self',
    ];
}

if ($navItems === []) {
    foreach ([
        '' => 'Beranda',
        'profil' => 'Profil',
        'akademik' => 'Akademik',
        'fasilitas' => 'Fasilitas',
        'galeri' => 'Galeri',
        'berita' => 'Berita',
        'ppdb' => 'PPDB',
        'kontak' => 'Kontak',
    ] as $url => $label) {
        $navItems[] = ['url' => $url, 'label' => $label, 'target' => '_self'];
    }
}
?>
<header class="site-header">
    <div class="container navbar">
        <a class="brand" href="<?= base_url('') ?>">
            <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo <?= e(Content::setting('site_name', 'MTs Al-Huda')) ?>">
            <span class="brand-text">
                <b><?= e(Content::setting('site_name', 'MTs Al-Huda')) ?></b>
                <span><?= e(Content::setting('site_subtitle', 'Gondang')) ?></span>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Buka menu" aria-expanded="false" data-nav-toggle>
            <span class="mi">menu</span>
        </button>

        <nav class="nav-links" id="nav-links" aria-label="Navigasi utama">
            <?php foreach ($navItems as $item): ?>
                <?php
                $itemRoot = $item['url'] === '' ? '' : explode('/', trim($item['url'], '/'))[0];
                $isActive = $itemRoot === $activeRoot;
                ?>
                <a href="<?= base_url($item['url']) ?>"
                   target="<?= e($item['target']) ?>"
                   class="<?= $isActive ? 'is-active' : '' ?>"
                   <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>

            <a class="btn btn-primary btn-sm nav-cta" href="<?= base_url('ppdb') ?>">
                <span class="mi mi-sm">how_to_reg</span> Daftar PPDB
            </a>
        </nav>
    </div>
</header>
