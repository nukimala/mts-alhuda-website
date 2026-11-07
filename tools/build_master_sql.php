<?php
/**
 * Master SQL Dump Generator for MTs Al-Huda Gondang
 *
 * Menggabungkan skema semi-Bahasa Indonesia terlengkap,
 * fitur integrasi Madrasahku Mobile App, compatibility views,
 * dan seluruh data riil (22 guru, 6 kelas, 154 siswa, 18 mapel, matriks jadwal).
 *
 * Menghasilkan:
 *   - database/mts_alhuda_production.sql
 *   - database/mts_alhuda.sql
 */

declare(strict_types=1);

$ROOT = dirname(__DIR__);
date_default_timezone_set('Asia/Jakarta');

echo "=== Memulai Kompilasi Master Database MTs Al-Huda ===\n";

$dataGuru = require $ROOT . '/tools/data_guru_list.php';
$dataJadwal = require $ROOT . '/tools/data_jadwal_matrix.php';
$siswaCsv = file($ROOT . '/tools/data_siswa_list.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Password default untuk guru & siswa: 'MtsAlhuda#2026'
$defaultPasswordHash = password_hash('MtsAlhuda#2026', PASSWORD_BCRYPT);
// Password admin: 'Admin#2026'
$adminPasswordHash = password_hash('Admin#2026', PASSWORD_BCRYPT);

function esc($str): string {
    if ($str === null) return "NULL";
    return "'" . addslashes((string)$str) . "'";
}

$sql = <<<SQL
-- ============================================================================
-- DATABASE MASTER DUMP: MTs Al-Huda Gondang
-- Standar Penamaan: Semi-Bahasa Indonesia (Tunggal)
-- Terintegrasi: Web Portal Madrasah & Mobile App (Madrasahku)
-- Disusun: 2026-11-07
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';
SET NAMES utf8mb4;

-- --------------------------------------------------------------------------
-- 0. BERSIHKAN TABEL MODUL AKREDITASI LAMA & TABEL GANDA
-- --------------------------------------------------------------------------
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

DROP VIEW IF EXISTS `users`;
DROP VIEW IF EXISTS `gurus`;
DROP VIEW IF EXISTS `teachers`;
DROP VIEW IF EXISTS `classes`;
DROP VIEW IF EXISTS `siswas`;
DROP VIEW IF EXISTS `students`;
DROP VIEW IF EXISTS `subjects`;
DROP VIEW IF EXISTS `class_subjects`;
DROP VIEW IF EXISTS `schedules`;
DROP VIEW IF EXISTS `school_locations`;
DROP VIEW IF EXISTS `teacher_attendances`;
DROP VIEW IF EXISTS `materials`;
DROP VIEW IF EXISTS `assignments`;
DROP VIEW IF EXISTS `submissions`;
DROP VIEW IF EXISTS `assignment_submissions`;
DROP VIEW IF EXISTS `academic_years`;
DROP VIEW IF EXISTS `halaman_profil`;
DROP VIEW IF EXISTS `activity_logs`;

-- --------------------------------------------------------------------------
-- 1. TABEL PENGGUNA (users)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `nama` varchar(120) DEFAULT NULL,
  `role` enum('admin','operator','kepala','guru','siswa') NOT NULL DEFAULT 'siswa',
  `role_label` varchar(50) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pengguna_username` (`username`),
  UNIQUE KEY `uq_pengguna_email` (`email`),
  KEY `idx_pengguna_role` (`role`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 2. TABEL GURU (teachers)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `guru`;
CREATE TABLE `guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nuptk` varchar(30) DEFAULT NULL,
  `gelar_depan` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `gelar_belakang` varchar(20) DEFAULT NULL,
  `gender` enum('L','P') NOT NULL DEFAULT 'L',
  `jenis_kelamin` enum('L','P') NOT NULL DEFAULT 'L',
  `phone` varchar(30) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `employment_status` varchar(50) NOT NULL DEFAULT 'honorer',
  `status_kepegawaian` varchar(50) DEFAULT 'GTT/PTT',
  `subject_specialty` varchar(120) DEFAULT NULL,
  `bidang_studi` varchar(120) DEFAULT NULL,
  `kode_jadwal` varchar(10) DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guru_user` (`user_id`),
  UNIQUE KEY `uq_guru_nip` (`nip`),
  UNIQUE KEY `uq_guru_nuptk` (`nuptk`),
  KEY `idx_guru_active` (`is_active`),
  CONSTRAINT `fk_guru_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 3. TABEL TAHUN AKADEMIK (academic_years)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `tahun_akademik`;
CREATE TABLE `tahun_akademik` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `nama_tahun` varchar(40) DEFAULT NULL,
  `semester` enum('ganjil','genap') NOT NULL DEFAULT 'ganjil',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  `active_flag` tinyint GENERATED ALWAYS AS (IF(`is_active` = 1, 1, NULL)) STORED,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tahun_name_sem` (`name`, `semester`),
  UNIQUE KEY `uq_tahun_active` (`active_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 4. TABEL KELAS (classes)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `tingkat` tinyint unsigned NOT NULL,
  `homeroom_teacher_id` bigint unsigned DEFAULT NULL,
  `wali_kelas_id` bigint unsigned DEFAULT NULL,
  `room` varchar(60) DEFAULT 'Ruang Kelas',
  `ruangan` varchar(60) DEFAULT 'Ruang Kelas',
  `capacity` smallint unsigned DEFAULT '32',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kelas_year_name` (`academic_year_id`,`name`),
  KEY `idx_kelas_level` (`level`),
  KEY `idx_kelas_homeroom` (`homeroom_teacher_id`),
  CONSTRAINT `fk_kelas_year` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademik` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_homeroom` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 5. TABEL SISWA (students)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned DEFAULT NULL,
  `nis` varchar(20) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `gender` enum('L','P') NOT NULL DEFAULT 'L',
  `jenis_kelamin` enum('L','P') NOT NULL DEFAULT 'L',
  `birth_place` varchar(80) DEFAULT 'Nganjuk',
  `tempat_lahir` varchar(80) DEFAULT 'Nganjuk',
  `birth_date` date DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `guardian_name` varchar(120) DEFAULT NULL,
  `nama_wali` varchar(120) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `enrolled_at` date DEFAULT '2026-07-15',
  `tahun_masuk` year DEFAULT '2026',
  `status` enum('aktif','lulus','mutasi','keluar') NOT NULL DEFAULT 'aktif',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_user` (`user_id`),
  UNIQUE KEY `uq_siswa_nis` (`nis`),
  UNIQUE KEY `uq_siswa_nisn` (`nisn`),
  KEY `idx_siswa_class` (`class_id`),
  KEY `idx_siswa_status` (`status`),
  CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`class_id`) REFERENCES `kelas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 6. TABEL MATA PELAJARAN (subjects)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `mata_pelajaran`;
CREATE TABLE `mata_pelajaran` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `kode_mapel` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `nama_mapel` varchar(120) NOT NULL,
  `group_name` enum('agama','umum','muatan_lokal','ekstrakurikuler') NOT NULL DEFAULT 'umum',
  `kelompok` enum('agama','umum','muatan_lokal','ekstrakurikuler') NOT NULL DEFAULT 'umum',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mapel_code` (`code`),
  KEY `idx_mapel_group` (`group_name`),
  KEY `idx_mapel_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 7. TABEL MATA PELAJARAN KELAS (class_subjects)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `mata_pelajaran_kelas`;
CREATE TABLE `mata_pelajaran_kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `mata_pelajaran_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `guru_id` bigint unsigned DEFAULT NULL,
  `weekly_hours` tinyint unsigned NOT NULL DEFAULT '2',
  `jam_mingguan` tinyint unsigned NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mpk_class_subject` (`class_id`,`subject_id`),
  KEY `idx_mpk_teacher` (`teacher_id`),
  KEY `idx_mpk_subject` (`subject_id`),
  CONSTRAINT `fk_mpk_class` FOREIGN KEY (`class_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mpk_subject` FOREIGN KEY (`subject_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mpk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 8. TABEL JADWAL PELAJARAN (schedules)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `jadwal`;
CREATE TABLE `jadwal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `mata_pelajaran_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `guru_id` bigint unsigned DEFAULT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `start_time` time NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `end_time` time NOT NULL,
  `jam_selesai` time DEFAULT NULL,
  `room` varchar(60) DEFAULT 'Ruang Kelas',
  `ruangan` varchar(60) DEFAULT 'Ruang Kelas',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal_teacher` (`teacher_id`,`academic_year_id`,`day_of_week`,`start_time`),
  KEY `idx_jadwal_class` (`kelas_id`,`day_of_week`,`start_time`),
  CONSTRAINT `fk_jadwal_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_jadwal_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_jadwal_year` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademik` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 9. TABEL LOKASI SEKOLAH (school_locations - Presensi QR & GPS)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `lokasi_sekolah`;
CREATE TABLE `lokasi_sekolah` (
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

-- --------------------------------------------------------------------------
-- 10. TABEL PRESENSI GURU (teacher_attendances)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `presensi_guru`;
CREATE TABLE `presensi_guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `guru_id` bigint unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `tanggal` date DEFAULT NULL,
  `attendance_type` enum('daily','session') NOT NULL DEFAULT 'daily',
  `schedule_id` bigint unsigned DEFAULT NULL,
  `check_in_at` datetime DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `check_out_at` datetime DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `long_masuk` decimal(11,8) DEFAULT NULL,
  `lat_pulang` decimal(10,8) DEFAULT NULL,
  `long_pulang` decimal(11,8) DEFAULT NULL,
  `status` enum('hadir','terlambat','izin','sakit','dinas','alpa') NOT NULL DEFAULT 'hadir',
  `notes` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presensi_daily` (`teacher_id`,`attendance_date`,`attendance_type`),
  KEY `idx_presensi_date` (`attendance_date`,`status`),
  CONSTRAINT `fk_presensi_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_presensi_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `jadwal` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 11. TABEL MATERI PEMBELAJARAN (materials)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `materi`;
CREATE TABLE `materi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned DEFAULT NULL,
  `guru_id` bigint unsigned DEFAULT NULL,
  `mata_pelajaran_id` bigint unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_materi_class_subject` (`class_subject_id`),
  CONSTRAINT `fk_materi_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_materi_author` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 12. TABEL TUGAS PEMBELAJARAN (assignments)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `tugas`;
CREATE TABLE `tugas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) DEFAULT NULL,
  `class_subject_id` bigint unsigned NOT NULL,
  `kelas_id` bigint unsigned DEFAULT NULL,
  `guru_id` bigint unsigned DEFAULT NULL,
  `mata_pelajaran_id` bigint unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `instruksi` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `file_tugas_url` varchar(255) DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT '100.00',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tugas_public` (`public_id`),
  KEY `idx_tugas_class` (`class_subject_id`),
  CONSTRAINT `fk_tugas_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tugas_author` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 13. TABEL PENGUMPULAN TUGAS (submissions / assignment_submissions)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengumpulan_tugas`;
CREATE TABLE `pengumpulan_tugas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) DEFAULT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `tugas_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `siswa_id` bigint unsigned DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_jawaban_url` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `catatan_siswa` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('submitted','late') NOT NULL DEFAULT 'submitted',
  `score` decimal(5,2) DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `catatan_guru` text DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pengumpulan_public` (`public_id`),
  UNIQUE KEY `uq_pengumpulan_pair` (`assignment_id`,`student_id`),
  KEY `idx_pengumpulan_student` (`student_id`),
  CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 14. TABEL PESAN KONTAK (contact_messages)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pesan_kontak`;
CREATE TABLE `pesan_kontak` (
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

-- --------------------------------------------------------------------------
-- 15. TABEL BERITA (posts)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `berita`;
CREATE TABLE `berita` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` varchar(300) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `isi` mediumtext DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Kegiatan',
  `kategori` varchar(50) NOT NULL DEFAULT 'Kegiatan',
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_berita_slug` (`slug`),
  KEY `idx_berita_status_date` (`status`,`published_at`),
  CONSTRAINT `fk_berita_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 16. TABEL GALERI (gallery)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `galeri`;
CREATE TABLE `galeri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `album` varchar(100) NOT NULL DEFAULT 'Kegiatan Madrasah',
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `caption` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_galeri_album` (`album`),
  CONSTRAINT `fk_galeri_user` FOREIGN KEY (`uploaded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 17. TABEL FASILITAS (facilities / sarpras)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `fasilitas`;
CREATE TABLE `fasilitas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `nama_fasilitas` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Sarana Pembelajaran',
  `description` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `condition` enum('sangat_baik','baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `kondisi` enum('sangat_baik','baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `quantity` smallint unsigned NOT NULL DEFAULT '1',
  `jumlah` smallint unsigned NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fasilitas_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 18. TABEL PENGUMUMAN (announcements)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengumuman`;
CREATE TABLE `pengumuman` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `content` text NOT NULL,
  `isi` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pengumuman_slug` (`slug`),
  KEY `idx_pengumuman_status` (`status`,`published_at`),
  CONSTRAINT `fk_pengumuman_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 19. TABEL AGENDA KEGIATAN (events)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `agenda`;
CREATE TABLE `agenda` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `lokasi` varchar(200) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `tgl_mulai` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `tgl_selesai` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','published','cancelled') NOT NULL DEFAULT 'published',
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_upcoming` (`status`,`start_at`),
  CONSTRAINT `fk_agenda_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 20. TABEL DOKUMEN PPDB (ppdb_documents)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `dokumen_ppdb`;
CREATE TABLE `dokumen_ppdb` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `file_size` int unsigned NOT NULL DEFAULT '0',
  `mime_type` varchar(100) DEFAULT 'application/pdf',
  `download_count` int unsigned NOT NULL DEFAULT '0',
  `jumlah_unduh` int unsigned NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ppdb_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 21. TABEL HALAMAN STATIS / PROFIL (pages)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `halaman`;
CREATE TABLE `halaman` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `content` mediumtext NOT NULL,
  `isi` mediumtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_halaman_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 22. TABEL PENGATURAN SITUS (settings)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `value_type` enum('string','text','int','bool','json') NOT NULL DEFAULT 'string',
  `group_name` varchar(60) NOT NULL DEFAULT 'umum',
  `label` varchar(160) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 23. TABEL MENU NAVIGASI (navigation)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(20) NOT NULL DEFAULT '_self',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 24. TABEL NOTIFIKASI (notifications)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifikasi_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 25. TABEL TOKEN API (api_tokens)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `token_api`;
CREATE TABLE `token_api` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `public_id` char(36) DEFAULT NULL,
  `token_hash` varchar(64) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `platform` varchar(50) DEFAULT 'unknown',
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_token_user` (`user_id`),
  CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 26. TABEL LOG AUDIT (activity_logs)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `log_audit`;
CREATE TABLE `log_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 27. TABEL SESI PENGGUNA (user_sessions)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `sesi_pengguna`;
CREATE TABLE `sesi_pengguna` (
  `id` varchar(128) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` mediumtext NOT NULL,
  `last_activity` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sesi_user` (`user_id`),
  KEY `idx_sesi_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 28. TABEL TOKEN RESET SANDI (password_resets)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `token_reset_sandi`;
CREATE TABLE `token_reset_sandi` (
  `email` varchar(160) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_hash`),
  KEY `idx_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 29. TABEL BATAS AKSES (rate_limits)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `batas_akses`;
CREATE TABLE `batas_akses` (
  `bucket` varchar(191) NOT NULL,
  `tokens` decimal(8,2) NOT NULL,
  `last_replenished_at` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_batas_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 30. TABEL SCHEMA MIGRATIONS & SEEDERS (Database Tracking)
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `version` varchar(20) NOT NULL,
  `name` varchar(191) NOT NULL,
  `batch` int unsigned NOT NULL DEFAULT '1',
  `statements` int unsigned NOT NULL DEFAULT '0',
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `schema_seeders`;
CREATE TABLE `schema_seeders` (
  `seeder` varchar(191) NOT NULL,
  `checksum` char(64) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seeder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- COMPATIBILITY VIEWS (Transparansi Bahasa Inggris ↔ Semi-Bahasa Indonesia)
-- ==========================================================================
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

SQL;

// --------------------------------------------------------------------------
// DATA SEEDING
// --------------------------------------------------------------------------

$sql .= "\n-- ==========================================================================\n";
$sql .= "-- DATA SEEDING (Pengaturan, Akun Inti, Guru, Siswa, Kelas, Mapel, Jadwal)\n";
$sql .= "-- ==========================================================================\n\n";

// 1. Tahun Akademik 2026/2027 Ganjil
$sql .= "-- 1. Tahun Akademik\n";
$sql .= "INSERT INTO `tahun_akademik` (`id`, `name`, `nama_tahun`, `semester`, `start_date`, `end_date`, `is_active`, `note`) VALUES\n";
$sql .= "(1, '2026/2027', '2026/2027', 'ganjil', '2026-07-13', '2026-12-19', 1, 'Semester Ganjil Tahun Ajaran 2026/2027');\n\n";

// 2. Akun Administrator & Kepala Madrasah
$sql .= "-- 2. Akun Administrator & Kepala Madrasah\n";
$sql .= "INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES\n";
$sql .= "(1, 'admin', 'admin@mts-alhuda.sch.id', '{$adminPasswordHash}', 'Administrator Madrasah', 'Administrator Madrasah', 'admin', 'Super Admin', '081234567890', '081234567890', 1, 0, NOW()),\n";
$sql .= "(2, 'operator', 'operator@mts-alhuda.sch.id', '{$adminPasswordHash}', 'Operator Madrasah', 'Operator Madrasah', 'operator', 'Operator Madrasah', '081234567891', '081234567891', 1, 0, NOW()),\n";
$sql .= "(3, 'sholihin', 'sholihin@mts-alhuda.sch.id', '{$adminPasswordHash}', 'Drs. H. M. Sholihin, M.Pd.I', 'Drs. H. M. Sholihin, M.Pd.I', 'kepala', 'Kepala Madrasah', '081234567892', '081234567892', 1, 0, NOW());\n\n";

// 3. Data Guru (22 Guru) & Akun Guru di Pengguna
$sql .= "-- 3. Guru & Pengguna Guru\n";
$teacherUserValues = [];
$teacherValues = [];
$nextUserId = 10;
$teacherMap = []; // kode_jadwal -> guru_id

foreach ($dataGuru as $g) {
    $userId = $nextUserId++;
    $guruId = (int)$g['no'];
    $kodeJadwal = $g['kode_jadwal'];
    $teacherMap[$kodeJadwal] = $guruId;

    $username = 'guru_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $g['nama']));
    if (strlen($username) > 30) $username = substr($username, 0, 30);
    $email = $username . '@mts-alhuda.sch.id';

    $fullName = trim(($g['gelar_depan'] ? $g['gelar_depan'] . ' ' : '') . $g['nama'] . ($g['gelar_belakang'] ? ', ' . $g['gelar_belakang'] : ''));

    $teacherUserValues[] = "({$userId}, " . esc($username) . ", " . esc($email) . ", '{$defaultPasswordHash}', " . esc($fullName) . ", " . esc($fullName) . ", 'guru', 'Guru', NULL, NULL, 1, 0, NOW())";

    $teacherValues[] = "({$guruId}, {$userId}, " . esc($g['nuptk'] ?: null) . ", " . esc($g['nuptk'] ?: null) . ", " . esc($g['gelar_depan']) . ", " . esc($fullName) . ", " . esc($g['gelar_belakang']) . ", " . esc($g['jk']) . ", " . esc($g['jk']) . ", NULL, NULL, " . esc($g['alamat']) . ", " . esc($g['alamat']) . ", NULL, 'honorer', 'GTT/PTT', " . esc($g['jabatan']) . ", " . esc($g['jabatan']) . ", " . esc($g['kode_jadwal']) . ", '2020-01-01', 1, NOW())";
}

$sql .= "INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES\n" . implode(",\n", $teacherUserValues) . ";\n\n";

$sql .= "INSERT INTO `guru` (`id`, `user_id`, `nip`, `nuptk`, `gelar_depan`, `nama_lengkap`, `gelar_belakang`, `gender`, `jenis_kelamin`, `phone`, `no_hp`, `address`, `alamat`, `foto_url`, `employment_status`, `status_kepegawaian`, `subject_specialty`, `bidang_studi`, `kode_jadwal`, `joined_at`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $teacherValues) . ";\n\n";

// 4. Kelas (7A, 7B, 8A, 8B, 9A, 9B)
$sql .= "-- 4. Kelas\n";
$classesDef = [
    1 => ['name' => '7A', 'level' => 7, 'wali_kode' => '02'],
    2 => ['name' => '7B', 'level' => 7, 'wali_kode' => '04'],
    3 => ['name' => '8A', 'level' => 8, 'wali_kode' => '05'],
    4 => ['name' => '8B', 'level' => 8, 'wali_kode' => '06'],
    5 => ['name' => '9A', 'level' => 9, 'wali_kode' => '07'],
    6 => ['name' => '9B', 'level' => 9, 'wali_kode' => '08'],
];
$classValues = [];
$classIdMap = []; // '7A' -> 1
foreach ($classesDef as $cid => $c) {
    $classIdMap[$c['name']] = $cid;
    $waliId = $teacherMap[$c['wali_kode']] ?? 1;
    $room = 'Ruang Kelas ' . $c['name'];
    $classValues[] = "({$cid}, 1, '{$c['name']}', '{$c['name']}', {$c['level']}, {$c['level']}, {$waliId}, {$waliId}, '{$room}', '{$room}', 32, {$cid}, 1, NOW())";
}
$sql .= "INSERT INTO `kelas` (`id`, `academic_year_id`, `name`, `nama_kelas`, `level`, `tingkat`, `homeroom_teacher_id`, `wali_kelas_id`, `room`, `ruangan`, `capacity`, `sort_order`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $classValues) . ";\n\n";

// 5. Mata Pelajaran (18 Mapel)
$sql .= "-- 5. Mata Pelajaran\n";
$mapelList = $dataJadwal['mapel'];
$mapelValues = [];
$mapelIdMap = []; // 'A' -> 1, 'QH' -> 1
$mId = 1;
foreach ($mapelList as $letter => $m) {
    $code = $m['code'];
    $name = $m['name'];
    $group = $m['group'];
    $mapelIdMap[$letter] = $mId;
    $mapelIdMap[$code] = $mId;

    $mapelValues[] = "({$mId}, " . esc($code) . ", " . esc($code) . ", " . esc($name) . ", " . esc($name) . ", '{$group}', '{$group}', {$mId}, 1, NOW())";
    $mId++;
}
$sql .= "INSERT INTO `mata_pelajaran` (`id`, `code`, `kode_mapel`, `name`, `nama_mapel`, `group_name`, `kelompok`, `sort_order`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $mapelValues) . ";\n\n";

// 6. Mata Pelajaran Kelas & Matriks Jadwal
$sql .= "-- 6. Mata Pelajaran Kelas & Jadwal Pelajaran (Matriks Lengkap Semester Ganjil 2026/2027)\n";
$mpkValues = [];
$jadwalValues = [];
$mpkId = 1;
$jadwalId = 1;
$mpkLookup = []; // "classId_subjectId" -> mpkId

$dayMap = [
    'SENIN' => ['num' => 1, 'nama' => 'Senin'],
    'SELASA' => ['num' => 2, 'nama' => 'Selasa'],
    'RABU' => ['num' => 3, 'nama' => 'Rabu'],
    'KAMIS' => ['num' => 4, 'nama' => 'Kamis'],
    'JUMAT' => ['num' => 5, 'nama' => 'Jumat'],
    'SABTU' => ['num' => 6, 'nama' => 'Sabtu'],
];

if (isset($dataJadwal['jadwal']) && is_array($dataJadwal['jadwal'])) {
    foreach ($dataJadwal['jadwal'] as $hariKey => $slots) {
        $hariInfo = $dayMap[$hariKey] ?? ['num' => 1, 'nama' => 'Senin'];

        foreach ($slots as $slot) {
            $jamMulai = $slot['jam_mulai'] . ':00';
            $jamSelesai = $slot['jam_selesai'] . ':00';

            foreach ($slot['kelas'] as $className => $kode) {
                if (empty($kode) || $kode === 'UPACARA' || $kode === 'ISTIRAHAT') {
                    continue;
                }

                // Format kode: misal "02A" -> guru 02, mapel A
                $guruKode = substr($kode, 0, 2);
                $mapelLetter = substr($kode, 2);

                if (!isset($mapelIdMap[$mapelLetter]) || !isset($teacherMap[$guruKode])) {
                    continue;
                }

                $cId = $classIdMap[$className] ?? null;
                $sId = $mapelIdMap[$mapelLetter];
                $tId = $teacherMap[$guruKode];

                if (!$cId) continue;

                $mpkKey = "{$cId}_{$sId}";
                if (!isset($mpkLookup[$mpkKey])) {
                    $mpkLookup[$mpkKey] = $mpkId;
                    $mpkValues[] = "({$mpkId}, {$cId}, {$cId}, {$sId}, {$sId}, {$tId}, {$tId}, 2, 2, 1, NOW())";
                    $mpkId++;
                }

                $curMpkId = $mpkLookup[$mpkKey];
                $room = 'Ruang Kelas ' . $className;

                $jadwalValues[] = "({$jadwalId}, {$curMpkId}, {$cId}, {$sId}, {$sId}, {$tId}, {$tId}, 1, {$hariInfo['num']}, '{$hariInfo['nama']}', '{$jamMulai}', '{$jamMulai}', '{$jamSelesai}', '{$jamSelesai}', '{$room}', '{$room}', 1, NOW())";
                $jadwalId++;
            }
        }
    }
}

if (!empty($mpkValues)) {
    $sql .= "INSERT INTO `mata_pelajaran_kelas` (`id`, `class_id`, `kelas_id`, `subject_id`, `mata_pelajaran_id`, `teacher_id`, `guru_id`, `weekly_hours`, `jam_mingguan`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $mpkValues) . ";\n\n";
}

if (!empty($jadwalValues)) {
    $sql .= "INSERT INTO `jadwal` (`id`, `class_subject_id`, `kelas_id`, `subject_id`, `mata_pelajaran_id`, `teacher_id`, `guru_id`, `academic_year_id`, `day_of_week`, `hari`, `start_time`, `jam_mulai`, `end_time`, `jam_selesai`, `room`, `ruangan`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $jadwalValues) . ";\n\n";
}

// 7. Siswa (154 Siswa Riil dari CSV) & Akun Pengguna Siswa
$sql .= "-- 7. Data Siswa Riil (154 Siswa dari CSV dapodik MTs Al-Huda)\n";
$siswaUserValues = [];
$siswaValues = [];
$header = null;
$sCount = 0;

foreach ($siswaCsv as $line) {
    $row = str_getcsv($line);
    if ($header === null) {
        $header = $row;
        continue;
    }
    if (empty($row[0])) continue;

    $sCount++;
    $siswaId = $sCount;
    $sUserId = 100 + $sCount;
    $sNis = '2026' . str_pad((string)$sCount, 4, '0', STR_PAD_LEFT);

    $sNama = trim($row[1]);
    $sNisn = trim($row[2]);
    $sTempat = trim($row[3]);
    $sTgl = trim($row[4]);
    $sKelasName = trim($row[5]);
    $sJk = (strtoupper(substr($row[6], 0, 1)) === 'P') ? 'P' : 'L';
    $sAlamat = trim($row[7]);

    $cId = $classIdMap[$sKelasName] ?? 1;

    $username = 'siswa_' . ($sNisn ?: $sNis);
    $email = $username . '@siswa.mts-alhuda.sch.id';

    $siswaUserValues[] = "({$sUserId}, " . esc($username) . ", " . esc($email) . ", '{$defaultPasswordHash}', " . esc($sNama) . ", " . esc($sNama) . ", 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW())";

    $siswaValues[] = "({$siswaId}, {$sUserId}, {$cId}, {$cId}, " . esc($sNis) . ", " . esc($sNisn) . ", " . esc($sNama) . ", '{$sJk}', '{$sJk}', " . esc($sTempat) . ", " . esc($sTempat) . ", " . esc($sTgl) . ", " . esc($sTgl) . ", 'Wali Murid', 'Wali Murid', NULL, " . esc($sAlamat) . ", " . esc($sAlamat) . ", NULL, '2026-07-15', 2026, 'aktif', 1, NOW())";
}

$sql .= "INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES\n" . implode(",\n", $siswaUserValues) . ";\n\n";

$sql .= "INSERT INTO `siswa` (`id`, `user_id`, `class_id`, `kelas_id`, `nis`, `nisn`, `nama_lengkap`, `gender`, `jenis_kelamin`, `birth_place`, `tempat_lahir`, `birth_date`, `tanggal_lahir`, `guardian_name`, `nama_wali`, `guardian_phone`, `address`, `alamat`, `foto_url`, `enrolled_at`, `tahun_masuk`, `status`, `is_active`, `created_at`) VALUES\n" . implode(",\n", $siswaValues) . ";\n\n";

// 8. Lokasi Sekolah (QR & GPS Geofencing)
$sql .= "-- 8. Lokasi Sekolah untuk Presensi Mobile QR & GPS\n";
$sql .= "INSERT INTO `lokasi_sekolah` (`id`, `nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`, `created_at`) VALUES\n";
$sql .= "(1, 'Kantor Guru MTs Al-Huda Gondang', 'MTS-ALHUDA-ABSENSI-2026-SECURE', -7.56234500, 111.89012300, 20, 1, NOW());\n\n";

// 9. Presensi Guru (Contoh Data Riil Hari Ini)
$sql .= "-- 9. Contoh Presensi Guru\n";
$sql .= "INSERT INTO `presensi_guru` (`id`, `teacher_id`, `guru_id`, `attendance_date`, `tanggal`, `attendance_type`, `check_in_at`, `jam_masuk`, `status`, `notes`, `keterangan`, `created_at`) VALUES\n";
$sql .= "(1, 1, 1, CURDATE(), CURDATE(), 'daily', NOW(), '06:45:00', 'hadir', 'Presensi tepat waktu via Mobile App', 'Presensi tepat waktu via Mobile App', NOW()),\n";
$sql .= "(2, 2, 2, CURDATE(), CURDATE(), 'daily', NOW(), '06:50:00', 'hadir', 'Hadir tepat waktu', 'Hadir tepat waktu', NOW()),\n";
$sql .= "(3, 3, 3, CURDATE(), CURDATE(), 'daily', NOW(), '06:55:00', 'hadir', 'Hadir tepat waktu', 'Hadir tepat waktu', NOW());\n\n";

// 10. Pengaturan Dasar (Settings)
$sql .= "-- 10. Pengaturan Dasar Madrasah\n";
$settingsData = [
    ['site_name', 'MTs Al-Huda', 'string', 'identitas', 'Nama madrasah', 1],
    ['site_subtitle', 'Gondang', 'string', 'identitas', 'Keterangan nama', 1],
    ['site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang', 'string', 'identitas', 'Nama lengkap madrasah', 1],
    ['site_nsm', '121235180045', 'string', 'identitas', 'Nomor Statistik Madrasah (NSM)', 1],
    ['site_npsn', '20582234', 'string', 'identitas', 'NPSN', 1],
    ['site_foundation', 'Yayasan Pendidikan Al-Huda Gondang', 'string', 'identitas', 'Nama yayasan', 1],
    ['accreditation_grade', 'A', 'string', 'identitas', 'Peringkat akreditasi BAN-S/M', 1],
    ['accreditation_year', '2024', 'string', 'identitas', 'Tahun akreditasi', 1],
    ['contact_address', 'Jl. Raya Gondang, Pandean, Kec. Gondang, Kab. Nganjuk, Jawa Timur 64451', 'text', 'kontak', 'Alamat lengkap', 1],
    ['contact_phone', '(0358) 771234', 'string', 'kontak', 'Nomor telepon kantor', 1],
    ['contact_whatsapp', '6281234567890', 'string', 'kontak', 'Nomor WhatsApp resmi', 1],
    ['contact_email', 'info@mts-alhuda.sch.id', 'string', 'kontak', 'Email resmi madrasah', 1],
    ['contact_hours', 'Senin - Sabtu, 07.00 - 14.00 WIB', 'string', 'kontak', 'Jam operasional sekolah', 1],
    ['contact_map_lat', '-7.562345', 'string', 'kontak', 'Latitude lokasi', 1],
    ['contact_map_lng', '111.890123', 'string', 'kontak', 'Longitude lokasi', 1],
    ['social_facebook', 'https://facebook.com/mtsalhudagondang', 'string', 'sosial', 'Facebook resmi', 1],
    ['social_instagram', 'https://instagram.com/mtsalhudagondang', 'string', 'sosial', 'Instagram resmi', 1],
    ['social_youtube', 'https://youtube.com/@mtsalhudagondang', 'string', 'sosial', 'Channel YouTube', 1],
    ['social_tiktok', 'https://tiktok.com/@mtsalhudagondang', 'string', 'sosial', 'Akun TikTok', 1],
    ['meta_title', 'MTs Al-Huda Gondang | Madrasah Berakhlak, Berilmu, dan Berprestasi', 'string', 'seo', 'Judul SEO situs', 1],
    ['meta_description', 'Situs resmi Madrasah Tsanawiyah Al-Huda Gondang Nganjuk. Menyajikan informasi profil, pendaftaran PPDB, kurikulum terpadu, dan portal akademik Madrasahku.', 'text', 'seo', 'Deskripsi SEO situs', 1],
    ['attendance_check_in_time', '07:00', 'string', 'presensi', 'Batas jam masuk', 0],
    ['attendance_late_tolerance', '15', 'int', 'presensi', 'Toleransi keterlambatan (menit)', 0],
    ['attendance_check_out_time', '14:00', 'string', 'presensi', 'Jam mulai boleh pulang', 0],
    ['security_max_login_attempts', '5', 'int', 'keamanan', 'Batas percobaan login gagal', 0],
    ['security_lockout_minutes', '15', 'int', 'keamanan', 'Durasi penguncian akun (menit)', 0],
    ['security_session_timeout', '120', 'int', 'keamanan', 'Batas waktu sesi web (menit)', 0],
    ['security_api_access_ttl', '60', 'int', 'keamanan', 'Masa aktif akses token API (menit)', 0],
    ['ppdb_status', 'buka', 'string', 'ppdb', 'Status Pendaftaran PPDB', 1],
    ['ppdb_tahun_ajaran', '2026/2027', 'string', 'ppdb', 'Tahun ajaran PPDB dibuka', 1],
    ['ppdb_kuota', '180', 'int', 'ppdb', 'Total daya tampung PPDB', 1],
];
$settingValues = [];
foreach ($settingsData as $s) {
    $settingValues[] = "('" . addslashes($s[0]) . "', " . esc($s[1]) . ", '{$s[2]}', '{$s[3]}', " . esc($s[4]) . ", {$s[5]}, NOW())";
}
$sql .= "INSERT INTO `pengaturan` (`setting_key`, `setting_value`, `value_type`, `group_name`, `label`, `is_public`, `created_at`) VALUES\n" . implode(",\n", $settingValues) . "\nON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);\n\n";

// 11. Konten Publik (Berita, Fasilitas, Galeri, Pengumuman, Agenda, Halaman)
$sql .= "-- 11. Konten Publik CMS MTs Al-Huda\n";

// Berita
$sql .= "INSERT INTO `berita` (`id`, `title`, `judul`, `slug`, `excerpt`, `content`, `isi`, `image_path`, `gambar`, `category`, `kategori`, `status`, `published_at`, `author_id`) VALUES\n";
$sql .= "(1, 'Peringatan Hari Santri Nasional Berlangsung Khidmat di Lapangan Madrasah', 'Peringatan Hari Santri Nasional Berlangsung Khidmat di Lapangan Madrasah', 'peringatan-hari-santri-nasional-berlangsung-khidmat', 'Seluruh dewan guru, staf, dan ratusan santri MTs Al-Huda Gondang mengikuti apel akbar peringatan Hari Santri Nasional dengan mengenakan busana muslim serba putih.', 'Seluruh dewan guru, karyawan, serta 154 peserta didik MTs Al-Huda Gondang memadati lapangan madrasah pada upacara peringatan Hari Santri Nasional. Bertindak selaku pembina upacara, Kepala Madrasah menekankan pentingnya jihad intelektual, adab santri, serta penguasaan teknologi di era digital. Acara dilanjutkan dengan penampilan paduan suara madrasah dan doa bersama untuk bangsa.', 'Seluruh dewan guru, karyawan, serta 154 peserta didik MTs Al-Huda Gondang memadati lapangan madrasah pada upacara peringatan Hari Santri Nasional. Bertindak selaku pembina upacara, Kepala Madrasah menekankan pentingnya jihad intelektual, adab santri, serta penguasaan teknologi di era digital. Acara dilanjutkan dengan penampilan paduan suara madrasah dan doa bersama untuk bangsa.', 'images/berita/hari-santri.jpg', 'images/berita/hari-santri.jpg', 'Kegiatan', 'Kegiatan', 'published', NOW(), 1),\n";
$sql .= "(2, 'Siswa MTs Al-Huda Raih Medali Emas Olimpiade Sains Madrasah Tingkat Kabupaten', 'Siswa MTs Al-Huda Raih Medali Emas Olimpiade Sains Madrasah Tingkat Kabupaten', 'siswa-mts-al-huda-raih-medali-emas-olimpiade-sains', 'Prestasi membanggakan kembali ditorehkan ananda peserta didik MTs Al-Huda dalam ajang Kompetisi Sains Madrasah (KSM) bidang Matematika dan IPA Terpadu.', 'Prestasi membanggakan kembali ditorehkan oleh santri binaan MTs Al-Huda Gondang dalam ajang Kompetisi Sains Madrasah (KSM) tingkat kabupaten. Melalui bimbingan intensif dewan guru MIPA, perwakilan madrasah berhasil membawa pulang Medali Emas dan berhak melaju ke tingkat provinsi. Kepala madrasah menyampaikan apresiasi setinggi-tingginya kepada pembina dan peserta didik.', 'Prestasi membanggakan kembali ditorehkan oleh santri binaan MTs Al-Huda Gondang dalam ajang Kompetisi Sains Madrasah (KSM) tingkat kabupaten. Melalui bimbingan intensif dewan guru MIPA, perwakilan madrasah berhasil membawa pulang Medali Emas dan berhak melaju ke tingkat provinsi. Kepala madrasah menyampaikan apresiasi setinggi-tingginya kepada pembina dan peserta didik.', 'images/berita/prestasi-ksm.jpg', 'images/berita/prestasi-ksm.jpg', 'Prestasi', 'Prestasi', 'published', NOW(), 1);\n\n";

// Fasilitas
$sql .= "INSERT INTO `fasilitas` (`id`, `name`, `nama_fasilitas`, `slug`, `category`, `description`, `deskripsi`, `image_path`, `gambar`, `condition`, `kondisi`, `quantity`, `jumlah`, `sort_order`, `is_active`) VALUES\n";
$sql .= "(1, 'Laboratorium Komputer & Multimedia', 'Laboratorium Komputer & Multimedia', 'laboratorium-komputer-multimedia', 'Sarana Pembelajaran', 'Ruang lab komputer berpendingin udara (AC) dengan 36 unit komputer personal generasi mutakhir, jaringan serat optik kecepatan tinggi, dan proyektor interaktif untuk pembelajaran TIK dan Asesmen Nasional.', 'Ruang lab komputer berpendingin udara (AC) dengan 36 unit komputer personal generasi mutakhir, jaringan serat optik kecepatan tinggi, dan proyektor interaktif untuk pembelajaran TIK dan Asesmen Nasional.', 'images/fasilitas/lab-komputer.jpg', 'images/fasilitas/lab-komputer.jpg', 'sangat_baik', 'sangat_baik', 36, 36, 1, 1),\n";
$sql .= "(2, 'Perpustakaan Khazanah Ilmu', 'Perpustakaan Khazanah Ilmu', 'perpustakaan-khazanah-ilmu', 'Sarana Literasi', 'Koleksi ribuan buku pelajaran, kitab kuning, ensiklopedia sains, novel sastra, serta area baca lesehan yang hening dan nyaman bagi santri.', 'Koleksi ribuan buku pelajaran, kitab kuning, ensiklopedia sains, novel sastra, serta area baca lesehan yang hening dan nyaman bagi santri.', 'images/fasilitas/perpustakaan.jpg', 'images/fasilitas/perpustakaan.jpg', 'baik', 'baik', 1, 1, 2, 1),\n";
$sql .= "(3, 'Masjid Jami Al-Huda', 'Masjid Jami Al-Huda', 'masjid-jami-al-huda', 'Sarana Ibadah', 'Pusat pembinaan spiritual madrasah untuk pelaksanaan shalat Dhuha berjamaah, dhuhur berjamaah, pembacaan ratibul haddad, serta kajian kitab akhlak santri.', 'Pusat pembinaan spiritual madrasah untuk pelaksanaan shalat Dhuha berjamaah, dhuhur berjamaah, pembacaan ratibul haddad, serta kajian kitab akhlak santri.', 'images/fasilitas/masjid.jpg', 'images/fasilitas/masjid.jpg', 'sangat_baik', 'sangat_baik', 1, 1, 3, 1),\n";
$sql .= "(4, 'Laboratorium IPA Terpadu', 'Laboratorium IPA Terpadu', 'laboratorium-ipa-terpadu', 'Sarana Pembelajaran', 'Fasilitas eksperimen Fisika, Biologi, dan Kimia dasar lengkap dengan mikroskop binokuler, alat peraga anatomi manusia, dan reagen praktikum aman.', 'Fasilitas eksperimen Fisika, Biologi, dan Kimia dasar lengkap dengan mikroskop binokuler, alat peraga anatomi manusia, dan reagen praktikum aman.', 'images/fasilitas/lab-ipa.jpg', 'images/fasilitas/lab-ipa.jpg', 'baik', 'baik', 1, 1, 4, 1);\n\n";

// Galeri
$sql .= "INSERT INTO `galeri` (`id`, `album`, `title`, `judul`, `caption`, `keterangan`, `image_path`, `gambar`, `sort_order`, `is_active`, `uploaded_by`) VALUES\n";
$sql .= "(1, 'Kegiatan Santri', 'Shalat Dhuha Berjamaah Pagi Hari', 'Shalat Dhuha Berjamaah Pagi Hari', 'Rutinitas pembiasaan shalat Dhuha berjamaah setiap pagi sebelum memulai jam pelajaran pertama.', 'Rutinitas pembiasaan shalat Dhuha berjamaah setiap pagi sebelum memulai jam pelajaran pertama.', 'images/galeri/dhuha-pagi.jpg', 'images/galeri/dhuha-pagi.jpg', 1, 1, 1),\n";
$sql .= "(2, 'Ekstrakurikuler', 'Latihan Regu Pramuka MTs Al-Huda', 'Latihan Regu Pramuka MTs Al-Huda', 'Latihan semaphore, tali temali, dan baris berbaris regu penggalang pramuka madrasah.', 'Latihan semaphore, tali temali, dan baris berbaris regu penggalang pramuka madrasah.', 'images/galeri/pramuka.jpg', 'images/galeri/pramuka.jpg', 2, 1, 1),\n";
$sql .= "(3, 'Pembelajaran', 'Praktikum Sains di Laboratorium', 'Praktikum Sains di Laboratorium', 'Antusiasme santri mengamati spesimen biologi sel tumbuhan menggunakan mikroskop.', 'Antusiasme santri mengamati spesimen biologi sel tumbuhan menggunakan mikroskop.', 'images/galeri/praktikum.jpg', 'images/galeri/praktikum.jpg', 3, 1, 1);\n\n";

// Pengumuman
$sql .= "INSERT INTO `pengumuman` (`id`, `title`, `judul`, `slug`, `content`, `isi`, `is_pinned`, `status`, `published_at`, `author_id`) VALUES\n";
$sql .= "(1, 'Penerimaan Peserta Didik Baru (PPDB) Jalur Prestasi dan Reguler Telah Dibuka', 'Penerimaan Peserta Didik Baru (PPDB) Jalur Prestasi dan Reguler Telah Dibuka', 'ppdb-2026-2027-telah-dibuka', 'Diberitahukan kepada seluruh calon wali murid bahwa pendaftaran siswa baru MTs Al-Huda Gondang untuk Tahun Ajaran 2026/2027 telah resmi dibuka secara online maupun langsung di kantor sekretariat madrasah.', 'Diberitahukan kepada seluruh calon wali murid bahwa pendaftaran siswa baru MTs Al-Huda Gondang untuk Tahun Ajaran 2026/2027 telah resmi dibuka secara online maupun langsung di kantor sekretariat madrasah.', 1, 'published', NOW(), 1),\n";
$sql .= "(2, 'Jadwal Penilaian Akhir Semester (PAS) Ganjil Tahun Ajaran 2026/2027', 'Jadwal Penilaian Akhir Semester (PAS) Ganjil Tahun Ajaran 2026/2027', 'jadwal-penilaian-akhir-semester-ganjil', 'Penilaian Akhir Semester (PAS) Ganjil akan dilaksanakan mulai tanggal 1 Desember 2026. Santri diharapkan melunasi administrasi dan mempersiapkan kartu ujian.', 'Penilaian Akhir Semester (PAS) Ganjil akan dilaksanakan mulai tanggal 1 Desember 2026. Santri diharapkan melunasi administrasi dan mempersiapkan kartu ujian.', 0, 'published', NOW(), 1);\n\n";

// Agenda
$sql .= "INSERT INTO `agenda` (`id`, `title`, `judul`, `description`, `deskripsi`, `location`, `lokasi`, `start_at`, `tgl_mulai`, `end_at`, `tgl_selesai`, `all_day`, `status`, `author_id`) VALUES\n";
$sql .= "(1, 'Penilaian Akhir Semester (PAS) Ganjil', 'Penilaian Akhir Semester (PAS) Ganjil', 'Ujian evaluasi hasil belajar semester ganjil untuk seluruh jenjang kelas 7, 8, dan 9.', 'Ujian evaluasi hasil belajar semester ganjil untuk seluruh jenjang kelas 7, 8, dan 9.', 'Ruang Ujian MTs Al-Huda', 'Ruang Ujian MTs Al-Huda', '2026-12-01 07:00:00', '2026-12-01 07:00:00', '2026-12-08 12:30:00', '2026-12-08 12:30:00', 0, 'published', 1),\n";
$sql .= "(2, 'Pondok Ramadhan & Bakti Sosial Santri', 'Pondok Ramadhan & Bakti Sosial Santri', 'Kajian intensif fikih puasa, tadarus 30 juz, serta pembagian paket sembako kepada dhuafa di lingkungan sekitar madrasah.', 'Kajian intensif fikih puasa, tadarus 30 juz, serta pembagian paket sembako kepada dhuafa di lingkungan sekitar madrasah.', 'Masjid Al-Huda & Lingkungan Desa', 'Masjid Al-Huda & Lingkungan Desa', '2027-03-15 08:00:00', '2027-03-15 08:00:00', '2027-03-20 16:00:00', '2027-03-20 16:00:00', 0, 'published', 1);\n\n";

// Dokumen PPDB
$sql .= "INSERT INTO `dokumen_ppdb` (`id`, `title`, `judul`, `description`, `file_path`, `file_url`, `file_size`, `mime_type`, `download_count`, `jumlah_unduh`, `sort_order`, `is_active`, `uploaded_by`) VALUES\n";
$sql .= "(1, 'Brosur & Panduan Pendaftaran PPDB 2026/2027', 'Brosur & Panduan Pendaftaran PPDB 2026/2027', 'Informasi alur pendaftaran, syarat berkas, seragam, dan program beasiswa tahfidz.', 'uploads/ppdb/brosur-ppdb-2026.pdf', 'uploads/ppdb/brosur-ppdb-2026.pdf', 1450000, 'application/pdf', 128, 128, 1, 1, 1),\n";
$sql .= "(2, 'Formulir Registrasi Siswa Baru (Format Cetak)', 'Formulir Registrasi Siswa Baru (Format Cetak)', 'Formulir biodata santri dan surat pernyataan kesanggupan tata tertib bermaterai.', 'uploads/ppdb/formulir-pendaftaran-2026.pdf', 'uploads/ppdb/formulir-pendaftaran-2026.pdf', 850000, 'application/pdf', 74, 74, 2, 1, 1);\n\n";

// Pesan Kontak Contoh
$sql .= "INSERT INTO `pesan_kontak` (`id`, `nama`, `email`, `telepon`, `subjek`, `pesan`, `status`, `balasan`, `created_at`) VALUES\n";
$sql .= "(1, 'Budi Santoso', 'budisantoso@gmail.com', '081298765432', 'Tanya Syarat PPDB Jalur Prestasi', 'Assalamu alaikum. Apakah untuk santri yang memiliki hafalan Al-Quran 3 Juz mendapatkan beasiswa SPP bebas hingga lulus? Mohon informasinya, terima kasih.', 'baru', NULL, NOW()),\n";
$sql .= "(2, 'Siti Rahmawati', 'rahmawatisiti@yahoo.com', '085712348901', 'Konfirmasi Biaya Seragam Santri Putri', 'Selamat pagi pihak tata usaha MTs Al-Huda. Untuk rincian seragam santri putri apakah sudah termasuk jilbab madrasah?', 'dibaca', NULL, NOW());\n\n";

// Menu Navigasi
$sql .= "INSERT INTO `menu` (`id`, `parent_id`, `title`, `url`, `target`, `sort_order`, `is_active`) VALUES\n";
$sql .= "(1, NULL, 'Beranda', '/', '_self', 1, 1),\n";
$sql .= "(2, NULL, 'Profil', '/profil', '_self', 2, 1),\n";
$sql .= "(3, NULL, 'Akademik', '/akademik', '_self', 3, 1),\n";
$sql .= "(4, NULL, 'Berita', '/berita', '_self', 4, 1),\n";
$sql .= "(5, NULL, 'Fasilitas', '/fasilitas', '_self', 5, 1),\n";
$sql .= "(6, NULL, 'Galeri', '/galeri', '_self', 6, 1),\n";
$sql .= "(7, NULL, 'PPDB', '/ppdb', '_self', 7, 1),\n";
$sql .= "(8, NULL, 'Kontak', '/kontak', '_self', 8, 1);\n\n";

// Riwayat Migrasi Resmi
$sql .= "INSERT INTO `schema_migrations` (`version`, `name`, `batch`, `statements`, `executed_at`) VALUES\n";
$sql .= "('001', 'foundation', 1, 3, NOW()),\n";
$sql .= "('002', 'identity_core', 1, 3, NOW()),\n";
$sql .= "('003', 'identity_security', 1, 4, NOW()),\n";
$sql .= "('004', 'cms_content', 1, 5, NOW()),\n";
$sql .= "('005', 'cms_support', 1, 5, NOW()),\n";
$sql .= "('006', 'academic_core', 1, 4, NOW()),\n";
$sql .= "('007', 'academic_learning', 1, 4, NOW()),\n";
$sql .= "('008', 'teacher_attendance', 1, 2, NOW()),\n";
$sql .= "('009', 'notifications', 1, 1, NOW()),\n";
$sql .= "('013', 'api_support', 1, 2, NOW()),\n";
$sql .= "('014', 'public_ids', 1, 2, NOW()),\n";
$sql .= "('015', 'madrasahku_integration', 1, 12, NOW()),\n";
$sql .= "('016', 'madrasahku_full_schema', 1, 35, NOW());\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$sql .= "-- ==========================================================================\n";
$sql .= "-- AKHIR DUMP MASTER DATABASE MTs AL-HUDA GONDANG\n";
$sql .= "-- Total Pengguna : " . ($sCount + count($dataGuru) + 3) . " akun\n";
$sql .= "-- Total Guru     : " . count($dataGuru) . " guru riil\n";
$sql .= "-- Total Siswa    : " . $sCount . " santri riil\n";
$sql .= "-- Total Mapel    : " . count($mapelList) . " mata pelajaran\n";
$sql .= "-- ==========================================================================\n";

$outFileProd = $ROOT . '/database/mts_alhuda_production.sql';
file_put_contents($outFileProd, $sql);
echo "Berhasil menghasilkan master production SQL: " . $outFileProd . " (" . number_format(filesize($outFileProd)) . " bytes)\n";

$outFileDev = $ROOT . '/database/mts_alhuda.sql';
file_put_contents($outFileDev, $sql);
echo "Berhasil menyinkronkan database/mts_alhuda.sql (" . number_format(filesize($outFileDev)) . " bytes)\n";
