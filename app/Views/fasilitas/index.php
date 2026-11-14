<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$facilities = $data['facilities'] ?? [];
?>

<main>
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Standar Sarana &amp; Prasarana (IA2024)
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">
                Fasilitas &amp; Lingkungan <span>Belajar</span>
            </h1>
            <p style="color: rgba(255,255,255,.85); max-width: 680px; font-size: 14.5px; line-height: 1.6;">
                Sarana prasarana terstandarisasi yang bersih, representatif, dan aman untuk menjamin kenyamanan belajar, ibadah, dan pengembangan bakat santri MTs Al-Huda Gondang.
            </p>
        </div>
    </section>

    <section class="page-section" style="padding: 50px 0 70px; background: #F8FAFC;">
        <div class="container">
            <!-- Highlight Box Standar Sarpras -->
            <div style="background: #ffffff; border-radius: 14px; border: 1px solid #E2E8F0; padding: 24px 28px; margin-bottom: 36px; box-shadow: 0 2px 8px rgba(0,0,0,.04); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">
                        Komitmen Sarpras Ramah Anak &amp; Berkelanjutan
                    </h3>
                    <p style="font-size: 13px; color: #64748B; margin: 0;">
                        Seluruh gedung dan ruang madrasah terawat, memiliki ventilasi alami yang cukup, serta didukung koneksi internet untuk pembelajaran era digital.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <span style="background: #DCFCE7; color: #15803D; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px;">
                        ✓ 100% Layak Pakai
                    </span>
                    <span style="background: #EFF6FF; color: #1D4ED8; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px;">
                        ★ Terakreditasi A
                    </span>
                </div>
            </div>

            <!-- Grid Kartu Fasilitas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 26px;">
                <?php if (empty($facilities)): ?>
                    <p style="color: #64748B;">Belum ada data fasilitas.</p>
                <?php else: ?>
                    <?php foreach ($facilities as $fac): ?>
                        <div style="background: #ffffff; border-radius: 14px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.05); display: flex; flex-direction: column;">
                            <div style="height: 200px; background: #E2E8F0; position: relative; overflow: hidden;">
                                <?php $facImage = $fac['image_path'] ?? $fac['gambar'] ?? ''; ?>
                                <?php if (!empty($facImage)): ?>
                                    <img src="<?= base_url($facImage) ?>" alt="<?= htmlspecialchars($fac['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg, #15803D 0%, #0B3D1E 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px">
                                        <span style="font-size: 32px; margin-bottom: 4px;">🏛️</span>
                                        <b style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;">Sarana MTS Al-Huda Gondang</b>
                                    </div>
                                <?php endif; ?>
                                
                                <span style="position: absolute; top: 12px; left: 12px; background: #15803D; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.15);">
                                    <?= htmlspecialchars($fac['category'] ?? $fac['icon'] ?? 'Gedung / Sarpras') ?>
                                </span>
                            </div>

                            <div style="padding: 22px; flex: 1; display: flex; flex-direction: column;">
                                <h3 style="font-size: 17px; font-weight: 800; color: #0F172A; margin-bottom: 8px; line-height: 1.35;">
                                    <?= htmlspecialchars($fac['name']) ?>
                                </h3>
                                <p style="font-size: 13.5px; color: #475569; line-height: 1.6; margin: 0; flex: 1;">
                                    <?= htmlspecialchars($fac['deskripsi'] ?? $fac['description'] ?? 'Fasilitas penunjang pembelajaran santri MTs Al-Huda Gondang.') ?>
                                </p>
                            </div>

                            <div style="padding: 12px 22px; background: #F8FAFC; border-top: 1px solid #E2E8F0; font-size: 12px; color: #15803D; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
                                <span>Status: Siap Digunakan</span>
                                <span>Prioritas #<?= (int) ($fac['sort_order'] ?? 1) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
