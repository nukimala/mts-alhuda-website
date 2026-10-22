-- ============================================================================
-- 003 — Keamanan identitas
-- api_tokens, password_resets, activity_logs
--
-- Prinsip yang dipakai:
--   * Token TIDAK PERNAH disimpan mentah. Yang masuk database hanya SHA-256 hex.
--     Kalau database bocor, token yang ada di sana tidak bisa dipakai.
--   * Access token 30 menit, refresh token 30 hari.
--   * Refresh selalu berotasi: token lama dihapus, token baru dibuat.
--   * Tidak ada tabel device token, tidak ada kolom FCM. Notifikasi push
--     pihak ketiga tidak dipakai.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS api_tokens (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id            BIGINT UNSIGNED NOT NULL,

    access_token_hash  CHAR(64)        NOT NULL,   -- sha256 hex
    refresh_token_hash CHAR(64)        NOT NULL,   -- sha256 hex

    device_name        VARCHAR(120)    NULL,
    platform           ENUM('android','ios','web','unknown') NOT NULL DEFAULT 'unknown',
    ip_address         VARCHAR(45)     NULL,
    user_agent         VARCHAR(255)    NULL,

    access_expires_at  DATETIME        NOT NULL,
    refresh_expires_at DATETIME        NOT NULL,
    last_used_at       DATETIME        NULL,
    revoked_at         DATETIME        NULL,

    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_api_tokens_access (access_token_hash),
    UNIQUE KEY uq_api_tokens_refresh (refresh_token_hash),
    KEY idx_api_tokens_user_live (user_id, revoked_at, refresh_expires_at),
    KEY idx_api_tokens_refresh_ttl (refresh_expires_at),
    CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NOT NULL,
    token_hash   CHAR(64)        NOT NULL,   -- sha256 hex
    expires_at   DATETIME        NOT NULL,
    used_at      DATETIME        NULL,
    requested_ip VARCHAR(45)     NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_user (user_id, expires_at),
    KEY idx_password_resets_cleanup (expires_at, used_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log audit. user_id NULL kalau aksi dilakukan tanpa sesi (misal login gagal
-- dengan email yang tidak terdaftar). ON DELETE SET NULL supaya log tidak
-- hilang saat akun dihapus — jejaknya tetap perlu ada.
CREATE TABLE IF NOT EXISTS activity_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NULL,
    action      VARCHAR(80)     NOT NULL,
    entity      VARCHAR(60)     NULL,
    entity_id   BIGINT UNSIGNED NULL,
    description VARCHAR(255)    NULL,
    metadata    JSON            NULL,
    ip_address  VARCHAR(45)     NULL,
    user_agent  VARCHAR(255)    NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_logs_user (user_id, created_at),
    KEY idx_activity_logs_entity (entity, entity_id, created_at),
    KEY idx_activity_logs_action (action, created_at),
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS api_tokens;
