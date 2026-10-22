-- ============================================================================
-- 002 — Identitas inti
-- users, teachers, students
--
-- Urutan wajib: users lebih dulu, karena dua tabel lain bergantung padanya.
--
-- Catatan penting:
--   students.class_id sengaja dibuat TANPA foreign key di sini.
--   Tabel `classes` baru dibuat pada migrasi 006. FK-nya ditambahkan di sana
--   lewat ALTER TABLE. Kalau dipaksakan sekarang, migrasi ini akan gagal.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS users (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name                  VARCHAR(120)    NOT NULL,
    email                 VARCHAR(160)    NOT NULL,
    username              VARCHAR(60)     NULL,
    password_hash         VARCHAR(255)    NOT NULL,

    -- K-1: `admin` adalah super admin. Label tampilan dipisah di role_label
    -- supaya nilai data tetap satu dan tidak ada duplikasi makna.
    role                  ENUM('admin','operator','kepala','guru','siswa','akreditasi')
                                          NOT NULL DEFAULT 'siswa',
    role_label            VARCHAR(40)     NULL,

    phone                 VARCHAR(24)     NULL,
    avatar_path           VARCHAR(255)    NULL,

    is_active             TINYINT(1)      NOT NULL DEFAULT 1,
    must_change_password  TINYINT(1)      NOT NULL DEFAULT 1,
    failed_login_attempts INT UNSIGNED    NOT NULL DEFAULT 0,
    locked_until          DATETIME        NULL,
    last_login_at         DATETIME        NULL,
    last_login_ip         VARCHAR(45)     NULL,

    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            DATETIME        NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_active (role, is_active),
    KEY idx_users_deleted (deleted_at),
    KEY idx_users_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teachers (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id            BIGINT UNSIGNED NOT NULL,
    nip                VARCHAR(30)     NULL,
    nuptk              VARCHAR(30)     NULL,
    gender             ENUM('L','P')   NOT NULL DEFAULT 'L',
    employment_status  ENUM('pns','pppk','honorer','gtty') NOT NULL DEFAULT 'honorer',
    subject_specialty  VARCHAR(120)    NULL,
    joined_at          DATE            NULL,
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teachers_user (user_id),
    UNIQUE KEY uq_teachers_nip (nip),
    UNIQUE KEY uq_teachers_nuptk (nuptk),
    KEY idx_teachers_status (employment_status),
    CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    nis            VARCHAR(20)     NOT NULL,
    nisn           VARCHAR(20)     NULL,
    class_id       BIGINT UNSIGNED NULL,          -- FK ditambahkan di migrasi 006
    gender         ENUM('L','P')   NOT NULL DEFAULT 'L',
    birth_place    VARCHAR(80)     NULL,
    birth_date     DATE            NULL,
    guardian_name  VARCHAR(120)    NULL,
    guardian_phone VARCHAR(24)     NULL,
    address        TEXT            NULL,
    enrolled_at    DATE            NULL,
    is_active      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_user (user_id),
    UNIQUE KEY uq_students_nis (nis),
    UNIQUE KEY uq_students_nisn (nisn),
    KEY idx_students_class_active (class_id, is_active),
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS users;
