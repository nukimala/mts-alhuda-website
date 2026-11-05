-- ========================================================
-- MIGRASI 016: Skema Lengkap Aplikasi Madrasahku (MTs Al-Huda)
-- Database: shulkhim_mts_alhuda_website
-- Dibuat sesuai spesifikasi resmi skema mobile app Flutter
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. TABEL PENGGUNA & AUTENTIKASI (users)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'guru', 'siswa') NOT NULL DEFAULT 'siswa',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. TABEL DATA GURU (gurus)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gurus` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `nip` VARCHAR(30) NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL DEFAULT 'L',
  `no_hp` VARCHAR(20) NULL,
  `alamat` TEXT NULL,
  `foto_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_gurus_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. TABEL DATA KELAS (classes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_kelas` VARCHAR(20) NOT NULL UNIQUE,
  `tingkat` ENUM('7', '8', '9') NOT NULL,
  `wali_kelas_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_classes_wali` FOREIGN KEY (`wali_kelas_id`) REFERENCES `gurus` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. TABEL DATA SISWA (siswas)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `siswas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `kelas_id` BIGINT UNSIGNED NOT NULL,
  `nisn` VARCHAR(20) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL DEFAULT 'L',
  `alamat` TEXT NULL,
  `nama_wali` VARCHAR(100) NULL,
  `foto_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_siswas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_siswas_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. TABEL MATA PELAJARAN (subjects)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_mapel` VARCHAR(20) NOT NULL UNIQUE,
  `nama_mapel` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. TABEL JADWAL PELAJARAN (schedules)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kelas_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `guru_id` BIGINT UNSIGNED NOT NULL,
  `hari` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `ruangan` VARCHAR(50) DEFAULT 'Ruang Kelas',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_schedules_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_guru` FOREIGN KEY (`guru_id`) REFERENCES `gurus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. TABEL TITIK PRESENSI & QR KANTOR (school_locations)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `school_locations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_lokasi` VARCHAR(100) NOT NULL DEFAULT 'Kantor Guru MTs Al-Huda',
  `qr_token` VARCHAR(100) NOT NULL UNIQUE,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `radius_meter` INT UNSIGNED NOT NULL DEFAULT 10,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. TABEL PRESENSI GURU (teacher_attendances)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teacher_attendances` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guru_id` BIGINT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME NULL,
  `jam_pulang` TIME NULL,
  `lat_masuk` DECIMAL(10, 8) NULL,
  `long_masuk` DECIMAL(11, 8) NULL,
  `lat_pulang` DECIMAL(10, 8) NULL,
  `long_pulang` DECIMAL(11, 8) NULL,
  `status` ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
  `keterangan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_guru_tanggal` (`guru_id`, `tanggal`),
  CONSTRAINT `fk_attendance_guru` FOREIGN KEY (`guru_id`) REFERENCES `gurus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. TABEL MATERI PEMBELAJARAN (materials)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materials` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guru_id` BIGINT UNSIGNED NOT NULL,
  `kelas_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NULL,
  `file_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_materials_guru` FOREIGN KEY (`guru_id`) REFERENCES `gurus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_materials_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_materials_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. TABEL TUGAS PEMBELAJARAN (assignments)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guru_id` BIGINT UNSIGNED NOT NULL,
  `kelas_id` BIGINT UNSIGNED NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `instruksi` TEXT NOT NULL,
  `file_tugas_url` VARCHAR(255) NULL,
  `deadline` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_assignments_guru` FOREIGN KEY (`guru_id`) REFERENCES `gurus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. TABEL PENGUMPULAN TUGAS SISWA (assignment_submissions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `siswa_id` BIGINT UNSIGNED NOT NULL,
  `file_jawaban_url` VARCHAR(255) NULL,
  `catatan_siswa` TEXT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('submitted', 'late') NOT NULL DEFAULT 'submitted',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_submission` (`assignment_id`, `siswa_id`),
  CONSTRAINT `fk_asub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asub_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SEED / SINKRONISASI DATA DARI TABEL WEB KE MADRASAHKU DB
-- --------------------------------------------------------

-- 1. users: salin dari pengguna
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
SELECT 
    `id`, 
    COALESCE(NULLIF(`username`, ''), LOWER(REPLACE(SUBSTRING_INDEX(`email`, '@', 1), '.', '_')), CONCAT('user_', `id`)),
    `email`,
    `password_hash`,
    CASE 
        WHEN `role` IN ('admin', 'operator', 'kepala') THEN 'admin'
        WHEN `role` = 'guru' THEN 'guru'
        ELSE 'siswa'
    END,
    `is_active`,
    `created_at`,
    `updated_at`
FROM `pengguna`;

-- 2. gurus: salin dari guru + pengguna
INSERT IGNORE INTO `gurus` (`id`, `user_id`, `nip`, `nama_lengkap`, `jenis_kelamin`, `no_hp`, `alamat`, `foto_url`, `created_at`, `updated_at`)
SELECT 
    g.`id`,
    g.`user_id`,
    g.`nip`,
    p.`name`,
    COALESCE(g.`gender`, 'L'),
    p.`phone`,
    NULL,
    p.`avatar_path`,
    g.`created_at`,
    g.`updated_at`
FROM `guru` g
JOIN `pengguna` p ON p.`id` = g.`user_id`;

-- 3. classes: salin dari kelas
INSERT IGNORE INTO `classes` (`id`, `nama_kelas`, `tingkat`, `wali_kelas_id`, `created_at`, `updated_at`)
SELECT 
    k.`id`,
    k.`name`,
    CASE 
        WHEN k.`level` IN (7, 8, 9) THEN CAST(k.`level` AS CHAR)
        WHEN k.`name` LIKE 'VII%' OR k.`name` LIKE '7%' THEN '7'
        WHEN k.`name` LIKE 'VIII%' OR k.`name` LIKE '8%' THEN '8'
        WHEN k.`name` LIKE 'IX%' OR k.`name` LIKE '9%' THEN '9'
        ELSE '7'
    END,
    k.`homeroom_teacher_id`,
    k.`created_at`,
    k.`updated_at`
FROM `kelas` k;

-- 4. siswas: salin dari siswa + pengguna
INSERT IGNORE INTO `siswas` (`id`, `user_id`, `kelas_id`, `nisn`, `nama_lengkap`, `jenis_kelamin`, `alamat`, `nama_wali`, `foto_url`, `created_at`, `updated_at`)
SELECT 
    s.`id`,
    s.`user_id`,
    s.`class_id`,
    s.`nisn`,
    p.`name`,
    COALESCE(s.`gender`, 'L'),
    s.`address`,
    s.`guardian_name`,
    p.`avatar_path`,
    s.`created_at`,
    s.`updated_at`
FROM `siswa` s
JOIN `pengguna` p ON p.`id` = s.`user_id`;

-- 5. subjects: salin dari mata_pelajaran
INSERT IGNORE INTO `subjects` (`id`, `kode_mapel`, `nama_mapel`, `created_at`, `updated_at`)
SELECT 
    `id`,
    `code`,
    `name`,
    `created_at`,
    `updated_at`
FROM `mata_pelajaran`;

-- 6. schedules: salin dari jadwal + mata_pelajaran_kelas
INSERT IGNORE INTO `schedules` (`id`, `kelas_id`, `subject_id`, `guru_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`, `created_at`, `updated_at`)
SELECT 
    j.`id`,
    mpk.`class_id`,
    mpk.`subject_id`,
    j.`teacher_id`,
    CASE j.`day_of_week`
        WHEN 1 THEN 'Senin'
        WHEN 2 THEN 'Selasa'
        WHEN 3 THEN 'Rabu'
        WHEN 4 THEN 'Kamis'
        WHEN 5 THEN 'Jumat'
        WHEN 6 THEN 'Sabtu'
        ELSE 'Senin'
    END,
    j.`start_time`,
    j.`end_time`,
    COALESCE(j.`room`, 'Ruang Kelas'),
    j.`created_at`,
    j.`updated_at`
FROM `jadwal` j
JOIN `mata_pelajaran_kelas` mpk ON mpk.`id` = j.`class_subject_id`
WHERE j.`day_of_week` BETWEEN 1 AND 6;

-- 7. school_locations: koordinat GPS MTs Al-Huda (Pandean, Gondang, Nganjuk)
INSERT IGNORE INTO `school_locations` (`id`, `nama_lokasi`, `qr_token`, `latitude`, `longitude`, `radius_meter`, `is_active`)
VALUES (1, 'Kantor Guru MTs Al-Huda', 'MTS-ALHUDA-ABSENSI-2026-SECURE', -7.56234500, 111.89012300, 15, 1);

-- 8. teacher_attendances: salin dari presensi_guru
INSERT IGNORE INTO `teacher_attendances` (`id`, `guru_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `keterangan`, `created_at`, `updated_at`)
SELECT 
    `id`,
    `teacher_id`,
    `attendance_date`,
    TIME(`check_in_at`),
    TIME(`check_out_at`),
    CASE 
        WHEN `status` IN ('hadir', 'terlambat', 'izin', 'sakit', 'alpa') THEN `status`
        ELSE 'hadir'
    END,
    `note`,
    `created_at`,
    `updated_at`
FROM `presensi_guru`;

-- 9. materials: salin dari materi
INSERT IGNORE INTO `materials` (`id`, `guru_id`, `kelas_id`, `subject_id`, `judul`, `deskripsi`, `file_url`, `created_at`, `updated_at`)
SELECT 
    m.`id`,
    COALESCE(m.`created_by`, mpk.`teacher_id`, 1),
    mpk.`class_id`,
    mpk.`subject_id`,
    m.`title`,
    m.`description`,
    m.`file_path`,
    m.`created_at`,
    m.`updated_at`
FROM `materi` m
JOIN `mata_pelajaran_kelas` mpk ON mpk.`id` = m.`class_subject_id`;

-- 10. assignments: salin dari tugas
INSERT IGNORE INTO `assignments` (`id`, `guru_id`, `kelas_id`, `subject_id`, `judul`, `instruksi`, `file_tugas_url`, `deadline`, `created_at`, `updated_at`)
SELECT 
    t.`id`,
    COALESCE(t.`created_by`, mpk.`teacher_id`, 1),
    mpk.`class_id`,
    mpk.`subject_id`,
    t.`title`,
    COALESCE(t.`description`, 'Harap kerjakan tugas sesuai arahan.'),
    t.`attachment_path`,
    COALESCE(t.`due_at`, DATE_ADD(NOW(), INTERVAL 7 DAY)),
    t.`created_at`,
    t.`updated_at`
FROM `tugas` t
JOIN `mata_pelajaran_kelas` mpk ON mpk.`id` = t.`class_subject_id`;

-- 11. assignment_submissions: salin dari pengumpulan_tugas
INSERT IGNORE INTO `assignment_submissions` (`id`, `assignment_id`, `siswa_id`, `file_jawaban_url`, `catatan_siswa`, `submitted_at`, `status`, `created_at`, `updated_at`)
SELECT 
    pt.`id`,
    pt.`assignment_id`,
    pt.`student_id`,
    pt.`file_path`,
    pt.`note`,
    pt.`submitted_at`,
    'submitted',
    pt.`created_at`,
    pt.`updated_at`
FROM `pengumpulan_tugas` pt;

SET FOREIGN_KEY_CHECKS = 1;
