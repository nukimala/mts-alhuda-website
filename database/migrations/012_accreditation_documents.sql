-- ============================================================================
-- 012 — Akreditasi IA2024: dokumen utama, tugas, hasil, tindak lanjut
-- main_documents, tasks, accreditation_results, follow_ups
--
-- Urutan pembuatan wajib: accreditation_results sebelum follow_ups, karena
-- tindak lanjut menunjuk ke hasil.
--
-- Keputusan K-15 (baru): `main_documents.doc_type` memakai VARCHAR, bukan ENUM.
-- Daftar dokumen utama mengikuti instrumen IA2024 yang teks resminya belum
-- diterima dari mitra (K-10). ENUM yang salah menutup pintu koreksi tanpa
-- migrasi baru; VARCHAR membiarkan seeder menyesuaikan diri.
--
-- Semua FK akreditasi memakai ON DELETE RESTRICT. Data akreditasi tidak boleh
-- hilang diam-diam karena seseorang menghapus periode.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS main_documents (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id   BIGINT UNSIGNED NOT NULL,

    doc_type    VARCHAR(60)     NOT NULL,           -- K-15
    title       VARCHAR(200)    NOT NULL,
    description TEXT            NULL,

    file_path   VARCHAR(255)    NULL,
    external_url VARCHAR(255)   NULL,
    mime_type   VARCHAR(100)    NULL,
    file_size   BIGINT UNSIGNED NULL,

    status      ENUM('belum','proses','selesai','revisi') NOT NULL DEFAULT 'belum',
    sort_order  INT             NOT NULL DEFAULT 0,

    uploaded_by BIGINT UNSIGNED NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_main_documents_period (period_id, doc_type, sort_order),
    KEY idx_main_documents_status (period_id, status),
    CONSTRAINT fk_main_documents_period FOREIGN KEY (period_id) REFERENCES accreditation_periods (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_main_documents_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id   BIGINT UNSIGNED NOT NULL,

    title       VARCHAR(200)    NOT NULL,
    description TEXT            NULL,

    assigned_to BIGINT UNSIGNED NULL,
    due_date    DATE            NULL,
    priority    ENUM('rendah','sedang','tinggi') NOT NULL DEFAULT 'sedang',
    status      ENUM('belum','proses','selesai','batal') NOT NULL DEFAULT 'belum',
    completed_at DATETIME       NULL,

    created_by  BIGINT UNSIGNED NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    -- Papan tugas: "tugas saya yang belum selesai, urut tenggat"
    KEY idx_tasks_assignee (assigned_to, status, due_date),
    KEY idx_tasks_period (period_id, status, due_date),
    KEY idx_tasks_overdue (due_date, status),
    CONSTRAINT fk_tasks_period FOREIGN KEY (period_id) REFERENCES accreditation_periods (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_assignee FOREIGN KEY (assigned_to) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accreditation_results (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id   BIGINT UNSIGNED NOT NULL,

    grade       VARCHAR(10)     NULL,               -- "A", "B", "Unggul"
    score       DECIMAL(6,2)    NULL,
    notes       TEXT            NULL,

    decided_at  DATETIME        NULL,
    decided_by  BIGINT UNSIGNED NULL,
    valid_until DATE            NULL,               -- masa berlaku sertifikat

    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_acc_results_period (period_id),
    KEY idx_acc_results_grade (grade, decided_at),
    CONSTRAINT fk_acc_results_period FOREIGN KEY (period_id) REFERENCES accreditation_periods (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_acc_results_by FOREIGN KEY (decided_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_acc_results_score CHECK (score IS NULL OR (score >= 0 AND score <= 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS follow_ups (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    result_id      BIGINT UNSIGNED NOT NULL,

    recommendation TEXT            NOT NULL,
    status         ENUM('belum','proses','selesai') NOT NULL DEFAULT 'belum',
    due_date       DATE            NULL,
    completed_at   DATETIME        NULL,

    responsible_id BIGINT UNSIGNED NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_follow_ups_result (result_id, status, due_date),
    KEY idx_follow_ups_open (status, due_date),
    KEY idx_follow_ups_owner (responsible_id),
    CONSTRAINT fk_follow_ups_result FOREIGN KEY (result_id) REFERENCES accreditation_results (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_follow_ups_owner FOREIGN KEY (responsible_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS follow_ups;
DROP TABLE IF EXISTS accreditation_results;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS main_documents;
