<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../Support/Content.php';

$address = Content::setting('contact_address', 'Gondang, Kabupaten Tulungagung, Jawa Timur');
$phone = Content::setting('contact_phone');
$email = Content::setting('contact_email');
$hours = Content::setting('contact_hours', 'Senin - Sabtu, 07.00 - 15.10 WIB');
$wa = Content::whatsapp();

$socials = [
    'social_facebook' => ['Facebook', 'public'],
    'social_instagram' => ['Instagram', 'photo_camera'],
    'social_youtube' => ['YouTube', 'smart_display'],
    'social_tiktok' => ['TikTok', 'music_note'],
];

$footerMenu = Content::menus('utama');
if ($footerMenu === []) {
    $footerMenu = [
        ['label' => 'Beranda', 'url' => ''],
        ['label' => 'Profil Madrasah', 'url' => 'profil'],
        ['label' => 'Fasilitas', 'url' => 'fasilitas'],
        ['label' => 'Galeri Foto', 'url' => 'galeri'],
        ['label' => 'Berita', 'url' => 'berita'],
    ];
}
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="">
                    <span><?= e(Content::setting('site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang')) ?></span>
                </div>
                <p class="footer-about">
                    Madrasah terpadu yang memadukan keunggulan ilmu keislaman, kurikulum nasional,
                    dan penguatan karakter santri berbasis adab.
                </p>
            </div>

            <div>
                <h4 class="footer-title">Tautan Cepat</h4>
                <div class="footer-links">
                    <?php foreach (array_slice($footerMenu, 0, 5) as $menu): ?>
                        <a href="<?= base_url((string) $menu['url']) ?>"><?= e((string) $menu['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h4 class="footer-title">Program</h4>
                <div class="footer-links">
                    <a href="<?= base_url('akademik') ?>">Tahfidz Al-Qur'an</a>
                    <a href="<?= base_url('akademik') ?>">Bahasa Arab</a>
                    <a href="<?= base_url('akademik') ?>">Sains &amp; Olimpiade</a>
                    <a href="<?= base_url('akademik') ?>">Seni &amp; Budaya</a>
                    <a href="<?= base_url('ppdb') ?>">Informasi PPDB</a>
                </div>
            </div>

            <div>
                <h4 class="footer-title">Kontak &amp; Layanan</h4>
                <div class="footer-links">
                    <span><span class="mi mi-sm">location_on</span> <?= e($address) ?></span>
                    <?php if ($phone !== ''): ?>
                        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>">
                            <span class="mi mi-sm">call</span> <?= e($phone) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <a href="mailto:<?= e($email) ?>">
                            <span class="mi mi-sm">mail</span> <?= e($email) ?>
                        </a>
                    <?php endif; ?>
                    <span><span class="mi mi-sm">schedule</span> <?= e($hours) ?></span>

                    <?php if ($wa !== ''): ?>
                        <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">
                            <span class="mi mi-sm">chat</span> WhatsApp
                        </a>
                    <?php endif; ?>

                    <?php foreach ($socials as $key => [$label, $icon]): ?>
                        <?php $link = Content::setting($key); ?>
                        <?php if ($link !== ''): ?>
                            <a href="<?= e($link) ?>" target="_blank" rel="noopener">
                                <span class="mi mi-sm"><?= e($icon) ?></span> <?= e($label) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(Content::setting('site_full_name', 'MTs Al-Huda Gondang')) ?>. Hak cipta dilindungi.</span>
            <span>
                <?php $grade = Content::setting('accreditation_grade'); ?>
                <?php if ($grade !== ''): ?>
                    Terakreditasi <?= e($grade) ?> &middot;
                <?php endif; ?>
                <a href="<?= base_url('admin/login') ?>">Panel Admin</a>
            </span>
        </div>
    </div>
</footer>

<script src="<?= base_url('js/script.js') ?>" defer></script>
</body>
</html>
