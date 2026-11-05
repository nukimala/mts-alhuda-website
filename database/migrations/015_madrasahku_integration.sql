-- ========================================================
-- MIGRASI 015: Integrasi Skema Madrasahku App
-- Database: shulkhim_mts_alhuda_website
-- Tujuan : Menyesuaikan tabel web agar kompatibel dengan
--           skema madrasahku_db (Flutter app), dan menghapus
--           modul akreditasi yang tidak digunakan.
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- BAGIAN A: HAPUS MODUL AKREDITASI
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pemeriksaan_indikator`;
DROP TABLE IF EXISTS `indikator_akreditasi`;
DROP TABLE IF EXISTS `hasil_akreditasi`;
DROP TABLE IF EXISTS `tindak_lanjut`;
DROP TABLE IF EXISTS `entri_dka`;
DROP TABLE IF EXISTS `bukti_butir`;
DROP TABLE IF EXISTS `bukti`;
DROP TABLE IF EXISTS `butir_akreditasi`;
DROP TABLE IF EXISTS `komponen_akreditasi`;
DROP TABLE IF EXISTS `periode_akreditasi`;
DROP TABLE IF EXISTS `agenda_tugas`;
DROP TABLE IF EXISTS `dokumen_utama`;

-- --------------------------------------------------------
-- BAGIAN B: TABEL PENGGUNA → kompatibel dengan `users`
-- --------------------------------------------------------
-- Tambah kolom username jika belum ada
ALTER TABLE `pengguna`
    MODIFY COLUMN `password_hash` VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS `username` VARCHAR(50) NULL AFTER `id`;

-- Isi username dari email (bagian sebelum @) untuk data lama
UPDATE `pengguna` SET `username` = LOWER(REPLACE(SUBSTRING_INDEX(`email`, '@', 1), '.', '_'))
WHERE `username` IS NULL AND `email` IS NOT NULL;

-- Isi username dari nama untuk yang tidak punya email
UPDATE `pengguna` SET `username` = LOWER(REPLACE(REPLACE(`nama`, ' ', '_'), '.', ''))
WHERE `username` IS NULL;

-- Buat unique setelah diisi
ALTER TABLE `pengguna`
    MODIFY COLUMN `username` VARCHAR(50) NOT NULL,
    ADD UNIQUE INDEX IF NOT EXISTS `uq_pengguna_username` (`username`);

-- --------------------------------------------------------
-- BAGIAN C: TABEL GURU → kompatibel dengan `gurus`
-- --------------------------------------------------------
ALTER TABLE `guru`
    ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100) NULL AFTER `nuptk`,
    ADD COLUMN IF NOT EXISTS `no_hp`        VARCHAR(20)  NULL AFTER `nama_lengkap`,
    ADD COLUMN IF NOT EXISTS `foto_url`     VARCHAR(255) NULL AFTER `no_hp`;

-- Salin nama lengkap dari tabel pengguna
UPDATE `guru` g
    JOIN `pengguna` p ON p.`id` = g.`pengguna_id`
SET g.`nama_lengkap` = p.`nama`
WHERE g.`nama_lengkap` IS NULL;

-- Salin no_hp jika kolom telepon ada
UPDATE `guru` SET `no_hp` = `telepon` WHERE `no_hp` IS NULL AND `telepon` IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN D: TABEL KELAS → kompatibel dengan `classes`
-- --------------------------------------------------------
ALTER TABLE `kelas`
    ADD COLUMN IF NOT EXISTS `nama_kelas`      VARCHAR(20) NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `tingkat`         TINYINT UNSIGNED NULL AFTER `nama_kelas`,
    ADD COLUMN IF NOT EXISTS `wali_kelas_id`   BIGINT UNSIGNED NULL AFTER `tingkat`;

-- Salin nama kelas dari kolom `name` / `nama` yang sudah ada
UPDATE `kelas` SET `nama_kelas` = `nama` WHERE `nama_kelas` IS NULL AND `nama` IS NOT NULL;

-- Isi tingkat dari nama kelas (VII=7, VIII=8, IX=9)
UPDATE `kelas` SET `tingkat` = 7 WHERE `nama_kelas` LIKE 'VII%' OR `nama_kelas` LIKE '7%';
UPDATE `kelas` SET `tingkat` = 8 WHERE `nama_kelas` LIKE 'VIII%' OR `nama_kelas` LIKE '8%';
UPDATE `kelas` SET `tingkat` = 9 WHERE `nama_kelas` LIKE 'IX%' OR `nama_kelas` LIKE '9%';

-- Salin wali kelas dari kolom lama jika ada
UPDATE `kelas` k
    JOIN `guru` g ON g.`id` = k.`homeroom_teacher_id`
SET k.`wali_kelas_id` = g.`id`
WHERE k.`wali_kelas_id` IS NULL AND k.`homeroom_teacher_id` IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN E: TABEL SISWA → kompatibel dengan `siswas`
-- --------------------------------------------------------
ALTER TABLE `siswa`
    ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100) NULL AFTER `pengguna_id`,
    ADD COLUMN IF NOT EXISTS `nama_wali`    VARCHAR(100) NULL AFTER `nama_lengkap`,
    ADD COLUMN IF NOT EXISTS `foto_url`     VARCHAR(255) NULL AFTER `nama_wali`,
    ADD COLUMN IF NOT EXISTS `kelas_id`     BIGINT UNSIGNED NULL AFTER `foto_url`;

-- Salin nama lengkap dari pengguna
UPDATE `siswa` s
    JOIN `pengguna` p ON p.`id` = s.`pengguna_id`
SET s.`nama_lengkap` = p.`nama`
WHERE s.`nama_lengkap` IS NULL;

-- Salin nama wali dari kolom lama
UPDATE `siswa` SET `nama_wali` = `guardian_name` WHERE `nama_wali` IS NULL AND `guardian_name` IS NOT NULL;
UPDATE `siswa` SET `nama_wali` = `nama_orang_tua` WHERE `nama_wali` IS NULL AND `nama_orang_tua` IS NOT NULL;

-- Salin kelas_id dari kolom lama
UPDATE `siswa` SET `kelas_id` = `class_id` WHERE `kelas_id` IS NULL AND `class_id` IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN F: TABEL MATA PELAJARAN → kompatibel dengan `subjects`
-- --------------------------------------------------------
ALTER TABLE `mata_pelajaran`
    ADD COLUMN IF NOT EXISTS `kode_mapel` VARCHAR(20) NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `nama_mapel` VARCHAR(100) NULL AFTER `kode_mapel`;

UPDATE `mata_pelajaran` SET `kode_mapel` = `kode`  WHERE `kode_mapel` IS NULL AND `kode` IS NOT NULL;
UPDATE `mata_pelajaran` SET `kode_mapel` = `code`  WHERE `kode_mapel` IS NULL AND `code` IS NOT NULL;
UPDATE `mata_pelajaran` SET `nama_mapel` = `nama`  WHERE `nama_mapel` IS NULL AND `nama` IS NOT NULL;
UPDATE `mata_pelajaran` SET `nama_mapel` = `name`  WHERE `nama_mapel` IS NULL AND `name` IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN G: TABEL JADWAL (flattened) → kompatibel dengan `schedules`
-- --------------------------------------------------------
-- Buat tabel jadwal_app sebagai tabel terflatten yang langsung bisa dibaca app mobile.
-- Tabel jadwal & mata_pelajaran_kelas tetap ada untuk web.
CREATE TABLE IF NOT EXISTS `jadwal_app` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kelas_id`    BIGINT UNSIGNED NOT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,  -- FK ke mata_pelajaran.id
    `guru_id`     BIGINT UNSIGNED NOT NULL,  -- FK ke guru.id
    `hari`        ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    `jam_mulai`   TIME NOT NULL,
    `jam_selesai` TIME NOT NULL,
    `ruangan`     VARCHAR(50) DEFAULT 'Ruang Kelas',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_jadwal_app_kelas`   (`kelas_id`),
    INDEX `idx_jadwal_app_guru`    (`guru_id`),
    INDEX `idx_jadwal_app_hari`    (`hari`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- BAGIAN H: TABEL PRESENSI GURU → kompatibel dengan `teacher_attendances`
-- --------------------------------------------------------
ALTER TABLE `presensi_guru`
    ADD COLUMN IF NOT EXISTS `tanggal`     DATE NULL AFTER `guru_id`,
    ADD COLUMN IF NOT EXISTS `lat_masuk`   DECIMAL(10,8) NULL AFTER `jam_masuk`,
    ADD COLUMN IF NOT EXISTS `long_masuk`  DECIMAL(11,8) NULL AFTER `lat_masuk`,
    ADD COLUMN IF NOT EXISTS `lat_pulang`  DECIMAL(10,8) NULL AFTER `jam_pulang`,
    ADD COLUMN IF NOT EXISTS `long_pulang` DECIMAL(11,8) NULL AFTER `lat_pulang`;

-- Isi kolom tanggal dari kolom tanggal_presensi / date yang mungkin ada
UPDATE `presensi_guru` SET `tanggal` = `tanggal_presensi` WHERE `tanggal` IS NULL AND `tanggal_presensi` IS NOT NULL;
UPDATE `presensi_guru` SET `tanggal` = DATE(`created_at`)  WHERE `tanggal` IS NULL;

-- --------------------------------------------------------
-- BAGIAN I: TABEL MATERI → kompatibel dengan `materials`
-- --------------------------------------------------------
ALTER TABLE `materi`
    ADD COLUMN IF NOT EXISTS `judul`     VARCHAR(150) NULL AFTER `subject_id`,
    ADD COLUMN IF NOT EXISTS `deskripsi` TEXT NULL AFTER `judul`,
    ADD COLUMN IF NOT EXISTS `file_url`  VARCHAR(255) NULL AFTER `deskripsi`;

UPDATE `materi` SET `judul`     = `title`     WHERE `judul`     IS NULL AND `title`     IS NOT NULL;
UPDATE `materi` SET `judul`     = `nama`      WHERE `judul`     IS NULL AND `nama`      IS NOT NULL;
UPDATE `materi` SET `deskripsi` = `body`      WHERE `deskripsi` IS NULL AND `body`      IS NOT NULL;
UPDATE `materi` SET `deskripsi` = `deskripsi_lama` WHERE `deskripsi` IS NULL AND `deskripsi_lama` IS NOT NULL;
UPDATE `materi` SET `file_url`  = `file_path` WHERE `file_url`  IS NULL AND `file_path` IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN J: TABEL TUGAS → kompatibel dengan `assignments`
-- --------------------------------------------------------
ALTER TABLE `tugas`
    ADD COLUMN IF NOT EXISTS `instruksi`     TEXT NULL AFTER `subject_id`,
    ADD COLUMN IF NOT EXISTS `file_tugas_url` VARCHAR(255) NULL AFTER `instruksi`,
    ADD COLUMN IF NOT EXISTS `deadline`      DATETIME NULL AFTER `file_tugas_url`,
    ADD COLUMN IF NOT EXISTS `kelas_id`      BIGINT UNSIGNED NULL AFTER `guru_id`;

UPDATE `tugas` SET `instruksi`      = `description`   WHERE `instruksi`     IS NULL AND `description`   IS NOT NULL;
UPDATE `tugas` SET `instruksi`      = `deskripsi`     WHERE `instruksi`     IS NULL AND `deskripsi`     IS NOT NULL;
UPDATE `tugas` SET `file_tugas_url` = `file_path`     WHERE `file_tugas_url` IS NULL AND `file_path`     IS NOT NULL;
UPDATE `tugas` SET `deadline`       = `due_date`      WHERE `deadline`      IS NULL AND `due_date`      IS NOT NULL;
UPDATE `tugas` SET `deadline`       = `batas_waktu`   WHERE `deadline`      IS NULL AND `batas_waktu`   IS NOT NULL;

-- --------------------------------------------------------
-- BAGIAN K: TABEL PENGUMPULAN TUGAS → kompatibel dengan `assignment_submissions`
-- --------------------------------------------------------
ALTER TABLE `pengumpulan_tugas`
    ADD COLUMN IF NOT EXISTS `file_jawaban_url` VARCHAR(255) NULL AFTER `siswa_id`,
    ADD COLUMN IF NOT EXISTS `catatan_siswa`    TEXT NULL AFTER `file_jawaban_url`,
    ADD COLUMN IF NOT EXISTS `submitted_at`     DATETIME NULL AFTER `catatan_siswa`,
    ADD COLUMN IF NOT EXISTS `status`           ENUM('submitted','late') NOT NULL DEFAULT 'submitted' AFTER `submitted_at`;

UPDATE `pengumpulan_tugas` SET `file_jawaban_url` = `file_path`   WHERE `file_jawaban_url` IS NULL AND `file_path`   IS NOT NULL;
UPDATE `pengumpulan_tugas` SET `catatan_siswa`    = `catatan`     WHERE `catatan_siswa`    IS NULL AND `catatan`     IS NOT NULL;
UPDATE `pengumpulan_tugas` SET `catatan_siswa`    = `komentar`    WHERE `catatan_siswa`    IS NULL AND `komentar`    IS NOT NULL;
UPDATE `pengumpulan_tugas` SET `submitted_at`     = `created_at`  WHERE `submitted_at`     IS NULL;

-- --------------------------------------------------------
-- BAGIAN L: TABEL BARU — school_locations (QR + GPS Geofencing)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `school_locations` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_lokasi`  VARCHAR(100) NOT NULL DEFAULT 'Kantor Guru MTs Al-Huda',
    `qr_token`     VARCHAR(100) NOT NULL UNIQUE,
    `latitude`     DECIMAL(10,8) NOT NULL,
    `longitude`    DECIMAL(11,8) NOT NULL,
    `radius_meter` INT UNSIGNED NOT NULL DEFAULT 10,
    `is_active`    BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed lokasi MTs Al-Huda (Desa Pandean, Gondang, Nganjuk)
INSERT IGNORE INTO `school_locations`
    (`nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`)
VALUES
    ('Kantor Guru MTs Al-Huda', 'MTS-ALHUDA-ABSENSI-2026-SECURE', -7.56234500, 111.89012300, 10, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- SELESAI — Verifikasi:
--   SHOW TABLES;  → tidak ada tabel akreditasi
--   DESC guru;    → ada kolom nama_lengkap, no_hp, foto_url
--   SELECT * FROM school_locations;  → 1 baris
-- ========================================================
