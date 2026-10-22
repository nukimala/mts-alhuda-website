-- ============================================================================
-- 010 — Akreditasi IA2024: struktur instrumen
-- accreditation_periods, accreditation_components, accreditation_butirs,
-- accreditation_indicators
--
-- Hierarki: periode -> komponen -> butir -> indikator.
-- Satu periode akreditasi punya beberapa komponen; satu komponen punya
-- beberapa butir; satu butir punya beberapa indikator penilaian.
--
-- Keputusan K-12 (baru): kolom teks butir dinamai `butir_text`, bukan `text`.
-- `TEXT` adalah tipe data di SQL. Memakainya sebagai nama kolom memaksa
-- backtick di setiap query — persis jebakan yang sudah dihindari pada
-- `settings.setting_key` (K-6). Lebih murah dihindari sekarang.
--
-- Keputusan K-13 (baru): `accreditation_components.code` dipakai sebagai
-- penanda komponen (mis. "A", "B"), sementara `number` disimpan sebagai
-- urutan angka. Instrumen IA2024 memakai penomoran bertingkat yang tidak
-- selalu bisa diurutkan sebagai teks.
--
-- Teks 33 butir TIDAK diisi di sini (K-10). Mitra menyerahkan instrumen
-- resmi; seeder hanya membuat kerangka kosong.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS accreditation_periods (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name        VARCHAR(80)     NOT NULL,           -- "Akreditasi 2026"
    start_date  DATE            NULL,
    end_date    DATE            NULL,

    status      ENUM('draft','berjalan','selesai','arsip') NOT NULL DEFAULT 'draft',
    is_active   TINYINT(1)      NOT NULL DEFAULT 0,
    note        VARCHAR(255)    NULL,

    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    active_flag TINYINT AS (IF(is_active = 1, 1, NULL)) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_acc_periods_name (name),
    UNIQUE KEY uq_acc_periods_active (active_flag),
    KEY idx_acc_periods_status (status, start_date),
    CONSTRAINT chk_acc_periods_range CHECK (end_date IS NULL OR start_date IS NULL OR end_date > start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accreditation_components (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id  BIGINT UNSIGNED NOT NULL,

    code       VARCHAR(10)     NOT NULL,            -- "A", "B", "C"
    name       VARCHAR(200)    NOT NULL,
    number     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    weight     DECIMAL(6,2)    NULL,
    sort_order INT             NOT NULL DEFAULT 0,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_acc_components_code (period_id, code),
    KEY idx_acc_components_order (period_id, sort_order, number),
    CONSTRAINT fk_acc_components_period FOREIGN KEY (period_id) REFERENCES accreditation_periods (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accreditation_butirs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    component_id BIGINT UNSIGNED NOT NULL,

    number       SMALLINT UNSIGNED NOT NULL,        -- nomor butir dalam komponen
    code         VARCHAR(20)     NULL,              -- penomoran bertingkat, mis. "1.2.3"
    butir_text   TEXT            NOT NULL,          -- K-12: bukan `text`

    status       ENUM('kosong','proses','lengkap','terverifikasi') NOT NULL DEFAULT 'kosong',
    sort_order   INT             NOT NULL DEFAULT 0,

    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_acc_butirs_number (component_id, number),
    KEY idx_acc_butirs_order (component_id, sort_order, number),
    KEY idx_acc_butirs_status (status),
    CONSTRAINT fk_acc_butirs_component FOREIGN KEY (component_id) REFERENCES accreditation_components (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accreditation_indicators (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    butir_id       BIGINT UNSIGNED NOT NULL,

    number         SMALLINT UNSIGNED NOT NULL,
    indicator_text TEXT            NOT NULL,
    sort_order     INT             NOT NULL DEFAULT 0,

    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_acc_indicators_number (butir_id, number),
    KEY idx_acc_indicators_order (butir_id, sort_order, number),
    CONSTRAINT fk_acc_indicators_butir FOREIGN KEY (butir_id) REFERENCES accreditation_butirs (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS accreditation_indicators;
DROP TABLE IF EXISTS accreditation_butirs;
DROP TABLE IF EXISTS accreditation_components;
DROP TABLE IF EXISTS accreditation_periods;
