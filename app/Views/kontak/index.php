<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$address = Content::setting('contact_address', 'Gondang, Kabupaten Tulungagung, Jawa Timur');
$phone = Content::setting('contact_phone');
$email = Content::setting('contact_email');
$hours = Content::setting('contact_hours', 'Senin - Sabtu, 07.00 - 15.10 WIB');
$wa = Content::whatsapp();
$lat = Content::setting('contact_map_lat');
$lng = Content::setting('contact_map_lng');

$socials = [
    'social_facebook' => ['Facebook', 'public'],
    'social_instagram' => ['Instagram', 'photo_camera'],
    'social_youtube' => ['YouTube', 'smart_display'],
    'social_tiktok' => ['TikTok', 'music_note'],
];

$sent = (bool) ($data['sent'] ?? false);
$errors = $data['errors'] ?? [];
$old = $data['old'] ?? [];
?>

<main>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= base_url('') ?>">Beranda</a>
                <span class="sep">/</span>
                <span class="current">Kontak</span>
            </nav>
            <h1>Kontak &amp; Layanan Informasi</h1>
            <p>Hubungi kami untuk pertanyaan seputar pendaftaran, program, atau kerja sama.</p>
        </div>
    </section>

    <section class="section">
        <div class="container contact-grid">
            <div>
                <span class="eyebrow">Sekretariat</span>
                <h2 style="font-size:var(--fs-sec);margin-bottom:var(--sp-4)">Alamat &amp; Jam Layanan</h2>

                <div class="contact-item">
                    <span class="mi">location_on</span>
                    <div>
                        <b>Alamat</b>
                        <span><?= e($address) ?></span>
                    </div>
                </div>

                <?php if ($phone !== ''): ?>
                <div class="contact-item">
                    <span class="mi">call</span>
                    <div>
                        <b>Telepon</b>
                        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($wa !== ''): ?>
                <div class="contact-item">
                    <span class="mi">chat</span>
                    <div>
                        <b>WhatsApp</b>
                        <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">Kirim pesan WhatsApp</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($email !== ''): ?>
                <div class="contact-item">
                    <span class="mi">mail</span>
                    <div>
                        <b>Email</b>
                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="contact-item">
                    <span class="mi">schedule</span>
                    <div>
                        <b>Jam Operasional</b>
                        <span><?= e($hours) ?></span>
                    </div>
                </div>

                <?php if ($lat !== '' && $lng !== ''): ?>
                <div class="contact-item">
                    <span class="mi">map</span>
                    <div>
                        <b>Peta Lokasi</b>
                        <a href="https://www.google.com/maps?q=<?= e($lat) ?>,<?= e($lng) ?>" target="_blank" rel="noopener">
                            Buka di Google Maps
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;margin-top:var(--sp-5)">
                    <?php foreach ($socials as $key => [$label, $icon]): ?>
                        <?php $link = Content::setting($key); ?>
                        <?php if ($link !== ''): ?>
                            <a class="btn btn-secondary btn-sm" href="<?= e($link) ?>" target="_blank" rel="noopener">
                                <span class="mi mi-sm"><?= e($icon) ?></span> <?= e($label) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card card-pad-lg">
                <h2 class="card-title" style="font-size:var(--fs-sec);margin-bottom:6px">Kirim Pesan</h2>
                <p style="font-size:var(--fs-sm);color:var(--gray);margin-bottom:var(--sp-5)">
                    Isi formulir di bawah, admin madrasah akan membalas melalui kontak yang Anda tuliskan.
                </p>

                <form method="post" action="<?= base_url('kontak') ?>" novalidate>
                    <input type="hidden" name="_csrf" value="<?= e($data['csrf'] ?? Csrf::token()) ?>">

                    <?php if ($sent): ?>
                        <div class="notice notice-success">
                            <span class="mi mi-sm">check_circle</span>
                            <span>Terima kasih. Pesan Anda sudah kami terima dan akan dibalas melalui kontak yang Anda tuliskan.</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="notice notice-danger">
                            <span class="mi mi-sm">error</span>
                            <span>
                                <?php foreach ($errors as $i => $error): ?>
                                    <?= $i > 0 ? '<br>' : '' ?><?= e($error) ?>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="field-row">
                        <div class="field">
                            <label for="nama">Nama lengkap</label>
                            <input type="text" id="nama" name="nama" placeholder="Nama Anda" required maxlength="120"
                                   value="<?= e($old['name'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="telepon">Nomor WhatsApp</label>
                            <input type="tel" id="telepon" name="telepon" placeholder="08xxxxxxxxxx" required maxlength="30"
                                   value="<?= e($old['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">Email <span class="muted" style="font-weight:600">(opsional)</span></label>
                        <input type="email" id="email" name="email" placeholder="nama@email.com" maxlength="160"
                               value="<?= e($old['email'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label for="topik">Topik</label>
                        <?php $currentTopic = $old['topic'] ?? 'ppdb'; ?>
                        <select id="topik" name="topik">
                            <option value="ppdb" <?= $currentTopic === 'ppdb' ? 'selected' : '' ?>>Pendaftaran / PPDB</option>
                            <option value="akademik" <?= $currentTopic === 'akademik' ? 'selected' : '' ?>>Akademik</option>
                            <option value="fasilitas" <?= $currentTopic === 'fasilitas' ? 'selected' : '' ?>>Fasilitas</option>
                            <option value="lainnya" <?= $currentTopic === 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="pesan">Pesan</label>
                        <textarea id="pesan" name="pesan" rows="5" placeholder="Tulis pertanyaan Anda..." required maxlength="2000"><?= e($old['message'] ?? '') ?></textarea>
                        <p class="field-hint">Maksimal 2000 karakter.</p>
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">
                        <span class="mi mi-sm">send</span> Kirim Pesan
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
