<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$settings = $data['settings'] ?? [];
$phone = $settings['phone'] ?? $settings['contact_phone'] ?? '(0358) 123456';
$email = $settings['email'] ?? $settings['contact_email'] ?? 'info@mtsalhuda.sch.id';
$wa = $settings['whatsapp'] ?? $settings['contact_whatsapp'] ?? '0812-3456-7890';
$address = $settings['school_address'] ?? $settings['contact_address'] ?? 'Jl. Letjend Suprapto No. 16, Desa Pandean, Kec. Gondang, Nganjuk, Jawa Timur';
$old = $data['old'] ?? [];
?>

<main>
    <section class="page-hero-clean">
        <div class="container">
            <div class="hero-label">Layanan Informasi &amp; Komunikasi</div>
            <h1>Hubungi <span>MTs Al-Huda</span> Gondang</h1>
            <p>
                Kami siap membantu menjawab pertanyaan seputar program madrasah, kegiatan santri, akademik, serta pendaftaran siswa baru (PPDB).
            </p>
        </div>
    </section>

    <section style="padding: 56px 0 72px; background: #FFFFFF;">
        <div class="container">
            <?php if (!empty($data['flash'])): ?>
                <div style="background: #DCFCE7; border: 1px solid #BBF7D0; color: #15803D; padding: 16px 20px; border-radius: 12px; margin-bottom: 28px; font-size: 14.5px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">✓</span>
                    <div><?= htmlspecialchars($data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['error'])): ?>
                <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 28px; font-size: 14.5px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">⚠</span>
                    <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 36px; align-items: start;">
                <!-- INFORMASI RESMI & ALAMAT -->
                <div>
                    <div style="margin-bottom: 24px;">
                        <div class="section-pretitle">Saluran Resmi</div>
                        <h2 class="section-title">Kantor Pelayanan &amp; Tata Usaha</h2>
                        <p class="section-subtitle">
                            Silakan datang langsung ke kantor madrasah atau hubungi kami melalui kanal komunikasi di bawah ini.
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px;">
                        <article class="doc-item" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
                            <span class="doc-label">Alamat Kantor</span>
                            <div class="doc-value" style="font-size: 14.5px; line-height: 1.5;">
                                <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </article>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <article class="doc-item" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
                                <span class="doc-label">Telepon Kantor</span>
                                <div class="doc-value" style="font-size: 14.5px;">
                                    <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </article>

                            <article class="doc-item" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
                                <span class="doc-label">WhatsApp Pelayanan</span>
                                <div class="doc-value" style="font-size: 14.5px; color: #15803D;">
                                    <?= htmlspecialchars($wa, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </article>
                        </div>

                        <article class="doc-item" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
                            <span class="doc-label">Email Resmi</span>
                            <div class="doc-value" style="font-size: 14.5px;">
                                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </article>

                        <article class="doc-item" style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
                            <span class="doc-label">Jam Pelayanan Pelajar &amp; Wali Santri</span>
                            <div class="doc-value" style="font-size: 14px;">
                                Senin sampai Sabtu: 07.00 - 14.00 WIB (Kecuali hari libur nasional)
                            </div>
                        </article>
                    </div>
                </div>

                <!-- FORMULIR KIRIM PESAN NYATA -->
                <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: 0 4px 14px rgba(15,23,42,.06);">
                    <div style="margin-bottom: 22px;">
                        <h3 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 6px;">Kirim Pesan / Pertanyaan</h3>
                        <p style="font-size: 13.5px; color: #64748B; margin: 0;">
                            Pesan Anda akan masuk ke kotak masuk admin madrasah dan segera kami tanggapi.
                        </p>
                    </div>

                    <form method="post" action="<?= url('kontak') ?>" style="display: flex; flex-direction: column; gap: 18px;">
                        <?= Csrf::field() ?>

                        <div>
                            <label for="contact_name" style="display: block; font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                Nama Lengkap <span style="color: #DC2626;">*</span>
                            </label>
                            <input type="text"
                                   id="contact_name"
                                   name="name"
                                   value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   required
                                   placeholder="Masukkan nama lengkap Anda"
                                   style="width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #fff;">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <label for="contact_email" style="display: block; font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                    Alamat Email <span style="color: #DC2626;">*</span>
                                </label>
                                <input type="email"
                                       id="contact_email"
                                       name="email"
                                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       required
                                       placeholder="nama@email.com"
                                       style="width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #fff;">
                            </div>

                            <div>
                                <label for="contact_phone" style="display: block; font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                    No. WhatsApp / Telepon
                                </label>
                                <input type="tel"
                                       id="contact_phone"
                                       name="phone"
                                       value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Contoh: 08123456789"
                                       style="width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #fff;">
                            </div>
                        </div>

                        <div>
                            <label for="contact_subject" style="display: block; font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                Subjek Pesan
                            </label>
                            <input type="text"
                                   id="contact_subject"
                                   name="subject"
                                   value="<?= htmlspecialchars($old['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Contoh: Informasi Persyaratan PPDB 2026"
                                   style="width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #fff;">
                        </div>

                        <div>
                            <label for="contact_message" style="display: block; font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 6px;">
                                Isi Pesan atau Pertanyaan <span style="color: #DC2626;">*</span>
                            </label>
                            <textarea id="contact_message"
                                      name="message"
                                      rows="5"
                                      required
                                      placeholder="Tuliskan pertanyaan atau informasi yang ingin Anda tanyakan..."
                                      style="width: 100%; padding: 12px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; color: #0F172A; background: #fff; line-height: 1.5; resize: vertical;"><?= htmlspecialchars($old['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div style="margin-top: 6px;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 46px; font-size: 14.5px;">
                                Kirim Pesan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
