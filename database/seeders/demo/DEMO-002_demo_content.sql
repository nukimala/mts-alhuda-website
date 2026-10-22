-- ============================================================================
-- DEMO-002 — Data contoh
--
-- Dijalankan HANYA dengan: php tools/migrate.php seed --demo
-- Tidak ikut pada `seed` biasa, supaya database produksi tidak terisi
-- data palsu tanpa disengaja.
--
-- Tujuan: teman tim yang baru clone repo langsung punya isi untuk dilihat
-- di frontend. Tanpa ini, halaman berita, galeri, fasilitas, dan agenda
-- tampil kosong dan orang mengira ada yang rusak.
--
-- Ini BUKAN data madrasah sebenarnya. Data nyata diisi operator lewat panel.
--
-- Idempoten. Tiga cara dipakai, sesuai ada-tidaknya unique key:
--   1. Tabel punya UNIQUE  -> ON DUPLICATE KEY UPDATE
--   2. Tidak ada UNIQUE    -> INSERT ... SELECT ... WHERE NOT EXISTS
--   3. Tidak bisa keduanya -> UPDATE dulu, INSERT kalau masih kosong
-- ============================================================================

-- @UP

-- --- Pengguna contoh -------------------------------------------------------
-- Sandi kedua akun di bawah: Contoh#AlHuda2026
-- must_change_password = 1 — wajib diganti sebelum dipakai sungguhan.
INSERT INTO users (name, email, username, password_hash, role, role_label, is_active, must_change_password)
VALUES
    ('Operator Contoh', 'operator@contoh.local', 'operator',
     '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK',
     'operator', 'Operator Madrasah', 1, 1),
    ('Guru Contoh', 'guru@contoh.local', 'guru',
     '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK',
     'guru', 'Guru', 1, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role);

-- --- Tahun ajaran & kelas contoh ------------------------------------------
INSERT INTO academic_years (name, start_date, end_date, is_active, note)
VALUES ('2025/2026', '2025-07-01', '2026-06-30', 1, 'Tahun ajaran contoh')
ON DUPLICATE KEY UPDATE note = VALUES(note);

INSERT INTO classes (academic_year_id, name, level, sort_order, is_active)
SELECT y.id, '7A', 7, 1, 1 FROM academic_years y WHERE y.name = '2025/2026'
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

INSERT INTO classes (academic_year_id, name, level, sort_order, is_active)
SELECT y.id, '8A', 8, 2, 1 FROM academic_years y WHERE y.name = '2025/2026'
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

INSERT INTO classes (academic_year_id, name, level, sort_order, is_active)
SELECT y.id, '9A', 9, 3, 1 FROM academic_years y WHERE y.name = '2025/2026'
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);

-- --- Mata pelajaran contoh ------------------------------------------------
INSERT INTO subjects (code, name, group_name, sort_order, is_active) VALUES
    ('QH',   'Al-Qur''an Hadis',         'agama',         1, 1),
    ('AA',   'Akidah Akhlak',            'agama',         2, 1),
    ('FQ',   'Fikih',                    'agama',         3, 1),
    ('SKI',  'Sejarah Kebudayaan Islam', 'agama',         4, 1),
    ('BIN',  'Bahasa Indonesia',         'umum',         10, 1),
    ('BIG',  'Bahasa Inggris',           'umum',         11, 1),
    ('MTK',  'Matematika',               'umum',         12, 1),
    ('IPA',  'Ilmu Pengetahuan Alam',    'umum',         13, 1),
    ('IPS',  'Ilmu Pengetahuan Sosial',  'umum',         14, 1),
    ('PKN',  'Pendidikan Pancasila',     'umum',         15, 1),
    ('SBK',  'Seni Budaya',              'umum',         16, 1),
    ('PJOK', 'Pendidikan Jasmani',       'umum',         17, 1),
    ('BTQ',  'Baca Tulis Al-Qur''an',    'muatan_lokal', 20, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), group_name = VALUES(group_name);

-- --- Guru, siswa, dan pembelajaran contoh ----------------------------------
-- Tanpa bagian ini, /api/v1/schedules dan /api/v1/assignments selalu
-- mengembalikan daftar kosong, dan tim Flutter tidak punya cara membedakan
-- "belum ada data" dari "integrasinya salah". Data ini yang membuat
-- endpoint itu bisa diuji.

INSERT INTO teachers (user_id, nip, gender, employment_status, subject_specialty, joined_at)
SELECT u.id, '198501012010011001', 'L', 'pns', 'Matematika', '2010-01-01'
FROM users u
WHERE u.username = 'guru'
  AND NOT EXISTS (SELECT 1 FROM teachers t WHERE t.user_id = u.id);

-- Akun siswa contoh. Sandi sama dengan akun contoh lain.
INSERT INTO users (name, email, username, password_hash, role, role_label, is_active, must_change_password)
VALUES
    ('Siswa Contoh', 'siswa@contoh.local', 'siswa',
     '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK',
     'siswa', 'Siswa', 1, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO students (user_id, nis, gender, class_id, enrolled_at, is_active)
SELECT u.id, '2025001', 'P', c.id, '2025-07-01', 1
FROM users u
JOIN classes c          ON c.name = '7A'
JOIN academic_years y   ON y.id = c.academic_year_id AND y.name = '2025/2026'
WHERE u.username = 'siswa'
  AND NOT EXISTS (SELECT 1 FROM students s WHERE s.user_id = u.id);

-- Matematika di 7A diampu guru contoh. BIN dan IPA sengaja belum punya guru:
-- keadaan nyata pada awal tahun, dan sekaligus menguji kolom yang boleh NULL.
INSERT INTO class_subjects (class_id, subject_id, teacher_id, weekly_hours, is_active)
SELECT c.id, s.id, t.id, 4, 1
FROM classes c
JOIN academic_years y ON y.id = c.academic_year_id AND y.name = '2025/2026'
JOIN subjects s       ON s.code = 'MTK'
LEFT JOIN users u     ON u.username = 'guru'
LEFT JOIN teachers t  ON t.user_id = u.id
WHERE c.name = '7A'
ON DUPLICATE KEY UPDATE weekly_hours = VALUES(weekly_hours);

INSERT INTO class_subjects (class_id, subject_id, teacher_id, weekly_hours, is_active)
SELECT c.id, s.id, NULL, 4, 1
FROM classes c
JOIN academic_years y ON y.id = c.academic_year_id AND y.name = '2025/2026'
JOIN subjects s       ON s.code IN ('BIN', 'IPA')
WHERE c.name = '7A'
ON DUPLICATE KEY UPDATE weekly_hours = VALUES(weekly_hours);

-- Jadwal guru contoh: Senin dan Rabu. UNIQUE (teacher_id, tahun, hari,
-- jam_mulai) membuat INSERT ini idempoten tanpa perlu WHERE NOT EXISTS.
INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time, room, is_active)
SELECT cs.id, t.id, y.id, 1, '07:30:00', '09:00:00', 'Ruang 7A', 1
FROM class_subjects cs
JOIN classes c          ON c.id = cs.class_id
JOIN subjects s         ON s.id = cs.subject_id
JOIN academic_years y   ON y.id = c.academic_year_id
JOIN users u            ON u.username = 'guru'
JOIN teachers t         ON t.user_id = u.id
WHERE c.name = '7A' AND s.code = 'MTK' AND y.name = '2025/2026'
ON DUPLICATE KEY UPDATE end_time = VALUES(end_time), room = VALUES(room), is_active = 1;

INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time, room, is_active)
SELECT cs.id, t.id, y.id, 3, '09:15:00', '10:45:00', 'Ruang 7A', 1
FROM class_subjects cs
JOIN classes c          ON c.id = cs.class_id
JOIN subjects s         ON s.id = cs.subject_id
JOIN academic_years y   ON y.id = c.academic_year_id
JOIN users u            ON u.username = 'guru'
JOIN teachers t         ON t.user_id = u.id
WHERE c.name = '7A' AND s.code = 'MTK' AND y.name = '2025/2026'
ON DUPLICATE KEY UPDATE end_time = VALUES(end_time), room = VALUES(room), is_active = 1;

-- Tugas contoh. public_id diisi UUID() karena kolom itu yang dipakai
-- aplikasi mobile sebagai pengenal stabil (K-7).
INSERT INTO assignments (public_id, class_subject_id, title, description, due_at, max_score, is_published, created_by)
SELECT UUID(), cs.id,
       'Tugas Contoh: Operasi Bilangan Bulat',
       'Kerjakan 10 soal pada buku paket halaman 24. Kumpulkan dalam bentuk foto atau PDF.',
       '2026-11-10 23:59:59', 100.00, 1, u.id
FROM class_subjects cs
JOIN classes c          ON c.id = cs.class_id
JOIN subjects s         ON s.id = cs.subject_id
JOIN academic_years y   ON y.id = c.academic_year_id
JOIN users u            ON u.username = 'operator'
WHERE c.name = '7A' AND s.code = 'MTK' AND y.name = '2025/2026'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.title = 'Tugas Contoh: Operasi Bilangan Bulat');

INSERT INTO assignments (public_id, class_subject_id, title, description, due_at, max_score, is_published, created_by)
SELECT UUID(), cs.id,
       'Tugas Contoh: Teks Deskripsi',
       'Tulis teks deskripsi tentang lingkungan madrasah, minimal 200 kata.',
       '2026-11-17 23:59:59', 100.00, 1, u.id
FROM class_subjects cs
JOIN classes c          ON c.id = cs.class_id
JOIN subjects s         ON s.id = cs.subject_id
JOIN academic_years y   ON y.id = c.academic_year_id
JOIN users u            ON u.username = 'operator'
WHERE c.name = '7A' AND s.code = 'BIN' AND y.name = '2025/2026'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.title = 'Tugas Contoh: Teks Deskripsi');

-- Materi contoh memakai tautan luar. Domain .invalid tidak pernah bisa
-- dibuka — disengaja, supaya tidak ada yang mengira ini berkas sungguhan.
INSERT INTO materials (class_subject_id, title, description, external_url, is_published, published_at, created_by)
SELECT cs.id,
       'Materi Contoh: Bilangan Bulat',
       'Ringkasan sifat operasi bilangan bulat beserta contoh soal.',
       'https://contoh.invalid/materi/bilangan-bulat.pdf',
       1, '2026-10-05 08:00:00', u.id
FROM class_subjects cs
JOIN classes c          ON c.id = cs.class_id
JOIN subjects s         ON s.id = cs.subject_id
JOIN academic_years y   ON y.id = c.academic_year_id
JOIN users u            ON u.username = 'operator'
WHERE c.name = '7A' AND s.code = 'MTK' AND y.name = '2025/2026'
  AND NOT EXISTS (SELECT 1 FROM materials m WHERE m.title = 'Materi Contoh: Bilangan Bulat');

-- --- Fasilitas contoh -----------------------------------------------------
-- Tidak ada UNIQUE pada nama fasilitas, jadi pakai WHERE NOT EXISTS.
INSERT INTO facilities (name, description, icon, sort_order, is_active)
SELECT * FROM (
    SELECT 'Ruang Kelas'  AS name, 'Kelas yang bersih dan terang untuk kegiatan belajar mengajar.' AS description, 'classroom' AS icon, 1 AS sort_order, 1 AS is_active
    UNION ALL SELECT 'Perpustakaan', 'Koleksi buku pelajaran, agama, dan umum untuk menumbuhkan minat baca.', 'book', 2, 1
    UNION ALL SELECT 'Musholla',     'Pusat kegiatan ibadah, sholat berjamaah, dan pembinaan spiritual.', 'mosque', 3, 1
    UNION ALL SELECT 'Lapangan',     'Area olahraga dan kegiatan ekstrakurikuler.', 'field', 4, 1
    UNION ALL SELECT 'Laboratorium', 'Ruang praktik untuk mata pelajaran sains.', 'lab', 5, 1
    UNION ALL SELECT 'Ruang Guru',   'Ruang kerja dan koordinasi para guru.', 'office', 6, 1
) AS contoh
WHERE NOT EXISTS (SELECT 1 FROM facilities f WHERE f.name = contoh.name);

-- --- Berita contoh --------------------------------------------------------
INSERT INTO posts (title, slug, excerpt, content, status, published_at, author_id)
VALUES
    ('Contoh Berita Pertama', 'contoh-berita-pertama',
     'Ini paragraf pembuka berita contoh. Ganti dengan berita sungguhan lewat panel admin.',
     '<p>Isi berita contoh. Halaman ini dipakai untuk menguji tampilan daftar dan halaman detail.</p>',
     'published', '2026-09-10 08:00:00',
     (SELECT id FROM users WHERE username = 'operator' LIMIT 1))
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt);

INSERT INTO posts (title, slug, excerpt, content, status, published_at, author_id)
VALUES
    ('Contoh Berita Kedua', 'contoh-berita-kedua',
     'Berita contoh kedua, lebih baru dari yang pertama. Dipakai untuk menguji urutan.',
     '<p>Isi berita contoh kedua.</p>',
     'published', '2026-10-01 09:30:00',
     (SELECT id FROM users WHERE username = 'operator' LIMIT 1))
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt);

INSERT INTO posts (title, slug, excerpt, content, status, published_at, author_id)
VALUES
    ('Contoh Berita Ketiga', 'contoh-berita-ketiga',
     'Berita contoh ketiga, paling baru. Muncul di urutan pertama halaman berita.',
     '<p>Isi berita contoh ketiga.</p>',
     'published', '2026-10-20 14:15:00',
     (SELECT id FROM users WHERE username = 'operator' LIMIT 1))
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt);

-- Berita draf. Sengaja ada, untuk membuktikan query publik menyaring status.
INSERT INTO posts (title, slug, excerpt, content, status, published_at, author_id)
VALUES
    ('Contoh Draf Belum Terbit', 'contoh-draf-belum-terbit',
     'Berita ini berstatus draf. Tidak boleh muncul di halaman publik.',
     '<p>Isi draf.</p>',
     'draft', NULL,
     (SELECT id FROM users WHERE username = 'operator' LIMIT 1))
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt);

-- --- Pengumuman contoh ----------------------------------------------------
INSERT INTO announcements (title, slug, content, audience, status, is_pinned, published_at, author_id)
VALUES
    ('Contoh Pengumuman', 'contoh-pengumuman',
     'Isi pengumuman contoh. Pengumuman yang disematkan tampil paling atas.',
     'semua', 'published', 1, '2026-10-15 07:00:00',
     (SELECT id FROM users WHERE username = 'operator' LIMIT 1))
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- --- Agenda contoh --------------------------------------------------------
INSERT INTO agendas (title, description, location, start_at, end_at, status, author_id)
SELECT 'Contoh Agenda Kegiatan',
       'Agenda contoh untuk menguji tampilan kalender dan daftar agenda.',
       'Aula Madrasah',
       '2026-11-05 08:00:00', '2026-11-05 12:00:00',
       'published',
       (SELECT id FROM users WHERE username = 'operator' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM agendas WHERE title = 'Contoh Agenda Kegiatan');

-- --- Menu contoh ----------------------------------------------------------
-- `url` relatif tanpa leading slash: base_url() yang menambahkan prefix folder.
INSERT INTO menus (label, url, location, sort_order, is_active)
SELECT * FROM (
    SELECT 'Beranda'   AS label, ''          AS url, 'utama' AS location, 1 AS sort_order, 1 AS is_active
    UNION ALL SELECT 'Profil',    'profil',    'utama', 2, 1
    UNION ALL SELECT 'Akademik',  'akademik',  'utama', 3, 1
    UNION ALL SELECT 'Fasilitas', 'fasilitas', 'utama', 4, 1
    UNION ALL SELECT 'Galeri',    'galeri',    'utama', 5, 1
    UNION ALL SELECT 'Berita',    'berita',    'utama', 6, 1
    UNION ALL SELECT 'PPDB',      'ppdb',      'utama', 7, 1
    UNION ALL SELECT 'Kontak',    'kontak',    'utama', 8, 1
) AS contoh
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.label = contoh.label AND m.location = contoh.location
);

-- --- Halaman statis contoh ------------------------------------------------
INSERT INTO pages (slug, title, content, status, sort_order)
VALUES ('profil', 'Profil Madrasah',
        '<p>Halaman profil contoh. Ganti dengan profil madrasah yang sebenarnya.</p>',
        'published', 1)
ON DUPLICATE KEY UPDATE content = VALUES(content);

INSERT INTO pages (slug, title, content, status, sort_order)
VALUES ('akademik', 'Program Akademik',
        '<p>Halaman akademik contoh.</p>',
        'published', 2)
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- --- Kerangka akreditasi contoh ------------------------------------------
-- Hanya struktur. Teks butir IA2024 TIDAK diisi (K-10) — menunggu instrumen
-- resmi dari mitra. Salah teks butir berarti seluruh DKA salah dasar.
INSERT INTO accreditation_periods (name, start_date, end_date, status, is_active, note)
VALUES ('Akreditasi Contoh', '2026-01-01', '2026-12-31', 'draft', 0,
        'Kerangka contoh. Teks butir menunggu instrumen resmi IA2024.')
ON DUPLICATE KEY UPDATE note = VALUES(note);

INSERT INTO accreditation_components (period_id, code, name, number, sort_order)
SELECT p.id, 'A', 'Komponen Contoh A', 1, 1
FROM accreditation_periods p WHERE p.name = 'Akreditasi Contoh'
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- @DOWN

-- Urutannya penting: anak dulu, induk belakangan. FK presensi dan jadwal
-- memakai RESTRICT, jadi menghapus guru atau kelas lebih dulu akan ditolak.
DELETE FROM schedules
 WHERE class_subject_id IN (
     SELECT id FROM class_subjects
      WHERE class_id IN (SELECT id FROM classes WHERE name IN ('7A', '8A', '9A'))
 );
DELETE FROM assignments WHERE title IN ('Tugas Contoh: Operasi Bilangan Bulat', 'Tugas Contoh: Teks Deskripsi');
DELETE FROM materials WHERE title = 'Materi Contoh: Bilangan Bulat';
DELETE FROM class_subjects
 WHERE class_id IN (SELECT id FROM classes WHERE name IN ('7A', '8A', '9A'));
DELETE FROM students WHERE nis = '2025001';
DELETE FROM teachers WHERE nip = '198501012010011001';

DELETE FROM accreditation_components
 WHERE period_id IN (SELECT id FROM accreditation_periods WHERE name = 'Akreditasi Contoh');
DELETE FROM accreditation_periods WHERE name = 'Akreditasi Contoh';

DELETE FROM pages WHERE slug IN ('profil', 'akademik');
DELETE FROM menus WHERE label IN ('Beranda','Profil','Akademik','Fasilitas','Galeri','Berita','PPDB','Kontak');
DELETE FROM agendas WHERE title = 'Contoh Agenda Kegiatan';
DELETE FROM announcements WHERE slug = 'contoh-pengumuman';
DELETE FROM posts WHERE slug IN ('contoh-berita-pertama','contoh-berita-kedua','contoh-berita-ketiga','contoh-draf-belum-terbit');
DELETE FROM facilities WHERE name IN ('Ruang Kelas','Perpustakaan','Musholla','Lapangan','Laboratorium','Ruang Guru');
DELETE FROM subjects WHERE code IN ('QH','AA','FQ','SKI','BIN','BIG','MTK','IPA','IPS','PKN','SBK','PJOK','BTQ');
DELETE FROM classes WHERE name IN ('7A','8A','9A');
DELETE FROM academic_years WHERE name = '2025/2026';
DELETE FROM users WHERE username IN ('operator','guru','siswa');
