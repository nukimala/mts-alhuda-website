<?php
ob_start();
$settings = $data['settings'] ?? [];
$tab = $data['tab'] ?? 'identitas';
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Pengaturan Situs &amp; Aplikasi</b>
</div>

<div class="page-head">
    <div>
        <h1>Pengaturan Situs &amp; Aplikasi</h1>
        <p>Identitas madrasah, presensi mobile MADRASAHKU, kontak, SEO, dan parameter sistem</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url() ?>" target="_blank">Lihat Website ↗</a>
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice success" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        <div><?= htmlspecialchars($data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="tab-bar">
        <a href="<?= base_url('admin/pengaturan?tab=identitas') ?>" class="<?= $tab === 'identitas' ? 'on' : '' ?>">Identitas Madrasah</a>
        <a href="<?= base_url('admin/pengaturan?tab=aplikasi') ?>" class="<?= $tab === 'aplikasi' ? 'on' : '' ?>" style="font-weight:700;color:<?= $tab === 'aplikasi' ? 'inherit' : '#15803D' ?>">Presensi &amp; Mobile App</a>
        <a href="<?= base_url('admin/pengaturan?tab=kontak') ?>" class="<?= $tab === 'kontak' ? 'on' : '' ?>">Kontak &amp; Lokasi</a>
        <a href="<?= base_url('admin/pengaturan?tab=sosmed') ?>" class="<?= $tab === 'sosmed' ? 'on' : '' ?>">Media Sosial</a>
        <a href="<?= base_url('admin/pengaturan?tab=seo') ?>" class="<?= $tab === 'seo' ? 'on' : '' ?>">SEO &amp; Meta</a>
        <a href="<?= base_url('admin/pengaturan?tab=email') ?>" class="<?= $tab === 'email' ? 'on' : '' ?>">Email &amp; Notifikasi</a>
        <a href="<?= base_url('admin/pengaturan?tab=keamanan') ?>" class="<?= $tab === 'keamanan' ? 'on' : '' ?>">Keamanan Sistem</a>
    </div>

    <form method="post" action="<?= base_url('admin/pengaturan/update') ?>" class="card-pad">
        <?= Csrf::field() ?>
        <input type="hidden" name="current_tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($tab === 'identitas'): ?>
            <div class="grid grid-2">
                <div>
                    <div class="field">
                        <label for="school_name">Nama Madrasah <span class="req">*</span></label>
                        <input type="text" id="school_name" name="school_name" value="<?= htmlspecialchars($settings['school_name'] ?? 'MTs Al-Huda Gondang', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="field">
                        <label for="npsn">Nomor Pokok Sekolah Nasional (NPSN)</label>
                        <input type="text" id="npsn" name="npsn" value="<?= htmlspecialchars($settings['npsn'] ?? '20512345', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="foundation_name">Yayasan Penyelenggara</label>
                        <input type="text" id="foundation_name" name="foundation_name" value="<?= htmlspecialchars($settings['foundation_name'] ?? 'Yayasan Pendidikan Al-Huda Gondang', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="accreditation_grade">Status Akreditasi</label>
                        <input type="text" id="accreditation_grade" name="accreditation_grade" value="<?= htmlspecialchars($settings['accreditation_grade'] ?? 'A (Unggul)', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div>
                    <div class="field">
                        <label for="school_address">Alamat Lengkap Madrasah</label>
                        <textarea id="school_address" name="school_address" style="min-height:92px"><?= htmlspecialchars($settings['school_address'] ?? 'Jl. Raya Gondang No. 12, Kec. Gondang, Kab. Nganjuk, Jawa Timur 64451', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="field">
                        <label for="school_vision">Visi Madrasah</label>
                        <input type="text" id="school_vision" name="school_vision" value="<?= htmlspecialchars($settings['school_vision'] ?? 'Membentuk generasi Qur\'ani, berakhlak mulia, dan berprestasi.', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="school_motto">Motto Pendidikan</label>
                        <input type="text" id="school_motto" name="school_motto" value="<?= htmlspecialchars($settings['school_motto'] ?? 'Beriman, Bertaqwa, Berilmu, dan Beramal.', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'aplikasi'): ?>
            <div class="notice info" style="margin-bottom:20px">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                <div>
                    <b>Sinkronisasi Aplikasi Mobile MADRASAHKU:</b>
                    Pengaturan di bawah ini menjadi acuan waktu server dan validasi radius GPS ketika guru melakukan presensi kehadiran melalui aplikasi Flutter. Nilai dipublikasikan secara otomatis ke <code>/api/v1/settings/public</code>.
                </div>
            </div>

            <div class="grid grid-2">
                <div>
                    <h3 style="font-size:15px;margin-bottom:14px;color:var(--green)">1. Aturan Waktu Presensi Guru</h3>
                    
                    <div class="field">
                        <label for="attendance_check_in_time">Jam Mulai Masuk Presensi</label>
                        <input type="time" id="attendance_check_in_time" name="attendance_check_in_time" value="<?= htmlspecialchars($settings['attendance_check_in_time'] ?? '06:30', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="hint">Waktu dimulainya jendela check-in kehadiran guru di pagi hari.</div>
                    </div>

                    <div class="field">
                        <label for="attendance_late_tolerance">Batas Toleransi Terlambat (Menit)</label>
                        <input type="number" id="attendance_late_tolerance" name="attendance_late_tolerance" value="<?= htmlspecialchars($settings['attendance_late_tolerance'] ?? '15', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="hint">Check-in setelah (Jam Masuk + Toleransi) otomatis berstatus <b>Terlambat</b>.</div>
                    </div>

                    <div class="field">
                        <label for="attendance_check_out_time">Jam Mulai Presensi Pulang (Check-out)</label>
                        <input type="time" id="attendance_check_out_time" name="attendance_check_out_time" value="<?= htmlspecialchars($settings['attendance_check_out_time'] ?? '14:00', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="hint">Waktu guru dapat melakukan presensi keluar pulang di aplikasi.</div>
                    </div>
                </div>

                <div>
                    <h3 style="font-size:15px;margin-bottom:14px;color:var(--green)">2. Geofence &amp; Validasi Lokasi GPS</h3>

                    <div class="grid grid-2">
                        <div class="field">
                            <label for="attendance_lat">Latitude Madrasah</label>
                            <input type="text" id="attendance_lat" name="attendance_lat" value="<?= htmlspecialchars($settings['attendance_lat'] ?? '-7.589123', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="attendance_lng">Longitude Madrasah</label>
                            <input type="text" id="attendance_lng" name="attendance_lng" value="<?= htmlspecialchars($settings['attendance_lng'] ?? '112.012345', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="attendance_radius_meters">Radius Toleransi Geofence (Meter)</label>
                        <input type="number" id="attendance_radius_meters" name="attendance_radius_meters" value="<?= htmlspecialchars($settings['attendance_radius_meters'] ?? '100', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="hint">Jarak maksimal posisi guru dari titik koordinat madrasah (default: 100 meter).</div>
                    </div>

                    <div class="field">
                        <label for="attendance_mobile_enabled">Status Presensi Mobile</label>
                        <select id="attendance_mobile_enabled" name="attendance_mobile_enabled">
                            <option value="1" <?= ($settings['attendance_mobile_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif : Guru Diizinkan Presensi via Ponsel</option>
                            <option value="0" <?= ($settings['attendance_mobile_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif : Hanya Presensi Operator Web</option>
                        </select>
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'kontak'): ?>
            <div class="grid grid-2">
                <div>
                    <div class="field">
                        <label for="phone">Nomor Telepon Kantor</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '(0358) 123456', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="whatsapp">WhatsApp Layanan Santri</label>
                        <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($settings['whatsapp'] ?? '0812-3456-7890', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="email">Alamat Email Resmi</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? 'info@mtsalhudagondang.sch.id', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div>
                    <div class="field">
                        <label for="maps_embed">Google Maps Embed URL</label>
                        <textarea id="maps_embed" name="maps_embed" style="min-height:120px" placeholder="https://maps.google.com/maps?q=..."><?= htmlspecialchars($settings['maps_embed'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="hint">Tautan sematan peta lokasi madrasah untuk halaman kontak publik.</div>
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'sosmed'): ?>
            <div style="max-width:680px">
                <div class="field">
                    <label for="facebook_url">Halaman Facebook</label>
                    <input type="url" id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? 'https://facebook.com/mtsalhudagondang', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="instagram_url">Akun Instagram</label>
                    <input type="url" id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? 'https://instagram.com/mtsalhudagondang', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="youtube_url">Kanal YouTube</label>
                    <input type="url" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url'] ?? 'https://youtube.com/@mtsalhudagondang', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

        <?php elseif ($tab === 'seo'): ?>
            <div style="max-width:680px">
                <div class="field">
                    <label for="meta_title">Judul Meta Default</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($settings['meta_title'] ?? 'MTs Al-Huda Gondang | Madrasah Unggul & Berkarakter', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="meta_description">Deskripsi Meta (Google Search)</label>
                    <textarea id="meta_description" name="meta_description" style="min-height:90px"><?= htmlspecialchars($settings['meta_description'] ?? 'Sistem Informasi resmi MTs Al-Huda Gondang, menyajikan informasi akademik terpadu, berita kegiatan, fasilitas sarpras, dan pendaftaran santri baru.', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="field">
                    <label for="meta_keywords">Kata Kunci (Keywords)</label>
                    <input type="text" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($settings['meta_keywords'] ?? 'mts al-huda, madrasah tsanawiyah gondang, nganjuk, ppdb mts, sekolah islam nganjuk', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

        <?php elseif ($tab === 'email'): ?>
            <div class="grid grid-2">
                <div>
                    <div class="field">
                        <label for="smtp_host">SMTP Server Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="smtp_port">SMTP Port</label>
                        <input type="text" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div>
                    <div class="field">
                        <label for="smtp_user">SMTP Username / Email</label>
                        <input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? 'notifikasi@mtsalhudagondang.sch.id', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="smtp_from_name">Nama Pengirim Email</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'MTs Al-Huda Gondang', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'keamanan'): ?>
            <div style="max-width:680px">
                <div class="field">
                    <label for="session_timeout">Batas Waktu Sesi Login (Menit)</label>
                    <input type="number" id="session_timeout" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout'] ?? '120', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="hint">Sesi akan otomatis keluar jika tidak ada aktivitas dalam batas waktu ini.</div>
                </div>

                <div class="field">
                    <label for="max_login_attempts">Maksimal Percobaan Gagal Login</label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?= htmlspecialchars($settings['max_login_attempts'] ?? '5', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="hint">Akun akan dikunci sementara selama 15 menit jika melebihi batas ini.</div>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top:24px;border-top:1px solid var(--border);padding-top:16px;display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-primary">Simpan Seluruh Pengaturan</button>
        </div>
    </form>
</div>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
