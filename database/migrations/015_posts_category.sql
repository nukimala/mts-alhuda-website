-- ============================================================================
-- 015 — Kategori berita + metadata SEO
--
-- Desain panel admin (Stitch "Kelola Berita" dan "Edit Berita") memakai:
--   - kolom Kategori pada tabel daftar berita,
--   - select Kategori pada formulir,
--   - kartu "Optimasi SEO & Metadata" berisi Meta Title dan Meta Description.
--
-- Skema 004 belum punya satu pun dari ketiganya. Ditambahkan di sini.
--
-- Kenapa kategori VARCHAR dan bukan ENUM:
--   ENUM memaksa ALTER TABLE setiap kali madrasah menambah kategori baru
--   ("Prestasi", "Kegiatan", "Pengumuman", "Opini & Riset", lalu "PPDB").
--   Kategori adalah isi, bukan aturan sistem. VARCHAR(60) cukup, dan daftar
--   pilihannya dijaga di sisi aplikasi (Post::CATEGORIES).
--
-- Default 'umum' dipakai supaya baris lama tetap valid tanpa UPDATE manual.
-- Kolom dibuat NOT NULL: nilai kosong selalu bisa diwakili 'umum'.
--
-- Kolom SEO dibiarkan NULL: berita lama belum punya metadata, dan halaman
-- publik sudah punya nilai cadangan dari pengaturan situs.
-- ============================================================================

-- @UP

ALTER TABLE posts
    ADD COLUMN category VARCHAR(60) NOT NULL DEFAULT 'umum' AFTER slug,
    ADD COLUMN meta_title VARCHAR(200) NULL AFTER cover_path,
    ADD COLUMN meta_description VARCHAR(320) NULL AFTER meta_title,
    ADD KEY idx_posts_category (category, status, published_at);

-- @DOWN

ALTER TABLE posts
    DROP INDEX idx_posts_category,
    DROP COLUMN meta_description,
    DROP COLUMN meta_title,
    DROP COLUMN category;
