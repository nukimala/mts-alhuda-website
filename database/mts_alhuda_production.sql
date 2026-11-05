-- ============================================================================
-- DATABASE DUMP: MTs Al-Huda Gondang (Complete Production with Data)
-- Tanggal: 2026-10-29 14:11:58
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';

-- --------------------------------------------------------------------------
-- Struktur Tabel: `agenda`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `agenda`;
CREATE TABLE `agenda` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','published','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agendas_upcoming` (`status`,`start_at`),
  KEY `idx_agendas_range` (`start_at`,`end_at`),
  KEY `fk_agendas_author` (`author_id`),
  CONSTRAINT `fk_agendas_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_agendas_range` CHECK (((`end_at` is null) or (`end_at` >= `start_at`)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `agenda` (1 baris)
INSERT INTO `agenda` (`id`, `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `status`, `author_id`, `created_at`, `updated_at`) VALUES
('1', 'Contoh Agenda Kegiatan', 'Agenda contoh untuk menguji tampilan kalender dan daftar agenda.', 'Aula Madrasah', '2026-11-05 08:00:00', '2026-11-05 12:00:00', '0', 'published', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `agenda_tugas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `agenda_tugas`;
CREATE TABLE `agenda_tugas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('rendah','sedang','tinggi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sedang',
  `status` enum('belum','proses','selesai','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `completed_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_assignee` (`assigned_to`,`status`,`due_date`),
  KEY `idx_tasks_period` (`period_id`,`status`,`due_date`),
  KEY `idx_tasks_overdue` (`due_date`,`status`),
  KEY `fk_tasks_creator` (`created_by`),
  CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_period` FOREIGN KEY (`period_id`) REFERENCES `periode_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `batas_akses`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `batas_akses`;
CREATE TABLE `batas_akses` (
  `bucket` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_rate_limits_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `batas_akses` (2 baris)
INSERT INTO `batas_akses` (`bucket`, `attempts`, `expires_at`) VALUES
('api-login:127.0.0.1', '10', '2026-10-27 06:42:08'),
('api-refresh:127.0.0.1', '3', '2026-10-27 06:42:06');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `berita`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `berita`;
CREATE TABLE `berita` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `excerpt` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0',
  `author_id` bigint unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `slug_live` varchar(200) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`slug`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_public` (`public_id`),
  UNIQUE KEY `uq_posts_slug_live` (`slug_live`),
  KEY `idx_posts_slug` (`slug`),
  KEY `idx_posts_feed` (`status`,`published_at`),
  KEY `idx_posts_featured` (`is_featured`,`status`,`published_at`),
  KEY `idx_posts_author` (`author_id`,`created_at`),
  KEY `idx_posts_deleted` (`deleted_at`),
  KEY `idx_posts_category` (`category`,`status`,`published_at`),
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5274 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `berita` (4 baris)
INSERT INTO `berita` (`id`, `public_id`, `title`, `slug`, `category`, `excerpt`, `content`, `cover_path`, `meta_title`, `meta_description`, `status`, `is_featured`, `views`, `author_id`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', NULL, 'Contoh Berita Pertama', 'contoh-berita-pertama', 'umum', 'Ini paragraf pembuka berita contoh. Ganti dengan berita sungguhan lewat panel admin.', '<p>Isi berita contoh. Halaman ini dipakai untuk menguji tampilan daftar dan halaman detail.</p>', NULL, NULL, NULL, 'published', '0', '0', '1', '2026-09-10 08:00:00', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('2', NULL, 'Contoh Berita Kedua', 'contoh-berita-kedua', 'umum', 'Berita contoh kedua, lebih baru dari yang pertama. Dipakai untuk menguji urutan.', '<p>Isi berita contoh kedua.</p>', NULL, NULL, NULL, 'published', '0', '0', '1', '2026-10-01 09:30:00', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('3', NULL, 'Contoh Berita Ketiga', 'contoh-berita-ketiga', 'umum', 'Berita contoh ketiga, paling baru. Muncul di urutan pertama halaman berita.', '<p>Isi berita contoh ketiga.</p>', NULL, NULL, NULL, 'published', '0', '9', '1', '2026-10-20 14:15:00', '2026-10-23 05:48:12', '2026-10-29 19:33:38', NULL),
('4', NULL, 'Contoh Draf Belum Terbit', 'contoh-draf-belum-terbit', 'umum', 'Berita ini berstatus draf. Tidak boleh muncul di halaman publik.', '<p>Isi draf.</p>', NULL, NULL, NULL, 'draft', '0', '0', '1', NULL, '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `bukti`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `bukti`;
CREATE TABLE `bukti` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `checksum` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('draft','valid','expired','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_evidence_public` (`public_id`),
  UNIQUE KEY `uq_evidence_checksum` (`period_id`,`checksum`),
  KEY `idx_evidence_period` (`period_id`,`status`,`created_at`),
  KEY `idx_evidence_expiry` (`valid_until`,`status`),
  KEY `idx_evidence_uploader` (`uploaded_by`),
  KEY `idx_evidence_deleted` (`deleted_at`),
  CONSTRAINT `fk_evidence_period` FOREIGN KEY (`period_id`) REFERENCES `periode_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_evidence_source` CHECK (((`file_path` is not null) or (`external_url` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `bukti` (5 baris)
INSERT INTO `bukti` (`id`, `public_id`, `period_id`, `title`, `description`, `file_path`, `external_url`, `mime_type`, `file_size`, `checksum`, `valid_until`, `status`, `uploaded_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('34', '3784bb27-f9d6-4a8d-b8c1-d6e351c93cd1', '26', 'Kurikulum Operasional Madrasah (KOM) 2026/2027', 'Dokumen resmi kurikulum terpadu nasional dan kepesantrenan yang telah disahkan Kemenag.', 'uploads/akreditasi/KOM_MTs_Al-Huda_2026.pdf', NULL, 'application/pdf', '1540000', NULL, NULL, 'valid', '1', '2026-10-29 19:54:53', '2026-10-29 19:54:53', NULL),
('35', 'c4b14f6d-249d-4115-bff9-e99dd86c9c3f', '26', 'SK Pembagian Tugas Mengajar & Jadwal Guru', 'Surat Keputusan Kepala Madrasah tentang penetapan beban jam mengajar dan tugas tambahan.', 'uploads/akreditasi/SK_Beban_Mengajar_2026.pdf', NULL, 'application/pdf', '1540000', NULL, NULL, 'valid', '1', '2026-10-29 19:54:53', '2026-10-29 19:54:53', NULL),
('36', 'db519913-76f6-4abc-aaa1-edd1b66ec587', '26', 'Laporan Hasil Asesmen Diagnostik & Sumatif Santri', 'Hasil evaluasi capaian pembelajaran santri per kompetensi dasar semester ganjil.', 'uploads/akreditasi/Laporan_Asesmen_2026.pdf', NULL, 'application/pdf', '1540000', NULL, NULL, 'valid', '1', '2026-10-29 19:54:53', '2026-10-29 19:54:53', NULL),
('37', '6c6770d6-a62c-4618-96fb-211b5eb3e8d5', '26', 'Program Bimbingan Tahfidz & Sertifikasi Juz 30', 'Daftar hafalan santri, kartu mutaba\'ah harian, dan piagam kelulusan tahfidz.', 'uploads/akreditasi/Dokumentasi_Tahfidz.pdf', NULL, 'application/pdf', '1540000', NULL, NULL, 'valid', '1', '2026-10-29 19:54:53', '2026-10-29 19:54:53', NULL),
('38', 'ea1ce41e-1fcc-42bf-aac0-3d82060894a2', '26', 'Laporan Pemeliharaan Sarana Prasarana Madrasah', 'Buku inventaris barang, sanitasi ramah anak, dan kelayakan laboratorium komputer.', 'uploads/akreditasi/Inventaris_Sarpras_2026.pdf', NULL, 'application/pdf', '1540000', NULL, NULL, 'valid', '1', '2026-10-29 19:54:53', '2026-10-29 19:54:53', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `bukti_butir`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `bukti_butir`;
CREATE TABLE `bukti_butir` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint unsigned NOT NULL,
  `butir_id` bigint unsigned NOT NULL,
  `linked_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_evidence_butir_pair` (`evidence_id`,`butir_id`),
  KEY `idx_evidence_butir_lookup` (`butir_id`,`evidence_id`),
  KEY `fk_evidence_butir_linked_by` (`linked_by`),
  CONSTRAINT `fk_evidence_butir_butir` FOREIGN KEY (`butir_id`) REFERENCES `butir_akreditasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_butir_evidence` FOREIGN KEY (`evidence_id`) REFERENCES `bukti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_butir_linked_by` FOREIGN KEY (`linked_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `bukti_butir` (5 baris)
INSERT INTO `bukti_butir` (`id`, `evidence_id`, `butir_id`, `linked_by`, `created_at`) VALUES
('45', '34', '34', NULL, '2026-10-29 19:54:53'),
('46', '35', '59', NULL, '2026-10-29 19:54:53'),
('47', '36', '50', NULL, '2026-10-29 19:54:53'),
('48', '37', '42', NULL, '2026-10-29 19:54:53'),
('49', '38', '35', NULL, '2026-10-29 19:54:53');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `butir_akreditasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `butir_akreditasi`;
CREATE TABLE `butir_akreditasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `component_id` bigint unsigned NOT NULL,
  `number` smallint unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `butir_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('kosong','proses','lengkap','terverifikasi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kosong',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_butirs_number` (`component_id`,`number`),
  KEY `idx_acc_butirs_order` (`component_id`,`sort_order`,`number`),
  KEY `idx_acc_butirs_status` (`status`),
  CONSTRAINT `fk_acc_butirs_component` FOREIGN KEY (`component_id`) REFERENCES `komponen_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `butir_akreditasi` (33 baris)
INSERT INTO `butir_akreditasi` (`id`, `component_id`, `number`, `code`, `butir_text`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
('34', '15', '1', '1.1', 'Madrasah menyusun dan mengembangkan visi, misi, dan tujuan yang terukur dan berorientasi pada profil pelajar rahmatan lil alamin.', 'kosong', '1', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('35', '15', '2', '1.2', 'Kurikulum Operasional Madrasah (KOM) disusun dengan melibatkan seluruh pemangku kepentingan dan berkarakteristik islami.', 'kosong', '2', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('36', '15', '3', '1.3', 'Kepemimpinan kepala madrasah efektif dalam mengelola kurikulum, pendidik, sarana, dan pembiayaan madrasah.', 'kosong', '3', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('37', '15', '4', '1.4', 'Madrasah menciptakan iklim lingkungan belajar yang aman, nyaman, inklusif, dan bebas dari perundungan serta kekerasan.', 'kosong', '4', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('38', '15', '5', '1.5', 'Madrasah menjalin kemitraan aktif dengan orang tua/wali santri dan masyarakat sekitar dalam mendukung program pendidikan.', 'kosong', '5', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('39', '15', '6', '1.6', 'Pengelolaan sarana dan prasarana madrasah mendukung ketercapaian proses pembelajaran yang berkualitas dan ramah lingkungan.', 'kosong', '6', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('40', '15', '7', '1.7', 'Pengelolaan keuangan madrasah transparan, akuntabel, dan berorientasi pada peningkatan mutu pembelajaran santri.', 'kosong', '7', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('41', '15', '8', '1.8', 'Madrasah melaksanakan evaluasi diri madrasah (EDM) secara berkala sebagai dasar penyusunan Rencana Kerja Madrasah (RKM).', 'kosong', '8', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('42', '16', '1', '2.1', 'Santri menunjukkan kedisiplinan dan pengamalan nilai-nilai keislaman serta akhlak karimah dalam kehidupan sehari-hari.', 'kosong', '1', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('43', '16', '2', '2.2', 'Santri menunjukkan kemampuan literasi membaca, menulis, dan literasi Al-Qur\'an secara tartil.', 'kosong', '2', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('44', '16', '3', '2.3', 'Santri memiliki kemampuan numerasi dan berpikir kritis dalam memecahkan masalah kontekstual.', 'kosong', '3', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('45', '16', '4', '2.4', 'Santri menunjukkan kreativitas, inovasi, dan kemandirian melalui karya akademik maupun non-akademik.', 'kosong', '4', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('46', '16', '5', '2.5', 'Santri meraih prestasi di bidang akademik, sains, keagamaan, olahraga, atau seni di tingkat kecamatan hingga nasional.', 'kosong', '5', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('47', '16', '6', '2.6', 'Santri memiliki kebiasaan hidup bersih, sehat, bugar, dan peduli terhadap kelestarian lingkungan madrasah.', 'kosong', '6', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('48', '16', '7', '2.7', 'Santri menunjukkan keterampilan kolaborasi dan komunikasi yang efektif antarsantri maupun dengan guru.', 'kosong', '7', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('49', '16', '8', '2.8', 'Kepuasan pemangku kepentingan dan madrasah/sekolah lanjutan terhadap kompetensi dan akhlak lulusan MTs.', 'kosong', '8', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('50', '17', '1', '3.1', 'Guru merencanakan pembelajaran secara terstruktur dengan menyusun Modul Ajar / RPP yang memuat integrasi nilai keislaman.', 'kosong', '1', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('51', '17', '2', '3.2', 'Guru memfasilitasi pembelajaran yang aktif, berdiferensiasi, dan berpusat pada santri.', 'kosong', '2', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('52', '17', '3', '3.3', 'Pemanfaatan media pembelajaran berbasis digital dan teknologi informasi untuk mendukung efektivitas belajar.', 'kosong', '3', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('53', '17', '4', '3.4', 'Guru menciptakan interaksi pembelajaran yang komunikatif, menyenangkan, santun, dan saling menghargai.', 'kosong', '4', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('54', '17', '5', '3.5', 'Guru melaksanakan penilaian proses (formatif) dan sumatif secara autentik, objektif, dan transparan.', 'kosong', '5', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('55', '17', '6', '3.6', 'Guru memberikan umpan balik konstruktif dan bimbingan remedial serta pengayaan sesuai kebutuhan santri.', 'kosong', '6', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('56', '17', '7', '3.7', 'Pelaksanaan program bimbingan konseling dan pendampingan santri secara terprogram dan berkesinambungan.', 'kosong', '7', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('57', '17', '8', '3.8', 'Pelaksanaan kegiatan ekstrakurikuler kepramukaan, keagamaan (tahfidz/hadrah), dan pengembangan minat bakat.', 'kosong', '8', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('58', '17', '9', '3.9', 'Pelaksanaan pembiasaan ibadah harian (sholat dhuha, sholat dhuhur berjamaah, dan tadarrus bersama).', 'kosong', '9', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('59', '18', '1', '4.1', 'Guru memiliki kualifikasi akademik minimal S1/D4 dan mengajar sesuai dengan latar belakang keilmuan / sertifikasi.', 'kosong', '1', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('60', '18', '2', '4.2', 'Guru aktif mengikuti kegiatan Pengembangan Keprofesian Berkelanjutan (PKB), MGMP, pelatihan, atau seminar.', 'kosong', '2', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('61', '18', '3', '4.3', 'Tenaga kependidikan (TU, pustakawan, laboran) memiliki kualifikasi dan kompetensi sesuai bidang tugasnya.', 'kosong', '3', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('62', '18', '4', '4.4', 'Kepala madrasah melaksanakan supervisi akademik terhadap seluruh guru secara teratur dan menindaklanjuti hasilnya.', 'kosong', '4', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('63', '18', '5', '4.5', 'Madrasah memiliki sistem evaluasi kinerja guru dan tenaga kependidikan secara objektif.', 'kosong', '5', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('64', '18', '6', '4.6', 'Madrasah memberikan penghargaan (reward) dan pembinaan yang adil terhadap dedikasi dan prestasi GTK.', 'kosong', '6', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('65', '18', '7', '4.7', 'Guru menunjukkan integritas, keteladanan moral, kedisiplinan, dan dedikasi tinggi dalam mendidik santri.', 'kosong', '7', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('66', '18', '8', '4.8', 'Penguasaan guru terhadap teknologi informasi dalam manajemen administrasi dan pengajaran digital.', 'kosong', '8', '2026-10-29 10:02:56', '2026-10-29 10:02:56');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `dokumen_ppdb`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `dokumen_ppdb`;
CREATE TABLE `dokumen_ppdb` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `download_count` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ppdb_docs_listing` (`is_active`,`category`,`sort_order`),
  KEY `idx_ppdb_docs_downloads` (`download_count`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `dokumen_ppdb` (4 baris)
INSERT INTO `dokumen_ppdb` (`id`, `title`, `description`, `category`, `file_path`, `file_type`, `file_size`, `sort_order`, `is_active`, `download_count`, `created_at`, `updated_at`) VALUES
('1', 'Formulir Pendaftaran Siswa Baru 2026/2027', 'Blangko resmi pendaftaran calon santri baru MTs Al-Huda Gondang.', 'Formulir', 'uploads/ppdb/formulir-ppdb-2026.pdf', 'PDF', '245000', '1', '1', '142', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('2', 'Brosur Informasi & Alur PPDB MTs Al-Huda', 'Panduan alur, syarat berkas, beasiswa tahfidz, dan jadwal seleksi.', 'Brosur', 'uploads/ppdb/brosur-ppdb-2026.pdf', 'PDF', '1250000', '2', '1', '389', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('3', 'Surat Pernyataan Kesanggupan Tata Tertib', 'Pernyataan kesanggupan santri & wali menaati tata tertib madrasah & pesantren.', 'Surat', 'uploads/ppdb/surat-tata-tertib.docx', 'DOCX', '84000', '3', '1', '98', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('4', 'Pedoman Tes Penempatan & Wawancara Peminatan', 'Informasi materi uji baca Al-Qur\'an dan tes akademik pemetaan kelas.', 'Panduan', 'uploads/ppdb/pedoman-tes-penempatan.pdf', 'PDF', '320000', '4', '1', '76', '2026-10-29 19:54:25', '2026-10-29 19:54:25');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `dokumen_utama`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `dokumen_utama`;
CREATE TABLE `dokumen_utama` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `doc_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `status` enum('belum','proses','selesai','revisi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `sort_order` int NOT NULL DEFAULT '0',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_main_documents_period` (`period_id`,`doc_type`,`sort_order`),
  KEY `idx_main_documents_status` (`period_id`,`status`),
  KEY `fk_main_documents_uploader` (`uploaded_by`),
  CONSTRAINT `fk_main_documents_period` FOREIGN KEY (`period_id`) REFERENCES `periode_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_main_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `entri_dka`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `entri_dka`;
CREATE TABLE `entri_dka` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `status` enum('draft','final','revisi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `draft_flag` tinyint GENERATED ALWAYS AS (if((`status` = _utf8mb4'draft'),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dka_version` (`butir_id`,`version`),
  UNIQUE KEY `uq_dka_one_draft` (`butir_id`,`draft_flag`),
  KEY `idx_dka_butir_status` (`butir_id`,`status`,`version`),
  KEY `idx_dka_editor` (`updated_by`),
  CONSTRAINT `fk_dka_butir` FOREIGN KEY (`butir_id`) REFERENCES `butir_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_dka_editor` FOREIGN KEY (`updated_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_dka_length` CHECK ((char_length(`content`) <= 1000))
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `entri_dka` (3 baris)
INSERT INTO `entri_dka` (`id`, `butir_id`, `content`, `version`, `status`, `updated_by`, `created_at`, `updated_at`) VALUES
('56', '34', 'MTs Al-Huda Gondang menumbuhkan iklim belajar yang religius dan inklusif. Pembiasaan sholat dhuha, sholat dhuhur berjamaah, serta tadarus Al-Qur\'an juz 30 dilaksanakan setiap hari sebelum jam pembelajaran dimulai. Terbukti menghasilkan penurunan signifikan kasus pelanggaran disiplin dan peningkatan budi pekerti santri.', '1', 'final', '1', '2026-10-29 19:55:10', '2026-10-29 19:55:10'),
('57', '35', 'Pendidik merancang modul ajar berdiferensiasi yang memfasilitasi keragaman gaya belajar siswa. Penggunaan sarana laboratorium komputer dan proyektor digital pada pembelajaran IPA dan Matematika meningkatkan ketertarikan dan partisipasi aktif santri hingga 92%.', '1', 'final', '1', '2026-10-29 19:55:10', '2026-10-29 19:55:10'),
('58', '36', 'Evaluasi hasil belajar santri dilaksanakan secara komprehensif, mencakup aspek kognitif, afektif, dan psikomotorik. Guru menyusun instrumen penilaian autentik, menyelenggarakan klinik remedial, serta memberikan pengayaan intensif bagi persiapan kompetisi sains madrasah.', '1', 'final', '1', '2026-10-29 19:55:10', '2026-10-29 19:55:10');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `fasilitas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `fasilitas`;
CREATE TABLE `fasilitas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_facilities_listing` (`is_active`,`sort_order`),
  KEY `idx_facilities_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `fasilitas` (6 baris)
INSERT INTO `fasilitas` (`id`, `name`, `description`, `image_path`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Ruang Kelas', 'Kelas yang bersih dan terang untuk kegiatan belajar mengajar.', NULL, 'classroom', '1', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('2', 'Perpustakaan', 'Koleksi buku pelajaran, agama, dan umum untuk menumbuhkan minat baca.', NULL, 'book', '2', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('3', 'Musholla', 'Pusat kegiatan ibadah, sholat berjamaah, dan pembinaan spiritual.', NULL, 'mosque', '3', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('4', 'Lapangan', 'Area olahraga dan kegiatan ekstrakurikuler.', NULL, 'field', '4', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('5', 'Laboratorium', 'Ruang praktik untuk mata pelajaran sains.', NULL, 'lab', '5', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('6', 'Ruang Guru', 'Ruang kerja dan koordinasi para guru.', NULL, 'office', '6', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `galeri`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `galeri`;
CREATE TABLE `galeri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `album` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_galleries_listing` (`is_active`,`album`,`sort_order`),
  KEY `idx_galleries_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `galeri` (6 baris)
INSERT INTO `galeri` (`id`, `album`, `title`, `image_path`, `caption`, `sort_order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Kegiatan Santri', 'Peringatan Hari Santri Nasional', 'assets/images/Grup Sholawat  MTS Al-Huda.jpg', 'Rangkaian apel akbar dan pembacaan sholawat nariyah santri MTs Al-Huda.', '1', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('2', 'Akademik & Literasi', 'Budaya Literasi di Perpustakaan', 'assets/images/Belajar di Perpustakaan.jpg', 'Aktivitas santri membaca referensi dan bedah buku di ruang perpustakaan madrasah.', '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('3', 'Ekstrakurikuler', 'Upacara Pembukaan Jambore Pramuka', 'assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg', 'Regu Pramuka Penggalang MTs Al-Huda berpartisipasi pada Jambore Cabang Nganjuk.', '3', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4', 'Kesehatan & Olahraga', 'Senam Pagi Kebugaran Santri', 'assets/images/Senam Bersama.jpg', 'Kegiatan senam sehat bersama dewan guru dan santri setiap hari Jumat pagi.', '4', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('5', 'Hubungan Masyarakat', 'Pertemuan Wali Santri & Komite', 'assets/images/Pertemuan Wali Santri.jpg', 'Rapat koordinasi dan pemaparan program peningkatan mutu pembelajaran bersama wali santri.', '5', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('6', 'Ekstrakurikuler', 'Penampilan Korps Drumband Al-Huda', 'assets/images/Drumband.jpg', 'Atraksi drumband santri dalam memeriahkan pawai taaruf dan peringatan hari besar islam.', '6', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `guru`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `guru`;
CREATE TABLE `guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nuptk` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `employment_status` enum('pns','pppk','honorer','gtty') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'honorer',
  `subject_specialty` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teachers_user` (`user_id`),
  UNIQUE KEY `uq_teachers_nip` (`nip`),
  UNIQUE KEY `uq_teachers_nuptk` (`nuptk`),
  UNIQUE KEY `uq_teachers_public` (`public_id`),
  KEY `idx_teachers_status` (`employment_status`),
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `guru` (13 baris)
INSERT INTO `guru` (`id`, `public_id`, `user_id`, `nip`, `nuptk`, `gender`, `employment_status`, `subject_specialty`, `joined_at`, `created_at`, `updated_at`) VALUES
('5', NULL, '2', '198501012010011001', NULL, 'L', 'pns', 'Matematika', '2010-01-01', '2026-10-23 06:03:01', '2026-10-23 06:03:01'),
('37', '6208a48f-b310-43f4-9bc1-a0bd0b873ee3', '4866', '196805121994031002', '3456789012345678', 'L', 'gtty', 'Akidah Akhlak', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('38', 'fb8739e2-39d2-4322-8f78-2f1c5f8c2395', '4867', '197508152005011003', '4567890123456789', 'L', 'gtty', 'Fiqih & Ke-NU-an', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('39', '22fa9eb4-341c-4ceb-a609-b031b49853bf', '4868', '198203202008012005', '5678901234567890', 'P', 'gtty', 'Bahasa Indonesia', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('40', '1265f846-b78b-478e-9e25-23ceffa177ab', '4869', '198011102006041004', '6789012345678901', 'L', 'gtty', 'Matematika', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('41', '0ceceb2f-7140-468a-9da9-e61cee6d0576', '4870', '198507252009022003', '7890123456789012', 'P', 'gtty', 'IPA Terpadu', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('42', '1603ac21-26bd-4096-99bd-5425b1318cab', '4871', '198812152011011002', '8901234567890123', 'L', 'gtty', 'Bahasa Inggris', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('43', '5598d8d4-459a-4c14-b609-bc2441761f3b', '4872', '198304052007011006', '9012345678901234', 'L', 'gtty', 'Al-Qur\'an Hadits & Tahfidz', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('44', '9a64ab32-1650-46c8-ae05-95ce8c1c17a6', '4873', '198609182010011005', '0123456789012345', 'L', 'gtty', 'IPS & SKI', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('45', '038af2e9-ee7a-4825-baf7-bdd01d7ec6d1', '4874', '199002142015022001', '1234567890123456', 'P', 'gtty', 'Seni Budaya & Prakarya', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('46', '59516b65-7b74-4e25-9744-bbc3e6d659ed', '4875', '199208082017011003', '2345678901234567', 'L', 'gtty', 'Pendidikan Jasmani & Olahraga', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('47', '33a0b897-aa64-48a8-80cc-08652e16516b', '4876', '199311202019032004', '3456789012345670', 'P', 'gtty', 'Informatika & Komputer', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('48', 'f869bef7-61c1-4a6f-8083-69487bb9c923', '4877', '198706102012011003', '4567890123456780', 'L', 'gtty', 'Bahasa Arab', '2018-07-16', '2026-10-29 19:54:25', '2026-10-29 19:54:25');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `halaman`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `halaman`;
CREATE TABLE `halaman` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `template` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `slug_live` varchar(120) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`slug`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug_live` (`slug_live`),
  KEY `idx_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`,`sort_order`),
  KEY `idx_pages_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `halaman` (2 baris)
INSERT INTO `halaman` (`id`, `slug`, `title`, `content`, `template`, `meta_title`, `meta_description`, `status`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'profil', 'Profil Madrasah', '<p>Halaman profil contoh. Ganti dengan profil madrasah yang sebenarnya.</p>', 'default', NULL, NULL, 'published', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL),
('2', 'akademik', 'Program Akademik', '<p>Halaman akademik contoh.</p>', 'default', NULL, NULL, 'published', '2', '2026-10-23 05:48:12', '2026-10-23 05:48:12', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `hasil_akreditasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `hasil_akreditasi`;
CREATE TABLE `hasil_akreditasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `decided_at` datetime DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_results_period` (`period_id`),
  KEY `idx_acc_results_grade` (`grade`,`decided_at`),
  KEY `fk_acc_results_by` (`decided_by`),
  CONSTRAINT `fk_acc_results_by` FOREIGN KEY (`decided_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_acc_results_period` FOREIGN KEY (`period_id`) REFERENCES `periode_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_acc_results_score` CHECK (((`score` is null) or ((`score` >= 0) and (`score` <= 100))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `indikator_akreditasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `indikator_akreditasi`;
CREATE TABLE `indikator_akreditasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `number` smallint unsigned NOT NULL,
  `indicator_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_indicators_number` (`butir_id`,`number`),
  KEY `idx_acc_indicators_order` (`butir_id`,`sort_order`,`number`),
  CONSTRAINT `fk_acc_indicators_butir` FOREIGN KEY (`butir_id`) REFERENCES `butir_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `jadwal`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `jadwal`;
CREATE TABLE `jadwal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedules_teacher_slot` (`teacher_id`,`academic_year_id`,`day_of_week`,`start_time`),
  KEY `idx_schedules_class_day` (`class_subject_id`,`day_of_week`,`start_time`),
  KEY `idx_schedules_year_day` (`academic_year_id`,`day_of_week`,`start_time`),
  CONSTRAINT `fk_schedules_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_year` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademik` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_schedules_day` CHECK ((`day_of_week` between 1 and 7)),
  CONSTRAINT `chk_schedules_time` CHECK ((`end_time` > `start_time`))
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `jadwal` (22 baris)
INSERT INTO `jadwal` (`id`, `class_subject_id`, `teacher_id`, `academic_year_id`, `day_of_week`, `start_time`, `end_time`, `room`, `is_active`, `created_at`, `updated_at`) VALUES
('47', '27', '43', '1', '1', '07:00:00', '08:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('48', '27', '39', '1', '1', '08:20:00', '09:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('49', '27', '40', '1', '1', '10:00:00', '11:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('50', '27', '38', '1', '1', '11:20:00', '12:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('51', '7', '41', '1', '2', '07:00:00', '08:20:00', 'Lab IPA', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('52', '27', '42', '1', '2', '08:20:00', '09:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('53', '30', '44', '1', '2', '10:00:00', '11:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('54', '27', '48', '1', '2', '11:20:00', '12:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('55', '27', '40', '1', '3', '07:00:00', '08:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('56', '27', '47', '1', '3', '08:20:00', '09:40:00', 'Lab Komputer', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('57', '27', '37', '1', '3', '10:00:00', '11:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('58', '27', '46', '1', '3', '11:20:00', '12:40:00', 'Lapangan', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('59', '27', '39', '1', '4', '07:00:00', '08:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('60', '7', '41', '1', '4', '08:20:00', '09:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('61', '27', '38', '1', '4', '10:00:00', '11:20:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('62', '27', '45', '1', '4', '11:20:00', '12:40:00', 'Ruang Kesenian', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('63', '27', '43', '1', '5', '07:00:00', '08:20:00', 'Musholla Al-Huda', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('64', '27', '37', '1', '5', '08:20:00', '09:40:00', 'Musholla Al-Huda', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('65', '27', '48', '1', '5', '10:00:00', '11:00:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('66', '27', '46', '1', '6', '07:00:00', '08:20:00', 'Lapangan Olahraga', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('67', '30', '44', '1', '6', '08:20:00', '09:40:00', 'Ruang Kelas 7A', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('68', '27', '46', '1', '6', '10:00:00', '11:20:00', 'Aula Madrasah', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `kelas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `homeroom_teacher_id` bigint unsigned DEFAULT NULL,
  `room` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` smallint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_classes_year_name` (`academic_year_id`,`name`),
  KEY `idx_classes_level` (`academic_year_id`,`level`,`sort_order`),
  KEY `idx_classes_homeroom` (`homeroom_teacher_id`),
  CONSTRAINT `fk_classes_homeroom` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_classes_year` FOREIGN KEY (`academic_year_id`) REFERENCES `tahun_akademik` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_classes_level` CHECK ((`level` between 1 and 12))
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `kelas` (3 baris)
INSERT INTO `kelas` (`id`, `academic_year_id`, `name`, `level`, `homeroom_teacher_id`, `room`, `capacity`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
('1', '1', '7A', '7', NULL, NULL, NULL, '1', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('2', '1', '8A', '8', NULL, NULL, NULL, '2', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('3', '1', '9A', '9', NULL, NULL, NULL, '3', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `komponen_akreditasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `komponen_akreditasi`;
CREATE TABLE `komponen_akreditasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` smallint unsigned NOT NULL DEFAULT '0',
  `weight` decimal(6,2) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_components_code` (`period_id`,`code`),
  KEY `idx_acc_components_order` (`period_id`,`sort_order`,`number`),
  CONSTRAINT `fk_acc_components_period` FOREIGN KEY (`period_id`) REFERENCES `periode_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `komponen_akreditasi` (5 baris)
INSERT INTO `komponen_akreditasi` (`id`, `period_id`, `code`, `name`, `number`, `weight`, `sort_order`, `created_at`, `updated_at`) VALUES
('1', '1', 'A', 'Komponen Contoh A', '1', NULL, '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('15', '26', 'K1', 'Karakteristik Satuan Pendidikan', '1', '25.00', '1', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('16', '26', 'K2', 'Mutu Lulusan', '2', '25.00', '2', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('17', '26', 'K3', 'Proses Pembelajaran', '3', '25.00', '3', '2026-10-29 10:02:56', '2026-10-29 10:02:56'),
('18', '26', 'K4', 'Mutu Guru & Tenaga Kependidikan', '4', '25.00', '4', '2026-10-29 10:02:56', '2026-10-29 10:02:56');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `log_aktivitas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`,`created_at`),
  KEY `idx_activity_logs_entity` (`entity`,`entity_id`,`created_at`),
  KEY `idx_activity_logs_action` (`action`,`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `log_aktivitas` (209 baris)
INSERT INTO `log_aktivitas` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
('1', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '::1', '', '2026-10-23 05:51:24'),
('2', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '::1', '', '2026-10-23 05:51:24'),
('3', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '::1', '', '2026-10-23 05:51:24'),
('4', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '::1', '', '2026-10-23 05:51:25'),
('5', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:25'),
('6', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:25'),
('7', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:25'),
('8', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:26'),
('9', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:26'),
('10', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:26'),
('11', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:26'),
('12', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:26'),
('13', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:27'),
('14', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 05:51:27'),
('15', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:03:20'),
('16', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:03:21'),
('17', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:04:06'),
('18', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:04:07'),
('19', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:04:16'),
('20', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Data)', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:04:17'),
('21', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '::1', '', '2026-10-23 06:05:05'),
('22', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '::1', '', '2026-10-23 06:05:06'),
('23', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '::1', '', '2026-10-23 06:05:06'),
('24', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '::1', '', '2026-10-23 06:05:06'),
('25', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '::1', '', '2026-10-23 06:05:06'),
('26', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '::1', '', '2026-10-23 06:05:06'),
('27', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('28', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('29', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('30', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('31', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('32', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:07'),
('33', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:08'),
('34', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:08'),
('35', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:08'),
('36', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:05:08'),
('37', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '::1', '', '2026-10-23 06:07:50'),
('38', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '::1', '', '2026-10-23 06:07:50'),
('39', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '::1', '', '2026-10-23 06:07:51'),
('40', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '::1', '', '2026-10-23 06:07:51'),
('41', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '::1', '', '2026-10-23 06:07:51'),
('42', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '::1', '', '2026-10-23 06:07:51'),
('43', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('44', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('45', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('46', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('47', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('48', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:52'),
('49', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:53'),
('50', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:53');
INSERT INTO `log_aktivitas` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
('51', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:53'),
('52', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '::1', '', '2026-10-23 06:07:53'),
('53', '1', 'api.login.failed', 'users', '1', 'Login API gagal — percobaan ke-1 dari 5', NULL, '127.0.0.1', '', '2026-10-26 21:38:16'),
('54', '1', 'api.login.success', 'users', '1', 'Masuk lewat API (android: Uji Otomatis)', NULL, '127.0.0.1', '', '2026-10-26 21:38:17'),
('55', '1', 'api.token.refresh', 'users', '1', 'Refresh token dipakai, pasangan token berotasi', NULL, '127.0.0.1', '', '2026-10-26 21:38:17'),
('56', '1', 'api.logout', 'users', '1', 'Keluar dari API — token dicabut', NULL, '127.0.0.1', '', '2026-10-26 21:38:17'),
('57', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-26 21:38:19'),
('58', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-26 21:38:19'),
('59', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:20'),
('60', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:21'),
('61', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:21'),
('62', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:21'),
('63', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:22'),
('64', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:22'),
('65', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:22'),
('66', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:23'),
('67', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:23'),
('68', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-26 21:38:23'),
('69', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-27 04:26:27'),
('70', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:27:08'),
('71', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:27:23'),
('72', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:27:53'),
('73', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:28:09'),
('74', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:29:48'),
('75', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'curl/8.13.0', '2026-10-27 04:30:46'),
('76', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '127.0.0.1', '', '2026-10-27 04:32:09'),
('77', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '127.0.0.1', '', '2026-10-27 04:32:09'),
('78', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '127.0.0.1', '', '2026-10-27 04:32:09'),
('79', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '127.0.0.1', '', '2026-10-27 04:32:09'),
('80', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 04:32:10'),
('81', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 04:32:10'),
('82', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('83', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('84', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('85', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('86', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('87', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('88', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:11'),
('89', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:12'),
('90', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:12'),
('91', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 04:32:12'),
('92', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:41:17'),
('93', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:41:17'),
('96', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:45:52'),
('97', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:45:53'),
('105', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:48:10'),
('106', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:48:10'),
('114', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '127.0.0.1', '', '2026-10-27 05:51:19'),
('115', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '127.0.0.1', '', '2026-10-27 05:51:19'),
('116', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '127.0.0.1', '', '2026-10-27 05:51:19');
INSERT INTO `log_aktivitas` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
('117', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '127.0.0.1', '', '2026-10-27 05:51:19'),
('118', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 05:51:20'),
('119', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 05:51:20'),
('120', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('121', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('122', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('123', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('124', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('125', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:21'),
('126', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:22'),
('127', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:22'),
('128', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:22'),
('129', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:51:22'),
('130', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 05:52:18'),
('131', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 05:52:57'),
('132', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:55:53'),
('133', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:55:54'),
('141', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 05:56:17'),
('142', NULL, 'login.success', 'users', '2439', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 05:56:50'),
('143', NULL, 'login.success', 'users', '2440', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 05:57:19'),
('144', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '127.0.0.1', '', '2026-10-27 05:57:50'),
('145', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '127.0.0.1', '', '2026-10-27 05:57:50'),
('146', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '127.0.0.1', '', '2026-10-27 05:57:50'),
('147', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '127.0.0.1', '', '2026-10-27 05:57:50'),
('148', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 05:57:51'),
('149', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 05:57:51'),
('150', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('151', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('152', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('153', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('154', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('155', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('156', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('157', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:52'),
('158', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:53'),
('159', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 05:57:53'),
('160', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:57:53'),
('161', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 05:57:53'),
('169', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', '', '2026-10-27 06:30:46'),
('170', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:21'),
('171', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:22'),
('172', '3', 'user.created', 'users', '2845', 'Membuat akun: Uji Guru Satu (Guru)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:22'),
('173', NULL, 'login.success', 'users', '2845', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('174', NULL, 'login.failed', 'users', '2845', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('175', '3', 'user.created', 'users', '2846', 'Membuat akun: Uji Operator Dua (Operator Madrasah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('176', '3', 'user.updated', 'users', '2846', 'Mengubah akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('177', '3', 'user.updated', 'users', '2845', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('178', '3', 'user.updated', 'users', '2845', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:23'),
('179', '3', 'user.deactivated', 'users', '2846', 'Menonaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:24'),
('180', '3', 'user.activated', 'users', '2846', 'Mengaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:24');
INSERT INTO `log_aktivitas` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
('181', '3', 'user.password_reset', 'users', '2846', 'Menyetel ulang sandi akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:24'),
('182', NULL, 'login.success', 'users', '2846', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:25'),
('183', '3', 'user.unlocked', 'users', '2846', 'Membuka kunci akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:25'),
('184', NULL, 'login.success', 'users', '2847', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:25'),
('185', '3', 'user.deleted', 'users', '2846', 'Menghapus akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:34:25'),
('186', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:25'),
('187', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:25'),
('188', '3', 'user.created', 'users', '2848', 'Membuat akun: Uji Guru Satu (Guru)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:26'),
('189', NULL, 'login.success', 'users', '2848', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:26'),
('190', NULL, 'login.failed', 'users', '2848', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:26'),
('191', '3', 'user.created', 'users', '2849', 'Membuat akun: Uji Operator Dua (Operator Madrasah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:27'),
('192', '3', 'user.updated', 'users', '2849', 'Mengubah akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:27'),
('193', '3', 'user.updated', 'users', '2848', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:27'),
('194', '3', 'user.updated', 'users', '2848', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:27'),
('195', '3', 'user.deactivated', 'users', '2849', 'Menonaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:28'),
('196', '3', 'user.activated', 'users', '2849', 'Mengaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:28'),
('197', '3', 'user.password_reset', 'users', '2849', 'Menyetel ulang sandi akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:28'),
('198', NULL, 'login.success', 'users', '2849', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:28'),
('199', '3', 'user.unlocked', 'users', '2849', 'Membuka kunci akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:28'),
('200', NULL, 'login.success', 'users', '2850', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:29'),
('201', '3', 'user.deleted', 'users', '2849', 'Menghapus akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:29'),
('202', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:54'),
('203', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:54'),
('204', '3', 'user.created', 'users', '2851', 'Membuat akun: Uji Guru Satu (Guru)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('205', NULL, 'login.success', 'users', '2851', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('206', NULL, 'login.failed', 'users', '2851', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('207', '3', 'user.created', 'users', '2852', 'Membuat akun: Uji Operator Dua (Operator Madrasah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('208', '3', 'user.updated', 'users', '2852', 'Mengubah akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('209', '3', 'user.updated', 'users', '2851', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:55'),
('210', '3', 'user.updated', 'users', '2851', 'Mengubah akun: Uji Guru Satu', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:56'),
('211', '3', 'user.deactivated', 'users', '2852', 'Menonaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:56'),
('212', '3', 'user.activated', 'users', '2852', 'Mengaktifkan akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:56'),
('213', '3', 'user.password_reset', 'users', '2852', 'Menyetel ulang sandi akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:57'),
('214', NULL, 'login.success', 'users', '2852', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:57'),
('215', '3', 'user.unlocked', 'users', '2852', 'Membuka kunci akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:57'),
('216', NULL, 'login.success', 'users', '2853', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:57'),
('217', '3', 'user.deleted', 'users', '2852', 'Menghapus akun: Uji Operator Dua (diubah)', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:36:57'),
('218', '3', 'api.login.failed', 'users', '3', 'Login API gagal — percobaan ke-1 dari 5', NULL, '127.0.0.1', '', '2026-10-27 06:37:06'),
('219', '3', 'api.login.success', 'users', '3', 'Masuk lewat API (android: Uji Otomatis)', NULL, '127.0.0.1', '', '2026-10-27 06:37:06'),
('220', '3', 'api.token.refresh', 'users', '3', 'Refresh token dipakai, pasangan token berotasi', NULL, '127.0.0.1', '', '2026-10-27 06:37:06'),
('221', '3', 'api.logout', 'users', '3', 'Keluar dari API — token dicabut', NULL, '127.0.0.1', '', '2026-10-27 06:37:07'),
('222', '2', 'api.login.success', 'users', '2', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 06:37:07'),
('223', '414', 'api.login.success', 'users', '414', 'Masuk lewat API (android: Uji Peran)', NULL, '127.0.0.1', '', '2026-10-27 06:37:07'),
('224', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:08'),
('225', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:08'),
('226', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:08'),
('227', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('228', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('229', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('230', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09');
INSERT INTO `log_aktivitas` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
('231', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('232', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('233', NULL, 'api.login.failed', 'users', NULL, 'Login API gagal — akun tidak dikenal', NULL, '127.0.0.1', '', '2026-10-27 06:37:09'),
('234', NULL, 'login.failed', 'users', '3', 'Percobaan login gagal', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:37:10'),
('235', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '127.0.0.1', 'MTS-Admin-Test/1.0', '2026-10-27 06:37:10'),
('243', '3', 'login.success', 'users', '3', 'Berhasil masuk panel admin', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 10:39:17'),
('244', '3', 'login.success', 'pengguna', '3', 'Berhasil masuk panel admin', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', '2026-10-29 14:35:06'),
('245', '2', 'attendance.check_in.daily', 'presensi_guru', '56', 'Presensi harian mandiri guru: terlambat @ 2026-10-29 14:54:06', NULL, '127.0.0.1', '', '2026-10-29 14:54:06'),
('246', '2', 'attendance.check_out', 'presensi_guru', '56', 'Presensi pulang mandiri guru @ 2026-10-29 14:54:06', NULL, '127.0.0.1', '', '2026-10-29 14:54:06');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `mata_pelajaran`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `mata_pelajaran`;
CREATE TABLE `mata_pelajaran` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` enum('agama','umum','muatan_lokal','ekstrakurikuler') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subjects_code` (`code`),
  KEY `idx_subjects_group` (`group_name`,`sort_order`),
  KEY `idx_subjects_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `mata_pelajaran` (13 baris)
INSERT INTO `mata_pelajaran` (`id`, `code`, `name`, `group_name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
('1', 'QH', 'Al-Qur\'an Hadis', 'agama', '1', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('2', 'AA', 'Akidah Akhlak', 'agama', '2', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('3', 'FQ', 'Fikih', 'agama', '3', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('4', 'SKI', 'Sejarah Kebudayaan Islam', 'agama', '4', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('5', 'BIN', 'Bahasa Indonesia', 'umum', '10', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('6', 'BIG', 'Bahasa Inggris', 'umum', '11', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('7', 'MTK', 'Matematika', 'umum', '12', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('8', 'IPA', 'Ilmu Pengetahuan Alam', 'umum', '13', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('9', 'IPS', 'Ilmu Pengetahuan Sosial', 'umum', '14', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('10', 'PKN', 'Pendidikan Pancasila', 'umum', '15', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('11', 'SBK', 'Seni Budaya', 'umum', '16', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('12', 'PJOK', 'Pendidikan Jasmani', 'umum', '17', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('13', 'BTQ', 'Baca Tulis Al-Qur\'an', 'muatan_lokal', '20', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `mata_pelajaran_kelas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `mata_pelajaran_kelas`;
CREATE TABLE `mata_pelajaran_kelas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `weekly_hours` tinyint unsigned NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_subjects_pair` (`class_id`,`subject_id`),
  KEY `idx_class_subjects_teacher` (`teacher_id`),
  KEY `idx_class_subjects_subject` (`subject_id`),
  CONSTRAINT `fk_class_subjects_class` FOREIGN KEY (`class_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_class_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_class_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `mata_pelajaran_kelas` (39 baris)
INSERT INTO `mata_pelajaran_kelas` (`id`, `class_id`, `subject_id`, `teacher_id`, `weekly_hours`, `is_active`, `created_at`, `updated_at`) VALUES
('5', '1', '7', '5', '4', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01'),
('6', '1', '5', NULL, '4', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01'),
('7', '1', '8', NULL, '4', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01'),
('27', '1', '1', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('28', '1', '2', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('29', '1', '3', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('30', '1', '4', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('31', '1', '6', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('32', '1', '9', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('33', '1', '10', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('34', '1', '11', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('35', '1', '12', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('36', '1', '13', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('37', '2', '1', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('38', '2', '2', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('39', '2', '3', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('40', '2', '4', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('41', '2', '5', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('42', '2', '6', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('43', '2', '7', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('44', '2', '8', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('45', '2', '9', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('46', '2', '10', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('47', '2', '11', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('48', '2', '12', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('49', '2', '13', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('50', '3', '1', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('51', '3', '2', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('52', '3', '3', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('53', '3', '4', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('54', '3', '5', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('55', '3', '6', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('56', '3', '7', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('57', '3', '8', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('58', '3', '9', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('59', '3', '10', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('60', '3', '11', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('61', '3', '12', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('62', '3', '13', NULL, '2', '1', '2026-10-29 19:54:25', '2026-10-29 19:54:25');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `materi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `materi`;
CREATE TABLE `materi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_materials_class` (`class_subject_id`,`is_published`,`created_at`),
  KEY `idx_materials_author` (`created_by`),
  CONSTRAINT `fk_materials_author` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_materials_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_materials_source` CHECK (((`file_path` is not null) or (`external_url` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `materi` (1 baris)
INSERT INTO `materi` (`id`, `class_subject_id`, `title`, `description`, `file_path`, `external_url`, `is_published`, `published_at`, `created_by`, `created_at`, `updated_at`) VALUES
('1', '5', 'Materi Contoh: Bilangan Bulat', 'Ringkasan sifat operasi bilangan bulat beserta contoh soal.', NULL, 'https://contoh.invalid/materi/bilangan-bulat.pdf', '1', '2026-10-05 08:00:00', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `media`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `checksum` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folder` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_path` (`path`),
  KEY `idx_media_checksum` (`checksum`),
  KEY `idx_media_folder` (`folder`,`created_at`),
  KEY `idx_media_type` (`mime_type`),
  KEY `fk_media_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `menu`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `label` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` enum('self','blank') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self',
  `location` enum('utama','footer','mobile') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utama',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menus_tree` (`location`,`parent_id`,`sort_order`),
  KEY `idx_menus_active` (`is_active`,`location`),
  KEY `fk_menus_parent` (`parent_id`),
  CONSTRAINT `fk_menus_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `menu` (8 baris)
INSERT INTO `menu` (`id`, `parent_id`, `label`, `url`, `target`, `location`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
('1', NULL, 'Beranda', '', 'self', 'utama', '1', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('2', NULL, 'Profil', 'profil', 'self', 'utama', '2', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('3', NULL, 'Akademik', 'akademik', 'self', 'utama', '3', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('4', NULL, 'Fasilitas', 'fasilitas', 'self', 'utama', '4', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('5', NULL, 'Galeri', 'galeri', 'self', 'utama', '5', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('6', NULL, 'Berita', 'berita', 'self', 'utama', '6', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('7', NULL, 'PPDB', 'ppdb', 'self', 'utama', '7', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('8', NULL, 'Kontak', 'kontak', 'self', 'utama', '8', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `notifikasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('assignment','grade','attendance','announcement','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `dedupe_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifications_dedupe` (`user_id`,`dedupe_key`),
  KEY `idx_notifications_poll` (`user_id`,`is_read`,`created_at`),
  KEY `idx_notifications_recent` (`user_id`,`created_at`),
  KEY `idx_notifications_type` (`type`,`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pemeriksaan_indikator`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pemeriksaan_indikator`;
CREATE TABLE `pemeriksaan_indikator` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `indicator_id` bigint unsigned NOT NULL,
  `status` enum('belum','sebagian','sesuai','tidak_sesuai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked_by` bigint unsigned DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_indicator_checks_pair` (`butir_id`,`indicator_id`),
  KEY `idx_indicator_checks_status` (`butir_id`,`status`),
  KEY `idx_indicator_checks_indicator` (`indicator_id`),
  KEY `fk_indicator_checks_by` (`checked_by`),
  CONSTRAINT `fk_indicator_checks_butir` FOREIGN KEY (`butir_id`) REFERENCES `butir_akreditasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_indicator_checks_by` FOREIGN KEY (`checked_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_indicator_checks_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `indikator_akreditasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pengaturan`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `value_type` enum('string','text','int','bool','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `label` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `pengaturan` (43 baris)
INSERT INTO `pengaturan` (`id`, `setting_key`, `setting_value`, `value_type`, `group_name`, `label`, `is_public`, `created_at`, `updated_at`) VALUES
('1', 'site_name', 'MTs Al-Huda', 'string', 'identitas', 'Nama madrasah', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('2', 'site_subtitle', 'Gondang', 'string', 'identitas', 'Keterangan nama', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('3', 'site_full_name', 'Madrasah Tsanawiyah Al-Huda Gondang', 'string', 'identitas', 'Nama lengkap', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('4', 'site_npsn', '', 'string', 'identitas', 'NPSN', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('5', 'site_foundation', 'Yayasan Al-Huda Gondang', 'string', 'identitas', 'Nama yayasan', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('6', 'accreditation_grade', 'A', 'string', 'identitas', 'Peringkat akreditasi', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('7', 'accreditation_year', '', 'string', 'identitas', 'Tahun akreditasi', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('8', 'contact_address', 'Gondang, Kabupaten Tulungagung, Jawa Timur', 'text', 'kontak', 'Alamat lengkap', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('9', 'contact_phone', '', 'string', 'kontak', 'Telepon', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('10', 'contact_whatsapp', '', 'string', 'kontak', 'WhatsApp', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('11', 'contact_email', 'info@mts-alhuda.sch.id', 'string', 'kontak', 'Email resmi', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('12', 'contact_hours', 'Senin - Sabtu, 07.00 - 15.10 WIB', 'string', 'kontak', 'Jam operasional', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('13', 'contact_map_lat', '', 'string', 'kontak', 'Lintang lokasi', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('14', 'contact_map_lng', '', 'string', 'kontak', 'Bujur lokasi', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('15', 'social_facebook', '', 'string', 'sosial', 'Facebook', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('16', 'social_instagram', '', 'string', 'sosial', 'Instagram', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('17', 'social_youtube', '', 'string', 'sosial', 'YouTube', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('18', 'social_tiktok', '', 'string', 'sosial', 'TikTok', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('19', 'meta_title', 'MTs Al-Huda Gondang — Madrasah Berakhlak, Berprestasi', 'string', 'seo', 'Meta title', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('20', 'meta_description', 'Madrasah Tsanawiyah Al-Huda Gondang. Membentuk generasi Qur\'ani, berakhlak mulia, dan berprestasi.', 'text', 'seo', 'Meta description', '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('21', 'attendance_check_in_time', '07:00', 'string', 'presensi', 'Jam masuk', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('22', 'attendance_late_tolerance', '15', 'int', 'presensi', 'Toleransi terlambat (menit)', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('23', 'attendance_check_out_time', '15:10', 'string', 'presensi', 'Jam pulang', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('24', 'security_max_login_attempts', '5', 'int', 'keamanan', 'Batas percobaan login gagal', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('25', 'security_lockout_minutes', '15', 'int', 'keamanan', 'Durasi kunci akun (menit)', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('26', 'security_session_timeout', '120', 'int', 'keamanan', 'Sesi web berakhir (menit)', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('27', 'security_api_access_ttl', '30', 'int', 'keamanan', 'Masa aktif token akses (menit)', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('28', 'security_api_refresh_ttl', '30', 'int', 'keamanan', 'Masa aktif token refresh (hari)', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('29', 'security_force_password_change', '1', 'bool', 'keamanan', 'Wajib ganti sandi saat login pertama', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('30', 'security_audit_log', '1', 'bool', 'keamanan', 'Catat log audit', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('31', 'mail_enabled', '0', 'bool', 'email', 'Notifikasi email aktif', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('32', 'mail_host', '', 'string', 'email', 'SMTP host', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('33', 'mail_port', '587', 'int', 'email', 'SMTP port', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('34', 'mail_encryption', 'tls', 'string', 'email', 'Enkripsi SMTP', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('35', 'mail_username', '', 'string', 'email', 'SMTP username', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('36', 'mail_password', '', 'string', 'email', 'SMTP password', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('37', 'mail_from_address', '', 'string', 'email', 'Alamat pengirim', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('38', 'mail_from_name', 'MTs Al-Huda Gondang', 'string', 'email', 'Nama pengirim', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('39', 'mail_on_password_reset', '1', 'bool', 'email', 'Kirim email reset sandi', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('40', 'mail_on_account_created', '1', 'bool', 'email', 'Kirim email akun baru', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('41', 'mail_on_new_assignment', '1', 'bool', 'email', 'Kirim email tugas baru', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('42', 'announcement_active', '0', 'bool', 'pengumuman', 'Tampilkan pengumuman', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('43', 'announcement_text', '', 'text', 'pengumuman', 'Isi pengumuman', '0', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pengguna`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','kepala','guru','siswa','akreditasi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `role_label` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `failed_login_attempts` int unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role_active` (`role`,`is_active`),
  KEY `idx_users_deleted` (`deleted_at`),
  KEY `idx_users_locked` (`locked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=5280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `pengguna` (16 baris)
INSERT INTO `pengguna` (`id`, `name`, `email`, `username`, `password_hash`, `role`, `role_label`, `phone`, `avatar_path`, `is_active`, `must_change_password`, `failed_login_attempts`, `locked_until`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Operator Contoh', 'operator@contoh.local', 'operator', '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK', 'operator', 'Operator Madrasah', NULL, NULL, '0', '1', '0', NULL, '2026-10-26 21:38:16', '127.0.0.1', '2026-10-23 05:48:12', '2026-10-29 11:20:56', NULL),
('2', 'Guru Contoh', 'guru@contoh.local', 'guru', '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK', 'guru', 'Guru', NULL, NULL, '1', '1', '0', NULL, '2026-10-27 06:37:07', '127.0.0.1', '2026-10-23 05:48:12', '2026-10-27 06:37:07', NULL),
('3', 'Administrator Madrasah', 'admin@mts-alhuda.sch.id', 'admin', '$2y$10$X9VOuS8nbqcFLt87p7FCTOVQkH4ithzTOv0FtJO6koqINzApYuw5G', 'admin', 'Super Admin', NULL, NULL, '1', '0', '0', NULL, '2026-10-29 14:35:06', '::1', '2026-10-23 05:48:13', '2026-10-29 14:35:06', NULL),
('414', 'Siswa Contoh', 'siswa@contoh.local', 'siswa', '$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK', 'siswa', 'Siswa', NULL, NULL, '1', '1', '0', NULL, '2026-10-27 06:37:07', '127.0.0.1', '2026-10-23 06:03:01', '2026-10-27 06:37:07', NULL),
('4866', 'Drs. H. M. Sholihin, M.Pd.I', 'sholihin@mts-alhuda.sch.id', 'sholihin', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'kepala', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4867', 'Ahmad Fauzi, S.Pd.I', 'fauzi@mts-alhuda.sch.id', 'fauzi', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4868', 'Siti Nurhaliza, S.Pd', 'siti.nurhaliza@mts-alhuda.sch.id', 'siti.nurhaliza', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4869', 'Budi Santoso, M.Pd', 'budi.santoso@mts-alhuda.sch.id', 'budi.santoso', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4870', 'Nurul Hidayati, S.Si', 'nurul.hidayati@mts-alhuda.sch.id', 'nurul.hidayati', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4871', 'Muhammad Rifa\'i, S.Pd', 'rifai@mts-alhuda.sch.id', 'rifai', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4872', 'Zainal Abidin, S.Pd.I', 'zainal.abidin@mts-alhuda.sch.id', 'zainal.abidin', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4873', 'Khoirul Anam, S.Pd', 'khoirul.anam@mts-alhuda.sch.id', 'khoirul.anam', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4874', 'Lailatul Badriyah, S.Pd', 'lailatul@mts-alhuda.sch.id', 'lailatul', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4875', 'Dimas Prasetyo, S.Or', 'dimas@mts-alhuda.sch.id', 'dimas', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4876', 'Fitri Handayani, S.Kom', 'fitri.handayani@mts-alhuda.sch.id', 'fitri.handayani', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL),
('4877', 'Ustadz Abdul Wahid, Lc', 'abdul.wahid@mts-alhuda.sch.id', 'abdul.wahid', '$2y$10$bVrbC0Dz/UvqPSI.j8Ng2uZ3LJraXcxI.qmfWRgCUrx8w4WzDDw3e', 'guru', NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25', NULL);

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pengumpulan_tugas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengumpulan_tugas`;
CREATE TABLE `pengumpulan_tugas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `graded_at` datetime DEFAULT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submissions_pair` (`assignment_id`,`student_id`),
  UNIQUE KEY `uq_submissions_public` (`public_id`),
  KEY `idx_submissions_student` (`student_id`,`submitted_at`),
  KEY `idx_submissions_ungraded` (`assignment_id`,`graded_at`),
  KEY `idx_submissions_grader` (`graded_by`),
  CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_submissions_grader` FOREIGN KEY (`graded_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_submissions_score` CHECK (((`score` is null) or (`score` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pengumuman`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengumuman`;
CREATE TABLE `pengumuman` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` enum('semua','siswa','guru','wali') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'semua',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_announcements_slug` (`slug`),
  KEY `idx_announcements_feed` (`status`,`is_pinned`,`published_at`),
  KEY `idx_announcements_expiry` (`expires_at`),
  KEY `idx_announcements_audience` (`audience`,`status`),
  KEY `fk_announcements_author` (`author_id`),
  CONSTRAINT `fk_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `pengumuman` (1 baris)
INSERT INTO `pengumuman` (`id`, `title`, `slug`, `content`, `audience`, `status`, `is_pinned`, `published_at`, `expires_at`, `author_id`, `created_at`, `updated_at`) VALUES
('1', 'Contoh Pengumuman', 'contoh-pengumuman', 'Isi pengumuman contoh. Pengumuman yang disematkan tampil paling atas.', 'semua', 'published', '1', '2026-10-15 07:00:00', NULL, '1', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `periode_akreditasi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `periode_akreditasi`;
CREATE TABLE `periode_akreditasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','berjalan','selesai','arsip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`is_active` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_periods_name` (`name`),
  UNIQUE KEY `uq_acc_periods_active` (`active_flag`),
  KEY `idx_acc_periods_status` (`status`,`start_date`),
  CONSTRAINT `chk_acc_periods_range` CHECK (((`end_date` is null) or (`start_date` is null) or (`end_date` > `start_date`)))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `periode_akreditasi` (2 baris)
INSERT INTO `periode_akreditasi` (`id`, `name`, `start_date`, `end_date`, `status`, `is_active`, `note`, `created_at`, `updated_at`) VALUES
('1', 'Akreditasi Contoh', '2026-01-01', '2026-12-31', 'draft', '0', 'Kerangka contoh. Teks butir menunggu instrumen resmi IA2024.', '2026-10-23 05:48:12', '2026-10-23 05:48:12'),
('26', 'Akreditasi IA2024', '2026-01-01', '2027-03-31', 'berjalan', '1', 'Instrumen Akreditasi Madrasah 2024/2027', '2026-10-29 10:02:56', '2026-10-29 10:02:56');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `pesan_kontak`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `pesan_kontak`;
CREATE TABLE `pesan_kontak` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('baru','dibaca','dibalas','arsip') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'baru',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_inbox` (`status`,`created_at`),
  KEY `idx_contact_email` (`email`),
  KEY `fk_contact_replied_by` (`replied_by`),
  CONSTRAINT `fk_contact_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `presensi_guru`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `presensi_guru`;
CREATE TABLE `presensi_guru` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `attendance_type` enum('daily','session') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `check_in_at` datetime DEFAULT NULL,
  `check_out_at` datetime DEFAULT NULL,
  `status` enum('hadir','terlambat','izin','sakit','dinas','alpa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hadir',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` enum('self','operator','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unique_key` varchar(96) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`teacher_id`,_utf8mb4'-',`attendance_date`,_utf8mb4'-',`attendance_type`,_utf8mb4'-',ifnull(`schedule_id`,0))) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teacher_attendances_key` (`unique_key`),
  KEY `idx_attendance_daily` (`attendance_date`,`status`),
  KEY `idx_attendance_teacher` (`teacher_id`,`attendance_date`),
  KEY `idx_attendance_schedule` (`schedule_id`,`attendance_date`),
  KEY `idx_attendance_unverified` (`verified_at`,`attendance_date`),
  KEY `fk_attendance_verifier` (`verified_by`),
  CONSTRAINT `fk_attendance_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `jadwal` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_verifier` FOREIGN KEY (`verified_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_attendance_checkout` CHECK (((`check_out_at` is null) or (`check_in_at` is null) or (`check_out_at` >= `check_in_at`))),
  CONSTRAINT `chk_attendance_type` CHECK ((((`attendance_type` = _utf8mb4'daily') and (`schedule_id` is null)) or ((`attendance_type` = _utf8mb4'session') and (`schedule_id` is not null))))
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `presensi_guru` (13 baris)
INSERT INTO `presensi_guru` (`id`, `teacher_id`, `schedule_id`, `attendance_date`, `attendance_type`, `check_in_at`, `check_out_at`, `status`, `note`, `method`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
('57', '5', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('58', '37', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('59', '38', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('60', '39', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('61', '40', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('62', '41', NULL, '2026-10-29', 'daily', '2026-10-29 07:22:15', NULL, 'terlambat', 'Terlambat 12 menit - kendala lalu lintas', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('63', '42', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('64', '43', NULL, '2026-10-29', 'daily', NULL, NULL, 'izin', 'Izin menghadiri dinas rapat KKM Kemenag Nganjuk', 'operator', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('65', '44', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('66', '45', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('67', '46', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('68', '47', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25'),
('69', '48', NULL, '2026-10-29', 'daily', '2026-10-29 06:48:30', NULL, 'hadir', 'Check-in tepat waktu via Mobile MADRASAHKU', 'self', NULL, NULL, '2026-10-29 19:54:25', '2026-10-29 19:54:25');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `reset_sandi`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `reset_sandi`;
CREATE TABLE `reset_sandi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`,`expires_at`),
  KEY `idx_password_resets_cleanup` (`expires_at`,`used_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `schema_migrations`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int unsigned NOT NULL DEFAULT '1',
  `statements` int unsigned NOT NULL DEFAULT '0',
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `schema_migrations` (15 baris)
INSERT INTO `schema_migrations` (`version`, `name`, `batch`, `statements`, `executed_at`) VALUES
('001', 'foundation', '1', '3', '2026-10-23 05:48:07'),
('002', 'identity_core', '1', '3', '2026-10-23 05:48:07'),
('003', 'identity_security', '1', '3', '2026-10-23 05:48:07'),
('004', 'cms_content', '1', '6', '2026-10-23 05:48:08'),
('005', 'cms_support', '1', '4', '2026-10-23 05:48:08'),
('006', 'academic_core', '1', '5', '2026-10-23 05:48:09'),
('007', 'academic_learning', '1', '4', '2026-10-23 05:48:09'),
('008', 'teacher_attendance', '1', '1', '2026-10-23 05:48:10'),
('009', 'notifications', '1', '1', '2026-10-23 05:48:10'),
('010', 'accreditation_structure', '1', '4', '2026-10-23 05:48:10'),
('011', 'accreditation_evidence', '1', '4', '2026-10-23 05:48:11'),
('012', 'accreditation_documents', '1', '4', '2026-10-23 05:48:11'),
('013', 'api_support', '1', '1', '2026-10-23 05:48:11'),
('014', 'public_ids', '1', '2', '2026-10-23 05:48:12'),
('015', 'posts_category', '2', '1', '2026-10-27 05:37:31');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `schema_seeders`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `schema_seeders`;
CREATE TABLE `schema_seeders` (
  `seeder` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checksum` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seeder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `schema_seeders` (2 baris)
INSERT INTO `schema_seeders` (`seeder`, `checksum`, `executed_at`) VALUES
('001_settings.sql', '7f3b177d69ed22b5ca93c6a51a9247ce888d8a5f4f6e1e6f76492eb48a5a0317', '2026-10-23 06:03:01'),
('DEMO-002_demo_content.sql', '5959e9a808867805ece2f54765acf82e09784f4f070965b0b2ae01d7270cbea7', '2026-10-23 06:03:01');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `siswa`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `birth_place` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `guardian_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `enrolled_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_user` (`user_id`),
  UNIQUE KEY `uq_students_nis` (`nis`),
  UNIQUE KEY `uq_students_nisn` (`nisn`),
  UNIQUE KEY `uq_students_public` (`public_id`),
  KEY `idx_students_class_active` (`class_id`,`is_active`),
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `siswa` (1 baris)
INSERT INTO `siswa` (`id`, `public_id`, `user_id`, `nis`, `nisn`, `class_id`, `gender`, `birth_place`, `birth_date`, `guardian_name`, `guardian_phone`, `address`, `enrolled_at`, `is_active`, `created_at`, `updated_at`) VALUES
('5', NULL, '414', '2025001', NULL, '1', 'P', NULL, NULL, NULL, NULL, NULL, '2025-07-01', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `tahun_akademik`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `tahun_akademik`;
CREATE TABLE `tahun_akademik` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`is_active` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_academic_years_name` (`name`),
  UNIQUE KEY `uq_academic_years_active` (`active_flag`),
  KEY `idx_academic_years_range` (`start_date`,`end_date`),
  CONSTRAINT `chk_academic_years_range` CHECK ((`end_date` > `start_date`))
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `tahun_akademik` (1 baris)
INSERT INTO `tahun_akademik` (`id`, `name`, `start_date`, `end_date`, `is_active`, `note`, `created_at`, `updated_at`) VALUES
('1', '2025/2026', '2025-07-01', '2026-06-30', '1', 'Tahun ajaran contoh', '2026-10-23 05:48:12', '2026-10-23 05:48:12');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `tindak_lanjut`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `tindak_lanjut`;
CREATE TABLE `tindak_lanjut` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `result_id` bigint unsigned NOT NULL,
  `recommendation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('belum','proses','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `responsible_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_follow_ups_result` (`result_id`,`status`,`due_date`),
  KEY `idx_follow_ups_open` (`status`,`due_date`),
  KEY `idx_follow_ups_owner` (`responsible_id`),
  CONSTRAINT `fk_follow_ups_owner` FOREIGN KEY (`responsible_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_follow_ups_result` FOREIGN KEY (`result_id`) REFERENCES `hasil_akreditasi` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Struktur Tabel: `token_api`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `token_api`;
CREATE TABLE `token_api` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `access_token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` enum('android','ios','web','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_tokens_access` (`access_token_hash`),
  UNIQUE KEY `uq_api_tokens_refresh` (`refresh_token_hash`),
  KEY `idx_api_tokens_user_live` (`user_id`,`revoked_at`,`refresh_expires_at`),
  KEY `idx_api_tokens_refresh_ttl` (`refresh_expires_at`),
  CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `token_api` (38 baris)
INSERT INTO `token_api` (`id`, `user_id`, `access_token_hash`, `refresh_token_hash`, `device_name`, `platform`, `ip_address`, `user_agent`, `access_expires_at`, `refresh_expires_at`, `last_used_at`, `revoked_at`, `created_at`) VALUES
('3', '3', '287be83f3bda9bdab42bbcba5a5b2106fd6f818cf233c838d723101d16682565', '58652b13122e2a75aab54a2cf77000c38ec2e4e0b6f116bc47f8e64107ff8099', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:21:24', '2026-11-22 05:51:24', '2026-10-23 05:51:24', '2026-10-23 05:51:24', '2026-10-23 05:51:24'),
('4', '3', '0db815ed1eff718f5c5be2223ec5bb58d6cf9f95e4c4c1ae221c2e58e53d1151', '9c2378a4dd4271ef67af3558cd320966ef088537fb43d7108c28d47b7ab3d654', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:21:24', '2026-11-22 05:51:24', '2026-10-23 05:51:25', '2026-10-23 05:51:25', '2026-10-23 05:51:24'),
('5', '2', '07ada44ecc5b91059f0b3ece298362288aef07e07cf819dfabf0845646d6e94e', 'a1b4ac673de7c4c734f1d40ef62922c097348a619cada61f81ec890741eda566', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:33:20', '2026-11-22 06:03:20', '2026-10-23 06:03:21', NULL, '2026-10-23 06:03:20'),
('6', '414', '2718361beb1eb93260a4c31cf68cf13b81d5a3f1b57dd9da033dfd14e81fa2d0', '372f5c1c1aac995c6cd8afbd78ba65ea2ab4371f314d11be00c820eacf09006e', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:33:21', '2026-11-22 06:03:21', '2026-10-23 06:03:21', NULL, '2026-10-23 06:03:21'),
('7', '2', '1f44389c6bc832efd071134f1ae8a13a6979112b0de6f1ef0f12d842d4c193ce', '0c591e7a26aa940b1548dbd632394ad99f79f9a3a82b569eb231dc76d05c1879', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:34:06', '2026-11-22 06:04:06', '2026-10-23 06:04:07', NULL, '2026-10-23 06:04:06'),
('8', '414', '10c54bcc29d943faa312eef5bd2dc8a28f804e0dff1b971b6761274031dac23e', '59ba9978187ea19a5145b80ac8e7db7e6f037b8017e0b64ed549f1a81ec38e74', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:34:07', '2026-11-22 06:04:07', '2026-10-23 06:04:07', NULL, '2026-10-23 06:04:07'),
('9', '414', '037a1d59ec101cf1473254516260d3d559ad5f509517ab465afc7d6f0f5f3f42', '87c59f0f170b8e96b5e4751aed572d2fa2274bede775af6bbe4e7dfc8485037c', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:34:16', '2026-11-22 06:04:16', '2026-10-23 06:04:17', NULL, '2026-10-23 06:04:16'),
('10', '3', 'a3510d1ec318e239f847143029d20dfbe04a3d950d5e0922735eca8131616413', 'f16b8abf1b8fd314682c76b554d12512f2035e84e5219de6221500a3e0b37b8c', 'Uji Data', 'android', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133', '2026-10-23 06:34:17', '2026-11-22 06:04:17', '2026-10-23 06:04:17', NULL, '2026-10-23 06:04:17'),
('11', '3', 'ee6e8925893651b0a7cd8ef617a6e662369e38eac6406312eab606585ac8b9b7', 'df170e6c001a74e3ab1ae03135ef716025861f1bb9bc932c2fc7b9ee5f528810', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:35:06', '2026-11-22 06:05:06', '2026-10-23 06:05:06', '2026-10-23 06:05:06', '2026-10-23 06:05:06'),
('12', '3', '5d85d49b91a2ead8053627d129d3c7068cd90623d3ff3872356439c61ffe137f', '1f63d7648d94ef94d6cd869cfb477457cde3f86d7cdb93acb8f263113bccdd46', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:35:06', '2026-11-22 06:05:06', '2026-10-23 06:05:06', '2026-10-23 06:05:06', '2026-10-23 06:05:06'),
('13', '2', '83f854424dab1eb3d8d412753f03a1ad5c7ef54e4a98c37713cc90973ae4bc3d', '12aa9aed50ae6eddea0a1b90983cdeec5862540546a017b142fc554d3daeb2fa', 'Uji Peran', 'android', '::1', '', '2026-10-23 06:35:06', '2026-11-22 06:05:06', '2026-10-23 06:05:07', NULL, '2026-10-23 06:05:06'),
('14', '414', '7ebdad038f3aca571535a0d0e5de2b37f5e4129f6b01095f7d4acb34704688fa', 'c475272672ea10091cb84910c7c492438cd752c315af68b0bf7155a8d3a7f54e', 'Uji Peran', 'android', '::1', '', '2026-10-23 06:35:06', '2026-11-22 06:05:06', '2026-10-23 06:05:07', NULL, '2026-10-23 06:05:06'),
('19', '3', 'b73b07418348f8b16a22cfe5916158fecb2e0c1357159a7536f3723aa974a596', 'a306986bfad594e92a465dfd5380094767dc6b6a9ba86d01000e79a06bba1caa', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:37:50', '2026-11-22 06:07:50', '2026-10-23 06:07:50', '2026-10-23 06:07:51', '2026-10-23 06:07:50'),
('20', '3', 'd7b6baca02ba3e3e768bb61cbefd29e4b29f73c5a6efa52ed317dce410aa55dc', 'a52ee029cf61c7a934153155befc4c4ed97e6e8e0431f5466864fb46b7d6f879', 'Uji Otomatis', 'android', '::1', '', '2026-10-23 06:37:51', '2026-11-22 06:07:51', '2026-10-23 06:07:51', '2026-10-23 06:07:51', '2026-10-23 06:07:51'),
('21', '2', '4e5f649fdf46e7c62d8d415d86b8a549768dc09c42ade0274d584e66b6f5bbce', '1a4c7967aa30068183f776a0792a4199848dbf57bc2184985f6c8a013f38f057', 'Uji Peran', 'android', '::1', '', '2026-10-23 06:37:51', '2026-11-22 06:07:51', '2026-10-23 06:07:51', NULL, '2026-10-23 06:07:51'),
('22', '414', 'cbfe2f2db520bfca01fb24f924481fc2e172eb4fad68352ce8fd932a2ae57ad4', '73aef1f653a97d7538d0e62bf75c96ddd0361c2c5d2c29a1cc6ecee82648fb0d', 'Uji Peran', 'android', '::1', '', '2026-10-23 06:37:51', '2026-11-22 06:07:51', '2026-10-23 06:07:52', NULL, '2026-10-23 06:07:51'),
('25', '1', '871628fa0521c38e015cc3cd1ed9074b8a43539e353ed4e3e97b1d50acc03af5', 'c5181c7462b44d537d6c0b85a4c0b1e74f58df8fce0795d8818a52c03b05579a', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-26 22:08:16', '2026-11-25 21:38:16', '2026-10-26 21:38:17', '2026-10-26 21:38:17', '2026-10-26 21:38:16'),
('26', '1', '8ecb154bb991e86f39948dcec42bff1b8bc2580ab90d61cc400c384a3198fccc', 'c96a706b8cc718b584c82506cd0ae9c8483bda5a8b536c38806c4cdbffe06227', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-26 22:08:17', '2026-11-25 21:38:17', '2026-10-26 21:38:17', '2026-10-26 21:38:17', '2026-10-26 21:38:17'),
('27', '2', 'bbede620bc49252643d3d81a639872df39c8f081101cfc5702f091ca4d536cf3', '5950c1d29f85151e28a86c8854f98d80c78db4d99f50972ed66f19b009318120', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-26 22:08:19', '2026-11-25 21:38:19', '2026-10-26 21:38:19', NULL, '2026-10-26 21:38:19'),
('28', '414', 'a89ad0962ea667e169e7dd8763fe11ad1f2cb6e85d46d6fb738bd0e66ee0efdb', '9326df73878f34ab26464365e9e5167936164831bec1d6e04206f841077939c6', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-26 22:08:19', '2026-11-25 21:38:19', '2026-10-26 21:38:20', NULL, '2026-10-26 21:38:19'),
('29', '3', 'f67c4a85b56e8ecc554f202bbe838707d911d6dacddac9f4761b0d05a3008f56', 'ad33d78a07a45c69ec2550719c9092c11fda78266b7869b3561748a8d7518eaf', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 05:02:09', '2026-11-26 04:32:09', '2026-10-27 04:32:09', '2026-10-27 04:32:09', '2026-10-27 04:32:09'),
('30', '3', '54ee7c517b6a7b039ab923c091e085ac80c1e8da53d1f419db82081fe219b3d9', '53b339f66ba26f146d37a995372a809ad05d0f537f8cc61a8619fbd5a5c752b3', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 05:02:09', '2026-11-26 04:32:09', '2026-10-27 04:32:09', '2026-10-27 04:32:09', '2026-10-27 04:32:09'),
('31', '2', 'd74c620f29368edbda1a3777161b7e316b58c5d2e75ece5683cae06da9b5f7b7', '09e0ae8589439a890972dd8b2732cde060aa274d41277e6c90686a1146977b66', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 05:02:10', '2026-11-26 04:32:10', '2026-10-27 04:32:10', NULL, '2026-10-27 04:32:10'),
('32', '414', 'f97c205fee35bbdf441015d4f216c9f6016e4c480c4cfafbbbf9eabbd829d387', '944c49d1c504dcc31d4cec1633e83114a985b73591a8531e4e38d438ac4985c7', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 05:02:10', '2026-11-26 04:32:10', '2026-10-27 04:32:10', NULL, '2026-10-27 04:32:10'),
('37', '3', 'f2e20374866e495af26a9794a07522904355b3a2a9b2221af40eff0a75a31e21', 'b787b578196983df2c52d9db4dd58be257e1aeb34b04c21397ac0b3b36aa407b', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 06:21:19', '2026-11-26 05:51:19', '2026-10-27 05:51:19', '2026-10-27 05:51:19', '2026-10-27 05:51:19'),
('38', '3', 'ef906220eccddb9b01954978b3a160dcc026a6e3566bf17c4b8b91de8e1c2c83', '9c4251884ae7ff4ffefe7be91615ca448b5c137dd3cc3b1965c1cce055da1274', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 06:21:19', '2026-11-26 05:51:19', '2026-10-27 05:51:19', '2026-10-27 05:51:19', '2026-10-27 05:51:19'),
('39', '2', '78bbf17343cefee5db89b32b7d58bcb3187e7ff2777b7a85a052aede5a0ba94a', '5a3a3a5eedde575b2269ecaf1373106974480463db0512afa9454953fc9c4b7a', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 06:21:20', '2026-11-26 05:51:20', '2026-10-27 05:51:20', NULL, '2026-10-27 05:51:20'),
('40', '414', 'bef3f22326bc6a984e5b65f55cae737fc1ccd9a3659a1ee9079c32a927ed567a', 'fbe58bcc75e00eb50f52dbd4647a7fe3a63a47707c7ed407a86a183ebd7c3939', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 06:21:20', '2026-11-26 05:51:20', '2026-10-27 05:51:20', NULL, '2026-10-27 05:51:20'),
('43', '3', 'd01fd0b4e415dc3ff0168a2f85922fbb496459281fa8ae3ed22d42cb8f0ca5f3', '7bf68bd0f42368be3fc4014cc5de3100610a37eaa731753b6cbe28c6a7385bc8', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 06:27:50', '2026-11-26 05:57:50', '2026-10-27 05:57:50', '2026-10-27 05:57:50', '2026-10-27 05:57:50'),
('44', '3', '8a366aa3dfd18d63c93d723c8402b6168c1897a69b075c276ee6a609ccd84ee0', '5fce83eba2051b16b81292bb2a392b580b2228605ecd3bd88514c16c94d53fc0', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 06:27:50', '2026-11-26 05:57:50', '2026-10-27 05:57:50', '2026-10-27 05:57:50', '2026-10-27 05:57:50'),
('45', '2', '536a208ac6563991f30bb73c2ec1966b44b35c0d23e25863b54ccdf8e64b195c', '94b9beea4aa4ed2020521bfdc5848fe7cbe7772e6c2a7e30532b8a779a0a9e9b', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 06:27:51', '2026-11-26 05:57:51', '2026-10-27 05:57:51', NULL, '2026-10-27 05:57:51'),
('46', '414', 'f72d70180b20df31e0d71391062c177f6ac1b488c1907b7de7b4f79a2319718c', '62c8562365ac28e4d2e94004d58043c5adbf21e7b2cf427dafe51fb4ab458b88', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 06:27:51', '2026-11-26 05:57:51', '2026-10-27 05:57:51', NULL, '2026-10-27 05:57:51'),
('52', '3', 'f44c72d8912000d464d2d975a19749335acbae6a692a01d43f710a9e99969f4a', '66f335dd885887be2f09aa3b9e3973282b499c2467d6cd9cbf2076152b2eee42', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 07:07:06', '2026-11-26 06:37:06', '2026-10-27 06:37:06', '2026-10-27 06:37:06', '2026-10-27 06:37:06'),
('53', '3', '2f1301ed4c05567774e0e2288eda3e7ed76b4db63f06a460d5c7dcc09474cae7', '1018117da94f4160366c6c4729c9afb628671232b1f4c2615b93892d0e2390cc', 'Uji Otomatis', 'android', '127.0.0.1', '', '2026-10-27 07:07:06', '2026-11-26 06:37:06', '2026-10-27 06:37:07', '2026-10-27 06:37:07', '2026-10-27 06:37:06'),
('54', '2', 'a269dd1cd929ec4afe7502bf22af38949972ed7772aac445cbf46537aa0dc897', 'e15dccd6cad9d45e7af268319cc55fd3c024199315130411fd8384d6e84b5928', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 07:07:07', '2026-11-26 06:37:07', '2026-10-27 06:37:07', NULL, '2026-10-27 06:37:07'),
('55', '414', 'e72866740daeba145919d203953900d8904ca7e6b722501571ca7af535d50836', 'ee92be879319dee2d0a3b2472ec04c8b83c1882bf65c013135fde8dab7063bb7', 'Uji Peran', 'android', '127.0.0.1', '', '2026-10-27 07:07:07', '2026-11-26 06:37:07', '2026-10-27 06:37:07', NULL, '2026-10-27 06:37:07'),
('58', '2', '0b66fba441e274227c721438b97ba626b5a7d98c89b4505b4c017d53788fcbc9', 'b6c43f028c93974ce4a9e027f2b0ee0cecb5ed4a3156b150ecd04c469d2a5f3b', 'Flutter Test Device', 'android', '127.0.0.1', 'Dart/Flutter', '2026-10-29 15:24:06', '2026-11-28 14:54:06', '2026-10-29 14:54:06', NULL, '2026-10-29 14:54:06'),
('59', '2', 'bdd78ba881a1a7a0e9e55131f78d1aaa15dc2b3aebbdcb48171df251bc40349c', 'd353cb227a28a89146b1fa8185711bb03f8c630f4f808eb6ad33efd99a3731b9', 'Flutter Test Device', 'android', '127.0.0.1', 'Dart/Flutter', '2026-10-29 15:24:52', '2026-11-28 14:54:52', '2026-10-29 14:54:52', NULL, '2026-10-29 14:54:52');

-- --------------------------------------------------------------------------
-- Struktur Tabel: `tugas`
-- --------------------------------------------------------------------------
DROP TABLE IF EXISTS `tugas`;
CREATE TABLE `tugas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_subject_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT '100.00',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignments_public` (`public_id`),
  KEY `idx_assignments_class_due` (`class_subject_id`,`due_at`),
  KEY `idx_assignments_open` (`is_published`,`due_at`),
  KEY `idx_assignments_author` (`created_by`),
  CONSTRAINT `fk_assignments_author` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assignments_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_assignments_score` CHECK ((`max_score` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Tabel: `tugas` (2 baris)
INSERT INTO `tugas` (`id`, `public_id`, `class_subject_id`, `title`, `description`, `attachment_path`, `due_at`, `max_score`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
('3', 'bb4b0c18-ce6c-11f1-9083-38142812b636', '5', 'Tugas Contoh: Operasi Bilangan Bulat', 'Kerjakan 10 soal pada buku paket halaman 24. Kumpulkan dalam bentuk foto atau PDF.', NULL, '2026-11-10 23:59:59', '100.00', '1', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01'),
('4', 'bb4bedca-ce6c-11f1-9083-38142812b636', '6', 'Tugas Contoh: Teks Deskripsi', 'Tulis teks deskripsi tentang lingkungan madrasah, minimal 200 kata.', NULL, '2026-11-17 23:59:59', '100.00', '1', '1', '2026-10-23 06:03:01', '2026-10-23 06:03:01');

SET FOREIGN_KEY_CHECKS = 1;
