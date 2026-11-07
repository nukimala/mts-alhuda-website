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

-- ==========================================================================
-- DATA SEEDING (Pengaturan, Akun Inti, Guru, Siswa, Kelas, Mapel, Jadwal)
-- ==========================================================================

-- 1. Tahun Akademik
INSERT INTO `tahun_akademik` (`id`, `name`, `nama_tahun`, `semester`, `start_date`, `end_date`, `is_active`, `note`) VALUES
(1, '2026/2027', '2026/2027', 'ganjil', '2026-07-13', '2026-12-19', 1, 'Semester Ganjil Tahun Ajaran 2026/2027');

-- 2. Akun Administrator & Kepala Madrasah
INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES
(1, 'admin', 'admin@mts-alhuda.sch.id', '$2y$10$COo56WXMSpqqUjgyzWvp5.BYdxdL3gYQzrMO9JCQ0Nd2yTN3c4/jS', 'Administrator Madrasah', 'Administrator Madrasah', 'admin', 'Super Admin', '081234567890', '081234567890', 1, 0, NOW()),
(2, 'operator', 'operator@mts-alhuda.sch.id', '$2y$10$COo56WXMSpqqUjgyzWvp5.BYdxdL3gYQzrMO9JCQ0Nd2yTN3c4/jS', 'Operator Madrasah', 'Operator Madrasah', 'operator', 'Operator Madrasah', '081234567891', '081234567891', 1, 0, NOW()),
(3, 'sholihin', 'sholihin@mts-alhuda.sch.id', '$2y$10$COo56WXMSpqqUjgyzWvp5.BYdxdL3gYQzrMO9JCQ0Nd2yTN3c4/jS', 'Drs. H. M. Sholihin, M.Pd.I', 'Drs. H. M. Sholihin, M.Pd.I', 'kepala', 'Kepala Madrasah', '081234567892', '081234567892', 1, 0, NOW());

-- 3. Guru & Pengguna Guru
INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES
(10, 'guru_said', 'guru_said@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'Drs. SAID', 'Drs. SAID', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(11, 'guru_sarmin', 'guru_sarmin@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'Drs. SARMIN', 'Drs. SARMIN', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(12, 'guru_mohammadmusthofa', 'guru_mohammadmusthofa@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHAMMAD MUSTHOFA, S.Pd.I', 'MOHAMMAD MUSTHOFA, S.Pd.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(13, 'guru_faridkurniawannovianto', 'guru_faridkurniawannovianto@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FARID KURNIAWAN NOVIANTO, SH', 'FARID KURNIAWAN NOVIANTO, SH', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(14, 'guru_subiyatin', 'guru_subiyatin@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SUBIYATIN, S.Si', 'SUBIYATIN, S.Si', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(15, 'guru_lilikrahmawati', 'guru_lilikrahmawati@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'LILIK RAHMAWATI, S.Pd', 'LILIK RAHMAWATI, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(16, 'guru_agussetiawan', 'guru_agussetiawan@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AGUS SETIAWAN, S.Pd', 'AGUS SETIAWAN, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(17, 'guru_setyojatmiko', 'guru_setyojatmiko@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SETYO JATMIKO, S.Pd', 'SETYO JATMIKO, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(18, 'guru_sodikinmuslimin', 'guru_sodikinmuslimin@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SODIKIN MUSLIMIN, S.Pd', 'SODIKIN MUSLIMIN, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(19, 'guru_ulfatulkhumaydah', 'guru_ulfatulkhumaydah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ULFATUL KHUMAYDAH, S.H.I', 'ULFATUL KHUMAYDAH, S.H.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(20, 'guru_sitiamiroh', 'guru_sitiamiroh@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SITI AMIROH, S.Pd.I', 'SITI AMIROH, S.Pd.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(21, 'guru_agusbudiirawan', 'guru_agusbudiirawan@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AGUS BUDI IRAWAN, S.Pd.', 'AGUS BUDI IRAWAN, S.Pd.', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(22, 'guru_muhammadabdulharisjauhari', 'guru_muhammadabdulharisjauhari@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD ABDUL HARIS JAUHARI, S.Pd.I', 'MUHAMMAD ABDUL HARIS JAUHARI, S.Pd.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(23, 'guru_sitimunfarida', 'guru_sitimunfarida@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SITI MUNFARIDA, S.Pd.I', 'SITI MUNFARIDA, S.Pd.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(24, 'guru_faridatunnimah', 'guru_faridatunnimah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FARIDATUN NI`MAH, S.Pd.I', 'FARIDATUN NI`MAH, S.Pd.I', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(25, 'guru_intanrosianadevi', 'guru_intanrosianadevi@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'INTAN ROSIANA DEVI, S.Pd.', 'INTAN ROSIANA DEVI, S.Pd.', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(26, 'guru_octaviautswatunkhasanah', 'guru_octaviautswatunkhasanah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'OCTAVIA UTSWATUN KHASANAH, S.Pd', 'OCTAVIA UTSWATUN KHASANAH, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(27, 'guru_nurulchofifah', 'guru_nurulchofifah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NURUL CHOFIFAH, S.Pd', 'NURUL CHOFIFAH, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(28, 'guru_mochsyabanwulana', 'guru_mochsyabanwulana@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOCH. SYABAN WULANA, S.Pd', 'MOCH. SYABAN WULANA, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(29, 'guru_windatriyana', 'guru_windatriyana@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'WINDA TRIYANA, S.Pd', 'WINDA TRIYANA, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(30, 'guru_nopipurwanti', 'guru_nopipurwanti@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NOPI PURWANTI, S.Pd', 'NOPI PURWANTI, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(31, 'guru_kumalaazizatullatifah', 'guru_kumalaazizatullatifah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KUMALA AZIZATUL LATIFAH, S.Pd', 'KUMALA AZIZATUL LATIFAH, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(32, 'guru_titikyuliati', 'guru_titikyuliati@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TITIK YULIATI, S.Pd', 'TITIK YULIATI, S.Pd', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(33, 'guru_nurfuad', 'guru_nurfuad@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NUR FUAD, S.H.I, M.A.', 'NUR FUAD, S.H.I, M.A.', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(34, 'guru_sulijadi', 'guru_sulijadi@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SULIJADI, M.M', 'SULIJADI, M.M', 'guru', 'Guru', NULL, NULL, 1, 0, NOW()),
(35, 'guru_alitadianwahyushalikhah', 'guru_alitadianwahyushalikhah@mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ALITA DIAN WAHYU SHALIKHAH', 'ALITA DIAN WAHYU SHALIKHAH', 'guru', 'Guru', NULL, NULL, 1, 0, NOW());

INSERT INTO `guru` (`id`, `user_id`, `nip`, `nuptk`, `gelar_depan`, `nama_lengkap`, `gelar_belakang`, `gender`, `jenis_kelamin`, `phone`, `no_hp`, `address`, `alamat`, `foto_url`, `employment_status`, `status_kepegawaian`, `subject_specialty`, `bidang_studi`, `kode_jadwal`, `joined_at`, `is_active`, `created_at`) VALUES
(1, 10, '0838743647200032', '0838743647200032', 'Drs.', 'Drs. SAID', '', 'L', 'L', NULL, NULL, 'Toko Pojok, RT. 004 RW. 002 Pandean, Gondang, Nganjuk 64451', 'Toko Pojok, RT. 004 RW. 002 Pandean, Gondang, Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '02', '2020-01-01', 1, NOW()),
(2, 11, '3439745645200003', '3439745645200003', 'Drs.', 'Drs. SARMIN', '', 'L', 'L', NULL, NULL, 'RT 01 RW 02 DS. JINTEL KEC. REJOSO KAB. NGANJUK', 'RT 01 RW 02 DS. JINTEL KEC. REJOSO KAB. NGANJUK', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '04', '2020-01-01', 1, NOW()),
(3, 12, '6553757659200012', '6553757659200012', '', 'MOHAMMAD MUSTHOFA, S.Pd.I', 'S.Pd.I', 'L', 'L', NULL, NULL, 'Jl. Soeprapto 24 RT. 004 RW. 002 Pandean, Gondang, Nganjuk 64451', 'Jl. Soeprapto 24 RT. 004 RW. 002 Pandean, Gondang, Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '05', '2020-01-01', 1, NOW()),
(4, 13, '0456757659200023', '0456757659200023', '', 'FARID KURNIAWAN NOVIANTO, SH', 'SH', 'L', 'L', NULL, NULL, 'RT/RW 02/02 Dsn Sambong Desa Campur Kecamatan Gondang Kabupaten Nganjuk', 'RT/RW 02/02 Dsn Sambong Desa Campur Kecamatan Gondang Kabupaten Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '06', '2020-01-01', 1, NOW()),
(5, 14, '7146757658300023', '7146757658300023', '', 'SUBIYATIN, S.Si', 'S.Si', 'P', 'P', NULL, NULL, 'RT/RW 02/02 Dsn Sambong Desa Campur Kecamatan Gondang Kabupaten Nganjuk', 'RT/RW 02/02 Dsn Sambong Desa Campur Kecamatan Gondang Kabupaten Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '07', '2020-01-01', 1, NOW()),
(6, 15, '5062760661210093', '5062760661210093', '', 'LILIK RAHMAWATI, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'SAMBONG RT.002 RW.005 DESA CAMPUR KECAMATAN GONDANG KABUPATEN NGANJUK', 'SAMBONG RT.002 RW.005 DESA CAMPUR KECAMATAN GONDANG KABUPATEN NGANJUK', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '08', '2020-01-01', 1, NOW()),
(7, 16, '1354760667200003', '1354760667200003', '', 'AGUS SETIAWAN, S.Pd', 'S.Pd', 'L', 'L', NULL, NULL, 'RT/RW :002/001, Dsn.Sawahan, Ds. Balonggebang, Kec.Gondang, Kab. Nganjuk', 'RT/RW :002/001, Dsn.Sawahan, Ds. Balonggebang, Kec.Gondang, Kab. Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '09', '2020-01-01', 1, NOW()),
(8, 17, '9261760662200023', '9261760662200023', '', 'SETYO JATMIKO, S.Pd', 'S.Pd', 'L', 'L', NULL, NULL, 'RT/RW 001/002 Desa Karang Tengah Kecamatan Bagor Kab Nganjuk', 'RT/RW 001/002 Desa Karang Tengah Kecamatan Bagor Kab Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '10', '2020-01-01', 1, NOW()),
(9, 18, '6050763666200003', '6050763666200003', '', 'SODIKIN MUSLIMIN, S.Pd', 'S.Pd', 'L', 'L', NULL, NULL, 'Jln. Mojo Rt 09, Rw 02. Ds Gondangkulon, Kec Gondang', 'Jln. Mojo Rt 09, Rw 02. Ds Gondangkulon, Kec Gondang', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '11', '2020-01-01', 1, NOW()),
(10, 19, '6852761662300052', '6852761662300052', '', 'ULFATUL KHUMAYDAH, S.H.I', 'S.H.I', 'P', 'P', NULL, NULL, 'UTARA MASJID KAWEDEGAN DSN. KAWEDEGAN DS. BALONGGEBANG KEC. GONDANG KAB. NGANJUK JATIM 64451', 'UTARA MASJID KAWEDEGAN DSN. KAWEDEGAN DS. BALONGGEBANG KEC. GONDANG KAB. NGANJUK JATIM 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '12', '2020-01-01', 1, NOW()),
(11, 20, '4561760661300033', '4561760661300033', '', 'SITI AMIROH, S.Pd.I', 'S.Pd.I', 'P', 'P', NULL, NULL, 'Dsn. Sambirejo RT 005 RW.005 DS. Katerban Kec.Baron Nganjuk', 'Dsn. Sambirejo RT 005 RW.005 DS. Katerban Kec.Baron Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL / WAKA KURIKULUM', 'GURU MAPEL / WAKA KURIKULUM', '13', '2020-01-01', 1, NOW()),
(12, 21, '8145755658110013', '8145755658110013', '', 'AGUS BUDI IRAWAN, S.Pd.', 'S.Pd.', 'L', 'L', NULL, NULL, 'RT 01 RW 01 Campur Gondang Nganjuk', 'RT 01 RW 01 Campur Gondang Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '14', '2020-01-01', 1, NOW()),
(13, 22, '20537588191001', '20537588191001', '', 'MUHAMMAD ABDUL HARIS JAUHARI, S.Pd.I', 'S.Pd.I', 'L', 'L', NULL, NULL, 'Haris Phonecell, Utara MTs Al-Huda Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', 'Haris Phonecell, Utara MTs Al-Huda Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'KEPALA MADRASAH', 'KEPALA MADRASAH', '01', '2020-01-01', 1, NOW()),
(14, 23, '20537588188001', '20537588188001', '', 'SITI MUNFARIDA, S.Pd.I', 'S.Pd.I', 'P', 'P', NULL, NULL, 'Dsn. Pulo Rt. 004/ Rw. 006 Ds. Campur Kec. Gondang Kab. Nganjuk Kode pos 64451', 'Dsn. Pulo Rt. 004/ Rw. 006 Ds. Campur Kec. Gondang Kab. Nganjuk Kode pos 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '15', '2020-01-01', 1, NOW()),
(15, 24, '20537588190003', '20537588190003', '', 'FARIDATUN NI`MAH, S.Pd.I', 'S.Pd.I', 'P', 'P', NULL, NULL, 'Haris Phonecell, Utara MTs Al-Huda Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', 'Haris Phonecell, Utara MTs Al-Huda Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '16', '2020-01-01', 1, NOW()),
(16, 25, '20537588190002', '20537588190002', '', 'INTAN ROSIANA DEVI, S.Pd.', 'S.Pd.', 'P', 'P', NULL, NULL, 'DESA NGUJUNG RT.01 / RW.01, Kel. Ngujung, Kec. Gondang Kab. Nganjuk, Prov. Jawa Timur KODE POS 64451', 'DESA NGUJUNG RT.01 / RW.01, Kel. Ngujung, Kec. Gondang Kab. Nganjuk, Prov. Jawa Timur KODE POS 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '17', '2020-01-01', 1, NOW()),
(17, 26, '20537588197001', '20537588197001', '', 'OCTAVIA UTSWATUN KHASANAH, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'Ds. Paron RT/RW : 11/03 Kec. Bagor Kab. Nganjuk', 'Ds. Paron RT/RW : 11/03 Kec. Bagor Kab. Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '19', '2020-01-01', 1, NOW()),
(18, 27, '20537588183001', '20537588183001', '', 'NURUL CHOFIFAH, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'Ds. Senjayan RT 02 RW 04 Kec. Gondang Kab. Nganjuk, 64451', 'Ds. Senjayan RT 02 RW 04 Kec. Gondang Kab. Nganjuk, 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '20', '2020-01-01', 1, NOW()),
(19, 28, '20537588196001', '20537588196001', '', 'MOCH. SYABAN WULANA, S.Pd', 'S.Pd', 'L', 'L', NULL, NULL, 'Dusun Jatigetih RT 001/RW 005, Desa Sanggrahan Kec. Gondang, Kab. Nganjuk 64451', 'Dusun Jatigetih RT 001/RW 005, Desa Sanggrahan Kec. Gondang, Kab. Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '21', '2020-01-01', 1, NOW()),
(20, 29, '20537588198001', '20537588198001', '', 'WINDA TRIYANA, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'RT 01 RW 01, DESA JINTEL, KECAMATAN REJOSO, KABUPATEN NGANJUK', 'RT 01 RW 01, DESA JINTEL, KECAMATAN REJOSO, KABUPATEN NGANJUK', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '22', '2020-01-01', 1, NOW()),
(21, 30, '20537588199002', '20537588199002', '', 'NOPI PURWANTI, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'RT.002/RW.011, DUSUN NGRAMBEK DESA CAMPUR KECAMATAN GONDANG KABUPATEN NGANJUK', 'RT.002/RW.011, DUSUN NGRAMBEK DESA CAMPUR KECAMATAN GONDANG KABUPATEN NGANJUK', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '23', '2020-01-01', 1, NOW()),
(22, 31, '20537588100001', '20537588100001', '', 'KUMALA AZIZATUL LATIFAH, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'TB. Lancar berkah Jln. Nganjuk Bojonegoro, RT.01 ,RW 01, Dsn Sambikerep, Ds Sambikerep, Kec Rejoso, Kab. Nganjuk', 'TB. Lancar berkah Jln. Nganjuk Bojonegoro, RT.01 ,RW 01, Dsn Sambikerep, Ds Sambikerep, Kec Rejoso, Kab. Nganjuk', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '24', '2020-01-01', 1, NOW()),
(23, 32, '20537992188001', '20537992188001', '', 'TITIK YULIATI, S.Pd', 'S.Pd', 'P', 'P', NULL, NULL, 'JL. CITARUM II NO.127, RT/RW: 02/04 BEGADUNG - NGANJUK', 'JL. CITARUM II NO.127, RT/RW: 02/04 BEGADUNG - NGANJUK', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '25', '2020-01-01', 1, NOW()),
(24, 33, '20537507185001', '20537507185001', '', 'NUR FUAD, S.H.I, M.A.', 'S.H.I, M.A.', 'L', 'L', NULL, NULL, 'Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', 'Desa Pandean RT 04/RW 02, Kec. Gondang, Kab. Nganjuk 64451', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '18', '2020-01-01', 1, NOW()),
(25, 34, NULL, NULL, '', 'SULIJADI, M.M', 'M.M', 'L', 'L', NULL, NULL, 'Gondang Kulon', 'Gondang Kulon', NULL, 'honorer', 'GTT/PTT', 'GURU MAPEL', 'GURU MAPEL', '03', '2020-01-01', 1, NOW()),
(26, 35, NULL, NULL, '', 'ALITA DIAN WAHYU SHALIKHAH', '', 'P', 'P', NULL, NULL, 'Gondang Kulon', 'Gondang Kulon', NULL, 'honorer', 'GTT/PTT', 'KEPALA TU', 'KEPALA TU', '', '2020-01-01', 1, NOW());

-- 4. Kelas
INSERT INTO `kelas` (`id`, `academic_year_id`, `name`, `nama_kelas`, `level`, `tingkat`, `homeroom_teacher_id`, `wali_kelas_id`, `room`, `ruangan`, `capacity`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 1, '7A', '7A', 7, 7, 1, 1, 'Ruang Kelas 7A', 'Ruang Kelas 7A', 32, 1, 1, NOW()),
(2, 1, '7B', '7B', 7, 7, 2, 2, 'Ruang Kelas 7B', 'Ruang Kelas 7B', 32, 2, 1, NOW()),
(3, 1, '8A', '8A', 8, 8, 3, 3, 'Ruang Kelas 8A', 'Ruang Kelas 8A', 32, 3, 1, NOW()),
(4, 1, '8B', '8B', 8, 8, 4, 4, 'Ruang Kelas 8B', 'Ruang Kelas 8B', 32, 4, 1, NOW()),
(5, 1, '9A', '9A', 9, 9, 5, 5, 'Ruang Kelas 9A', 'Ruang Kelas 9A', 32, 5, 1, NOW()),
(6, 1, '9B', '9B', 9, 9, 6, 6, 'Ruang Kelas 9B', 'Ruang Kelas 9B', 32, 6, 1, NOW());

-- 5. Mata Pelajaran
INSERT INTO `mata_pelajaran` (`id`, `code`, `kode_mapel`, `name`, `nama_mapel`, `group_name`, `kelompok`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'QH', 'QH', 'Qur\'an Hadits', 'Qur\'an Hadits', 'agama', 'agama', 1, 1, NOW()),
(2, 'AA', 'AA', 'Akidah Akhlaq', 'Akidah Akhlaq', 'agama', 'agama', 2, 1, NOW()),
(3, 'FQH', 'FQH', 'Fiqih', 'Fiqih', 'agama', 'agama', 3, 1, NOW()),
(4, 'SKI', 'SKI', 'SKI', 'SKI', 'agama', 'agama', 4, 1, NOW()),
(5, 'BAR', 'BAR', 'Bahasa Arab', 'Bahasa Arab', 'agama', 'agama', 5, 1, NOW()),
(6, 'PPKN', 'PPKN', 'PPKn', 'PPKn', 'umum', 'umum', 6, 1, NOW()),
(7, 'BIN', 'BIN', 'Bhs. Indonesia', 'Bhs. Indonesia', 'umum', 'umum', 7, 1, NOW()),
(8, 'IPS', 'IPS', 'IPS Terpadu', 'IPS Terpadu', 'umum', 'umum', 8, 1, NOW()),
(9, 'IPA', 'IPA', 'IPA Terpadu', 'IPA Terpadu', 'umum', 'umum', 9, 1, NOW()),
(10, 'MTK', 'MTK', 'Matematika', 'Matematika', 'umum', 'umum', 10, 1, NOW()),
(11, 'BIG', 'BIG', 'Bahasa Inggris', 'Bahasa Inggris', 'umum', 'umum', 11, 1, NOW()),
(12, 'PJK', 'PJK', 'Penjaskes', 'Penjaskes', 'umum', 'umum', 12, 1, NOW()),
(13, 'SBK', 'SBK', 'SBK/Prakarya', 'SBK/Prakarya', 'umum', 'umum', 13, 1, NOW()),
(14, 'BJW', 'BJW', 'Bahasa Jawa', 'Bahasa Jawa', 'muatan_lokal', 'muatan_lokal', 14, 1, NOW()),
(15, 'TFZ', 'TFZ', 'Tahfidzul Qur\'an/QH-B', 'Tahfidzul Qur\'an/QH-B', 'agama', 'agama', 15, 1, NOW()),
(16, 'TIK', 'TIK', 'TIK', 'TIK', 'umum', 'umum', 16, 1, NOW()),
(17, 'PRK', 'PRK', 'Prakarya/BK', 'Prakarya/BK', 'umum', 'umum', 17, 1, NOW()),
(18, 'BTA', 'BTA', 'BTA (Baca Tulis Al-Qur\'an)', 'BTA (Baca Tulis Al-Qur\'an)', 'agama', 'agama', 18, 1, NOW()),
(19, 'TLM', 'TLM', 'Ta\'lim Muta\'alim', 'Ta\'lim Muta\'alim', 'agama', 'agama', 19, 1, NOW()),
(20, 'ASW', 'ASW', 'Aswaja', 'Aswaja', 'agama', 'agama', 20, 1, NOW()),
(21, 'MBF', 'MBF', 'Mabadiul Fiqiyah', 'Mabadiul Fiqiyah', 'agama', 'agama', 21, 1, NOW()),
(22, 'UPC', 'UPC', 'Upacara', 'Upacara', 'umum', 'umum', 22, 1, NOW()),
(23, 'NRY', 'NRY', 'Nariyahan', 'Nariyahan', 'agama', 'agama', 23, 1, NOW()),
(24, 'UPC2', 'UPC2', 'Upacara', 'Upacara', 'umum', 'umum', 24, 1, NOW()),
(25, 'THL', 'THL', 'Tahlilan', 'Tahlilan', 'agama', 'agama', 25, 1, NOW());

-- 6. Mata Pelajaran Kelas & Jadwal Pelajaran (Matriks Lengkap Semester Ganjil 2026/2027)
-- 7. Data Siswa Riil (154 Siswa dari CSV dapodik MTs Al-Huda)
INSERT INTO `pengguna` (`id`, `username`, `email`, `password_hash`, `name`, `nama`, `role`, `role_label`, `phone`, `telepon`, `is_active`, `must_change_password`, `created_at`) VALUES
(101, 'siswa_0125256561', 'siswa_0125256561@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AUREL FITRI RAMADANI', 'AUREL FITRI RAMADANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(102, 'siswa_0132181195', 'siswa_0132181195@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AKBAR CAESAR NURFATTAH', 'AKBAR CAESAR NURFATTAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(103, 'siswa_0147986014', 'siswa_0147986014@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AQILA AZZARA ARROZAQ', 'AQILA AZZARA ARROZAQ', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(104, 'siswa_3122286780', 'siswa_3122286780@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TAUFIQ FIRMANSYAH', 'TAUFIQ FIRMANSYAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(105, 'siswa_3143394636', 'siswa_3143394636@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FRIZKY JIANDRA MICHELL PRATAMA', 'FRIZKY JIANDRA MICHELL PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(106, 'siswa_0139282971', 'siswa_0139282971@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MAYDINA WINDI ASTARI', 'MAYDINA WINDI ASTARI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(107, 'siswa_3125657162', 'siswa_3125657162@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ALZATUL ANAFISA', 'ALZATUL ANAFISA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(108, 'siswa_0134199917', 'siswa_0134199917@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AZZAHRA CHAREN BINTANG NOVIANTO', 'AZZAHRA CHAREN BINTANG NOVIANTO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(109, 'siswa_0135161987', 'siswa_0135161987@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOCH ALVA EZA DWI SETIAWAN', 'MOCH ALVA EZA DWI SETIAWAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(110, 'siswa_0138943630', 'siswa_0138943630@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DESTA PRATAMA', 'DESTA PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(111, 'siswa_0134933151', 'siswa_0134933151@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'Iqbal Zairurrofiq Al Munfi\'', 'Iqbal Zairurrofiq Al Munfi\'', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(112, 'siswa_0138325085', 'siswa_0138325085@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SASKIA JENI DWI MAHARANI', 'SASKIA JENI DWI MAHARANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(113, 'siswa_0133759532', 'siswa_0133759532@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MARSAH AULIA ARAFAH', 'MARSAH AULIA ARAFAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(114, 'siswa_0138482337', 'siswa_0138482337@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MICHELIN KANIA PUTRI', 'MICHELIN KANIA PUTRI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(115, 'siswa_0132715066', 'siswa_0132715066@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AHMAD YUDA PRATAMA', 'AHMAD YUDA PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(116, 'siswa_3120411886', 'siswa_3120411886@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ILHAM NUR FANSAURI', 'ILHAM NUR FANSAURI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(117, 'siswa_0133745552', 'siswa_0133745552@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DINAYA VAYLA RAHMAH', 'DINAYA VAYLA RAHMAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(118, 'siswa_0133146106', 'siswa_0133146106@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KEISHA AFIFAH ZAHRA', 'KEISHA AFIFAH ZAHRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(119, 'siswa_0135092230', 'siswa_0135092230@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANJANI SALMAI KHOLIDA', 'ANJANI SALMAI KHOLIDA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(120, 'siswa_0136486454', 'siswa_0136486454@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD ALIFIANDRA DAVID MUSYAFFA', 'MUHAMMAD ALIFIANDRA DAVID MUSYAFFA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(121, 'siswa_0125978898', 'siswa_0125978898@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KEVIN APRILLIO DWI MUSTOFA', 'KEVIN APRILLIO DWI MUSTOFA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(122, 'siswa_0131516719', 'siswa_0131516719@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ZAHRA DIANTIKA REGINA PUTRI', 'ZAHRA DIANTIKA REGINA PUTRI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(123, 'siswa_3132869202', 'siswa_3132869202@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NURDAFFA ARYA RAMADHAN', 'NURDAFFA ARYA RAMADHAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(124, 'siswa_0136835358', 'siswa_0136835358@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'IRMA RAHMAWATI', 'IRMA RAHMAWATI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(125, 'siswa_0127662965', 'siswa_0127662965@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AHMAD FAIZ NUR QOLBI ASYARI', 'AHMAD FAIZ NUR QOLBI ASYARI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(126, 'siswa_0122355512', 'siswa_0122355512@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AHMAD JAZULI WIJAYA', 'AHMAD JAZULI WIJAYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(127, 'siswa_3131460024', 'siswa_3131460024@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AHMADIL FU\'AD AL ALWI', 'AHMADIL FU\'AD AL ALWI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(128, 'siswa_3123144201', 'siswa_3123144201@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ALLFIN ADITYA KURNIA PUTRA', 'ALLFIN ADITYA KURNIA PUTRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(129, 'siswa_3125113021', 'siswa_3125113021@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AMANDA FELICIA KUSUMAJAYA', 'AMANDA FELICIA KUSUMAJAYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(130, 'siswa_0123741924', 'siswa_0123741924@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DESTIN NATALIA KUSUMA', 'DESTIN NATALIA KUSUMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(131, 'siswa_3120286136', 'siswa_3120286136@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DWI SAPUTRA', 'DWI SAPUTRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(132, 'siswa_0136508249', 'siswa_0136508249@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FAIZ MAULANA AZIDAN', 'FAIZ MAULANA AZIDAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(133, 'siswa_0139764423', 'siswa_0139764423@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FAWWAS SALMAN ALIFI', 'FAWWAS SALMAN ALIFI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(134, 'siswa_0129471609', 'siswa_0129471609@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JELITA ANASTASYA', 'JELITA ANASTASYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(135, 'siswa_0128457316', 'siswa_0128457316@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JULIA RAHMAHWATI', 'JULIA RAHMAHWATI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(136, 'siswa_0123738023', 'siswa_0123738023@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KHESHIA MEY AZZAHRA', 'KHESHIA MEY AZZAHRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(137, 'siswa_0125830346', 'siswa_0125830346@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'LUKY AFNAN OKTAVIAN', 'LUKY AFNAN OKTAVIAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(138, 'siswa_0127403990', 'siswa_0127403990@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD ARIS MULYADI', 'MUHAMMAD ARIS MULYADI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(139, 'siswa_0123720553', 'siswa_0123720553@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NAAFIL MUBAROKAH', 'NAAFIL MUBAROKAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(140, 'siswa_0117578051', 'siswa_0117578051@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOH. FAZDA HAZIQOL MUGHIETSA', 'MOH. FAZDA HAZIQOL MUGHIETSA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(141, 'siswa_0124560956', 'siswa_0124560956@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'HAFIZ KENZIE ELANO', 'HAFIZ KENZIE ELANO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(142, 'siswa_0138598517', 'siswa_0138598517@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MARISKA AVRIL LUTFIANTI', 'MARISKA AVRIL LUTFIANTI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(143, 'siswa_0124809726', 'siswa_0124809726@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD JAYSON AZKA ALFARIZQI', 'MUHAMMAD JAYSON AZKA ALFARIZQI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(144, 'siswa_3126950786', 'siswa_3126950786@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NOVELIA QORRY NAZZILA', 'NOVELIA QORRY NAZZILA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(145, 'siswa_3121182277', 'siswa_3121182277@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'QURROTUN AININ NADIVAH', 'QURROTUN AININ NADIVAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(146, 'siswa_0125388541', 'siswa_0125388541@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'REHAN MAULANA ISHAQ', 'REHAN MAULANA ISHAQ', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(147, 'siswa_0126925173', 'siswa_0126925173@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'YULIANA DWI NUR CHAYANTI', 'YULIANA DWI NUR CHAYANTI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(148, 'siswa_0127039881', 'siswa_0127039881@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AFRIAN ANGGA YUDISTIRA', 'AFRIAN ANGGA YUDISTIRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(149, 'siswa_0122124162', 'siswa_0122124162@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AGIEL PUTRA HERLINO', 'AGIEL PUTRA HERLINO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(150, 'siswa_0122769141', 'siswa_0122769141@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ALFINA KHOLIFAH', 'ALFINA KHOLIFAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(151, 'siswa_3123980685', 'siswa_3123980685@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ALICA ZAHRA ANGRAINI', 'ALICA ZAHRA ANGRAINI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(152, 'siswa_0122098741', 'siswa_0122098741@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANNISSA DWI NOVITASARI', 'ANNISSA DWI NOVITASARI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(153, 'siswa_0131007102', 'siswa_0131007102@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DARYL AYDINE NUGROHO', 'DARYL AYDINE NUGROHO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(154, 'siswa_0123416714', 'siswa_0123416714@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'DIMAS YONGKY PRAMUDIKA', 'DIMAS YONGKY PRAMUDIKA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(155, 'siswa_0129950827', 'siswa_0129950827@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FARREL RAZQA AL FARIZKY', 'FARREL RAZQA AL FARIZKY', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(156, 'siswa_0126770938', 'siswa_0126770938@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KIARA LAKSITA RAMADHANI', 'KIARA LAKSITA RAMADHANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(157, 'siswa_0121939595', 'siswa_0121939595@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AZHYFA ALMEDINA WIJAYA', 'AZHYFA ALMEDINA WIJAYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(158, 'siswa_3120786159', 'siswa_3120786159@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'HALIDA DWI MUKHBITAH', 'HALIDA DWI MUKHBITAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(159, 'siswa_0127456938', 'siswa_0127456938@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'LUKMAN AFFAN OKTAVIAN', 'LUKMAN AFFAN OKTAVIAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(160, 'siswa_0127988378', 'siswa_0127988378@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD HAN AL WAHID', 'MUHAMMAD HAN AL WAHID', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(161, 'siswa_0122473332', 'siswa_0122473332@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD RIZAL AFFANDI', 'MUHAMMAD RIZAL AFFANDI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(162, 'siswa_3126201177', 'siswa_3126201177@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NUR SYAFA PUTRI RAMADHANI', 'NUR SYAFA PUTRI RAMADHANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(163, 'siswa_0126619547', 'siswa_0126619547@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'PUTRA PRASETYO ADI PRAYOGO', 'PUTRA PRASETYO ADI PRAYOGO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(164, 'siswa_0127677402', 'siswa_0127677402@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'WISNUTAMA SYAHDHAN', 'WISNUTAMA SYAHDHAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(165, 'siswa_0125494962', 'siswa_0125494962@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'WIWIK BINTI APRELIA', 'WIWIK BINTI APRELIA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(166, 'siswa_0115017731', 'siswa_0115017731@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'YENI AFISSUNANI', 'YENI AFISSUNANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(167, 'siswa_0115914277', 'siswa_0115914277@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KAISAN GREE GHIFFARY', 'KAISAN GREE GHIFFARY', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(168, 'siswa_3122832734', 'siswa_3122832734@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MEZZALUNA ARSESHA SETIAWAN', 'MEZZALUNA ARSESHA SETIAWAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(169, 'siswa_0123453484', 'siswa_0123453484@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOCH AL NIZAM PUTRA BAHRI', 'MOCH AL NIZAM PUTRA BAHRI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(170, 'siswa_0102224941', 'siswa_0102224941@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MARDIANSYAH ADI SAPUTRA', 'MARDIANSYAH ADI SAPUTRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(171, 'siswa_0113713659', 'siswa_0113713659@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANDHI PUTRA MAULANA', 'ANDHI PUTRA MAULANA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(172, 'siswa_0111114726', 'siswa_0111114726@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANDHIKA PUTRA MAULANA', 'ANDHIKA PUTRA MAULANA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(173, 'siswa_3128531526', 'siswa_3128531526@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'CHAYRA FAYOLLA NAZHIROH', 'CHAYRA FAYOLLA NAZHIROH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(174, 'siswa_0124350997', 'siswa_0124350997@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JOHAN RIZKI PRATAMA', 'JOHAN RIZKI PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(175, 'siswa_0133962880', 'siswa_0133962880@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'M.FERENDRA YUBI HAIDAR AFIAR YULIANTO', 'M.FERENDRA YUBI HAIDAR AFIAR YULIANTO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(176, 'siswa_0138152999', 'siswa_0138152999@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHAMAD FAREL DWI ADITYA', 'MOHAMAD FAREL DWI ADITYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(177, 'siswa_0121869487', 'siswa_0121869487@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHAMAD RIZQI ALDIANSYAH', 'MOHAMAD RIZQI ALDIANSYAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(178, 'siswa_0129929586', 'siswa_0129929586@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHAMMAD ARFI EKO PRASETYO', 'MOHAMMAD ARFI EKO PRASETYO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(179, 'siswa_0122721604', 'siswa_0122721604@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHHAMAD ANDIKA PRATAMA', 'MOHHAMAD ANDIKA PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(180, 'siswa_3126750130', 'siswa_3126750130@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUCHAMMAD RIZQI PUTRA PRATAMA', 'MUCHAMMAD RIZQI PUTRA PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(181, 'siswa_0125139495', 'siswa_0125139495@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD EKO RIFA\'I', 'MUHAMMAD EKO RIFA\'I', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(182, 'siswa_3128724594', 'siswa_3128724594@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD SAFI\'I', 'MUHAMMAD SAFI\'I', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(183, 'siswa_0133809735', 'siswa_0133809735@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NABILA PERMATASARI', 'NABILA PERMATASARI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(184, 'siswa_3122381181', 'siswa_3122381181@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SEPTIA LILA JUARIYAH', 'SEPTIA LILA JUARIYAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(185, 'siswa_0128890869', 'siswa_0128890869@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SINDI CANDRA WINATA', 'SINDI CANDRA WINATA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(186, 'siswa_0122247568', 'siswa_0122247568@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'VERRA ANANDA MEICHA PUTRIATAMA', 'VERRA ANANDA MEICHA PUTRIATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(187, 'siswa_0137782431', 'siswa_0137782431@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'VICKHA DWI WAHYUNI', 'VICKHA DWI WAHYUNI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(188, 'siswa_0118357022', 'siswa_0118357022@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'CAHAYA GEMILANG', 'CAHAYA GEMILANG', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(189, 'siswa_0115522421', 'siswa_0115522421@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'CANTIKA ENJELISCA AGUSTINA', 'CANTIKA ENJELISCA AGUSTINA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(190, 'siswa_0114135122', 'siswa_0114135122@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FARID HIDAYAT', 'FARID HIDAYAT', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(191, 'siswa_0118463369', 'siswa_0118463369@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FRISSIL ANGELICA PUTRI', 'FRISSIL ANGELICA PUTRI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(192, 'siswa_0123521552', 'siswa_0123521552@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'GIZELA AYU ANINDYA', 'GIZELA AYU ANINDYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(193, 'siswa_0122180419', 'siswa_0122180419@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JUVA DERY NAFIANSA', 'JUVA DERY NAFIANSA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(194, 'siswa_0125793283', 'siswa_0125793283@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'M. FADIL IKHSAN THAHIR', 'M. FADIL IKHSAN THAHIR', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(195, 'siswa_0117159158', 'siswa_0117159158@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MEYRZA YOPANA FII`ADZALILA', 'MEYRZA YOPANA FII`ADZALILA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(196, 'siswa_0122084024', 'siswa_0122084024@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MIRZA ADMA PUTRA', 'MIRZA ADMA PUTRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(197, 'siswa_0111605886', 'siswa_0111605886@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOHAMAD MALIK HIDAYATULLOH', 'MOHAMAD MALIK HIDAYATULLOH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(198, 'siswa_0122486854', 'siswa_0122486854@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'REVANITA OKTAVIANI WIBOWO', 'REVANITA OKTAVIANI WIBOWO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(199, 'siswa_0128481369', 'siswa_0128481369@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'RICHARD ALVIANO ZANUAR', 'RICHARD ALVIANO ZANUAR', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(200, 'siswa_0112395460', 'siswa_0112395460@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SIFA\'UL KILA RAMADHANI', 'SIFA\'UL KILA RAMADHANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(201, 'siswa_0113639191', 'siswa_0113639191@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SOFIAN ALBERT FERNANDO', 'SOFIAN ALBERT FERNANDO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(202, 'siswa_3117406875', 'siswa_3117406875@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SYIFA\'UL KHUMAIROH', 'SYIFA\'UL KHUMAIROH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(203, 'siswa_0112271627', 'siswa_0112271627@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TARI CINTA SEFIYA', 'TARI CINTA SEFIYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(204, 'siswa_0127043670', 'siswa_0127043670@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TISYA NUR MAULIDA HARSANTY', 'TISYA NUR MAULIDA HARSANTY', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(205, 'siswa_0114862518', 'siswa_0114862518@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TRIAS DESTA SARI', 'TRIAS DESTA SARI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(206, 'siswa_0122423929', 'siswa_0122423929@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'XANCHE KALLISTA BENING SALSABILA', 'XANCHE KALLISTA BENING SALSABILA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(207, 'siswa_0112578982', 'siswa_0112578982@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'RIZQI NUR ANGGRAINI', 'RIZQI NUR ANGGRAINI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(208, 'siswa_0116592410', 'siswa_0116592410@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SENDI JAYA PRATAMA', 'SENDI JAYA PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(209, 'siswa_3110590155', 'siswa_3110590155@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'TEZZA LATIF NAZELA AVRELA TAMA', 'TEZZA LATIF NAZELA AVRELA TAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(210, 'siswa_0117095937', 'siswa_0117095937@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'YONGKI BIWANTORO', 'YONGKI BIWANTORO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(211, 'siswa_0111930479', 'siswa_0111930479@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'YOVI KIA PUTRA ARLYAN', 'YOVI KIA PUTRA ARLYAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(212, 'siswa_0128144891', 'siswa_0128144891@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANDRIAN ADJI PRATAMA', 'ANDRIAN ADJI PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(213, 'siswa_0115451414', 'siswa_0115451414@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANISA\' NURMAIDA', 'ANISA\' NURMAIDA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(214, 'siswa_0125211601', 'siswa_0125211601@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ASSYAFIRA DIKA MARTASYA', 'ASSYAFIRA DIKA MARTASYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(215, 'siswa_0127059266', 'siswa_0127059266@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'CANDRA PUTRA KENCANA', 'CANDRA PUTRA KENCANA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(216, 'siswa_0118609836', 'siswa_0118609836@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FACHRI REZA ALFIANSYAH', 'FACHRI REZA ALFIANSYAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(217, 'siswa_0126143547', 'siswa_0126143547@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'HERLIN DWI WIDYANINGRUM', 'HERLIN DWI WIDYANINGRUM', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(218, 'siswa_0116921911', 'siswa_0116921911@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'INTAN PERMATA', 'INTAN PERMATA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(219, 'siswa_0114152139', 'siswa_0114152139@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'IRFAN FAHREZA', 'IRFAN FAHREZA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(220, 'siswa_0111484929', 'siswa_0111484929@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'KHOIRUL FUAD FACHRUDIN', 'KHOIRUL FUAD FACHRUDIN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(221, 'siswa_0125587125', 'siswa_0125587125@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'M. LUCKY ADI PUTRA', 'M. LUCKY ADI PUTRA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(222, 'siswa_0113640889', 'siswa_0113640889@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOCH. MANDALA PUTRA DWIANTO', 'MOCH. MANDALA PUTRA DWIANTO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(223, 'siswa_3111579330', 'siswa_3111579330@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOH. KHAMAT PAJAR ARTA HARTAWAN', 'MOH. KHAMAT PAJAR ARTA HARTAWAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(224, 'siswa_0122007443', 'siswa_0122007443@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD ALKHALIFI DZIKRI CANDRA YUDA', 'MUHAMMAD ALKHALIFI DZIKRI CANDRA YUDA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(225, 'siswa_0112328335', 'siswa_0112328335@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD NIZAL IQBAL AL-HADI', 'MUHAMMAD NIZAL IQBAL AL-HADI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(226, 'siswa_0119064628', 'siswa_0119064628@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'REVALINA AYU ANGGRAINI', 'REVALINA AYU ANGGRAINI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(227, 'siswa_0118975478', 'siswa_0118975478@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'RIDHO PANJI MAULANA', 'RIDHO PANJI MAULANA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(228, 'siswa_0114072167', 'siswa_0114072167@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'NIKO SEPTIAN REVANO', 'NIKO SEPTIAN REVANO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(229, 'siswa_0117270690', 'siswa_0117270690@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'PADHIL ZAEROFI AFIF', 'PADHIL ZAEROFI AFIF', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(230, 'siswa_0112501135', 'siswa_0112501135@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'REYHAN PRATAMA', 'REYHAN PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(231, 'siswa_0102645489', 'siswa_0102645489@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'RISMA AURELLIA AGUSTINA', 'RISMA AURELLIA AGUSTINA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(232, 'siswa_0113014679', 'siswa_0113014679@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'RIF\'ATUL LAYLY AZ\'ZAHRA SARAS', 'RIF\'ATUL LAYLY AZ\'ZAHRA SARAS', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(233, 'siswa_0117526617', 'siswa_0117526617@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SAIFULLOH EKO YUSUF', 'SAIFULLOH EKO YUSUF', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(234, 'siswa_0126350319', 'siswa_0126350319@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'STEFANNIE PUTRI RAGIL ERNAWATI', 'STEFANNIE PUTRI RAGIL ERNAWATI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(235, 'siswa_0115598112', 'siswa_0115598112@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'SYIFA AURA PURNAMA', 'SYIFA AURA PURNAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(236, 'siswa_0116837133', 'siswa_0116837133@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ZAINURIL FERDIANSYAH', 'ZAINURIL FERDIANSYAH', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(237, 'siswa_0116754662', 'siswa_0116754662@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ZHAHIRA ALYA NUR RAHMADHANI', 'ZHAHIRA ALYA NUR RAHMADHANI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(238, 'siswa_0117285048', 'siswa_0117285048@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'AHMAD YUSQI MURTADHO', 'AHMAD YUSQI MURTADHO', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(239, 'siswa_0118580095', 'siswa_0118580095@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ANDRE BACHTIAR', 'ANDRE BACHTIAR', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(240, 'siswa_0108603886', 'siswa_0108603886@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'BINTANG WAHYU OKTAVIAN', 'BINTANG WAHYU OKTAVIAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(241, 'siswa_0117719391', 'siswa_0117719391@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'FADILA ALIFIA ATMAJA', 'FADILA ALIFIA ATMAJA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(242, 'siswa_0118231326', 'siswa_0118231326@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'IRFAN PUTRA ADITYA', 'IRFAN PUTRA ADITYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(243, 'siswa_0116095543', 'siswa_0116095543@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'ISNAENI EKA OKTAVIANA', 'ISNAENI EKA OKTAVIANA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(244, 'siswa_0125432058', 'siswa_0125432058@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JOHAN RIDHO ALMADA', 'JOHAN RIDHO ALMADA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(245, 'siswa_0118114356', 'siswa_0118114356@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'JUNIA SHERIEL ANANDITA', 'JUNIA SHERIEL ANANDITA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(246, 'siswa_0119907158', 'siswa_0119907158@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'LEANDRA PUTRI AYUNINGTYAS', 'LEANDRA PUTRI AYUNINGTYAS', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(247, 'siswa_0119759257', 'siswa_0119759257@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'LUDVI NUR FADLI', 'LUDVI NUR FADLI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(248, 'siswa_0113823266', 'siswa_0113823266@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOCHAMMAD NUR WAHID', 'MOCHAMMAD NUR WAHID', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(249, 'siswa_0115522345', 'siswa_0115522345@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MOH. ADI PRATAMA', 'MOH. ADI PRATAMA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(250, 'siswa_0113846546', 'siswa_0113846546@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD ILHAM HANAFI', 'MUHAMMAD ILHAM HANAFI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(251, 'siswa_0117755815', 'siswa_0117755815@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD KHOIRUL MARZUQI', 'MUHAMMAD KHOIRUL MARZUQI', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(252, 'siswa_0129011176', 'siswa_0129011176@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD NAJIB SETYAWAN', 'MUHAMMAD NAJIB SETYAWAN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(253, 'siswa_0116152712', 'siswa_0116152712@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD NUR KHOIRUDIN', 'MUHAMMAD NUR KHOIRUDIN', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW()),
(254, 'siswa_0118205624', 'siswa_0118205624@siswa.mts-alhuda.sch.id', '$2y$10$xpEEH3IIicSBFpR0h.vgpOlwEa8znO6CZGT5LvP42PMSJYgfggXtC', 'MUHAMMAD REZA ADITYA', 'MUHAMMAD REZA ADITYA', 'siswa', 'Siswa', NULL, NULL, 1, 0, NOW());

INSERT INTO `siswa` (`id`, `user_id`, `class_id`, `kelas_id`, `nis`, `nisn`, `nama_lengkap`, `gender`, `jenis_kelamin`, `birth_place`, `tempat_lahir`, `birth_date`, `tanggal_lahir`, `guardian_name`, `nama_wali`, `guardian_phone`, `address`, `alamat`, `foto_url`, `enrolled_at`, `tahun_masuk`, `status`, `is_active`, `created_at`) VALUES
(1, 101, 1, 1, '20260001', '0125256561', 'AUREL FITRI RAMADANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-08-13', '2012-08-13', 'Wali Murid', 'Wali Murid', NULL, 'DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(2, 102, 1, 1, '20260002', '0132181195', 'AKBAR CAESAR NURFATTAH', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-11-05', '2013-11-05', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JINTEL, DS. JINTEL JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. JINTEL, DS. JINTEL JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(3, 103, 1, 1, '20260003', '0147986014', 'AQILA AZZARA ARROZAQ', 'P', 'P', 'NGANJUK', 'NGANJUK', '2014-03-02', '2014-03-02', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGLUMBU, DS. KETAWANG KETAWANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGLUMBU, DS. KETAWANG KETAWANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(4, 104, 1, 1, '20260004', '3122286780', 'TAUFIQ FIRMANSYAH', 'L', 'L', 'Nganjuk', 'Nganjuk', '2012-04-06', '2012-04-06', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(5, 105, 1, 1, '20260005', '3143394636', 'FRIZKY JIANDRA MICHELL PRATAMA', 'L', 'L', 'Nganjuk', 'Nganjuk', '2014-04-17', '2014-04-17', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(6, 106, 1, 1, '20260006', '0139282971', 'MAYDINA WINDI ASTARI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-12-01', '2013-12-01', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(7, 107, 1, 1, '20260007', '3125657162', 'ALZATUL ANAFISA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-12-18', '2012-12-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. GERBONG, DS. DEMANGAN DEMANGAN, TANJUNGANOM, NGANJUK, JAWA TIMUR, 64483', 'DSN. GERBONG, DS. DEMANGAN DEMANGAN, TANJUNGANOM, NGANJUK, JAWA TIMUR, 64483', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(8, 108, 1, 1, '20260008', '0134199917', 'AZZAHRA CHAREN BINTANG NOVIANTO', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-11-18', '2013-11-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(9, 109, 1, 1, '20260009', '0135161987', 'MOCH ALVA EZA DWI SETIAWAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-12-18', '2013-12-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(10, 110, 1, 1, '20260010', '0138943630', 'DESTA PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-12-25', '2013-12-25', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(11, 111, 1, 1, '20260011', '0134933151', 'Iqbal Zairurrofiq Al Munfi\'', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-09-05', '2013-09-05', 'Wali Murid', 'Wali Murid', NULL, 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(12, 112, 1, 1, '20260012', '0138325085', 'SASKIA JENI DWI MAHARANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-01-11', '2013-01-11', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BESUKI, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BESUKI, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(13, 113, 1, 1, '20260013', '0133759532', 'MARSAH AULIA ARAFAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-03-12', '2013-03-12', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGGEBANG, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGGEBANG, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(14, 114, 1, 1, '20260014', '0138482337', 'MICHELIN KANIA PUTRI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-10-22', '2013-10-22', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(15, 115, 1, 1, '20260015', '0132715066', 'AHMAD YUDA PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-06-25', '2013-06-25', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(16, 116, 1, 1, '20260016', '3120411886', 'ILHAM NUR FANSAURI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-11-21', '2012-11-21', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(17, 117, 1, 1, '20260017', '0133745552', 'DINAYA VAYLA RAHMAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-03-03', '2013-03-03', 'Wali Murid', 'Wali Murid', NULL, 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(18, 118, 1, 1, '20260018', '0133146106', 'KEISHA AFIFAH ZAHRA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-10-03', '2013-10-03', 'Wali Murid', 'Wali Murid', NULL, 'DOROGENENG, PEHSERUT PEHSERUT, SUKOMORO, NGANJUK, JAWA TIMUR, 64481', 'DOROGENENG, PEHSERUT PEHSERUT, SUKOMORO, NGANJUK, JAWA TIMUR, 64481', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(19, 119, 1, 1, '20260019', '0135092230', 'ANJANI SALMAI KHOLIDA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-09-06', '2013-09-06', 'Wali Murid', 'Wali Murid', NULL, 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(20, 120, 1, 1, '20260020', '0136486454', 'MUHAMMAD ALIFIANDRA DAVID MUSYAFFA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-12-22', '2013-12-22', 'Wali Murid', 'Wali Murid', NULL, 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. TALUN, DS. TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(21, 121, 1, 1, '20260021', '0125978898', 'KEVIN APRILLIO DWI MUSTOFA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-19', '2012-04-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN. PANDEAN, DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. PANDEAN, DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(22, 122, 1, 1, '20260022', '0131516719', 'ZAHRA DIANTIKA REGINA PUTRI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-01-13', '2013-01-13', 'Wali Murid', 'Wali Murid', NULL, 'DSN PANDEAN DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN PANDEAN DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(23, 123, 1, 1, '20260023', '3132869202', 'NURDAFFA ARYA RAMADHAN', 'L', 'L', 'GRESIK', 'GRESIK', '2013-07-17', '2013-07-17', 'Wali Murid', 'Wali Murid', NULL, 'PERENG SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR', 'PERENG SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(24, 124, 1, 1, '20260024', '0136835358', 'IRMA RAHMAWATI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-06-14', '2013-06-14', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(25, 125, 3, 3, '20260025', '0127662965', 'AHMAD FAIZ NUR QOLBI ASYARI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-03-08', '2012-03-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(26, 126, 3, 3, '20260026', '0122355512', 'AHMAD JAZULI WIJAYA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-11-06', '2012-11-06', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(27, 127, 3, 3, '20260027', '3131460024', 'AHMADIL FU\'AD AL ALWI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-04-05', '2013-04-05', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(28, 128, 3, 3, '20260028', '3123144201', 'ALLFIN ADITYA KURNIA PUTRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-08-24', '2012-08-24', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(29, 129, 3, 3, '20260029', '3125113021', 'AMANDA FELICIA KUSUMAJAYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-11-05', '2012-11-05', 'Wali Murid', 'Wali Murid', NULL, 'DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(30, 130, 3, 3, '20260030', '0123741924', 'DESTIN NATALIA KUSUMA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-12-25', '2012-12-25', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(31, 131, 3, 3, '20260031', '3120286136', 'DWI SAPUTRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-12-19', '2012-12-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(32, 132, 3, 3, '20260032', '0136508249', 'FAIZ MAULANA AZIDAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-03-31', '2013-03-31', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(33, 133, 3, 3, '20260033', '0139764423', 'FAWWAS SALMAN ALIFI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-03-11', '2013-03-11', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(34, 134, 3, 3, '20260034', '0129471609', 'JELITA ANASTASYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-09-22', '2012-09-22', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(35, 135, 3, 3, '20260035', '0128457316', 'JULIA RAHMAHWATI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-07-17', '2012-07-17', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(36, 136, 3, 3, '20260036', '0123738023', 'KHESHIA MEY AZZAHRA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-05-19', '2012-05-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(37, 137, 3, 3, '20260037', '0125830346', 'LUKY AFNAN OKTAVIAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-10-23', '2012-10-23', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(38, 138, 3, 3, '20260038', '0127403990', 'MUHAMMAD ARIS MULYADI', 'L', 'L', 'BALIKPAPAN', 'BALIKPAPAN', '2012-09-23', '2012-09-23', 'Wali Murid', 'Wali Murid', NULL, 'DSN. PANDEAN, DS. GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. PANDEAN, DS. GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(39, 139, 3, 3, '20260039', '0123720553', 'NAAFIL MUBAROKAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-03-22', '2012-03-22', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SAMBONG, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SAMBONG, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(40, 140, 3, 3, '20260040', '0117578051', 'MOH. FAZDA HAZIQOL MUGHIETSA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-09-10', '2011-09-10', 'Wali Murid', 'Wali Murid', NULL, 'CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(41, 141, 4, 4, '20260041', '0124560956', 'HAFIZ KENZIE ELANO', 'L', 'L', 'BATAM', 'BATAM', '2012-04-07', '2012-04-07', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(42, 142, 4, 4, '20260042', '0138598517', 'MARISKA AVRIL LUTFIANTI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-04-10', '2013-04-10', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(43, 143, 4, 4, '20260043', '0124809726', 'MUHAMMAD JAYSON AZKA ALFARIZQI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-12-24', '2012-12-24', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(44, 144, 4, 4, '20260044', '3126950786', 'NOVELIA QORRY NAZZILA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-11-29', '2012-11-29', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(45, 145, 4, 4, '20260045', '3121182277', 'QURROTUN AININ NADIVAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-07-20', '2013-07-20', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SANGGRAHAN, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SANGGRAHAN, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(46, 146, 4, 4, '20260046', '0125388541', 'REHAN MAULANA ISHAQ', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-05-12', '2012-05-12', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(47, 147, 4, 4, '20260047', '0126925173', 'YULIANA DWI NUR CHAYANTI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-07-31', '2012-07-31', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATISARI, DS. NGANGKATAN NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. JATISARI, DS. NGANGKATAN NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(48, 148, 4, 4, '20260048', '0127039881', 'AFRIAN ANGGA YUDISTIRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-12', '2012-04-12', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(49, 149, 4, 4, '20260049', '0122124162', 'AGIEL PUTRA HERLINO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-05-20', '2012-05-20', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(50, 150, 4, 4, '20260050', '0122769141', 'ALFINA KHOLIFAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-12-30', '2012-12-30', 'Wali Murid', 'Wali Murid', NULL, 'DSN KAMPUNG BARU PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN KAMPUNG BARU PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(51, 151, 4, 4, '20260051', '3123980685', 'ALICA ZAHRA ANGRAINI', 'P', 'P', 'TANGERANG', 'TANGERANG', '2012-01-09', '2012-01-09', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KEDUNGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KEDUNGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(52, 152, 4, 4, '20260052', '0122098741', 'ANNISSA DWI NOVITASARI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-11-09', '2012-11-09', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SENGGOWAR, DS. SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SENGGOWAR, DS. SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(53, 153, 4, 4, '20260053', '0131007102', 'DARYL AYDINE NUGROHO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-03-04', '2013-03-04', 'Wali Murid', 'Wali Murid', NULL, 'DS. TALUN, REJOSO TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DS. TALUN, REJOSO TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(54, 154, 4, 4, '20260054', '0123416714', 'DIMAS YONGKY PRAMUDIKA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-03-19', '2012-03-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BESUKI, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BESUKI, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(55, 155, 4, 4, '20260055', '0129950827', 'FARREL RAZQA AL FARIZKY', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-08-28', '2012-08-28', 'Wali Murid', 'Wali Murid', NULL, 'DS. TALUN, REJOSO TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DS. TALUN, REJOSO TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(56, 156, 4, 4, '20260056', '0126770938', 'KIARA LAKSITA RAMADHANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-07-31', '2012-07-31', 'Wali Murid', 'Wali Murid', NULL, 'DSN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(57, 157, 4, 4, '20260057', '0121939595', 'AZHYFA ALMEDINA WIJAYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-10-21', '2012-10-21', 'Wali Murid', 'Wali Murid', NULL, 'DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(58, 158, 1, 1, '20260058', '3120786159', 'HALIDA DWI MUKHBITAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-12-29', '2012-12-29', 'Wali Murid', 'Wali Murid', NULL, 'JL. RAYA GONDANG-NGLUYU KM. 1,7 SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'JL. RAYA GONDANG-NGLUYU KM. 1,7 SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(59, 159, 1, 1, '20260059', '0127456938', 'LUKMAN AFFAN OKTAVIAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-10-23', '2012-10-23', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SUKOREJO, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(60, 160, 1, 1, '20260060', '0127988378', 'MUHAMMAD HAN AL WAHID', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-05-13', '2012-05-13', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGLINGGO, DS. NGLINGGO NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(61, 161, 1, 1, '20260061', '0122473332', 'MUHAMMAD RIZAL AFFANDI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-02-18', '2012-02-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(62, 162, 1, 1, '20260062', '3126201177', 'NUR SYAFA PUTRI RAMADHANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-08-14', '2012-08-14', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(63, 163, 1, 1, '20260063', '0126619547', 'PUTRA PRASETYO ADI PRAYOGO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-12-10', '2012-12-10', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(64, 164, 1, 1, '20260064', '0127677402', 'WISNUTAMA SYAHDHAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-07-16', '2012-07-16', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(65, 165, 1, 1, '20260065', '0125494962', 'WIWIK BINTI APRELIA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-09-18', '2012-09-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(66, 166, 1, 1, '20260066', '0115017731', 'YENI AFISSUNANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-06-19', '2011-06-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SAMBONG, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SAMBONG, DS. GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(67, 167, 1, 1, '20260067', '0115914277', 'KAISAN GREE GHIFFARY', 'L', 'L', 'TANGERANG', 'TANGERANG', '2011-06-06', '2011-06-06', 'Wali Murid', 'Wali Murid', NULL, 'JL. RAYA SUKOMORO-PACE KM. 1 KAPAS, SUKOMORO, NGANJUK, JAWA TIMUR, 64481', 'JL. RAYA SUKOMORO-PACE KM. 1 KAPAS, SUKOMORO, NGANJUK, JAWA TIMUR, 64481', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(68, 168, 1, 1, '20260068', '3122832734', 'MEZZALUNA ARSESHA SETIAWAN', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-06-21', '2012-06-21', 'Wali Murid', 'Wali Murid', NULL, 'DUSUSN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUSN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(69, 169, 1, 1, '20260069', '0123453484', 'MOCH AL NIZAM PUTRA BAHRI', 'L', 'L', 'KEDIRI', 'KEDIRI', '2012-08-06', '2012-08-06', 'Wali Murid', 'Wali Murid', NULL, 'JL. JOYOBOYO II / 12 DUSUN TEPUS DESA SUKOREJO KECAMATAN NGASEM KABUPATEN KEDIRI SUKOREJO, NGASEM, KEDIRI, JAWA TIMUR, 64182', 'JL. JOYOBOYO II / 12 DUSUN TEPUS DESA SUKOREJO KECAMATAN NGASEM KABUPATEN KEDIRI SUKOREJO, NGASEM, KEDIRI, JAWA TIMUR, 64182', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(70, 170, 1, 1, '20260070', '0102224941', 'MARDIANSYAH ADI SAPUTRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2010-03-27', '2010-03-27', 'Wali Murid', 'Wali Murid', NULL, 'DESA BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64452', 'DESA BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(71, 171, 1, 1, '20260071', '0113713659', 'ANDHI PUTRA MAULANA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-02-11', '2011-02-11', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(72, 172, 1, 1, '20260072', '0111114726', 'ANDHIKA PUTRA MAULANA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-02-11', '2011-02-11', 'Wali Murid', 'Wali Murid', NULL, 'DSN JINTEL, REJOSO JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN JINTEL, REJOSO JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(73, 173, 1, 1, '20260073', '3128531526', 'CHAYRA FAYOLLA NAZHIROH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-12-07', '2012-12-07', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(74, 174, 1, 1, '20260074', '0124350997', 'JOHAN RIZKI PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-10-28', '2012-10-28', 'Wali Murid', 'Wali Murid', NULL, 'Dsn Ngrambek Desa Campur Kec. Gondang CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'Dsn Ngrambek Desa Campur Kec. Gondang CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(75, 175, 1, 1, '20260075', '0133962880', 'M.FERENDRA YUBI HAIDAR AFIAR YULIANTO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-02-01', '2013-02-01', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(76, 176, 1, 1, '20260076', '0138152999', 'MOHAMAD FAREL DWI ADITYA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2013-02-28', '2013-02-28', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGASEM, DS. SUMBERJO SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(77, 177, 1, 1, '20260077', '0121869487', 'MOHAMAD RIZQI ALDIANSYAH', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-02-08', '2012-02-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(78, 178, 1, 1, '20260078', '0129929586', 'MOHAMMAD ARFI EKO PRASETYO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-03-08', '2012-03-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SAMBONG, DS. CAMPUR, GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SAMBONG, DS. CAMPUR, GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(79, 179, 1, 1, '20260079', '0122721604', 'MOHHAMAD ANDIKA PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-12-27', '2012-12-27', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(80, 180, 1, 1, '20260080', '3126750130', 'MUCHAMMAD RIZQI PUTRA PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-09-21', '2012-09-21', 'Wali Murid', 'Wali Murid', NULL, 'DSN. PUHKEREP, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. PUHKEREP, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(81, 181, 1, 1, '20260081', '0125139495', 'MUHAMMAD EKO RIFA\'I', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-08-29', '2012-08-29', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(82, 182, 1, 1, '20260082', '3128724594', 'MUHAMMAD SAFI\'I', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-01-20', '2012-01-20', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KAWEDEGAN, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KAWEDEGAN, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(83, 183, 1, 1, '20260083', '0133809735', 'NABILA PERMATASARI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2013-03-12', '2013-03-12', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(84, 184, 1, 1, '20260084', '3122381181', 'SEPTIA LILA JUARIYAH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-06-18', '2012-06-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(85, 185, 1, 1, '20260085', '0128890869', 'SINDI CANDRA WINATA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-08-11', '2012-08-11', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(86, 186, 1, 1, '20260086', '0122247568', 'VERRA ANANDA MEICHA PUTRIATAMA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-05-08', '2012-05-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(87, 187, 1, 1, '20260087', '0137782431', 'VICKHA DWI WAHYUNI', 'P', 'P', 'BINUANG', 'BINUANG', '2013-02-08', '2013-02-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(88, 188, 5, 5, '20260088', '0118357022', 'CAHAYA GEMILANG', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-05-21', '2011-05-21', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN JATISARI NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64452', 'DUSUN JATISARI NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(89, 189, 5, 5, '20260089', '0115522421', 'CANTIKA ENJELISCA AGUSTINA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-08-03', '2011-08-03', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KENTINGAN PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64452', 'DUSUN KENTINGAN PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(90, 190, 5, 5, '20260090', '0114135122', 'FARID HIDAYAT', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-07-25', '2011-07-25', 'Wali Murid', 'Wali Murid', NULL, 'DESA TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DESA TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(91, 191, 5, 5, '20260091', '0118463369', 'FRISSIL ANGELICA PUTRI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-09-14', '2011-09-14', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(92, 192, 5, 5, '20260092', '0123521552', 'GIZELA AYU ANINDYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-03-17', '2012-03-17', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(93, 193, 5, 5, '20260093', '0122180419', 'JUVA DERY NAFIANSA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-09-21', '2012-09-21', 'Wali Murid', 'Wali Murid', NULL, 'DSN BALONGREJO, BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN BALONGREJO, BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(94, 194, 5, 5, '20260094', '0125793283', 'M. FADIL IKHSAN THAHIR', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-18', '2012-04-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(95, 195, 5, 5, '20260095', '0117159158', 'MEYRZA YOPANA FII`ADZALILA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-09-19', '2011-09-19', 'Wali Murid', 'Wali Murid', NULL, 'TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(96, 196, 5, 5, '20260096', '0122084024', 'MIRZA ADMA PUTRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-05-19', '2012-05-19', 'Wali Murid', 'Wali Murid', NULL, 'DSN NGRINGIN JINTEL JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN NGRINGIN JINTEL JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(97, 197, 5, 5, '20260097', '0111605886', 'MOHAMAD MALIK HIDAYATULLOH', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-10-08', '2011-10-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(98, 198, 5, 5, '20260098', '0122486854', 'REVANITA OKTAVIANI WIBOWO', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-10-17', '2012-10-17', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(99, 199, 5, 5, '20260099', '0128481369', 'RICHARD ALVIANO ZANUAR', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-01-10', '2012-01-10', 'Wali Murid', 'Wali Murid', NULL, 'DSN. SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. SENGGOWAR SENGGOWAR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(100, 200, 5, 5, '20260100', '0112395460', 'SIFA\'UL KILA RAMADHANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-08-11', '2011-08-11', 'Wali Murid', 'Wali Murid', NULL, 'DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(101, 201, 5, 5, '20260101', '0113639191', 'SOFIAN ALBERT FERNANDO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-08-29', '2011-08-29', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATIGETIH, DS. SANGGRAHAN, GONDANG SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. JATIGETIH, DS. SANGGRAHAN, GONDANG SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(102, 202, 5, 5, '20260102', '3117406875', 'SYIFA\'UL KHUMAIROH', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-05-11', '2011-05-11', 'Wali Murid', 'Wali Murid', NULL, 'CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'CAMPUR CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(103, 203, 5, 5, '20260103', '0112271627', 'TARI CINTA SEFIYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-09-08', '2011-09-08', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KAWEDEKAN, BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KAWEDEKAN, BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(104, 204, 5, 5, '20260104', '0127043670', 'TISYA NUR MAULIDA HARSANTY', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-01-28', '2012-01-28', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(105, 205, 5, 5, '20260105', '0114862518', 'TRIAS DESTA SARI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-12-23', '2011-12-23', 'Wali Murid', 'Wali Murid', NULL, 'DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(106, 206, 5, 5, '20260106', '0122423929', 'XANCHE KALLISTA BENING SALSABILA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-02-18', '2012-02-18', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BESUKI, DS. NGLINGGO, GONDANG NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BESUKI, DS. NGLINGGO, GONDANG NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(107, 207, 5, 5, '20260107', '0112578982', 'RIZQI NUR ANGGRAINI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-05-24', '2011-05-24', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN SUKOREJO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64452', 'DUSUN SUKOREJO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(108, 208, 5, 5, '20260108', '0116592410', 'SENDI JAYA PRATAMA', 'L', 'L', 'PRINGSEWU', 'PRINGSEWU', '2011-04-11', '2011-04-11', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BALONGGLAGAH CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64452', 'DUSUN BALONGGLAGAH CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(109, 209, 5, 5, '20260109', '3110590155', 'TEZZA LATIF NAZELA AVRELA TAMA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-04-10', '2012-04-10', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(110, 210, 5, 5, '20260110', '0117095937', 'YONGKI BIWANTORO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-10-19', '2011-10-19', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(111, 211, 5, 5, '20260111', '0111930479', 'YOVI KIA PUTRA ARLYAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-07-03', '2011-07-03', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(112, 212, 6, 6, '20260112', '0128144891', 'ANDRIAN ADJI PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-20', '2012-04-20', 'Wali Murid', 'Wali Murid', NULL, 'DS. PANDEAN, GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. PANDEAN, GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(113, 213, 6, 6, '20260113', '0115451414', 'ANISA\' NURMAIDA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-09-23', '2011-09-23', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(114, 214, 6, 6, '20260114', '0125211601', 'ASSYAFIRA DIKA MARTASYA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-03-24', '2012-03-24', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(115, 215, 6, 6, '20260115', '0127059266', 'CANDRA PUTRA KENCANA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-07', '2012-04-07', 'Wali Murid', 'Wali Murid', NULL, 'DS. JINTEL, REJOSO JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DS. JINTEL, REJOSO JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(116, 216, 6, 6, '20260116', '0118609836', 'FACHRI REZA ALFIANSYAH', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-02-07', '2011-02-07', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATISARI, DS. NGANGKATAN, REJOSO NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. JATISARI, DS. NGANGKATAN, REJOSO NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(117, 217, 6, 6, '20260117', '0126143547', 'HERLIN DWI WIDYANINGRUM', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-01-16', '2012-01-16', 'Wali Murid', 'Wali Murid', NULL, 'DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(118, 218, 6, 6, '20260118', '0116921911', 'INTAN PERMATA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-04-02', '2011-04-02', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KEDUNGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. KEDUNGREJO, DS. BALONGGEBANG, GONDANG BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(119, 219, 6, 6, '20260119', '0114152139', 'IRFAN FAHREZA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-07-06', '2011-07-06', 'Wali Murid', 'Wali Murid', NULL, 'DS. PANDEAN, GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DS. PANDEAN, GONDANG PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(120, 220, 6, 6, '20260120', '0111484929', 'KHOIRUL FUAD FACHRUDIN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-06-22', '2011-06-22', 'Wali Murid', 'Wali Murid', NULL, 'DSN. JATISARI, DS. NGANGKATAN, REJOSO NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. JATISARI, DS. NGANGKATAN, REJOSO NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(121, 221, 6, 6, '20260121', '0125587125', 'M. LUCKY ADI PUTRA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-07-03', '2012-07-03', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BALONGGLAGAH, DS. CAMPUR, GONDANG, NGANJUK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BALONGGLAGAH, DS. CAMPUR, GONDANG, NGANJUK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(122, 222, 6, 6, '20260122', '0113640889', 'MOCH. MANDALA PUTRA DWIANTO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-05-09', '2011-05-09', 'Wali Murid', 'Wali Murid', NULL, 'DSN. BORO, DS. SUMBERJO, KEC. GONDANG SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. BORO, DS. SUMBERJO, KEC. GONDANG SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(123, 223, 6, 6, '20260123', '3111579330', 'MOH. KHAMAT PAJAR ARTA HARTAWAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-11-16', '2011-11-16', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(124, 224, 6, 6, '20260124', '0122007443', 'MUHAMMAD ALKHALIFI DZIKRI CANDRA YUDA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-05-20', '2012-05-20', 'Wali Murid', 'Wali Murid', NULL, 'DSN. NGRAMBEK, DS. CAMPUR, GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DSN. NGRAMBEK, DS. CAMPUR, GONDANG CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(125, 225, 6, 6, '20260125', '0112328335', 'MUHAMMAD NIZAL IQBAL AL-HADI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-01-11', '2012-01-11', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(126, 226, 6, 6, '20260126', '0119064628', 'REVALINA AYU ANGGRAINI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-03-14', '2011-03-14', 'Wali Murid', 'Wali Murid', NULL, 'DSN. KENTINGAN, DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DSN. KENTINGAN, DS. PUHKEREP, REJOSO PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(127, 227, 6, 6, '20260127', '0118975478', 'RIDHO PANJI MAULANA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-11-16', '2011-11-16', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(128, 228, 6, 6, '20260128', '0114072167', 'NIKO SEPTIAN REVANO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-09-05', '2011-09-05', 'Wali Murid', 'Wali Murid', NULL, 'DESA PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DESA PANDEAN PANDEAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(129, 229, 6, 6, '20260129', '0117270690', 'PADHIL ZAEROFI AFIF', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-12-26', '2011-12-26', 'Wali Murid', 'Wali Murid', NULL, 'dusun jatigetih SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64452', 'dusun jatigetih SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64452', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(130, 230, 6, 6, '20260130', '0112501135', 'REYHAN PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-12-25', '2011-12-25', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(131, 231, 6, 6, '20260131', '0102645489', 'RISMA AURELLIA AGUSTINA', 'P', 'P', 'KEDIRI', 'KEDIRI', '2010-06-07', '2010-06-07', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN SIDOWALEK KULON SIDOWAREK, PLEMAHAN, KEDIRI, JAWA TIMUR, 64155', 'DUSUN SIDOWALEK KULON SIDOWAREK, PLEMAHAN, KEDIRI, JAWA TIMUR, 64155', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(132, 232, 1, 1, '20260132', '0113014679', 'RIF\'ATUL LAYLY AZ\'ZAHRA SARAS', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-10-02', '2011-10-02', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(133, 233, 1, 1, '20260133', '0117526617', 'SAIFULLOH EKO YUSUF', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-04-26', '2011-04-26', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(134, 234, 1, 1, '20260134', '0126350319', 'STEFANNIE PUTRI RAGIL ERNAWATI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2012-03-20', '2012-03-20', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN BESUKI NGLINGGO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(135, 235, 1, 1, '20260135', '0115598112', 'SYIFA AURA PURNAMA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-12-01', '2011-12-01', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN BALONGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(136, 236, 1, 1, '20260136', '0116837133', 'ZAINURIL FERDIANSYAH', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-06-01', '2011-06-01', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN NGRAMBEK CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(137, 237, 1, 1, '20260137', '0116754662', 'ZHAHIRA ALYA NUR RAHMADHANI', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-12-09', '2011-12-09', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(138, 238, 1, 1, '20260138', '0117285048', 'AHMAD YUSQI MURTADHO', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-03-10', '2011-03-10', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(139, 239, 1, 1, '20260139', '0118580095', 'ANDRE BACHTIAR', 'L', 'L', 'BOYOLALI', 'BOYOLALI', '2011-06-29', '2011-06-29', 'Wali Murid', 'Wali Murid', NULL, 'JL. PAHLAWAN GONDANGKULON, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'JL. PAHLAWAN GONDANGKULON, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(140, 240, 1, 1, '20260140', '0108603886', 'BINTANG WAHYU OKTAVIAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2010-12-03', '2010-12-03', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(141, 241, 1, 1, '20260141', '0117719391', 'FADILA ALIFIA ATMAJA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-06-11', '2011-06-11', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(142, 242, 1, 1, '20260142', '0118231326', 'IRFAN PUTRA ADITYA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-05-12', '2011-05-12', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(143, 243, 1, 1, '20260143', '0116095543', 'ISNAENI EKA OKTAVIANA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-10-03', '2011-10-03', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(144, 244, 1, 1, '20260144', '0125432058', 'JOHAN RIDHO ALMADA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-02-25', '2012-02-25', 'Wali Murid', 'Wali Murid', NULL, 'JATISARI NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'JATISARI NGANGKATAN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(145, 245, 1, 1, '20260145', '0118114356', 'JUNIA SHERIEL ANANDITA', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-06-26', '2011-06-26', 'Wali Murid', 'Wali Murid', NULL, 'DESA TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DESA TALUN TALUN, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(146, 246, 1, 1, '20260146', '0119907158', 'LEANDRA PUTRI AYUNINGTYAS', 'P', 'P', 'NGANJUK', 'NGANJUK', '2011-06-08', '2011-06-08', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN SUKOREJO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN SUKOREJO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(147, 247, 1, 1, '20260147', '0119759257', 'LUDVI NUR FADLI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-12-22', '2011-12-22', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KEDUNGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KEDUNGREJO BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(148, 248, 1, 1, '20260148', '0113823266', 'MOCHAMMAD NUR WAHID', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-05-29', '2011-05-29', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(149, 249, 1, 1, '20260149', '0115522345', 'MOH. ADI PRATAMA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-08-09', '2011-08-09', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN NGASEM SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN NGASEM SUMBERJO, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(150, 250, 1, 1, '20260150', '0113846546', 'MUHAMMAD ILHAM HANAFI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-07-02', '2011-07-02', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PULO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN PULO CAMPUR, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(151, 251, 1, 1, '20260151', '0117755815', 'MUHAMMAD KHOIRUL MARZUQI', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-04-05', '2012-04-05', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN JATIGETIH SANGGRAHAN, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(152, 252, 1, 1, '20260152', '0129011176', 'MUHAMMAD NAJIB SETYAWAN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2012-03-14', '2012-03-14', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN NGRINGIN JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN NGRINGIN JINTEL, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(153, 253, 1, 1, '20260153', '0116152712', 'MUHAMMAD NUR KHOIRUDIN', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-04-29', '2011-04-29', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', 'DUSUN PUHKEREP PUHKEREP, REJOSO, NGANJUK, JAWA TIMUR, 64453', NULL, '2026-07-15', 2026, 'aktif', 1, NOW()),
(154, 254, 1, 1, '20260154', '0118205624', 'MUHAMMAD REZA ADITYA', 'L', 'L', 'NGANJUK', 'NGANJUK', '2011-06-23', '2011-06-23', 'Wali Murid', 'Wali Murid', NULL, 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', 'DUSUN KAWEDEGAN BALONGGEBANG, GONDANG, NGANJUK, JAWA TIMUR, 64451', NULL, '2026-07-15', 2026, 'aktif', 1, NOW());

-- 8. Lokasi Sekolah untuk Presensi Mobile QR & GPS
INSERT INTO `lokasi_sekolah` (`id`, `nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`, `created_at`) VALUES
(1, 'Kantor Guru MTs Al-Huda Gondang', 'MTS-ALHUDA-ABSENSI-2026-SECURE', -7.56234500, 111.89012300, 20, 1, NOW());

-- 9. Contoh Presensi Guru
INSERT INTO `presensi_guru` (`id`, `teacher_id`, `guru_id`, `attendance_date`, `tanggal`, `attendance_type`, `check_in_at`, `jam_masuk`, `status`, `notes`, `keterangan`, `created_at`) VALUES
(1, 1, 1, CURDATE(), CURDATE(), 'daily', NOW(), '06:45:00', 'hadir', 'Presensi tepat waktu via Mobile App', 'Presensi tepat waktu via Mobile App', NOW()),
(2, 2, 2, CURDATE(), CURDATE(), 'daily', NOW(), '06:50:00', 'hadir', 'Hadir tepat waktu', 'Hadir tepat waktu', NOW()),
(3, 3, 3, CURDATE(), CURDATE(), 'daily', NOW(), '06:55:00', 'hadir', 'Hadir tepat waktu', 'Hadir tepat waktu', NOW());

-- 10. Pengaturan Dasar Madrasah
INSERT INTO `pengaturan` (`setting_key`, `setting_value`, `value_type`, `group_name`, `label`, `is_public`, `created_at`) VALUES
('site_name', 'MTs Al-Huda', 'string', 'identitas', 'Nama madrasah', 1, NOW()),
('site_subtitle', 'Gondang', 'string', 'identitas', 'Keterangan nama', 1, NOW()),
('site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang', 'string', 'identitas', 'Nama lengkap madrasah', 1, NOW()),
('site_nsm', '121235180045', 'string', 'identitas', 'Nomor Statistik Madrasah (NSM)', 1, NOW()),
('site_npsn', '20582234', 'string', 'identitas', 'NPSN', 1, NOW()),
('site_foundation', 'Yayasan Pendidikan Al-Huda Gondang', 'string', 'identitas', 'Nama yayasan', 1, NOW()),
('accreditation_grade', 'A', 'string', 'identitas', 'Peringkat akreditasi BAN-S/M', 1, NOW()),
('accreditation_year', '2024', 'string', 'identitas', 'Tahun akreditasi', 1, NOW()),
('contact_address', 'Jl. Raya Gondang, Pandean, Kec. Gondang, Kab. Nganjuk, Jawa Timur 64451', 'text', 'kontak', 'Alamat lengkap', 1, NOW()),
('contact_phone', '(0358) 771234', 'string', 'kontak', 'Nomor telepon kantor', 1, NOW()),
('contact_whatsapp', '6281234567890', 'string', 'kontak', 'Nomor WhatsApp resmi', 1, NOW()),
('contact_email', 'info@mts-alhuda.sch.id', 'string', 'kontak', 'Email resmi madrasah', 1, NOW()),
('contact_hours', 'Senin - Sabtu, 07.00 - 14.00 WIB', 'string', 'kontak', 'Jam operasional sekolah', 1, NOW()),
('contact_map_lat', '-7.562345', 'string', 'kontak', 'Latitude lokasi', 1, NOW()),
('contact_map_lng', '111.890123', 'string', 'kontak', 'Longitude lokasi', 1, NOW()),
('social_facebook', 'https://facebook.com/mtsalhudagondang', 'string', 'sosial', 'Facebook resmi', 1, NOW()),
('social_instagram', 'https://instagram.com/mtsalhudagondang', 'string', 'sosial', 'Instagram resmi', 1, NOW()),
('social_youtube', 'https://youtube.com/@mtsalhudagondang', 'string', 'sosial', 'Channel YouTube', 1, NOW()),
('social_tiktok', 'https://tiktok.com/@mtsalhudagondang', 'string', 'sosial', 'Akun TikTok', 1, NOW()),
('meta_title', 'MTs Al-Huda Gondang | Madrasah Berakhlak, Berilmu, dan Berprestasi', 'string', 'seo', 'Judul SEO situs', 1, NOW()),
('meta_description', 'Situs resmi Madrasah Tsanawiyah Al-Huda Gondang Nganjuk. Menyajikan informasi profil, pendaftaran PPDB, kurikulum terpadu, dan portal akademik Madrasahku.', 'text', 'seo', 'Deskripsi SEO situs', 1, NOW()),
('attendance_check_in_time', '07:00', 'string', 'presensi', 'Batas jam masuk', 0, NOW()),
('attendance_late_tolerance', '15', 'int', 'presensi', 'Toleransi keterlambatan (menit)', 0, NOW()),
('attendance_check_out_time', '14:00', 'string', 'presensi', 'Jam mulai boleh pulang', 0, NOW()),
('security_max_login_attempts', '5', 'int', 'keamanan', 'Batas percobaan login gagal', 0, NOW()),
('security_lockout_minutes', '15', 'int', 'keamanan', 'Durasi penguncian akun (menit)', 0, NOW()),
('security_session_timeout', '120', 'int', 'keamanan', 'Batas waktu sesi web (menit)', 0, NOW()),
('security_api_access_ttl', '60', 'int', 'keamanan', 'Masa aktif akses token API (menit)', 0, NOW()),
('ppdb_status', 'buka', 'string', 'ppdb', 'Status Pendaftaran PPDB', 1, NOW()),
('ppdb_tahun_ajaran', '2026/2027', 'string', 'ppdb', 'Tahun ajaran PPDB dibuka', 1, NOW()),
('ppdb_kuota', '180', 'int', 'ppdb', 'Total daya tampung PPDB', 1, NOW())
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 11. Konten Publik CMS MTs Al-Huda
INSERT INTO `berita` (`id`, `title`, `judul`, `slug`, `excerpt`, `content`, `isi`, `image_path`, `gambar`, `category`, `kategori`, `status`, `published_at`, `author_id`) VALUES
(1, 'Peringatan Hari Santri Nasional Berlangsung Khidmat di Lapangan Madrasah', 'Peringatan Hari Santri Nasional Berlangsung Khidmat di Lapangan Madrasah', 'peringatan-hari-santri-nasional-berlangsung-khidmat', 'Seluruh dewan guru, staf, dan ratusan santri MTs Al-Huda Gondang mengikuti apel akbar peringatan Hari Santri Nasional dengan mengenakan busana muslim serba putih.', 'Seluruh dewan guru, karyawan, serta 154 peserta didik MTs Al-Huda Gondang memadati lapangan madrasah pada upacara peringatan Hari Santri Nasional. Bertindak selaku pembina upacara, Kepala Madrasah menekankan pentingnya jihad intelektual, adab santri, serta penguasaan teknologi di era digital. Acara dilanjutkan dengan penampilan paduan suara madrasah dan doa bersama untuk bangsa.', 'Seluruh dewan guru, karyawan, serta 154 peserta didik MTs Al-Huda Gondang memadati lapangan madrasah pada upacara peringatan Hari Santri Nasional. Bertindak selaku pembina upacara, Kepala Madrasah menekankan pentingnya jihad intelektual, adab santri, serta penguasaan teknologi di era digital. Acara dilanjutkan dengan penampilan paduan suara madrasah dan doa bersama untuk bangsa.', 'images/berita/hari-santri.jpg', 'images/berita/hari-santri.jpg', 'Kegiatan', 'Kegiatan', 'published', NOW(), 1),
(2, 'Siswa MTs Al-Huda Raih Medali Emas Olimpiade Sains Madrasah Tingkat Kabupaten', 'Siswa MTs Al-Huda Raih Medali Emas Olimpiade Sains Madrasah Tingkat Kabupaten', 'siswa-mts-al-huda-raih-medali-emas-olimpiade-sains', 'Prestasi membanggakan kembali ditorehkan ananda peserta didik MTs Al-Huda dalam ajang Kompetisi Sains Madrasah (KSM) bidang Matematika dan IPA Terpadu.', 'Prestasi membanggakan kembali ditorehkan oleh santri binaan MTs Al-Huda Gondang dalam ajang Kompetisi Sains Madrasah (KSM) tingkat kabupaten. Melalui bimbingan intensif dewan guru MIPA, perwakilan madrasah berhasil membawa pulang Medali Emas dan berhak melaju ke tingkat provinsi. Kepala madrasah menyampaikan apresiasi setinggi-tingginya kepada pembina dan peserta didik.', 'Prestasi membanggakan kembali ditorehkan oleh santri binaan MTs Al-Huda Gondang dalam ajang Kompetisi Sains Madrasah (KSM) tingkat kabupaten. Melalui bimbingan intensif dewan guru MIPA, perwakilan madrasah berhasil membawa pulang Medali Emas dan berhak melaju ke tingkat provinsi. Kepala madrasah menyampaikan apresiasi setinggi-tingginya kepada pembina dan peserta didik.', 'images/berita/prestasi-ksm.jpg', 'images/berita/prestasi-ksm.jpg', 'Prestasi', 'Prestasi', 'published', NOW(), 1);

INSERT INTO `fasilitas` (`id`, `name`, `nama_fasilitas`, `slug`, `category`, `description`, `deskripsi`, `image_path`, `gambar`, `condition`, `kondisi`, `quantity`, `jumlah`, `sort_order`, `is_active`) VALUES
(1, 'Laboratorium Komputer & Multimedia', 'Laboratorium Komputer & Multimedia', 'laboratorium-komputer-multimedia', 'Sarana Pembelajaran', 'Ruang lab komputer berpendingin udara (AC) dengan 36 unit komputer personal generasi mutakhir, jaringan serat optik kecepatan tinggi, dan proyektor interaktif untuk pembelajaran TIK dan Asesmen Nasional.', 'Ruang lab komputer berpendingin udara (AC) dengan 36 unit komputer personal generasi mutakhir, jaringan serat optik kecepatan tinggi, dan proyektor interaktif untuk pembelajaran TIK dan Asesmen Nasional.', 'images/fasilitas/lab-komputer.jpg', 'images/fasilitas/lab-komputer.jpg', 'sangat_baik', 'sangat_baik', 36, 36, 1, 1),
(2, 'Perpustakaan Khazanah Ilmu', 'Perpustakaan Khazanah Ilmu', 'perpustakaan-khazanah-ilmu', 'Sarana Literasi', 'Koleksi ribuan buku pelajaran, kitab kuning, ensiklopedia sains, novel sastra, serta area baca lesehan yang hening dan nyaman bagi santri.', 'Koleksi ribuan buku pelajaran, kitab kuning, ensiklopedia sains, novel sastra, serta area baca lesehan yang hening dan nyaman bagi santri.', 'images/fasilitas/perpustakaan.jpg', 'images/fasilitas/perpustakaan.jpg', 'baik', 'baik', 1, 1, 2, 1),
(3, 'Masjid Jami Al-Huda', 'Masjid Jami Al-Huda', 'masjid-jami-al-huda', 'Sarana Ibadah', 'Pusat pembinaan spiritual madrasah untuk pelaksanaan shalat Dhuha berjamaah, dhuhur berjamaah, pembacaan ratibul haddad, serta kajian kitab akhlak santri.', 'Pusat pembinaan spiritual madrasah untuk pelaksanaan shalat Dhuha berjamaah, dhuhur berjamaah, pembacaan ratibul haddad, serta kajian kitab akhlak santri.', 'images/fasilitas/masjid.jpg', 'images/fasilitas/masjid.jpg', 'sangat_baik', 'sangat_baik', 1, 1, 3, 1),
(4, 'Laboratorium IPA Terpadu', 'Laboratorium IPA Terpadu', 'laboratorium-ipa-terpadu', 'Sarana Pembelajaran', 'Fasilitas eksperimen Fisika, Biologi, dan Kimia dasar lengkap dengan mikroskop binokuler, alat peraga anatomi manusia, dan reagen praktikum aman.', 'Fasilitas eksperimen Fisika, Biologi, dan Kimia dasar lengkap dengan mikroskop binokuler, alat peraga anatomi manusia, dan reagen praktikum aman.', 'images/fasilitas/lab-ipa.jpg', 'images/fasilitas/lab-ipa.jpg', 'baik', 'baik', 1, 1, 4, 1);

INSERT INTO `galeri` (`id`, `album`, `title`, `judul`, `caption`, `keterangan`, `image_path`, `gambar`, `sort_order`, `is_active`, `uploaded_by`) VALUES
(1, 'Kegiatan Santri', 'Shalat Dhuha Berjamaah Pagi Hari', 'Shalat Dhuha Berjamaah Pagi Hari', 'Rutinitas pembiasaan shalat Dhuha berjamaah setiap pagi sebelum memulai jam pelajaran pertama.', 'Rutinitas pembiasaan shalat Dhuha berjamaah setiap pagi sebelum memulai jam pelajaran pertama.', 'images/galeri/dhuha-pagi.jpg', 'images/galeri/dhuha-pagi.jpg', 1, 1, 1),
(2, 'Ekstrakurikuler', 'Latihan Regu Pramuka MTs Al-Huda', 'Latihan Regu Pramuka MTs Al-Huda', 'Latihan semaphore, tali temali, dan baris berbaris regu penggalang pramuka madrasah.', 'Latihan semaphore, tali temali, dan baris berbaris regu penggalang pramuka madrasah.', 'images/galeri/pramuka.jpg', 'images/galeri/pramuka.jpg', 2, 1, 1),
(3, 'Pembelajaran', 'Praktikum Sains di Laboratorium', 'Praktikum Sains di Laboratorium', 'Antusiasme santri mengamati spesimen biologi sel tumbuhan menggunakan mikroskop.', 'Antusiasme santri mengamati spesimen biologi sel tumbuhan menggunakan mikroskop.', 'images/galeri/praktikum.jpg', 'images/galeri/praktikum.jpg', 3, 1, 1);

INSERT INTO `pengumuman` (`id`, `title`, `judul`, `slug`, `content`, `isi`, `is_pinned`, `status`, `published_at`, `author_id`) VALUES
(1, 'Penerimaan Peserta Didik Baru (PPDB) Jalur Prestasi dan Reguler Telah Dibuka', 'Penerimaan Peserta Didik Baru (PPDB) Jalur Prestasi dan Reguler Telah Dibuka', 'ppdb-2026-2027-telah-dibuka', 'Diberitahukan kepada seluruh calon wali murid bahwa pendaftaran siswa baru MTs Al-Huda Gondang untuk Tahun Ajaran 2026/2027 telah resmi dibuka secara online maupun langsung di kantor sekretariat madrasah.', 'Diberitahukan kepada seluruh calon wali murid bahwa pendaftaran siswa baru MTs Al-Huda Gondang untuk Tahun Ajaran 2026/2027 telah resmi dibuka secara online maupun langsung di kantor sekretariat madrasah.', 1, 'published', NOW(), 1),
(2, 'Jadwal Penilaian Akhir Semester (PAS) Ganjil Tahun Ajaran 2026/2027', 'Jadwal Penilaian Akhir Semester (PAS) Ganjil Tahun Ajaran 2026/2027', 'jadwal-penilaian-akhir-semester-ganjil', 'Penilaian Akhir Semester (PAS) Ganjil akan dilaksanakan mulai tanggal 1 Desember 2026. Santri diharapkan melunasi administrasi dan mempersiapkan kartu ujian.', 'Penilaian Akhir Semester (PAS) Ganjil akan dilaksanakan mulai tanggal 1 Desember 2026. Santri diharapkan melunasi administrasi dan mempersiapkan kartu ujian.', 0, 'published', NOW(), 1);

INSERT INTO `agenda` (`id`, `title`, `judul`, `description`, `deskripsi`, `location`, `lokasi`, `start_at`, `tgl_mulai`, `end_at`, `tgl_selesai`, `all_day`, `status`, `author_id`) VALUES
(1, 'Penilaian Akhir Semester (PAS) Ganjil', 'Penilaian Akhir Semester (PAS) Ganjil', 'Ujian evaluasi hasil belajar semester ganjil untuk seluruh jenjang kelas 7, 8, dan 9.', 'Ujian evaluasi hasil belajar semester ganjil untuk seluruh jenjang kelas 7, 8, dan 9.', 'Ruang Ujian MTs Al-Huda', 'Ruang Ujian MTs Al-Huda', '2026-12-01 07:00:00', '2026-12-01 07:00:00', '2026-12-08 12:30:00', '2026-12-08 12:30:00', 0, 'published', 1),
(2, 'Pondok Ramadhan & Bakti Sosial Santri', 'Pondok Ramadhan & Bakti Sosial Santri', 'Kajian intensif fikih puasa, tadarus 30 juz, serta pembagian paket sembako kepada dhuafa di lingkungan sekitar madrasah.', 'Kajian intensif fikih puasa, tadarus 30 juz, serta pembagian paket sembako kepada dhuafa di lingkungan sekitar madrasah.', 'Masjid Al-Huda & Lingkungan Desa', 'Masjid Al-Huda & Lingkungan Desa', '2027-03-15 08:00:00', '2027-03-15 08:00:00', '2027-03-20 16:00:00', '2027-03-20 16:00:00', 0, 'published', 1);

INSERT INTO `dokumen_ppdb` (`id`, `title`, `judul`, `description`, `file_path`, `file_url`, `file_size`, `mime_type`, `download_count`, `jumlah_unduh`, `sort_order`, `is_active`, `uploaded_by`) VALUES
(1, 'Brosur & Panduan Pendaftaran PPDB 2026/2027', 'Brosur & Panduan Pendaftaran PPDB 2026/2027', 'Informasi alur pendaftaran, syarat berkas, seragam, dan program beasiswa tahfidz.', 'uploads/ppdb/brosur-ppdb-2026.pdf', 'uploads/ppdb/brosur-ppdb-2026.pdf', 1450000, 'application/pdf', 128, 128, 1, 1, 1),
(2, 'Formulir Registrasi Siswa Baru (Format Cetak)', 'Formulir Registrasi Siswa Baru (Format Cetak)', 'Formulir biodata santri dan surat pernyataan kesanggupan tata tertib bermaterai.', 'uploads/ppdb/formulir-pendaftaran-2026.pdf', 'uploads/ppdb/formulir-pendaftaran-2026.pdf', 850000, 'application/pdf', 74, 74, 2, 1, 1);

INSERT INTO `pesan_kontak` (`id`, `nama`, `email`, `telepon`, `subjek`, `pesan`, `status`, `balasan`, `created_at`) VALUES
(1, 'Budi Santoso', 'budisantoso@gmail.com', '081298765432', 'Tanya Syarat PPDB Jalur Prestasi', 'Assalamu alaikum. Apakah untuk santri yang memiliki hafalan Al-Quran 3 Juz mendapatkan beasiswa SPP bebas hingga lulus? Mohon informasinya, terima kasih.', 'baru', NULL, NOW()),
(2, 'Siti Rahmawati', 'rahmawatisiti@yahoo.com', '085712348901', 'Konfirmasi Biaya Seragam Santri Putri', 'Selamat pagi pihak tata usaha MTs Al-Huda. Untuk rincian seragam santri putri apakah sudah termasuk jilbab madrasah?', 'dibaca', NULL, NOW());

INSERT INTO `menu` (`id`, `parent_id`, `title`, `url`, `target`, `sort_order`, `is_active`) VALUES
(1, NULL, 'Beranda', '/', '_self', 1, 1),
(2, NULL, 'Profil', '/profil', '_self', 2, 1),
(3, NULL, 'Akademik', '/akademik', '_self', 3, 1),
(4, NULL, 'Berita', '/berita', '_self', 4, 1),
(5, NULL, 'Fasilitas', '/fasilitas', '_self', 5, 1),
(6, NULL, 'Galeri', '/galeri', '_self', 6, 1),
(7, NULL, 'PPDB', '/ppdb', '_self', 7, 1),
(8, NULL, 'Kontak', '/kontak', '_self', 8, 1);

INSERT INTO `schema_migrations` (`version`, `name`, `batch`, `statements`, `executed_at`) VALUES
('001', 'foundation', 1, 3, NOW()),
('002', 'identity_core', 1, 3, NOW()),
('003', 'identity_security', 1, 4, NOW()),
('004', 'cms_content', 1, 5, NOW()),
('005', 'cms_support', 1, 5, NOW()),
('006', 'academic_core', 1, 4, NOW()),
('007', 'academic_learning', 1, 4, NOW()),
('008', 'teacher_attendance', 1, 2, NOW()),
('009', 'notifications', 1, 1, NOW()),
('013', 'api_support', 1, 2, NOW()),
('014', 'public_ids', 1, 2, NOW()),
('015', 'madrasahku_integration', 1, 12, NOW()),
('016', 'madrasahku_full_schema', 1, 35, NOW());

SET FOREIGN_KEY_CHECKS = 1;
-- ==========================================================================
-- AKHIR DUMP MASTER DATABASE MTs AL-HUDA GONDANG
-- Total Pengguna : 183 akun
-- Total Guru     : 26 guru riil
-- Total Siswa    : 154 santri riil
-- Total Mapel    : 25 mata pelajaran
-- ==========================================================================
