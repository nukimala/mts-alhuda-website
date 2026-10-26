<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$post = $data['post'] ?? null;
$related = $data['related'] ?? [];

$tanggal = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($value);
    return $ts ? date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
};

if ($post === null) {
    return;
}

$cover = $post['cover_path']
    ? base_url($post['cover_path'])
    : base_url('assets/images/Belajar di Perpustakaan.jpg');
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <a href="<?= base_url('berita') ?>">Berita</a>
                <span class="sep">/</span>
                <span class="current"><?= e($post['title']) ?></span>
            </nav>
            <h1 style="max-width:34ch"><?= e($post['title']) ?></h1>
            <div class="post-meta" style="margin-top:var(--sp-3)">
                <span class="mi mi-sm">calendar_today</span>
                <?= e($tanggal($post['published_at'] ?? null)) ?>
                <span class="sep">&middot;</span>
                <span class="mi mi-sm">visibility</span>
                <?= (int) ($post['views'] ?? 0) ?> kali dibaca
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:var(--sp-8);align-items:start">
                <article>
                    <img class="article-cover" src="<?= e($cover) ?>" alt="<?= e($post['title']) ?>">

                    <?php if (!empty($post['excerpt'])): ?>
                        <p style="font-size:15px;line-height:1.7;color:var(--gray);font-weight:600;margin-bottom:var(--sp-5)">
                            <?= e($post['excerpt']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="article">
                        <?= $post['content'] ?>
                    </div>

                    <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;margin-top:var(--sp-8);padding-top:var(--sp-5);border-top:1px solid var(--border)">
                        <a class="btn btn-secondary" href="<?= base_url('berita') ?>">
                            <span class="mi mi-sm">arrow_back</span> Semua Berita
                        </a>
                        <?php $shareUrl = rawurlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')); ?>
                        <a class="btn btn-ghost" href="https://wa.me/?text=<?= $shareUrl ?>" target="_blank" rel="noopener">
                            <span class="mi mi-sm">share</span> Bagikan
                        </a>
                    </div>
                </article>

                <aside>
                    <?php if ($related !== []): ?>
                        <div class="card">
                            <div class="card-head">
                                <h2 class="card-title">Baca Juga</h2>
                            </div>
                            <div class="dash-list">
                                <?php foreach ($related as $item): ?>
                                    <div class="dash-row">
                                        <div>
                                            <b>
                                                <a href="<?= base_url('berita/' . $item['slug']) ?>" style="color:var(--text)">
                                                    <?= e($item['title']) ?>
                                                </a>
                                            </b>
                                            <small><?= e($tanggal($item['published_at'] ?? null)) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card" style="margin-top:var(--sp-4);background:var(--green-bg);border-color:var(--pale-green)">
                        <h2 class="card-title" style="margin-bottom:6px">Informasi PPDB</h2>
                        <p style="font-size:var(--fs-sm);color:var(--dark-green);margin:0 0 var(--sp-3)">
                            Pendaftaran santri baru dibuka. Lihat alur dan syaratnya.
                        </p>
                        <a class="btn btn-primary btn-sm btn-block" href="<?= base_url('ppdb') ?>">Buka Info PPDB</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
