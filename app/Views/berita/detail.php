<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$post = $data['post'] ?? [];
$otherPosts = $data['other_posts'] ?? [];
?>

<main>
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Warta &amp; Berita Madrasah
            </div>
            <h1 style="color: #ffffff; font-size: 28px; line-height: 1.3; font-weight: 800; max-width: 820px; margin-bottom: 14px;">
                <?= htmlspecialchars($post['title'] ?? 'Detail Berita', ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <div style="display: flex; align-items: center; gap: 14px; font-size: 12.5px; color: rgba(255,255,255,.8); flex-wrap: wrap;">
                <span>📅 <?= !empty($post['published_at']) ? date('d F Y', strtotime($post['published_at'])) : date('d F Y') ?></span>
                <span>•</span>
                <span>✍️ Oleh <?= htmlspecialchars($post['author_name'] ?? 'Humas MTs Al-Huda', ENT_QUOTES, 'UTF-8') ?></span>
                <span>•</span>
                <span>👁️ <?= (int) ($post['views'] ?? 0) ?> Kali Dilihat</span>
            </div>
        </div>
    </section>

    <section class="page-section" style="padding: 40px 0 60px;">
        <div class="container" style="display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 36px; align-items: start;">
            <!-- Konten Utama Berita -->
            <article class="article-body" style="background: #ffffff; border-radius: 12px; border: 1px solid #E2E8F0; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,.04);">
                <?php if (!empty($post['cover_path'])): ?>
                    <div style="margin-bottom: 24px; border-radius: 10px; overflow: hidden; max-height: 420px;">
                        <img src="<?= base_url($post['cover_path']) ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: auto; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <?php if (!empty($post['excerpt'])): ?>
                    <div style="font-size: 15px; font-weight: 600; color: #334155; line-height: 1.6; margin-bottom: 20px; padding-left: 14px; border-left: 4px solid #15803D;">
                        <?= htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div style="font-size: 14.5px; line-height: 1.8; color: #1E293B;">
                    <?= nl2br(htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                </div>

                <div style="margin-top: 36px; padding-top: 20px; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
                    <a class="btn btn-outline" href="<?= url('berita') ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 600;">
                        ← Kembali ke Berita
                    </a>
                </div>
            </article>

            <!-- Sidebar Berita Terkini -->
            <aside>
                <div style="background: #ffffff; border-radius: 12px; border: 1px solid #E2E8F0; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.04);">
                    <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 16px; border-bottom: 2px solid #15803D; padding-bottom: 8px;">
                        Berita Lainnya
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($otherPosts as $op): ?>
                            <a href="<?= url('berita/' . $op['slug']) ?>" style="text-decoration: none; display: flex; gap: 10px; align-items: flex-start; group">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 700; color: #1E293B; line-height: 1.35; margin-bottom: 4px;">
                                        <?= htmlspecialchars($op['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <span style="font-size: 11px; color: #64748B;">
                                        <?= !empty($op['published_at']) ? date('d M Y', strtotime($op['published_at'])) : '' ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
