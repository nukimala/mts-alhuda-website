<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$posts = $data['posts'] ?? [];
$total = (int) ($data['total'] ?? 0);
$page = (int) ($data['page'] ?? 1);
$pages = (int) ($data['pages'] ?? 1);

$tanggal = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($value);
    return $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
};
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Berita</span>
            </nav>
            <h1>Berita &amp; Artikel</h1>
            <p>Kabar terbaru seputar kegiatan, prestasi, dan program madrasah.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if ($posts === []): ?>
                <div class="empty">
                    <p class="empty-title">Belum ada berita terbit</p>
                    <p class="empty-text">Berita akan tampil di sini setelah dipublikasikan dari panel admin.</p>
                </div>
            <?php else: ?>
                <div class="section-head-row">
                    <div class="section-head" style="margin-bottom:0">
                        <span class="eyebrow">Kabar Madrasah</span>
                        <h2><?= $total ?> Berita Terbit</h2>
                    </div>
                    <?php if ($pages > 1): ?>
                        <span class="pill">Halaman <?= $page ?> dari <?= $pages ?></span>
                    <?php endif; ?>
                </div>

                <div class="grid-3">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <a class="post-cover" href="<?= base_url('berita/' . $post['slug']) ?>">
                                <img src="<?= e($post['cover_path'] ? base_url($post['cover_path']) : base_url('assets/images/Belajar di Perpustakaan.jpg')) ?>"
                                     alt="<?= e($post['title']) ?>" loading="lazy">
                            </a>
                            <div class="post-body">
                                <div class="post-meta">
                                    <span class="mi mi-sm">calendar_today</span>
                                    <?= e($tanggal($post['published_at'] ?? null)) ?>
                                    <?php if ((int) ($post['is_featured'] ?? 0) === 1): ?>
                                        <span class="pill pill-green" style="margin-left:auto">Unggulan</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="post-title">
                                    <a href="<?= base_url('berita/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                                </h3>
                                <p class="post-excerpt"><?= e($post['excerpt'] ?? '') ?></p>
                                <a class="post-more" href="<?= base_url('berita/' . $post['slug']) ?>">
                                    Baca selengkapnya <span class="mi mi-sm">arrow_forward</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($pages > 1): ?>
                    <nav class="pagination" aria-label="Navigasi halaman">
                        <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>"
                           href="<?= base_url('berita?halaman=' . max(1, $page - 1)) ?>">
                            <span class="mi mi-sm">chevron_left</span>
                        </a>

                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="is-active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= base_url('berita?halaman=' . $i) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <a class="<?= $page >= $pages ? 'is-disabled' : '' ?>"
                           href="<?= base_url('berita?halaman=' . min($pages, $page + 1)) ?>">
                            <span class="mi mi-sm">chevron_right</span>
                        </a>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
