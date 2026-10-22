-- ============================================================================
-- 004 — Konten web (CMS), bagian isi
-- posts, pages, galleries, facilities, announcements, agendas
--
-- Semua tabel di domain ini dibaca oleh halaman publik, jadi yang penting
-- adalah index untuk query halaman publik — bukan untuk admin.
--
-- Keputusan K-11 (baru): slug unik hanya di antara baris yang belum dihapus.
-- MariaDB tidak punya partial index, jadi dipakai generated column
-- `slug_live` = IF(deleted_at IS NULL, slug, NULL) + UNIQUE.
-- Tanpa ini, berita yang dihapus tetap "menahan" slug-nya selamanya dan
-- penulis tidak bisa memakai ulang judul yang sama.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS posts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id    CHAR(36)        NULL,               -- K-7, untuk API mobile

    title        VARCHAR(200)    NOT NULL,
    slug         VARCHAR(200)    NOT NULL,
    excerpt      VARCHAR(400)    NULL,
    content      LONGTEXT        NOT NULL,
    cover_path   VARCHAR(255)    NULL,

    status       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    is_featured  TINYINT(1)      NOT NULL DEFAULT 0,
    views        INT UNSIGNED    NOT NULL DEFAULT 0,

    author_id    BIGINT UNSIGNED NULL,
    published_at DATETIME        NULL,

    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME        NULL,

    slug_live    VARCHAR(200) AS (IF(deleted_at IS NULL, slug, NULL)) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_posts_public (public_id),
    UNIQUE KEY uq_posts_slug_live (slug_live),
    KEY idx_posts_slug (slug),
    -- Query beranda: WHERE status='published' ORDER BY published_at DESC LIMIT n
    KEY idx_posts_feed (status, published_at),
    KEY idx_posts_featured (is_featured, status, published_at),
    KEY idx_posts_author (author_id, created_at),
    KEY idx_posts_deleted (deleted_at),
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    slug             VARCHAR(120)    NOT NULL,
    title            VARCHAR(200)    NOT NULL,
    content          LONGTEXT        NOT NULL,
    template         VARCHAR(60)     NOT NULL DEFAULT 'default',

    meta_title       VARCHAR(200)    NULL,
    meta_description VARCHAR(320)    NULL,

    status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    sort_order       INT             NOT NULL DEFAULT 0,

    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       DATETIME        NULL,

    slug_live        VARCHAR(120) AS (IF(deleted_at IS NULL, slug, NULL)) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug_live (slug_live),
    KEY idx_pages_slug (slug),
    KEY idx_pages_status (status, sort_order),
    KEY idx_pages_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu baris = satu foto. Kolom `album` dipakai untuk mengelompokkan foto
-- dalam satu kegiatan, tanpa perlu tabel album terpisah.
CREATE TABLE IF NOT EXISTS galleries (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    album      VARCHAR(120)    NULL,
    title      VARCHAR(200)    NOT NULL,
    image_path VARCHAR(255)    NOT NULL,
    caption    VARCHAR(255)    NULL,
    sort_order INT             NOT NULL DEFAULT 0,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME        NULL,

    PRIMARY KEY (id),
    KEY idx_galleries_listing (is_active, album, sort_order),
    KEY idx_galleries_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS facilities (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(160)    NOT NULL,
    description TEXT            NULL,
    image_path  VARCHAR(255)    NULL,
    icon        VARCHAR(60)     NULL,
    sort_order  INT             NOT NULL DEFAULT 0,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,

    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL,

    PRIMARY KEY (id),
    KEY idx_facilities_listing (is_active, sort_order),
    KEY idx_facilities_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pengumuman resmi. Punya slug karena bisa dibuka sebagai halaman sendiri.
-- Tidak memakai soft delete: pengumuman memakai status dan expires_at.
CREATE TABLE IF NOT EXISTS announcements (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    title        VARCHAR(200)    NOT NULL,
    slug         VARCHAR(200)    NOT NULL,
    content      LONGTEXT        NOT NULL,
    audience     ENUM('semua','siswa','guru','wali') NOT NULL DEFAULT 'semua',

    status       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    is_pinned    TINYINT(1)      NOT NULL DEFAULT 0,
    published_at DATETIME        NULL,
    expires_at   DATETIME        NULL,

    author_id    BIGINT UNSIGNED NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_announcements_slug (slug),
    -- Query halaman publik: yang terbit, belum kedaluwarsa, disematkan lebih dulu
    KEY idx_announcements_feed (status, is_pinned, published_at),
    KEY idx_announcements_expiry (expires_at),
    KEY idx_announcements_audience (audience, status),
    CONSTRAINT fk_announcements_author FOREIGN KEY (author_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agendas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    title       VARCHAR(200)    NOT NULL,
    description TEXT            NULL,
    location    VARCHAR(200)    NULL,

    start_at    DATETIME        NOT NULL,
    end_at      DATETIME        NULL,
    all_day     TINYINT(1)      NOT NULL DEFAULT 0,

    status      ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',

    author_id   BIGINT UNSIGNED NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_agendas_upcoming (status, start_at),
    KEY idx_agendas_range (start_at, end_at),
    CONSTRAINT fk_agendas_author FOREIGN KEY (author_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_agendas_range CHECK (end_at IS NULL OR end_at >= start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS agendas;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS facilities;
DROP TABLE IF EXISTS galleries;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS posts;
