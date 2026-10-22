-- ============================================================================
-- 009 — Notifikasi
-- notifications
--
-- Notifikasi in-app. Aplikasi mobile memakai polling ke endpoint
-- "notifikasi belum dibaca", bukan push pihak ketiga.
--
-- Karena itu index utamanya adalah index polling:
--   WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 20
-- dan index itulah yang menentukan apakah polling terasa ringan atau berat
-- saat 300 siswa membuka aplikasi bersamaan.
--
-- Tidak ada tabel device token. Tidak ada kolom FCM. Tidak ada service
-- account. Sesuai keputusan bebas Firebase.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id    BIGINT UNSIGNED NOT NULL,
    title      VARCHAR(160)    NOT NULL,
    body       VARCHAR(500)    NULL,
    link       VARCHAR(255)    NULL,

    type       ENUM('assignment','grade','attendance','announcement','system') NOT NULL DEFAULT 'system',

    -- Penanda sumber, supaya satu kejadian tidak mengirim notifikasi dua kali.
    -- Contoh: "assignment:12" saat guru menilai tugas 12.
    dedupe_key VARCHAR(120)    NULL,

    is_read    TINYINT(1)      NOT NULL DEFAULT 0,
    read_at    DATETIME        NULL,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    -- Index polling utama. Urutan kolom mengikuti pola query, bukan selera.
    KEY idx_notifications_poll (user_id, is_read, created_at),
    KEY idx_notifications_recent (user_id, created_at),
    KEY idx_notifications_type (type, created_at),
    UNIQUE KEY uq_notifications_dedupe (user_id, dedupe_key),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS notifications;
