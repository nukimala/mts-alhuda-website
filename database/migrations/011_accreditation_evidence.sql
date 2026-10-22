-- ============================================================================
-- 011 — Akreditasi IA2024: bukti & DKA
-- evidence, evidence_butir, dka_entries, indicator_checks
--
-- Ini inti fitur Bank Bukti.
--
-- `evidence_butir` adalah tabel pivot: satu berkas bukti bisa dipakai untuk
-- banyak butir sekaligus. Itu bukan kemewahan — banyak bukti memang relevan
-- untuk lebih dari satu butir (mis. SK pembagian tugas menjawab butir
-- pendidik sekaligus butir kurikulum). Menyalin berkasnya per butir akan
-- membuat disk penuh dan versi bukti jadi tidak sinkron.
--
-- Keputusan K-5: hanya boleh ada SATU draft DKA per butir.
-- Dua orang menulis draft butir yang sama lalu saling menimpa adalah
-- kegagalan yang paling sulit dideteksi setelah kejadian. Karena MariaDB
-- tidak punya partial index, dipakai generated column `draft_flag`.
--
-- Keputusan K-14 (baru): `evidence.checksum` unik PER PERIODE, bukan global.
-- Berkas identik yang diunggah di dua periode akreditasi berbeda adalah dua
-- bukti yang sah — periodenya berbeda.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS evidence (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id    CHAR(36)        NULL,              -- K-7

    period_id    BIGINT UNSIGNED NOT NULL,

    title        VARCHAR(200)    NOT NULL,
    description  TEXT            NULL,

    file_path    VARCHAR(255)    NULL,
    external_url VARCHAR(255)    NULL,
    mime_type    VARCHAR(100)    NULL,
    file_size    BIGINT UNSIGNED NULL,
    checksum     CHAR(64)        NULL,              -- sha256 hex

    -- NULL = tidak berbatas waktu. Index di bawah dipakai untuk query
    -- "bukti yang akan kedaluwarsa dalam 30 hari".
    valid_until  DATE            NULL,
    status       ENUM('draft','valid','expired','rejected') NOT NULL DEFAULT 'draft',

    uploaded_by  BIGINT UNSIGNED NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME        NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_evidence_public (public_id),
    UNIQUE KEY uq_evidence_checksum (period_id, checksum),
    KEY idx_evidence_period (period_id, status, created_at),
    KEY idx_evidence_expiry (valid_until, status),
    KEY idx_evidence_uploader (uploaded_by),
    KEY idx_evidence_deleted (deleted_at),
    CONSTRAINT fk_evidence_period FOREIGN KEY (period_id) REFERENCES accreditation_periods (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_evidence_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_evidence_source CHECK (file_path IS NOT NULL OR external_url IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot bukti <-> butir. Keduanya CASCADE: melepas tautan tidak boleh
-- menghapus bukti maupun butirnya, hanya baris pivot ini.
CREATE TABLE IF NOT EXISTS evidence_butir (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evidence_id BIGINT UNSIGNED NOT NULL,
    butir_id    BIGINT UNSIGNED NOT NULL,

    linked_by   BIGINT UNSIGNED NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_evidence_butir_pair (evidence_id, butir_id),
    -- Arah sebaliknya: "bukti apa saja untuk butir ini"
    KEY idx_evidence_butir_lookup (butir_id, evidence_id),
    CONSTRAINT fk_evidence_butir_evidence FOREIGN KEY (evidence_id) REFERENCES evidence (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_evidence_butir_butir FOREIGN KEY (butir_id) REFERENCES accreditation_butirs (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_evidence_butir_linked_by FOREIGN KEY (linked_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dka_entries (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    butir_id   BIGINT UNSIGNED NOT NULL,

    content    TEXT            NOT NULL,
    version    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status     ENUM('draft','final','revisi') NOT NULL DEFAULT 'draft',

    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    draft_flag TINYINT AS (IF(status = 'draft', 1, NULL)) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_dka_version (butir_id, version),
    -- K-5: satu draft per butir. Draft kedua ditolak database, bukan aplikasi.
    UNIQUE KEY uq_dka_one_draft (butir_id, draft_flag),
    KEY idx_dka_butir_status (butir_id, status, version),
    KEY idx_dka_editor (updated_by),
    CONSTRAINT fk_dka_butir FOREIGN KEY (butir_id) REFERENCES accreditation_butirs (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dka_editor FOREIGN KEY (updated_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    -- Batas 1000 karakter ditegakkan database DAN aplikasi. Dua lapis,
    -- karena satu lapis selalu bisa dilewati.
    CONSTRAINT chk_dka_length CHECK (CHAR_LENGTH(content) <= 1000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status per indikator dalam satu butir.
-- UNIQUE (butir_id, indicator_id): satu indikator dinilai sekali per butir.
CREATE TABLE IF NOT EXISTS indicator_checks (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    butir_id     BIGINT UNSIGNED NOT NULL,
    indicator_id BIGINT UNSIGNED NOT NULL,

    status       ENUM('belum','sebagian','sesuai','tidak_sesuai') NOT NULL DEFAULT 'belum',
    note         VARCHAR(500)    NULL,

    checked_by   BIGINT UNSIGNED NULL,
    checked_at   DATETIME        NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_indicator_checks_pair (butir_id, indicator_id),
    KEY idx_indicator_checks_status (butir_id, status),
    KEY idx_indicator_checks_indicator (indicator_id),
    CONSTRAINT fk_indicator_checks_butir FOREIGN KEY (butir_id) REFERENCES accreditation_butirs (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_indicator_checks_indicator FOREIGN KEY (indicator_id) REFERENCES accreditation_indicators (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_indicator_checks_by FOREIGN KEY (checked_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS indicator_checks;
DROP TABLE IF EXISTS dka_entries;
DROP TABLE IF EXISTS evidence_butir;
DROP TABLE IF EXISTS evidence;
