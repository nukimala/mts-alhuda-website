-- ============================================================================
-- 017 — Topik pembelajaran (pengelompokan materi & tugas per minggu)
--
-- Latar: aplikasi mobile mengelompokkan materi/tugas per "Minggu N — Nama".
-- Sampai 016 tidak ada tabel topik; grouping itu tidak punya tempat di DB.
--
-- Keputusan:
--   - Topik dimiliki oleh mata_pelajaran_kelas (class_subject_id) — unit ajar
--     yang sama dengan materi & tugas, jadi guru tidak bisa memindahkan
--     topik ke kelas/mapel yang salah.
--   - UNIQUE (class_subject_id, minggu) mencegah dua topik di minggu yang
--     sama untuk rombongan belajar + mapel yang sama.
--   - kelas_id / guru_id / mata_pelajaran_id denormalisasi (pola yang sama
--     dengan materi & tugas di 016) supaya app/API bisa filter tanpa join.
--   - topik_id di materi & tugas boleh NULL = "Tanpa Topik" (baris lama
--     tetap sah).
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS `topik` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `class_subject_id`  BIGINT UNSIGNED NOT NULL,
  -- Denormalisasi (pola 016) — boleh NULL, diisi app/API
  `kelas_id`          BIGINT UNSIGNED NULL,
  `mata_pelajaran_id` BIGINT UNSIGNED NULL,
  `guru_id`           BIGINT UNSIGNED NULL,

  `minggu`            TINYINT UNSIGNED NOT NULL,      -- pertemuan ke-N (1..20)
  `nama`              VARCHAR(150)     NOT NULL,      -- mis. "Kurva", "FPB dan KPK"
  `deskripsi`         TEXT             NULL,
  `is_active`         TINYINT(1)       NOT NULL DEFAULT 1,

  `created_by`        BIGINT UNSIGNED  NULL,
  `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME         NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_topik_mpk_minggu` (`class_subject_id`, `minggu`),
  KEY `idx_topik_kelas` (`kelas_id`),
  KEY `idx_topik_mapel` (`mata_pelajaran_id`),
  KEY `idx_topik_guru`  (`guru_id`),
  CONSTRAINT `fk_topik_class_subject` FOREIGN KEY (`class_subject_id`)
      REFERENCES `mata_pelajaran_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_topik_creator` FOREIGN KEY (`created_by`)
      REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `materi`
  ADD COLUMN IF NOT EXISTS `topik_id` BIGINT UNSIGNED NULL AFTER `mata_pelajaran_id`;
ALTER TABLE `tugas`
  ADD COLUMN IF NOT EXISTS `topik_id` BIGINT UNSIGNED NULL AFTER `mata_pelajaran_id`;

ALTER TABLE `materi`
  ADD CONSTRAINT `fk_materi_topik` FOREIGN KEY (`topik_id`)
      REFERENCES `topik` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `tugas`
  ADD CONSTRAINT `fk_tugas_topik` FOREIGN KEY (`topik_id`)
      REFERENCES `topik` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Views kompatibilitas: kolom topik_id ikut diekspos (additive, backward compatible)
CREATE OR REPLACE VIEW `materials` AS
SELECT `materi`.`id` AS `id`,`materi`.`class_subject_id` AS `class_subject_id`,`materi`.`kelas_id` AS `kelas_id`,`materi`.`guru_id` AS `guru_id`,`materi`.`mata_pelajaran_id` AS `mata_pelajaran_id`,`materi`.`topik_id` AS `topik_id`,`materi`.`title` AS `judul`,`materi`.`title` AS `title`,`materi`.`description` AS `deskripsi`,`materi`.`description` AS `description`,`materi`.`file_path` AS `file_path`,`materi`.`file_url` AS `file_url`,`materi`.`external_url` AS `external_url`,`materi`.`is_published` AS `is_published`,`materi`.`published_at` AS `published_at`,`materi`.`created_by` AS `created_by`,`materi`.`created_at` AS `created_at`,`materi`.`updated_at` AS `updated_at`
FROM `materi`;

CREATE OR REPLACE VIEW `assignments` AS
SELECT `tugas`.`id` AS `id`,`tugas`.`public_id` AS `public_id`,`tugas`.`class_subject_id` AS `class_subject_id`,`tugas`.`kelas_id` AS `kelas_id`,`tugas`.`guru_id` AS `guru_id`,`tugas`.`mata_pelajaran_id` AS `mata_pelajaran_id`,`tugas`.`topik_id` AS `topik_id`,`tugas`.`title` AS `judul`,`tugas`.`title` AS `title`,`tugas`.`description` AS `instruksi`,`tugas`.`description` AS `description`,`tugas`.`attachment_path` AS `file_tugas_url`,`tugas`.`attachment_path` AS `attachment_path`,`tugas`.`due_at` AS `deadline`,`tugas`.`due_at` AS `due_at`,`tugas`.`max_score` AS `max_score`,`tugas`.`is_published` AS `is_published`,`tugas`.`created_by` AS `created_by`,`tugas`.`created_at` AS `created_at`,`tugas`.`updated_at` AS `updated_at`
FROM `tugas`;

-- View kompatibilitas `topiks` (selaras gaya materials/assignments)
CREATE OR REPLACE VIEW `topiks` AS
SELECT `topik`.`id` AS `id`,`topik`.`class_subject_id` AS `class_subject_id`,`topik`.`kelas_id` AS `kelas_id`,`topik`.`guru_id` AS `guru_id`,`topik`.`mata_pelajaran_id` AS `mata_pelajaran_id`,`topik`.`minggu` AS `minggu`,`topik`.`nama` AS `nama`,`topik`.`deskripsi` AS `deskripsi`,`topik`.`is_active` AS `is_active`,`topik`.`created_by` AS `created_by`,`topik`.`created_at` AS `created_at`,`topik`.`updated_at` AS `updated_at`
FROM `topik`;

-- @DOWN

DROP VIEW IF EXISTS `topiks`;

CREATE OR REPLACE VIEW `assignments` AS
SELECT `tugas`.`id` AS `id`,`tugas`.`public_id` AS `public_id`,`tugas`.`class_subject_id` AS `class_subject_id`,`tugas`.`kelas_id` AS `kelas_id`,`tugas`.`guru_id` AS `guru_id`,`tugas`.`mata_pelajaran_id` AS `mata_pelajaran_id`,`tugas`.`title` AS `judul`,`tugas`.`title` AS `title`,`tugas`.`description` AS `instruksi`,`tugas`.`description` AS `description`,`tugas`.`attachment_path` AS `file_tugas_url`,`tugas`.`attachment_path` AS `attachment_path`,`tugas`.`due_at` AS `deadline`,`tugas`.`due_at` AS `due_at`,`tugas`.`max_score` AS `max_score`,`tugas`.`is_published` AS `is_published`,`tugas`.`created_by` AS `created_by`,`tugas`.`created_at` AS `created_at`,`tugas`.`updated_at` AS `updated_at`
FROM `tugas`;

CREATE OR REPLACE VIEW `materials` AS
SELECT `materi`.`id` AS `id`,`materi`.`class_subject_id` AS `class_subject_id`,`materi`.`kelas_id` AS `kelas_id`,`materi`.`guru_id` AS `guru_id`,`materi`.`mata_pelajaran_id` AS `mata_pelajaran_id`,`materi`.`title` AS `judul`,`materi`.`title` AS `title`,`materi`.`description` AS `deskripsi`,`materi`.`description` AS `description`,`materi`.`file_path` AS `file_path`,`materi`.`file_url` AS `file_url`,`materi`.`external_url` AS `external_url`,`materi`.`is_published` AS `is_published`,`materi`.`published_at` AS `published_at`,`materi`.`created_by` AS `created_by`,`materi`.`created_at` AS `created_at`,`materi`.`updated_at` AS `updated_at`
FROM `materi`;

ALTER TABLE `tugas` DROP FOREIGN KEY `fk_tugas_topik`;
ALTER TABLE `materi` DROP FOREIGN KEY `fk_materi_topik`;
ALTER TABLE `tugas` DROP COLUMN `topik_id`;
ALTER TABLE `materi` DROP COLUMN `topik_id`;
DROP TABLE IF EXISTS `topik`;
