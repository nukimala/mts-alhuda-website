-- ============================================================================
-- 005 — Konten web (CMS), bagian penunjang
-- menus, contact_messages, ppdb_documents, media
--
-- PPDB sengaja tanpa formulir: hanya dokumen unduhan dan informasi.
-- Karena itu tidak ada tabel pendaftar, dan `ppdb_documents` hanya menghitung
-- jumlah unduhan — tanpa menyimpan identitas pengunduh.
-- ============================================================================

-- @UP

-- Menu navigasi. parent_id menunjuk dirinya sendiri (submenu satu tingkat).
-- ON DELETE CASCADE: hapus menu induk, submenunya ikut hilang. Menu yatim
-- tidak ada gunanya dan hanya membingungkan halaman publik.
CREATE TABLE IF NOT EXISTS menus (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id  BIGINT UNSIGNED NULL,

    label      VARCHAR(120)    NOT NULL,
    url        VARCHAR(255)    NOT NULL,
    target     ENUM('self','blank') NOT NULL DEFAULT 'self',
    location   ENUM('utama','footer','mobile') NOT NULL DEFAULT 'utama',
    sort_order INT             NOT NULL DEFAULT 0,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_menus_tree (location, parent_id, sort_order),
    KEY idx_menus_active (is_active, location),
    CONSTRAINT fk_menus_parent FOREIGN KEY (parent_id) REFERENCES menus (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pesan dari formulir kontak di halaman publik.
-- Ini satu-satunya tempat data pribadi masuk dari luar — hanya nama, email,
-- telepon, dan isi pesan. Tidak ada pelacakan IP, tidak ada user agent.
CREATE TABLE IF NOT EXISTS contact_messages (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name       VARCHAR(120)    NOT NULL,
    email      VARCHAR(160)    NOT NULL,
    phone      VARCHAR(24)     NULL,
    subject    VARCHAR(200)    NULL,
    message    TEXT            NOT NULL,

    status     ENUM('baru','dibaca','dibalas','arsip') NOT NULL DEFAULT 'baru',
    replied_at DATETIME        NULL,
    replied_by BIGINT UNSIGNED NULL,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_contact_inbox (status, created_at),
    KEY idx_contact_email (email),
    CONSTRAINT fk_contact_replied_by FOREIGN KEY (replied_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dokumen unduhan PPDB. download_count hanya angka — tidak ada tabel log
-- per unduhan, karena itu akan menyimpan identitas pengunduh.
CREATE TABLE IF NOT EXISTS ppdb_documents (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    title          VARCHAR(200)    NOT NULL,
    description    TEXT            NULL,
    category       VARCHAR(60)     NOT NULL DEFAULT 'umum',

    file_path      VARCHAR(255)    NOT NULL,
    file_type      VARCHAR(20)     NULL,
    file_size      BIGINT UNSIGNED NULL,

    sort_order     INT             NOT NULL DEFAULT 0,
    is_active      TINYINT(1)      NOT NULL DEFAULT 1,
    download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,

    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_ppdb_docs_listing (is_active, category, sort_order),
    KEY idx_ppdb_docs_downloads (download_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pustaka berkas. `checksum` mencegah berkas identik diunggah berulang kali
-- dan tersimpan dua kali di disk.
CREATE TABLE IF NOT EXISTS media (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    path        VARCHAR(255)    NOT NULL,
    file_name   VARCHAR(255)    NOT NULL,
    mime_type   VARCHAR(100)    NOT NULL,
    extension   VARCHAR(12)     NULL,
    file_size   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    checksum    CHAR(64)        NULL,               -- sha256 hex
    width       INT UNSIGNED    NULL,
    height      INT UNSIGNED    NULL,
    alt         VARCHAR(255)    NULL,
    folder      VARCHAR(120)    NOT NULL DEFAULT 'umum',

    uploaded_by BIGINT UNSIGNED NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_media_path (path),
    KEY idx_media_checksum (checksum),
    KEY idx_media_folder (folder, created_at),
    KEY idx_media_type (mime_type),
    CONSTRAINT fk_media_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS ppdb_documents;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS menus;
