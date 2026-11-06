-- ========================================================
-- MIGRASI 016: Skema Madrasahku Terpadu (Semi-Bahasa Indonesia)
-- Database: shulkhim_mts_alhuda_website
-- Tujuan : Standardisasi seluruh skema ke semi-Bahasa Indonesia,
--           penghapusan tabel ganda berbahasa Inggris, dan
--           penyediaan Compatibility Views untuk Flutter mobile app.
-- ========================================================

-- @UP

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Bersihkan tabel ganda berbahasa Inggris jika sempat dibuat
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `materials`;
DROP TABLE IF EXISTS `teacher_attendances`;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `siswas`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `gurus`;
DROP TABLE IF EXISTS `users`;

-- 2. Pastikan tabel pesan_kontak ada
CREATE TABLE IF NOT EXISTS `pesan_kontak` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `subjek` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('baru','dibaca','dibalas') NOT NULL DEFAULT 'baru',
  `balasan` text DEFAULT NULL,
  `dibalas_oleh` bigint unsigned DEFAULT NULL,
  `dibalas_pada` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kontak_status` (`status`),
  KEY `idx_kontak_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pastikan tabel lokasi_sekolah ada (QR & GPS Geofencing)
CREATE TABLE IF NOT EXISTS `lokasi_sekolah` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(100) NOT NULL DEFAULT 'Kantor Guru MTs Al-Huda',
  `qr_token` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL DEFAULT '-7.58430000',
  `longitude` decimal(11,8) NOT NULL DEFAULT '111.96820000',
  `radius_meter` int unsigned NOT NULL DEFAULT '15',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lokasi_qr` (`qr_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lokasi_sekolah` (`id`, `nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`)
VALUES (1, 'Kantor Guru MTs Al-Huda Gondang', 'MTS-ALHUDA-ABSENSI-2026-SECURE', -7.56234500, 111.89012300, 20, 1);

-- 4. Sinkronisasi kolom-kolom pendukung mobile app pada tabel semi-Bahasa Indonesia
ALTER TABLE `pengguna`
  ADD COLUMN IF NOT EXISTS `nama` VARCHAR(120) NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `telepon` VARCHAR(30) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `foto_url` VARCHAR(255) NULL AFTER `avatar_path`;

UPDATE `pengguna` SET `nama` = `name` WHERE `nama` IS NULL;
UPDATE `pengguna` SET `telepon` = `phone` WHERE `telepon` IS NULL;
UPDATE `pengguna` SET `foto_url` = `avatar_path` WHERE `foto_url` IS NULL;

ALTER TABLE `guru`
  ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100) NULL AFTER `nuptk`,
  ADD COLUMN IF NOT EXISTS `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L' AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `no_hp` VARCHAR(30) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `alamat` TEXT NULL AFTER `address`,
  ADD COLUMN IF NOT EXISTS `foto_url` VARCHAR(255) NULL AFTER `alamat`,
  ADD COLUMN IF NOT EXISTS `status_kepegawaian` VARCHAR(50) DEFAULT 'GTT/PTT' AFTER `employment_status`,
  ADD COLUMN IF NOT EXISTS `bidang_studi` VARCHAR(120) NULL AFTER `subject_specialty`,
  ADD COLUMN IF NOT EXISTS `kode_jadwal` VARCHAR(10) NULL AFTER `bidang_studi`;

UPDATE `guru` SET `nama_lengkap` = COALESCE(`nama_lengkap`, (SELECT `name` FROM `pengguna` WHERE `pengguna`.`id` = `guru`.`user_id`));
UPDATE `guru` SET `jenis_kelamin` = `gender` WHERE `jenis_kelamin` IS NULL;
UPDATE `guru` SET `no_hp` = `phone` WHERE `no_hp` IS NULL;
UPDATE `guru` SET `alamat` = `address` WHERE `alamat` IS NULL;
UPDATE `guru` SET `status_kepegawaian` = `employment_status` WHERE `status_kepegawaian` IS NULL;
UPDATE `guru` SET `bidang_studi` = `subject_specialty` WHERE `bidang_studi` IS NULL;

ALTER TABLE `kelas`
  ADD COLUMN IF NOT EXISTS `nama_kelas` VARCHAR(20) NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `tingkat` TINYINT UNSIGNED NULL AFTER `level`,
  ADD COLUMN IF NOT EXISTS `wali_kelas_id` BIGINT UNSIGNED NULL AFTER `homeroom_teacher_id`,
  ADD COLUMN IF NOT EXISTS `ruangan` VARCHAR(60) DEFAULT 'Ruang Kelas' AFTER `room`;

UPDATE `kelas` SET `nama_kelas` = `name` WHERE `nama_kelas` IS NULL;
UPDATE `kelas` SET `tingkat` = `level` WHERE `tingkat` IS NULL;
UPDATE `kelas` SET `wali_kelas_id` = `homeroom_teacher_id` WHERE `wali_kelas_id` IS NULL;
UPDATE `kelas` SET `ruangan` = `room` WHERE `ruangan` IS NULL;

ALTER TABLE `siswa`
  ADD COLUMN IF NOT EXISTS `kelas_id` BIGINT UNSIGNED NULL AFTER `class_id`,
  ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100) NULL AFTER `nisn`,
  ADD COLUMN IF NOT EXISTS `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L' AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `tempat_lahir` VARCHAR(80) DEFAULT 'Nganjuk' AFTER `birth_place`,
  ADD COLUMN IF NOT EXISTS `tanggal_lahir` DATE NULL AFTER `birth_date`,
  ADD COLUMN IF NOT EXISTS `nama_wali` VARCHAR(120) NULL AFTER `guardian_name`,
  ADD COLUMN IF NOT EXISTS `alamat` TEXT NULL AFTER `address`,
  ADD COLUMN IF NOT EXISTS `foto_url` VARCHAR(255) NULL AFTER `alamat`,
  ADD COLUMN IF NOT EXISTS `tahun_masuk` YEAR DEFAULT '2026' AFTER `enrolled_at`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('aktif','lulus','mutasi','keluar') NOT NULL DEFAULT 'aktif' AFTER `tahun_masuk`;

UPDATE `siswa` SET `kelas_id` = `class_id` WHERE `kelas_id` IS NULL;
UPDATE `siswa` SET `nama_lengkap` = COALESCE(`nama_lengkap`, (SELECT `name` FROM `pengguna` WHERE `pengguna`.`id` = `siswa`.`user_id`));
UPDATE `siswa` SET `jenis_kelamin` = `gender` WHERE `jenis_kelamin` IS NULL;
UPDATE `siswa` SET `tempat_lahir` = `birth_place` WHERE `tempat_lahir` IS NULL;
UPDATE `siswa` SET `tanggal_lahir` = `birth_date` WHERE `tanggal_lahir` IS NULL;
UPDATE `siswa` SET `nama_wali` = `guardian_name` WHERE `nama_wali` IS NULL;
UPDATE `siswa` SET `alamat` = `address` WHERE `alamat` IS NULL;

ALTER TABLE `mata_pelajaran`
  ADD COLUMN IF NOT EXISTS `kode_mapel` VARCHAR(20) NULL AFTER `code`,
  ADD COLUMN IF NOT EXISTS `nama_mapel` VARCHAR(120) NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `kelompok` ENUM('agama','umum','muatan_lokal','ekstrakurikuler') NOT NULL DEFAULT 'umum' AFTER `group_name`;

UPDATE `mata_pelajaran` SET `kode_mapel` = `code` WHERE `kode_mapel` IS NULL;
UPDATE `mata_pelajaran` SET `nama_mapel` = `name` WHERE `nama_mapel` IS NULL;
UPDATE `mata_pelajaran` SET `kelompok` = `group_name` WHERE `kelompok` IS NULL;

ALTER TABLE `mata_pelajaran_kelas`
  ADD COLUMN IF NOT EXISTS `kelas_id` BIGINT UNSIGNED NULL AFTER `class_id`,
  ADD COLUMN IF NOT EXISTS `mata_pelajaran_id` BIGINT UNSIGNED NULL AFTER `subject_id`,
  ADD COLUMN IF NOT EXISTS `guru_id` BIGINT UNSIGNED NULL AFTER `teacher_id`,
  ADD COLUMN IF NOT EXISTS `jam_mingguan` TINYINT UNSIGNED NOT NULL DEFAULT '2' AFTER `weekly_hours`;

UPDATE `mata_pelajaran_kelas` SET `kelas_id` = `class_id` WHERE `kelas_id` IS NULL;
UPDATE `mata_pelajaran_kelas` SET `mata_pelajaran_id` = `subject_id` WHERE `mata_pelajaran_id` IS NULL;
UPDATE `mata_pelajaran_kelas` SET `guru_id` = `teacher_id` WHERE `guru_id` IS NULL;
UPDATE `mata_pelajaran_kelas` SET `jam_mingguan` = `weekly_hours` WHERE `jam_mingguan` IS NULL;

ALTER TABLE `jadwal`
  ADD COLUMN IF NOT EXISTS `kelas_id` BIGINT UNSIGNED NULL AFTER `class_subject_id`,
  ADD COLUMN IF NOT EXISTS `subject_id` BIGINT UNSIGNED NULL AFTER `kelas_id`,
  ADD COLUMN IF NOT EXISTS `mata_pelajaran_id` BIGINT UNSIGNED NULL AFTER `subject_id`,
  ADD COLUMN IF NOT EXISTS `guru_id` BIGINT UNSIGNED NULL AFTER `teacher_id`,
  ADD COLUMN IF NOT EXISTS `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL DEFAULT 'Senin' AFTER `day_of_week`,
  ADD COLUMN IF NOT EXISTS `jam_mulai` TIME NULL AFTER `start_time`,
  ADD COLUMN IF NOT EXISTS `jam_selesai` TIME NULL AFTER `end_time`,
  ADD COLUMN IF NOT EXISTS `ruangan` VARCHAR(60) DEFAULT 'Ruang Kelas' AFTER `room`;

UPDATE `jadwal` j
  JOIN `mata_pelajaran_kelas` mpk ON mpk.`id` = j.`class_subject_id`
SET j.`kelas_id` = mpk.`class_id`,
    j.`subject_id` = mpk.`subject_id`,
    j.`mata_pelajaran_id` = mpk.`subject_id`,
    j.`guru_id` = j.`teacher_id`
WHERE j.`kelas_id` IS NULL;

UPDATE `jadwal` SET `jam_mulai` = `start_time` WHERE `jam_mulai` IS NULL;
UPDATE `jadwal` SET `jam_selesai` = `end_time` WHERE `jam_selesai` IS NULL;
UPDATE `jadwal` SET `ruangan` = `room` WHERE `ruangan` IS NULL;
UPDATE `jadwal` SET `hari` = CASE `day_of_week`
  WHEN 1 THEN 'Senin'
  WHEN 2 THEN 'Selasa'
  WHEN 3 THEN 'Rabu'
  WHEN 4 THEN 'Kamis'
  WHEN 5 THEN 'Jumat'
  WHEN 6 THEN 'Sabtu'
  ELSE 'Minggu'
END WHERE `hari` IS NULL OR `hari` = 'Senin';

ALTER TABLE `presensi_guru`
  ADD COLUMN IF NOT EXISTS `guru_id` BIGINT UNSIGNED NULL AFTER `teacher_id`,
  ADD COLUMN IF NOT EXISTS `tanggal` DATE NULL AFTER `attendance_date`,
  ADD COLUMN IF NOT EXISTS `jam_masuk` TIME NULL AFTER `check_in_at`,
  ADD COLUMN IF NOT EXISTS `jam_pulang` TIME NULL AFTER `check_out_at`,
  ADD COLUMN IF NOT EXISTS `lat_masuk` DECIMAL(10,8) NULL AFTER `jam_masuk`,
  ADD COLUMN IF NOT EXISTS `long_masuk` DECIMAL(11,8) NULL AFTER `lat_masuk`,
  ADD COLUMN IF NOT EXISTS `lat_pulang` DECIMAL(10,8) NULL AFTER `jam_pulang`,
  ADD COLUMN IF NOT EXISTS `long_pulang` DECIMAL(11,8) NULL AFTER `lat_pulang`,
  ADD COLUMN IF NOT EXISTS `keterangan` VARCHAR(255) NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `lampiran` VARCHAR(255) NULL AFTER `attachment_path`;

UPDATE `presensi_guru` SET `guru_id` = `teacher_id` WHERE `guru_id` IS NULL;
UPDATE `presensi_guru` SET `tanggal` = `attendance_date` WHERE `tanggal` IS NULL;
UPDATE `presensi_guru` SET `jam_masuk` = TIME(`check_in_at`) WHERE `jam_masuk` IS NULL AND `check_in_at` IS NOT NULL;
UPDATE `presensi_guru` SET `jam_pulang` = TIME(`check_out_at`) WHERE `jam_pulang` IS NULL AND `check_out_at` IS NOT NULL;
UPDATE `presensi_guru` SET `keterangan` = `notes` WHERE `keterangan` IS NULL;
UPDATE `presensi_guru` SET `lampiran` = `attachment_path` WHERE `lampiran` IS NULL;

ALTER TABLE `materi`
  ADD COLUMN IF NOT EXISTS `kelas_id` BIGINT UNSIGNED NULL AFTER `class_subject_id`,
  ADD COLUMN IF NOT EXISTS `guru_id` BIGINT UNSIGNED NULL AFTER `kelas_id`,
  ADD COLUMN IF NOT EXISTS `mata_pelajaran_id` BIGINT UNSIGNED NULL AFTER `guru_id`,
  ADD COLUMN IF NOT EXISTS `judul` VARCHAR(200) NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `deskripsi` TEXT NULL AFTER `description`,
  ADD COLUMN IF NOT EXISTS `file_url` VARCHAR(255) NULL AFTER `file_path`;

UPDATE `materi` SET `judul` = `title` WHERE `judul` IS NULL;
UPDATE `materi` SET `deskripsi` = `description` WHERE `deskripsi` IS NULL;
UPDATE `materi` SET `file_url` = `file_path` WHERE `file_url` IS NULL;

ALTER TABLE `tugas`
  ADD COLUMN IF NOT EXISTS `kelas_id` BIGINT UNSIGNED NULL AFTER `class_subject_id`,
  ADD COLUMN IF NOT EXISTS `guru_id` BIGINT UNSIGNED NULL AFTER `kelas_id`,
  ADD COLUMN IF NOT EXISTS `mata_pelajaran_id` BIGINT UNSIGNED NULL AFTER `guru_id`,
  ADD COLUMN IF NOT EXISTS `judul` VARCHAR(200) NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `instruksi` TEXT NULL AFTER `description`,
  ADD COLUMN IF NOT EXISTS `file_tugas_url` VARCHAR(255) NULL AFTER `attachment_path`,
  ADD COLUMN IF NOT EXISTS `deadline` DATETIME NULL AFTER `due_at`;

UPDATE `tugas` SET `judul` = `title` WHERE `judul` IS NULL;
UPDATE `tugas` SET `instruksi` = `description` WHERE `instruksi` IS NULL;
UPDATE `tugas` SET `file_tugas_url` = `attachment_path` WHERE `file_tugas_url` IS NULL;
UPDATE `tugas` SET `deadline` = `due_at` WHERE `deadline` IS NULL;

ALTER TABLE `pengumpulan_tugas`
  ADD COLUMN IF NOT EXISTS `tugas_id` BIGINT UNSIGNED NULL AFTER `assignment_id`,
  ADD COLUMN IF NOT EXISTS `siswa_id` BIGINT UNSIGNED NULL AFTER `student_id`,
  ADD COLUMN IF NOT EXISTS `file_jawaban_url` VARCHAR(255) NULL AFTER `file_path`,
  ADD COLUMN IF NOT EXISTS `catatan_siswa` TEXT NULL AFTER `note`,
  ADD COLUMN IF NOT EXISTS `nilai` DECIMAL(5,2) NULL AFTER `score`,
  ADD COLUMN IF NOT EXISTS `catatan_guru` TEXT NULL AFTER `feedback`;

UPDATE `pengumpulan_tugas` SET `tugas_id` = `assignment_id` WHERE `tugas_id` IS NULL;
UPDATE `pengumpulan_tugas` SET `siswa_id` = `student_id` WHERE `siswa_id` IS NULL;
UPDATE `pengumpulan_tugas` SET `file_jawaban_url` = `file_path` WHERE `file_jawaban_url` IS NULL;
UPDATE `pengumpulan_tugas` SET `catatan_siswa` = `note` WHERE `catatan_siswa` IS NULL;
UPDATE `pengumpulan_tugas` SET `nilai` = `score` WHERE `nilai` IS NULL;
UPDATE `pengumpulan_tugas` SET `catatan_guru` = `feedback` WHERE `catatan_guru` IS NULL;

-- 5. Buat / Perbarui Compatibility Views untuk Mobile App Flutter
CREATE OR REPLACE VIEW `users` AS 
SELECT `id`, `username`, `email`, `password_hash` AS `password`, `password_hash`, `name`, `nama`, `role`, `is_active`, `created_at`, `updated_at` 
FROM `pengguna`;

CREATE OR REPLACE VIEW `gurus` AS 
SELECT `id`, `user_id`, `nip`, `nuptk`, `gelar_depan`, `nama_lengkap`, `gelar_belakang`, `jenis_kelamin`, `gender`, `no_hp`, `phone`, `alamat`, `address`, `foto_url`, `status_kepegawaian`, `bidang_studi`, `kode_jadwal`, `is_active`, `created_at`, `updated_at` 
FROM `guru`;

CREATE OR REPLACE VIEW `teachers` AS 
SELECT `id`, `user_id`, `nip`, `nuptk`, `gelar_depan`, `nama_lengkap`, `gelar_belakang`, `gender`, `jenis_kelamin`, `phone` AS `no_hp`, `address` AS `alamat`, `foto_url`, `employment_status`, `subject_specialty`, `is_active`, `created_at`, `updated_at` 
FROM `guru`;

CREATE OR REPLACE VIEW `classes` AS 
SELECT `id`, `academic_year_id`, `name`, `nama_kelas`, `level`, `tingkat`, `homeroom_teacher_id` AS `wali_kelas_id`, `room`, `ruangan`, `capacity`, `sort_order`, `is_active`, `created_at`, `updated_at` 
FROM `kelas`;

CREATE OR REPLACE VIEW `siswas` AS 
SELECT `id`, `user_id`, `class_id` AS `kelas_id`, `class_id`, `nis`, `nisn`, `nama_lengkap`, `jenis_kelamin`, `gender`, `tempat_lahir`, `tanggal_lahir`, `nama_wali`, `alamat`, `foto_url`, `status`, `created_at`, `updated_at` 
FROM `siswa`;

CREATE OR REPLACE VIEW `students` AS 
SELECT `id`, `user_id`, `class_id`, `class_id` AS `kelas_id`, `nis`, `nisn`, `nama_lengkap`, `gender`, `jenis_kelamin`, `birth_place`, `birth_date`, `guardian_name`, `address`, `foto_url`, `enrolled_at`, `status`, `is_active`, `created_at`, `updated_at` 
FROM `siswa`;

CREATE OR REPLACE VIEW `subjects` AS 
SELECT `id`, `code` AS `kode_mapel`, `code`, `name` AS `nama_mapel`, `name`, `group_name` AS `kelompok`, `group_name`, `sort_order`, `is_active`, `created_at`, `updated_at` 
FROM `mata_pelajaran`;

CREATE OR REPLACE VIEW `class_subjects` AS 
SELECT `id`, `class_id` AS `kelas_id`, `class_id`, `subject_id` AS `mata_pelajaran_id`, `subject_id`, `teacher_id` AS `guru_id`, `teacher_id`, `weekly_hours` AS `jam_mingguan`, `weekly_hours`, `is_active`, `created_at`, `updated_at` 
FROM `mata_pelajaran_kelas`;

CREATE OR REPLACE VIEW `schedules` AS 
SELECT `id`, `class_subject_id`, `kelas_id` AS `class_id`, `kelas_id`, `subject_id` AS `mata_pelajaran_id`, `subject_id`, `teacher_id` AS `guru_id`, `teacher_id`, `academic_year_id`, `day_of_week`, `hari`, `start_time` AS `jam_mulai`, `start_time`, `end_time` AS `jam_selesai`, `end_time`, `room` AS `ruangan`, `room`, `is_active`, `created_at`, `updated_at` 
FROM `jadwal`;

CREATE OR REPLACE VIEW `school_locations` AS 
SELECT `id`, `nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`, `created_at`, `updated_at` 
FROM `lokasi_sekolah`;

CREATE OR REPLACE VIEW `teacher_attendances` AS 
SELECT `id`, `teacher_id` AS `guru_id`, `teacher_id`, `attendance_date` AS `tanggal`, `attendance_date`, `attendance_type`, `schedule_id`, `check_in_at`, `jam_masuk`, `check_out_at`, `jam_pulang`, `lat_masuk`, `long_masuk`, `lat_pulang`, `long_pulang`, `status`, `keterangan`, `notes`, `attachment_path` AS `lampiran`, `created_at`, `updated_at` 
FROM `presensi_guru`;

CREATE OR REPLACE VIEW `materials` AS 
SELECT `id`, `class_subject_id`, `kelas_id`, `guru_id`, `mata_pelajaran_id`, `title` AS `judul`, `title`, `description` AS `deskripsi`, `description`, `file_path`, `file_url`, `external_url`, `is_published`, `published_at`, `created_by`, `created_at`, `updated_at` 
FROM `materi`;

CREATE OR REPLACE VIEW `assignments` AS 
SELECT `id`, `public_id`, `class_subject_id`, `kelas_id`, `guru_id`, `mata_pelajaran_id`, `title` AS `judul`, `title`, `description` AS `instruksi`, `description`, `attachment_path` AS `file_tugas_url`, `attachment_path`, `due_at` AS `deadline`, `due_at`, `max_score`, `is_published`, `created_by`, `created_at`, `updated_at` 
FROM `tugas`;

CREATE OR REPLACE VIEW `submissions` AS 
SELECT `id`, `public_id`, `assignment_id`, `assignment_id` AS `tugas_id`, `student_id`, `student_id` AS `siswa_id`, `file_path`, `file_path` AS `file_jawaban_url`, `note` AS `catatan_siswa`, `note`, `submitted_at`, `status`, `score` AS `nilai`, `score`, `feedback` AS `catatan_guru`, `feedback`, `graded_at`, `graded_by`, `created_at`, `updated_at` 
FROM `pengumpulan_tugas`;

CREATE OR REPLACE VIEW `assignment_submissions` AS 
SELECT `id`, `public_id`, `assignment_id` AS `tugas_id`, `assignment_id`, `student_id` AS `siswa_id`, `student_id`, `file_jawaban_url`, `catatan_siswa`, `submitted_at`, `status`, `nilai` AS `score`, `nilai`, `catatan_guru` AS `feedback`, `catatan_guru`, `graded_at`, `graded_by`, `created_at`, `updated_at` 
FROM `pengumpulan_tugas`;

CREATE OR REPLACE VIEW `academic_years` AS 
SELECT `id`, `name` AS `nama_tahun`, `name`, `semester`, `start_date`, `end_date`, `is_active`, `note`, `created_at`, `updated_at` 
FROM `tahun_akademik`;

CREATE OR REPLACE VIEW `halaman_profil` AS 
SELECT `id`, `slug`, `title` AS `judul`, `title`, `content` AS `isi`, `content`, `is_active`, `updated_by`, `created_at`, `updated_at` 
FROM `halaman`;

CREATE OR REPLACE VIEW `activity_logs` AS 
SELECT `id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at` 
FROM `log_audit`;

SET FOREIGN_KEY_CHECKS = 1;

-- @DOWN

DROP VIEW IF EXISTS `activity_logs`;
DROP VIEW IF EXISTS `halaman_profil`;
DROP VIEW IF EXISTS `academic_years`;
DROP VIEW IF EXISTS `assignment_submissions`;
DROP VIEW IF EXISTS `submissions`;
DROP VIEW IF EXISTS `assignments`;
DROP VIEW IF EXISTS `materials`;
DROP VIEW IF EXISTS `teacher_attendances`;
DROP VIEW IF EXISTS `school_locations`;
DROP VIEW IF EXISTS `schedules`;
DROP VIEW IF EXISTS `class_subjects`;
DROP VIEW IF EXISTS `subjects`;
DROP VIEW IF EXISTS `students`;
DROP VIEW IF EXISTS `siswas`;
DROP VIEW IF EXISTS `classes`;
DROP VIEW IF EXISTS `teachers`;
DROP VIEW IF EXISTS `gurus`;
DROP VIEW IF EXISTS `users`;
