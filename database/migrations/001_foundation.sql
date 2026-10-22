-- ============================================================================
-- 001 — Fondasi
-- Tabel sistem: pelacak migrasi, pelacak seeder, dan pengaturan situs.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS schema_migrations (
    version     VARCHAR(20)  NOT NULL,
    name        VARCHAR(191) NOT NULL,
    batch       INT UNSIGNED NOT NULL DEFAULT 1,
    statements  INT UNSIGNED NOT NULL DEFAULT 0,
    executed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version),
    KEY idx_schema_migrations_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_seeders (
    seeder      VARCHAR(191) NOT NULL,
    checksum    CHAR(64)     NOT NULL,
    executed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (seeder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catatan K-6: kolom sengaja dinamai setting_key / setting_value,
-- bukan `key` / `value`, supaya tidak bertabrakan dengan kata kunci SQL.
CREATE TABLE IF NOT EXISTS settings (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(120)    NOT NULL,
    setting_value TEXT            NULL,
    value_type    ENUM('string','text','int','bool','json') NOT NULL DEFAULT 'string',
    group_name    VARCHAR(60)     NOT NULL DEFAULT 'umum',
    label         VARCHAR(160)    NULL,
    is_public     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key),
    KEY idx_settings_group (group_name, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS schema_seeders;
DROP TABLE IF EXISTS schema_migrations;
