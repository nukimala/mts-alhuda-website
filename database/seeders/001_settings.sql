-- ============================================================================
-- 001_settings.sql — pengaturan dasar situs
--
-- Nilai diambil dari konten statis yang sudah ada di view publik,
-- bukan karangan. Yang belum pasti ditandai komentar TODO.
--
-- Seeder ini idempoten: memakai ON DUPLICATE KEY UPDATE pada setting_key,
-- jadi aman dijalankan berulang tanpa menggandakan baris.
-- ============================================================================

INSERT INTO settings (setting_key, setting_value, value_type, group_name, label, is_public) VALUES
-- --- identitas -------------------------------------------------------------
('site_name',        'MTs Al-Huda',                          'string', 'identitas', 'Nama madrasah',        1),
('site_subtitle',    'Gondang',                              'string', 'identitas', 'Keterangan nama',      1),
('site_full_name',   'Madrasah Tsanawiyah Al-Huda Gondang',  'string', 'identitas', 'Nama lengkap',         1),
('site_npsn',        '',                                     'string', 'identitas', 'NPSN',                 1),
('site_foundation',  'Yayasan Al-Huda Gondang',              'string', 'identitas', 'Nama yayasan',         1),
('accreditation_grade', 'A',                                 'string', 'identitas', 'Peringkat akreditasi', 1),
('accreditation_year',  '',                                  'string', 'identitas', 'Tahun akreditasi',     1),

-- --- kontak ---------------------------------------------------------------
('contact_address',  'Gondang, Kabupaten Tulungagung, Jawa Timur', 'text', 'kontak', 'Alamat lengkap',   1),
('contact_phone',    '',                                     'string', 'kontak', 'Telepon',              1),
('contact_whatsapp', '',                                     'string', 'kontak', 'WhatsApp',             1),
('contact_email',    'info@mts-alhuda.sch.id',               'string', 'kontak', 'Email resmi',          1),
('contact_hours',    'Senin - Sabtu, 07.00 - 15.10 WIB',     'string', 'kontak', 'Jam operasional',      1),
('contact_map_lat',  '',                                     'string', 'kontak', 'Lintang lokasi',       1),
('contact_map_lng',  '',                                     'string', 'kontak', 'Bujur lokasi',         1),

-- --- media sosial ---------------------------------------------------------
('social_facebook',  '',                                     'string', 'sosial',  'Facebook',             1),
('social_instagram', '',                                     'string', 'sosial',  'Instagram',            1),
('social_youtube',   '',                                     'string', 'sosial',  'YouTube',              1),
('social_tiktok',    '',                                     'string', 'sosial',  'TikTok',               1),

-- --- SEO ------------------------------------------------------------------
('meta_title',       'MTs Al-Huda Gondang — Madrasah Berakhlak, Berprestasi', 'string', 'seo', 'Meta title', 1),
('meta_description', 'Madrasah Tsanawiyah Al-Huda Gondang. Membentuk generasi Qur''ani, berakhlak mulia, dan berprestasi.', 'text', 'seo', 'Meta description', 1),

-- --- aturan presensi guru -------------------------------------------------
-- Jam acuan selalu jam server, bukan jam perangkat guru.
('attendance_check_in_time',  '07:00', 'string', 'presensi', 'Jam masuk',                0),
('attendance_late_tolerance', '15',    'int',    'presensi', 'Toleransi terlambat (menit)', 0),
('attendance_check_out_time', '15:10', 'string', 'presensi', 'Jam pulang',               0),

-- --- keamanan akun --------------------------------------------------------
('security_max_login_attempts', '5',   'int',  'keamanan', 'Batas percobaan login gagal',      0),
('security_lockout_minutes',    '15',  'int',  'keamanan', 'Durasi kunci akun (menit)',        0),
('security_session_timeout',    '120', 'int',  'keamanan', 'Sesi web berakhir (menit)',        0),
('security_api_access_ttl',     '30',  'int',  'keamanan', 'Masa aktif token akses (menit)',   0),
('security_api_refresh_ttl',    '30',  'int',  'keamanan', 'Masa aktif token refresh (hari)',  0),
('security_force_password_change', '1', 'bool', 'keamanan', 'Wajib ganti sandi saat login pertama', 0),
('security_audit_log',          '1',   'bool', 'keamanan', 'Catat log audit',                  0),

-- --- notifikasi email -----------------------------------------------------
-- SMTP hanya untuk email transaksional. Tidak ada penyedia identitas eksternal,
-- tidak ada layanan push pihak ketiga.
('mail_enabled',     '0',  'bool',   'email', 'Notifikasi email aktif',        0),
('mail_host',        '',   'string', 'email', 'SMTP host',                    0),
('mail_port',        '587','int',    'email', 'SMTP port',                    0),
('mail_encryption',  'tls','string', 'email', 'Enkripsi SMTP',                0),
('mail_username',    '',   'string', 'email', 'SMTP username',                0),
('mail_password',    '',   'string', 'email', 'SMTP password',                0),
('mail_from_address','',   'string', 'email', 'Alamat pengirim',              0),
('mail_from_name',   'MTs Al-Huda Gondang', 'string', 'email', 'Nama pengirim', 0),
('mail_on_password_reset', '1', 'bool', 'email', 'Kirim email reset sandi',  0),
('mail_on_account_created','1', 'bool', 'email', 'Kirim email akun baru',    0),
('mail_on_new_assignment', '1', 'bool', 'email', 'Kirim email tugas baru',   0),

-- --- pengumuman beranda ---------------------------------------------------
('announcement_active', '0',  'bool',   'pengumuman', 'Tampilkan pengumuman', 0),
('announcement_text',   '',   'text',   'pengumuman', 'Isi pengumuman',       0)
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type    = VALUES(value_type),
    group_name    = VALUES(group_name),
    label         = VALUES(label),
    is_public     = VALUES(is_public);
