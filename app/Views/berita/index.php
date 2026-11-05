<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$posts = $data['posts'] ?? [];
$q = $data['q'] ?? '';
$page = $data['page'] ?? 1;
$totalPages = $data['total_pages'] ?? 1;
?>

<main>
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Pusat Informasi &amp; Warta
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">Kabar Terbaru dari <span>Madrasah</span></h1>
            <p style="color: rgba(255,255,255,.85); max-width: 640px; font-size: 14.5px;">
                Dokumentasi kegiatan, prestasi siswa, agenda penting, dan pengumuman resmi MTs Al-Huda Gondang.
            </p>
        </div>
    </section>

    <section class="page-section" style="padding: 40px 0 60px;">
        <div class="container">
            <!-- Search Bar -->
            <form method="get" action="<?= url('berita') ?>" style="margin-bottom: 30px; display: flex; gap: 10px; max-width: 480px;">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari berita atau pengumuman..." style="flex: 1; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13.5px; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: 8px; font-weight: 700;">Cari</button>
                <?php if ($q !== ''): ?>
                    <a href="<?= url('berita') ?>" class="btn btn-outline" style="padding: 10px 14px; border-radius: 8px;">Reset</a>
                <?php endif; ?>
            </form>

            <?php if (empty($posts)): ?>
                <div style="padding: 50px 20px; text-align: center; color: #64748B; background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0;">
                    <p style="font-size: 15px; font-weight: 700; color: #1E293B; margin-bottom: 6px;">Belum ada berita yang ditemukan.</p>
                    <p style="font-size: 13px;">Silakan coba kata kunci pencarian yang lain.</p>
                </div>
            <?php else: ?>
                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
                    <?php foreach ($posts as $item): ?>
                        <article class="info-card" style="background: #ffffff; border-radius: 12px; border: 1px solid #E2E8F0; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 6px rgba(0,0,0,.04); transition: transform .2s ease, box-shadow .2s ease;">
                            <div style="height: 190px; background: #E2E8F0; position: relative; overflow: hidden;">
                                <?php if (!empty($item['cover_path'])): ?>
                                    <img src="<?= base_url($item['cover_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #15803D 0%, #0B3D1E 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 18px;">
                                        MTs Al-Huda
                                    </div>
                                <?php endif; ?>
                                <?php if ((int) ($item['is_featured'] ?? 0) === 1): ?>
                                    <span style="position: absolute; top: 10px; left: 10px; background: #2563EB; color: #fff; font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
                                        ★ Unggulan
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                                <div style="font-size: 12px; color: #64748B; margin-bottom: 8px;">
                                    📅 <?= !empty($item['published_at']) ? date('d M Y', strtotime($item['published_at'])) : date('d M Y') ?>
                                </div>
                                <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; line-height: 1.4; margin-bottom: 8px;">
                                    <a href="<?= url('berita/' . $item['slug']) ?>" style="text-decoration: none; color: inherit;">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>
                                <p style="font-size: 13px; color: #475569; line-height: 1.55; margin-bottom: 16px; flex: 1;">
                                    <?= htmlspecialchars($item['excerpt'] ?? mb_substr(strip_tags($item['content']), 0, 100) . '...') ?>
                                </p>
                                <a href="<?= url('berita/' . $item['slug']) ?>" style="color: #15803D; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    Baca Selengkapnya <span>›</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="margin-top: 36px; display: flex; justify-content: center; gap: 8px;">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="<?= url('berita?page=' . $p . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>" style="padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #CBD5E1; <?= $p === $page ? 'background:#15803D;color:#fff;border-color:#15803D' : 'background:#fff;color:#475569' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
