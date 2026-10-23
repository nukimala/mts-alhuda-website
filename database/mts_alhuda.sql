-- ===========================================================================
-- MTs Al-Huda Gondang - database siap impor (satu berkas)
--
-- Isi    : 42 tabel (14 migrasi) + pengaturan awal + data contoh
-- Dibuat : 23 Oktober 2026, dari MySQL 8.4.3
-- Impor  : lihat database/README.md bagian "Impor satu berkas"
--
-- Akun contoh (username / sandi):
--   admin      / Admin#AlHuda2026   (email admin@mts-alhuda.sch.id; wajib ganti)
--   operator   / Contoh#AlHuda2026
--   guru       / Contoh#AlHuda2026
--   siswa      / Contoh#AlHuda2026
--
-- Berkas ini membuat database `mts_alhuda`. Kalau sudah ada, tabelnya
-- ditimpa satu per satu (DROP TABLE IF EXISTS) - bukan database-nya.
-- ===========================================================================

CREATE DATABASE IF NOT EXISTS `mts_alhuda`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `mts_alhuda`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`is_active` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_academic_years_name` (`name`),
  UNIQUE KEY `uq_academic_years_active` (`active_flag`),
  KEY `idx_academic_years_range` (`start_date`,`end_date`),
  CONSTRAINT `chk_academic_years_range` CHECK ((`end_date` > `start_date`))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` (`id`, `name`, `start_date`, `end_date`, `is_active`, `note`, `created_at`, `updated_at`) VALUES (1,'2025/2026','2025-07-01','2026-06-30',1,'Tahun ajaran contoh','2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `accreditation_butirs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_butirs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `component_id` bigint unsigned NOT NULL,
  `number` smallint unsigned NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `butir_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('kosong','proses','lengkap','terverifikasi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kosong',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_butirs_number` (`component_id`,`number`),
  KEY `idx_acc_butirs_order` (`component_id`,`sort_order`,`number`),
  KEY `idx_acc_butirs_status` (`status`),
  CONSTRAINT `fk_acc_butirs_component` FOREIGN KEY (`component_id`) REFERENCES `accreditation_components` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `accreditation_butirs` WRITE;
/*!40000 ALTER TABLE `accreditation_butirs` DISABLE KEYS */;
/*!40000 ALTER TABLE `accreditation_butirs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `accreditation_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` smallint unsigned NOT NULL DEFAULT '0',
  `weight` decimal(6,2) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_components_code` (`period_id`,`code`),
  KEY `idx_acc_components_order` (`period_id`,`sort_order`,`number`),
  CONSTRAINT `fk_acc_components_period` FOREIGN KEY (`period_id`) REFERENCES `accreditation_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `accreditation_components` WRITE;
/*!40000 ALTER TABLE `accreditation_components` DISABLE KEYS */;
INSERT INTO `accreditation_components` VALUES (1,1,'A','Komponen Contoh A',1,NULL,1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `accreditation_components` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `accreditation_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_indicators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `number` smallint unsigned NOT NULL,
  `indicator_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_indicators_number` (`butir_id`,`number`),
  KEY `idx_acc_indicators_order` (`butir_id`,`sort_order`,`number`),
  CONSTRAINT `fk_acc_indicators_butir` FOREIGN KEY (`butir_id`) REFERENCES `accreditation_butirs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `accreditation_indicators` WRITE;
/*!40000 ALTER TABLE `accreditation_indicators` DISABLE KEYS */;
/*!40000 ALTER TABLE `accreditation_indicators` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `accreditation_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','berjalan','selesai','arsip') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`is_active` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_periods_name` (`name`),
  UNIQUE KEY `uq_acc_periods_active` (`active_flag`),
  KEY `idx_acc_periods_status` (`status`,`start_date`),
  CONSTRAINT `chk_acc_periods_range` CHECK (((`end_date` is null) or (`start_date` is null) or (`end_date` > `start_date`)))
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `accreditation_periods` WRITE;
/*!40000 ALTER TABLE `accreditation_periods` DISABLE KEYS */;
INSERT INTO `accreditation_periods` (`id`, `name`, `start_date`, `end_date`, `status`, `is_active`, `note`, `created_at`, `updated_at`) VALUES (1,'Akreditasi Contoh','2026-01-01','2026-12-31','draft',0,'Kerangka contoh. Teks butir menunggu instrumen resmi IA2024.','2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `accreditation_periods` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `accreditation_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `grade` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `decided_at` datetime DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_results_period` (`period_id`),
  KEY `idx_acc_results_grade` (`grade`,`decided_at`),
  KEY `fk_acc_results_by` (`decided_by`),
  CONSTRAINT `fk_acc_results_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_acc_results_period` FOREIGN KEY (`period_id`) REFERENCES `accreditation_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_acc_results_score` CHECK (((`score` is null) or ((`score` >= 0) and (`score` <= 100))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `accreditation_results` WRITE;
/*!40000 ALTER TABLE `accreditation_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `accreditation_results` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`,`created_at`),
  KEY `idx_activity_logs_entity` (`entity`,`entity_id`,`created_at`),
  KEY `idx_activity_logs_action` (`action`,`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,3,'api.login.failed','users',3,'Login API gagal — percobaan ke-1 dari 5',NULL,'::1','','2026-10-23 05:51:24'),(2,3,'api.login.success','users',3,'Masuk lewat API (android: Uji Otomatis)',NULL,'::1','','2026-10-23 05:51:24'),(3,3,'api.token.refresh','users',3,'Refresh token dipakai, pasangan token berotasi',NULL,'::1','','2026-10-23 05:51:24'),(4,3,'api.logout','users',3,'Keluar dari API — token dicabut',NULL,'::1','','2026-10-23 05:51:25'),(5,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:25'),(6,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:25'),(7,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:25'),(8,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:26'),(9,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:26'),(10,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:26'),(11,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:26'),(12,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:26'),(13,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:27'),(14,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 05:51:27'),(15,2,'api.login.success','users',2,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:03:20'),(16,414,'api.login.success','users',414,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:03:21'),(17,2,'api.login.success','users',2,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:04:06'),(18,414,'api.login.success','users',414,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:04:07'),(19,414,'api.login.success','users',414,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:04:16'),(20,3,'api.login.success','users',3,'Masuk lewat API (android: Uji Data)',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:04:17'),(21,3,'api.login.failed','users',3,'Login API gagal — percobaan ke-1 dari 5',NULL,'::1','','2026-10-23 06:05:05'),(22,3,'api.login.success','users',3,'Masuk lewat API (android: Uji Otomatis)',NULL,'::1','','2026-10-23 06:05:06'),(23,3,'api.token.refresh','users',3,'Refresh token dipakai, pasangan token berotasi',NULL,'::1','','2026-10-23 06:05:06'),(24,3,'api.logout','users',3,'Keluar dari API — token dicabut',NULL,'::1','','2026-10-23 06:05:06'),(25,2,'api.login.success','users',2,'Masuk lewat API (android: Uji Peran)',NULL,'::1','','2026-10-23 06:05:06'),(26,414,'api.login.success','users',414,'Masuk lewat API (android: Uji Peran)',NULL,'::1','','2026-10-23 06:05:06'),(27,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(28,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(29,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(30,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(31,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(32,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:07'),(33,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:08'),(34,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:08'),(35,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:08'),(36,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:05:08'),(37,3,'api.login.failed','users',3,'Login API gagal — percobaan ke-1 dari 5',NULL,'::1','','2026-10-23 06:07:50'),(38,3,'api.login.success','users',3,'Masuk lewat API (android: Uji Otomatis)',NULL,'::1','','2026-10-23 06:07:50'),(39,3,'api.token.refresh','users',3,'Refresh token dipakai, pasangan token berotasi',NULL,'::1','','2026-10-23 06:07:51'),(40,3,'api.logout','users',3,'Keluar dari API — token dicabut',NULL,'::1','','2026-10-23 06:07:51'),(41,2,'api.login.success','users',2,'Masuk lewat API (android: Uji Peran)',NULL,'::1','','2026-10-23 06:07:51'),(42,414,'api.login.success','users',414,'Masuk lewat API (android: Uji Peran)',NULL,'::1','','2026-10-23 06:07:51'),(43,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(44,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(45,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(46,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(47,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(48,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:52'),(49,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:53'),(50,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:53'),(51,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:53'),(52,NULL,'api.login.failed','users',NULL,'Login API gagal — akun tidak dikenal',NULL,'::1','','2026-10-23 06:07:53');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `agendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','published','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `author_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agendas_upcoming` (`status`,`start_at`),
  KEY `idx_agendas_range` (`start_at`,`end_at`),
  KEY `fk_agendas_author` (`author_id`),
  CONSTRAINT `fk_agendas_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_agendas_range` CHECK (((`end_at` is null) or (`end_at` >= `start_at`)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `agendas` WRITE;
/*!40000 ALTER TABLE `agendas` DISABLE KEYS */;
INSERT INTO `agendas` VALUES (1,'Contoh Agenda Kegiatan','Agenda contoh untuk menguji tampilan kalender dan daftar agenda.','Aula Madrasah','2026-11-05 08:00:00','2026-11-05 12:00:00',0,'published',1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `agendas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` enum('semua','siswa','guru','wali') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'semua',
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
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
  CONSTRAINT `fk_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Contoh Pengumuman','contoh-pengumuman','Isi pengumuman contoh. Pengumuman yang disematkan tampil paling atas.','semua','published',1,'2026-10-15 07:00:00',NULL,1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `access_token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` enum('android','ios','web','unknown') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
INSERT INTO `api_tokens` VALUES (3,3,'287be83f3bda9bdab42bbcba5a5b2106fd6f818cf233c838d723101d16682565','58652b13122e2a75aab54a2cf77000c38ec2e4e0b6f116bc47f8e64107ff8099','Uji Otomatis','android','::1','','2026-10-23 06:21:24','2026-11-22 05:51:24','2026-10-23 05:51:24','2026-10-23 05:51:24','2026-10-23 05:51:24'),(4,3,'0db815ed1eff718f5c5be2223ec5bb58d6cf9f95e4c4c1ae221c2e58e53d1151','9c2378a4dd4271ef67af3558cd320966ef088537fb43d7108c28d47b7ab3d654','Uji Otomatis','android','::1','','2026-10-23 06:21:24','2026-11-22 05:51:24','2026-10-23 05:51:25','2026-10-23 05:51:25','2026-10-23 05:51:24'),(5,2,'07ada44ecc5b91059f0b3ece298362288aef07e07cf819dfabf0845646d6e94e','a1b4ac673de7c4c734f1d40ef62922c097348a619cada61f81ec890741eda566','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:33:20','2026-11-22 06:03:20','2026-10-23 06:03:21',NULL,'2026-10-23 06:03:20'),(6,414,'2718361beb1eb93260a4c31cf68cf13b81d5a3f1b57dd9da033dfd14e81fa2d0','372f5c1c1aac995c6cd8afbd78ba65ea2ab4371f314d11be00c820eacf09006e','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:33:21','2026-11-22 06:03:21','2026-10-23 06:03:21',NULL,'2026-10-23 06:03:21'),(7,2,'1f44389c6bc832efd071134f1ae8a13a6979112b0de6f1ef0f12d842d4c193ce','0c591e7a26aa940b1548dbd632394ad99f79f9a3a82b569eb231dc76d05c1879','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:34:06','2026-11-22 06:04:06','2026-10-23 06:04:07',NULL,'2026-10-23 06:04:06'),(8,414,'10c54bcc29d943faa312eef5bd2dc8a28f804e0dff1b971b6761274031dac23e','59ba9978187ea19a5145b80ac8e7db7e6f037b8017e0b64ed549f1a81ec38e74','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:34:07','2026-11-22 06:04:07','2026-10-23 06:04:07',NULL,'2026-10-23 06:04:07'),(9,414,'037a1d59ec101cf1473254516260d3d559ad5f509517ab465afc7d6f0f5f3f42','87c59f0f170b8e96b5e4751aed572d2fa2274bede775af6bbe4e7dfc8485037c','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:34:16','2026-11-22 06:04:16','2026-10-23 06:04:17',NULL,'2026-10-23 06:04:16'),(10,3,'a3510d1ec318e239f847143029d20dfbe04a3d950d5e0922735eca8131616413','f16b8abf1b8fd314682c76b554d12512f2035e84e5219de6221500a3e0b37b8c','Uji Data','android','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-10-23 06:34:17','2026-11-22 06:04:17','2026-10-23 06:04:17',NULL,'2026-10-23 06:04:17'),(11,3,'ee6e8925893651b0a7cd8ef617a6e662369e38eac6406312eab606585ac8b9b7','df170e6c001a74e3ab1ae03135ef716025861f1bb9bc932c2fc7b9ee5f528810','Uji Otomatis','android','::1','','2026-10-23 06:35:06','2026-11-22 06:05:06','2026-10-23 06:05:06','2026-10-23 06:05:06','2026-10-23 06:05:06'),(12,3,'5d85d49b91a2ead8053627d129d3c7068cd90623d3ff3872356439c61ffe137f','1f63d7648d94ef94d6cd869cfb477457cde3f86d7cdb93acb8f263113bccdd46','Uji Otomatis','android','::1','','2026-10-23 06:35:06','2026-11-22 06:05:06','2026-10-23 06:05:06','2026-10-23 06:05:06','2026-10-23 06:05:06'),(13,2,'83f854424dab1eb3d8d412753f03a1ad5c7ef54e4a98c37713cc90973ae4bc3d','12aa9aed50ae6eddea0a1b90983cdeec5862540546a017b142fc554d3daeb2fa','Uji Peran','android','::1','','2026-10-23 06:35:06','2026-11-22 06:05:06','2026-10-23 06:05:07',NULL,'2026-10-23 06:05:06'),(14,414,'7ebdad038f3aca571535a0d0e5de2b37f5e4129f6b01095f7d4acb34704688fa','c475272672ea10091cb84910c7c492438cd752c315af68b0bf7155a8d3a7f54e','Uji Peran','android','::1','','2026-10-23 06:35:06','2026-11-22 06:05:06','2026-10-23 06:05:07',NULL,'2026-10-23 06:05:06'),(19,3,'b73b07418348f8b16a22cfe5916158fecb2e0c1357159a7536f3723aa974a596','a306986bfad594e92a465dfd5380094767dc6b6a9ba86d01000e79a06bba1caa','Uji Otomatis','android','::1','','2026-10-23 06:37:50','2026-11-22 06:07:50','2026-10-23 06:07:50','2026-10-23 06:07:51','2026-10-23 06:07:50'),(20,3,'d7b6baca02ba3e3e768bb61cbefd29e4b29f73c5a6efa52ed317dce410aa55dc','a52ee029cf61c7a934153155befc4c4ed97e6e8e0431f5466864fb46b7d6f879','Uji Otomatis','android','::1','','2026-10-23 06:37:51','2026-11-22 06:07:51','2026-10-23 06:07:51','2026-10-23 06:07:51','2026-10-23 06:07:51'),(21,2,'4e5f649fdf46e7c62d8d415d86b8a549768dc09c42ade0274d584e66b6f5bbce','1a4c7967aa30068183f776a0792a4199848dbf57bc2184985f6c8a013f38f057','Uji Peran','android','::1','','2026-10-23 06:37:51','2026-11-22 06:07:51','2026-10-23 06:07:51',NULL,'2026-10-23 06:07:51'),(22,414,'cbfe2f2db520bfca01fb24f924481fc2e172eb4fad68352ce8fd932a2ae57ad4','73aef1f653a97d7538d0e62bf75c96ddd0361c2c5d2c29a1cc6ecee82648fb0d','Uji Peran','android','::1','','2026-10-23 06:37:51','2026-11-22 06:07:51','2026-10-23 06:07:52',NULL,'2026-10-23 06:07:51');
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_subject_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  CONSTRAINT `fk_assignments_author` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assignments_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_assignments_score` CHECK ((`max_score` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (3,'bb4b0c18-ce6c-11f1-9083-38142812b636',5,'Tugas Contoh: Operasi Bilangan Bulat','Kerjakan 10 soal pada buku paket halaman 24. Kumpulkan dalam bentuk foto atau PDF.',NULL,'2026-11-10 23:59:59',100.00,1,1,'2026-10-23 06:03:01','2026-10-23 06:03:01'),(4,'bb4bedca-ce6c-11f1-9083-38142812b636',6,'Tugas Contoh: Teks Deskripsi','Tulis teks deskripsi tentang lingkungan madrasah, minimal 200 kata.',NULL,'2026-11-17 23:59:59',100.00,1,1,'2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `class_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_subjects` (
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
  CONSTRAINT `fk_class_subjects_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_class_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_class_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `class_subjects` WRITE;
/*!40000 ALTER TABLE `class_subjects` DISABLE KEYS */;
INSERT INTO `class_subjects` VALUES (5,1,7,5,4,1,'2026-10-23 06:03:01','2026-10-23 06:03:01'),(6,1,5,NULL,4,1,'2026-10-23 06:03:01','2026-10-23 06:03:01'),(7,1,8,NULL,4,1,'2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `class_subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `homeroom_teacher_id` bigint unsigned DEFAULT NULL,
  `room` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` smallint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_classes_year_name` (`academic_year_id`,`name`),
  KEY `idx_classes_level` (`academic_year_id`,`level`,`sort_order`),
  KEY `idx_classes_homeroom` (`homeroom_teacher_id`),
  CONSTRAINT `fk_classes_homeroom` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_classes_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_classes_level` CHECK ((`level` between 1 and 12))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,1,'7A',7,NULL,NULL,NULL,1,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(2,1,'8A',8,NULL,NULL,NULL,2,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(3,1,'9A',9,NULL,NULL,NULL,3,1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('baru','dibaca','dibalas','arsip') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'baru',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_inbox` (`status`,`created_at`),
  KEY `idx_contact_email` (`email`),
  KEY `fk_contact_replied_by` (`replied_by`),
  CONSTRAINT `fk_contact_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `dka_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dka_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` smallint unsigned NOT NULL DEFAULT '1',
  `status` enum('draft','final','revisi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `draft_flag` tinyint GENERATED ALWAYS AS (if((`status` = _utf8mb4'draft'),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dka_version` (`butir_id`,`version`),
  UNIQUE KEY `uq_dka_one_draft` (`butir_id`,`draft_flag`),
  KEY `idx_dka_butir_status` (`butir_id`,`status`,`version`),
  KEY `idx_dka_editor` (`updated_by`),
  CONSTRAINT `fk_dka_butir` FOREIGN KEY (`butir_id`) REFERENCES `accreditation_butirs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_dka_editor` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_dka_length` CHECK ((char_length(`content`) <= 1000))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dka_entries` WRITE;
/*!40000 ALTER TABLE `dka_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `dka_entries` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evidence` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `checksum` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('draft','valid','expired','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
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
  CONSTRAINT `fk_evidence_period` FOREIGN KEY (`period_id`) REFERENCES `accreditation_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_evidence_source` CHECK (((`file_path` is not null) or (`external_url` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `evidence` WRITE;
/*!40000 ALTER TABLE `evidence` DISABLE KEYS */;
/*!40000 ALTER TABLE `evidence` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `evidence_butir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evidence_butir` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint unsigned NOT NULL,
  `butir_id` bigint unsigned NOT NULL,
  `linked_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_evidence_butir_pair` (`evidence_id`,`butir_id`),
  KEY `idx_evidence_butir_lookup` (`butir_id`,`evidence_id`),
  KEY `fk_evidence_butir_linked_by` (`linked_by`),
  CONSTRAINT `fk_evidence_butir_butir` FOREIGN KEY (`butir_id`) REFERENCES `accreditation_butirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_butir_evidence` FOREIGN KEY (`evidence_id`) REFERENCES `evidence` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_butir_linked_by` FOREIGN KEY (`linked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `evidence_butir` WRITE;
/*!40000 ALTER TABLE `evidence_butir` DISABLE KEYS */;
/*!40000 ALTER TABLE `evidence_butir` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_facilities_listing` (`is_active`,`sort_order`),
  KEY `idx_facilities_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Ruang Kelas','Kelas yang bersih dan terang untuk kegiatan belajar mengajar.',NULL,'classroom',1,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(2,'Perpustakaan','Koleksi buku pelajaran, agama, dan umum untuk menumbuhkan minat baca.',NULL,'book',2,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(3,'Musholla','Pusat kegiatan ibadah, sholat berjamaah, dan pembinaan spiritual.',NULL,'mosque',3,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(4,'Lapangan','Area olahraga dan kegiatan ekstrakurikuler.',NULL,'field',4,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(5,'Laboratorium','Ruang praktik untuk mata pelajaran sains.',NULL,'lab',5,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(6,'Ruang Guru','Ruang kerja dan koordinasi para guru.',NULL,'office',6,1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL);
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `follow_ups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `result_id` bigint unsigned NOT NULL,
  `recommendation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('belum','proses','selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `responsible_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_follow_ups_result` (`result_id`,`status`,`due_date`),
  KEY `idx_follow_ups_open` (`status`,`due_date`),
  KEY `idx_follow_ups_owner` (`responsible_id`),
  CONSTRAINT `fk_follow_ups_owner` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_follow_ups_result` FOREIGN KEY (`result_id`) REFERENCES `accreditation_results` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `follow_ups` WRITE;
/*!40000 ALTER TABLE `follow_ups` DISABLE KEYS */;
/*!40000 ALTER TABLE `follow_ups` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `galleries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `galleries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `album` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_galleries_listing` (`is_active`,`album`,`sort_order`),
  KEY `idx_galleries_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `galleries` WRITE;
/*!40000 ALTER TABLE `galleries` DISABLE KEYS */;
/*!40000 ALTER TABLE `galleries` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `indicator_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indicator_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `butir_id` bigint unsigned NOT NULL,
  `indicator_id` bigint unsigned NOT NULL,
  `status` enum('belum','sebagian','sesuai','tidak_sesuai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked_by` bigint unsigned DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_indicator_checks_pair` (`butir_id`,`indicator_id`),
  KEY `idx_indicator_checks_status` (`butir_id`,`status`),
  KEY `idx_indicator_checks_indicator` (`indicator_id`),
  KEY `fk_indicator_checks_by` (`checked_by`),
  CONSTRAINT `fk_indicator_checks_butir` FOREIGN KEY (`butir_id`) REFERENCES `accreditation_butirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_indicator_checks_by` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_indicator_checks_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `accreditation_indicators` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `indicator_checks` WRITE;
/*!40000 ALTER TABLE `indicator_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `indicator_checks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `main_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `doc_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `status` enum('belum','proses','selesai','revisi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `sort_order` int NOT NULL DEFAULT '0',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_main_documents_period` (`period_id`,`doc_type`,`sort_order`),
  KEY `idx_main_documents_status` (`period_id`,`status`),
  KEY `fk_main_documents_uploader` (`uploaded_by`),
  CONSTRAINT `fk_main_documents_period` FOREIGN KEY (`period_id`) REFERENCES `accreditation_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_main_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `main_documents` WRITE;
/*!40000 ALTER TABLE `main_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `main_documents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_materials_class` (`class_subject_id`,`is_published`,`created_at`),
  KEY `idx_materials_author` (`created_by`),
  CONSTRAINT `fk_materials_author` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_materials_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_materials_source` CHECK (((`file_path` is not null) or (`external_url` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,5,'Materi Contoh: Bilangan Bulat','Ringkasan sifat operasi bilangan bulat beserta contoh soal.',NULL,'https://contoh.invalid/materi/bilangan-bulat.pdf',1,'2026-10-05 08:00:00',1,'2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `checksum` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `alt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folder` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_path` (`path`),
  KEY `idx_media_checksum` (`checksum`),
  KEY `idx_media_folder` (`folder`,`created_at`),
  KEY `idx_media_type` (`mime_type`),
  KEY `fk_media_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` enum('self','blank') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self',
  `location` enum('utama','footer','mobile') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utama',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menus_tree` (`location`,`parent_id`,`sort_order`),
  KEY `idx_menus_active` (`is_active`,`location`),
  KEY `fk_menus_parent` (`parent_id`),
  CONSTRAINT `fk_menus_parent` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,NULL,'Beranda','','self','utama',1,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(2,NULL,'Profil','profil','self','utama',2,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(3,NULL,'Akademik','akademik','self','utama',3,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(4,NULL,'Fasilitas','fasilitas','self','utama',4,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(5,NULL,'Galeri','galeri','self','utama',5,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(6,NULL,'Berita','berita','self','utama',6,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(7,NULL,'PPDB','ppdb','self','utama',7,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(8,NULL,'Kontak','kontak','self','utama',8,1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('assignment','grade','attendance','announcement','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `dedupe_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifications_dedupe` (`user_id`,`dedupe_key`),
  KEY `idx_notifications_poll` (`user_id`,`is_read`,`created_at`),
  KEY `idx_notifications_recent` (`user_id`,`created_at`),
  KEY `idx_notifications_type` (`type`,`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1534 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `meta_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `slug_live` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`slug`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug_live` (`slug_live`),
  KEY `idx_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`,`sort_order`),
  KEY `idx_pages_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `template`, `meta_title`, `meta_description`, `status`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'profil','Profil Madrasah','<p>Halaman profil contoh. Ganti dengan profil madrasah yang sebenarnya.</p>','default',NULL,NULL,'published',1,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(2,'akademik','Program Akademik','<p>Halaman akademik contoh.</p>','default',NULL,NULL,'published',2,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`,`expires_at`),
  KEY `idx_password_resets_cleanup` (`expires_at`,`used_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0',
  `author_id` bigint unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `slug_live` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`slug`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_public` (`public_id`),
  UNIQUE KEY `uq_posts_slug_live` (`slug_live`),
  KEY `idx_posts_slug` (`slug`),
  KEY `idx_posts_feed` (`status`,`published_at`),
  KEY `idx_posts_featured` (`is_featured`,`status`,`published_at`),
  KEY `idx_posts_author` (`author_id`,`created_at`),
  KEY `idx_posts_deleted` (`deleted_at`),
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` (`id`, `public_id`, `title`, `slug`, `excerpt`, `content`, `cover_path`, `status`, `is_featured`, `views`, `author_id`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,NULL,'Contoh Berita Pertama','contoh-berita-pertama','Ini paragraf pembuka berita contoh. Ganti dengan berita sungguhan lewat panel admin.','<p>Isi berita contoh. Halaman ini dipakai untuk menguji tampilan daftar dan halaman detail.</p>',NULL,'published',0,0,1,'2026-09-10 08:00:00','2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(2,NULL,'Contoh Berita Kedua','contoh-berita-kedua','Berita contoh kedua, lebih baru dari yang pertama. Dipakai untuk menguji urutan.','<p>Isi berita contoh kedua.</p>',NULL,'published',0,0,1,'2026-10-01 09:30:00','2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(3,NULL,'Contoh Berita Ketiga','contoh-berita-ketiga','Berita contoh ketiga, paling baru. Muncul di urutan pertama halaman berita.','<p>Isi berita contoh ketiga.</p>',NULL,'published',0,3,1,'2026-10-20 14:15:00','2026-10-23 05:48:12','2026-10-23 06:07:51',NULL),(4,NULL,'Contoh Draf Belum Terbit','contoh-draf-belum-terbit','Berita ini berstatus draf. Tidak boleh muncul di halaman publik.','<p>Isi draf.</p>',NULL,'draft',0,0,1,NULL,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ppdb_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ppdb_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `download_count` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ppdb_docs_listing` (`is_active`,`category`,`sort_order`),
  KEY `idx_ppdb_docs_downloads` (`download_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ppdb_documents` WRITE;
/*!40000 ALTER TABLE `ppdb_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `ppdb_documents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limits` (
  `bucket` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_rate_limits_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES ('api-login:::1',10,'2026-10-23 06:12:52'),('api-refresh:::1',6,'2026-10-23 06:10:06');
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_subject_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedules_teacher_slot` (`teacher_id`,`academic_year_id`,`day_of_week`,`start_time`),
  KEY `idx_schedules_class_day` (`class_subject_id`,`day_of_week`,`start_time`),
  KEY `idx_schedules_year_day` (`academic_year_id`,`day_of_week`,`start_time`),
  CONSTRAINT `fk_schedules_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_schedules_day` CHECK ((`day_of_week` between 1 and 7)),
  CONSTRAINT `chk_schedules_time` CHECK ((`end_time` > `start_time`))
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (9,5,5,1,1,'07:30:00','09:00:00','Ruang 7A',1,'2026-10-23 06:03:01','2026-10-23 06:03:01'),(10,5,5,1,3,'09:15:00','10:45:00','Ruang 7A',1,'2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int unsigned NOT NULL DEFAULT '1',
  `statements` int unsigned NOT NULL DEFAULT '0',
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`),
  KEY `idx_schema_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES ('001','foundation',1,3,'2026-10-23 05:48:07'),('002','identity_core',1,3,'2026-10-23 05:48:07'),('003','identity_security',1,3,'2026-10-23 05:48:07'),('004','cms_content',1,6,'2026-10-23 05:48:08'),('005','cms_support',1,4,'2026-10-23 05:48:08'),('006','academic_core',1,5,'2026-10-23 05:48:09'),('007','academic_learning',1,4,'2026-10-23 05:48:09'),('008','teacher_attendance',1,1,'2026-10-23 05:48:10'),('009','notifications',1,1,'2026-10-23 05:48:10'),('010','accreditation_structure',1,4,'2026-10-23 05:48:10'),('011','accreditation_evidence',1,4,'2026-10-23 05:48:11'),('012','accreditation_documents',1,4,'2026-10-23 05:48:11'),('013','api_support',1,1,'2026-10-23 05:48:11'),('014','public_ids',1,2,'2026-10-23 05:48:12');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schema_seeders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_seeders` (
  `seeder` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `checksum` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seeder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schema_seeders` WRITE;
/*!40000 ALTER TABLE `schema_seeders` DISABLE KEYS */;
INSERT INTO `schema_seeders` VALUES ('001_settings.sql','7f3b177d69ed22b5ca93c6a51a9247ce888d8a5f4f6e1e6f76492eb48a5a0317','2026-10-23 06:03:01'),('DEMO-002_demo_content.sql','5959e9a808867805ece2f54765acf82e09784f4f070965b0b2ae01d7270cbea7','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `schema_seeders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `value_type` enum('string','text','int','bool','json') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `label` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','MTs Al-Huda','string','identitas','Nama madrasah',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(2,'site_subtitle','Gondang','string','identitas','Keterangan nama',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(3,'site_full_name','Madrasah Tsanawiyah Al-Huda Gondang','string','identitas','Nama lengkap',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(4,'site_npsn','','string','identitas','NPSN',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(5,'site_foundation','Yayasan Al-Huda Gondang','string','identitas','Nama yayasan',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(6,'accreditation_grade','A','string','identitas','Peringkat akreditasi',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(7,'accreditation_year','','string','identitas','Tahun akreditasi',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(8,'contact_address','Gondang, Kabupaten Tulungagung, Jawa Timur','text','kontak','Alamat lengkap',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(9,'contact_phone','','string','kontak','Telepon',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(10,'contact_whatsapp','','string','kontak','WhatsApp',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(11,'contact_email','info@mts-alhuda.sch.id','string','kontak','Email resmi',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(12,'contact_hours','Senin - Sabtu, 07.00 - 15.10 WIB','string','kontak','Jam operasional',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(13,'contact_map_lat','','string','kontak','Lintang lokasi',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(14,'contact_map_lng','','string','kontak','Bujur lokasi',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(15,'social_facebook','','string','sosial','Facebook',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(16,'social_instagram','','string','sosial','Instagram',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(17,'social_youtube','','string','sosial','YouTube',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(18,'social_tiktok','','string','sosial','TikTok',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(19,'meta_title','MTs Al-Huda Gondang — Madrasah Berakhlak, Berprestasi','string','seo','Meta title',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(20,'meta_description','Madrasah Tsanawiyah Al-Huda Gondang. Membentuk generasi Qur\'ani, berakhlak mulia, dan berprestasi.','text','seo','Meta description',1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(21,'attendance_check_in_time','07:00','string','presensi','Jam masuk',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(22,'attendance_late_tolerance','15','int','presensi','Toleransi terlambat (menit)',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(23,'attendance_check_out_time','15:10','string','presensi','Jam pulang',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(24,'security_max_login_attempts','5','int','keamanan','Batas percobaan login gagal',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(25,'security_lockout_minutes','15','int','keamanan','Durasi kunci akun (menit)',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(26,'security_session_timeout','120','int','keamanan','Sesi web berakhir (menit)',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(27,'security_api_access_ttl','30','int','keamanan','Masa aktif token akses (menit)',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(28,'security_api_refresh_ttl','30','int','keamanan','Masa aktif token refresh (hari)',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(29,'security_force_password_change','1','bool','keamanan','Wajib ganti sandi saat login pertama',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(30,'security_audit_log','1','bool','keamanan','Catat log audit',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(31,'mail_enabled','0','bool','email','Notifikasi email aktif',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(32,'mail_host','','string','email','SMTP host',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(33,'mail_port','587','int','email','SMTP port',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(34,'mail_encryption','tls','string','email','Enkripsi SMTP',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(35,'mail_username','','string','email','SMTP username',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(36,'mail_password','','string','email','SMTP password',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(37,'mail_from_address','','string','email','Alamat pengirim',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(38,'mail_from_name','MTs Al-Huda Gondang','string','email','Nama pengirim',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(39,'mail_on_password_reset','1','bool','email','Kirim email reset sandi',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(40,'mail_on_account_created','1','bool','email','Kirim email akun baru',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(41,'mail_on_new_assignment','1','bool','email','Kirim email tugas baru',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(42,'announcement_active','0','bool','pengumuman','Tampilkan pengumuman',0,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(43,'announcement_text','','text','pengumuman','Isi pengumuman',0,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `nis` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `gender` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `birth_place` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `guardian_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (5,NULL,414,'2025001',NULL,1,'P',NULL,NULL,NULL,NULL,NULL,'2025-07-01',1,'2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` enum('agama','umum','muatan_lokal','ekstrakurikuler') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'umum',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subjects_code` (`code`),
  KEY `idx_subjects_group` (`group_name`,`sort_order`),
  KEY `idx_subjects_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'QH','Al-Qur\'an Hadis','agama',1,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(2,'AA','Akidah Akhlak','agama',2,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(3,'FQ','Fikih','agama',3,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(4,'SKI','Sejarah Kebudayaan Islam','agama',4,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(5,'BIN','Bahasa Indonesia','umum',10,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(6,'BIG','Bahasa Inggris','umum',11,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(7,'MTK','Matematika','umum',12,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(8,'IPA','Ilmu Pengetahuan Alam','umum',13,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(9,'IPS','Ilmu Pengetahuan Sosial','umum',14,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(10,'PKN','Pendidikan Pancasila','umum',15,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(11,'SBK','Seni Budaya','umum',16,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(12,'PJOK','Pendidikan Jasmani','umum',17,1,'2026-10-23 05:48:12','2026-10-23 05:48:12'),(13,'BTQ','Baca Tulis Al-Qur\'an','muatan_lokal',20,1,'2026-10-23 05:48:12','2026-10-23 05:48:12');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_submissions_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_submissions_score` CHECK (((`score` is null) or (`score` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('rendah','sedang','tinggi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sedang',
  `status` enum('belum','proses','selesai','batal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `completed_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_assignee` (`assigned_to`,`status`,`due_date`),
  KEY `idx_tasks_period` (`period_id`,`status`,`due_date`),
  KEY `idx_tasks_overdue` (`due_date`,`status`),
  KEY `fk_tasks_creator` (`created_by`),
  CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_period` FOREIGN KEY (`period_id`) REFERENCES `accreditation_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teacher_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `attendance_type` enum('daily','session') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `check_in_at` datetime DEFAULT NULL,
  `check_out_at` datetime DEFAULT NULL,
  `status` enum('hadir','terlambat','izin','sakit','dinas','alpa') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hadir',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` enum('self','operator','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unique_key` varchar(96) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`teacher_id`,_utf8mb4'-',`attendance_date`,_utf8mb4'-',`attendance_type`,_utf8mb4'-',ifnull(`schedule_id`,0))) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teacher_attendances_key` (`unique_key`),
  KEY `idx_attendance_daily` (`attendance_date`,`status`),
  KEY `idx_attendance_teacher` (`teacher_id`,`attendance_date`),
  KEY `idx_attendance_schedule` (`schedule_id`,`attendance_date`),
  KEY `idx_attendance_unverified` (`verified_at`,`attendance_date`),
  KEY `fk_attendance_verifier` (`verified_by`),
  CONSTRAINT `fk_attendance_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_attendance_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_attendance_checkout` CHECK (((`check_out_at` is null) or (`check_in_at` is null) or (`check_out_at` >= `check_in_at`))),
  CONSTRAINT `chk_attendance_type` CHECK ((((`attendance_type` = _utf8mb4'daily') and (`schedule_id` is null)) or ((`attendance_type` = _utf8mb4'session') and (`schedule_id` is not null))))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teacher_attendances` WRITE;
/*!40000 ALTER TABLE `teacher_attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_attendances` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `nip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nuptk` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `employment_status` enum('pns','pppk','honorer','gtty') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'honorer',
  `subject_specialty` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teachers_user` (`user_id`),
  UNIQUE KEY `uq_teachers_nip` (`nip`),
  UNIQUE KEY `uq_teachers_nuptk` (`nuptk`),
  UNIQUE KEY `uq_teachers_public` (`public_id`),
  KEY `idx_teachers_status` (`employment_status`),
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (5,NULL,2,'198501012010011001',NULL,'L','pns','Matematika','2010-01-01','2026-10-23 06:03:01','2026-10-23 06:03:01');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','operator','kepala','guru','siswa','akreditasi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `role_label` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `failed_login_attempts` int unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role_active` (`role`,`is_active`),
  KEY `idx_users_deleted` (`deleted_at`),
  KEY `idx_users_locked` (`locked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=1223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Operator Contoh','operator@contoh.local','operator','$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK','operator','Operator Madrasah',NULL,NULL,1,1,0,NULL,NULL,NULL,'2026-10-23 05:48:12','2026-10-23 05:48:12',NULL),(2,'Guru Contoh','guru@contoh.local','guru','$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK','guru','Guru',NULL,NULL,1,1,0,NULL,'2026-10-23 06:07:51','::1','2026-10-23 05:48:12','2026-10-23 06:07:51',NULL),(3,'Administrator Madrasah','admin@mts-alhuda.sch.id','admin','$2y$10$.aBNtZkSEV0JKnVdksmgp.8AO922tKcKmYHopbjzsrNKchFRCnGn2','admin','Super Admin',NULL,NULL,1,1,0,NULL,'2026-10-23 06:07:50','::1','2026-10-23 05:48:13','2026-10-23 14:17:11',NULL),(414,'Siswa Contoh','siswa@contoh.local','siswa','$2y$10$f.KFnsTga..ZEw2LIIebXuY4TeZ/hKuuyDNc93i1JaIkCOtdFU.UK','siswa','Siswa',NULL,NULL,1,1,0,NULL,'2026-10-23 06:07:51','::1','2026-10-23 06:03:01','2026-10-23 06:07:51',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

