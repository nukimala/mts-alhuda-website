-- ============================================================================
-- 013 — Penunjang API
-- rate_limits
--
-- Tabel kecil untuk membatasi percobaan login dan penyalahgunaan endpoint.
-- Tanpa Redis, tanpa memori bersama. Baris kedaluwarsa dibersihkan berkala
-- oleh RateLimiter saat ada request masuk.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket     VARCHAR(191) NOT NULL,
    attempts   INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME     NOT NULL,
    PRIMARY KEY (bucket),
    KEY idx_rate_limits_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS rate_limits;
