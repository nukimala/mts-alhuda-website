-- ============================================================================
-- 014 — ID publik untuk tabel yang diekspos API
--
-- Keputusan K-7: tabel yang dibaca aplikasi mobile tidak boleh memakai ID
-- berurutan. Kalau `/api/v1/students/1` berlaku, maka `/students/2`,
-- `/students/3` juga — dan data seluruh siswa bisa dienumerasi berurutan
-- hanya dengan mengubah angka. `public_id` (UUID) menutup itu.
--
-- Enam tabel yang diekspos API:
--   posts        -> sudah punya public_id di migrasi 004
--   assignments  -> sudah punya public_id di migrasi 007
--   submissions  -> sudah punya public_id di migrasi 007
--   evidence     -> sudah punya public_id di migrasi 011
--   students     -> ditambahkan di sini
--   teachers     -> ditambahkan di sini
--
-- Dua tabel terakhir dibuat di migrasi 002, sebelum keputusan ini diambil,
-- dan migrasi yang sudah dijalankan tidak boleh diubah. Karena itu dipakai
-- ALTER di file terpisah.
--
-- Kolom dibiarkan NULL: baris lama tidak punya UUID, dan pengisiannya
-- dilakukan aplikasi saat membuat/menyimpan data. UNIQUE pada kolom NULL
-- memperbolehkan banyak baris NULL, jadi aman.
-- ============================================================================

-- @UP

ALTER TABLE students
    ADD COLUMN public_id CHAR(36) NULL AFTER id,
    ADD UNIQUE KEY uq_students_public (public_id);

ALTER TABLE teachers
    ADD COLUMN public_id CHAR(36) NULL AFTER id,
    ADD UNIQUE KEY uq_teachers_public (public_id);

-- @DOWN

ALTER TABLE teachers
    DROP INDEX uq_teachers_public,
    DROP COLUMN public_id;

ALTER TABLE students
    DROP INDEX uq_students_public,
    DROP COLUMN public_id;
