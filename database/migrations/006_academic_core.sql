-- ============================================================================
-- 006 — Akademik inti
-- academic_years, classes, subjects, class_subjects
--
-- Sekaligus menutup utang dari migrasi 002: `students.class_id` dibuat tanpa
-- foreign key di sana karena tabel `classes` belum ada. Di sini FK-nya
-- ditambahkan lewat ALTER TABLE.
--
-- Keputusan K-3: hanya boleh ada SATU tahun ajaran aktif.
-- MariaDB tidak punya partial index (`WHERE is_active = 1`), jadi dipakai
-- generated column `active_flag` yang bernilai 1 saat aktif dan NULL saat
-- tidak. UNIQUE pada kolom itu membuat baris kedua yang aktif ditolak,
-- sementara baris tidak aktif (NULL) boleh berapa pun.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS academic_years (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(40)     NOT NULL,          -- "2025/2026"
    start_date  DATE            NOT NULL,
    end_date    DATE            NOT NULL,
    is_active   TINYINT(1)      NOT NULL DEFAULT 0,
    note        VARCHAR(255)    NULL,

    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    active_flag TINYINT AS (IF(is_active = 1, 1, NULL)) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_academic_years_name (name),
    UNIQUE KEY uq_academic_years_active (active_flag),
    KEY idx_academic_years_range (start_date, end_date),
    CONSTRAINT chk_academic_years_range CHECK (end_date > start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_year_id    BIGINT UNSIGNED NOT NULL,

    name                VARCHAR(60)     NOT NULL,   -- "7A"
    level               TINYINT UNSIGNED NOT NULL,  -- 7, 8, 9
    homeroom_teacher_id BIGINT UNSIGNED NULL,       -- wali kelas
    room                VARCHAR(60)     NULL,
    capacity            SMALLINT UNSIGNED NULL,
    sort_order          INT             NOT NULL DEFAULT 0,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,

    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_year_name (academic_year_id, name),
    KEY idx_classes_level (academic_year_id, level, sort_order),
    KEY idx_classes_homeroom (homeroom_teacher_id),
    CONSTRAINT fk_classes_year FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_classes_homeroom FOREIGN KEY (homeroom_teacher_id) REFERENCES teachers (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_classes_level CHECK (level BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(20)     NOT NULL,            -- "MTK", "QH"
    name       VARCHAR(120)    NOT NULL,
    group_name ENUM('agama','umum','muatan_lokal','ekstrakurikuler') NOT NULL DEFAULT 'umum',
    sort_order INT             NOT NULL DEFAULT 0,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1,

    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_code (code),
    KEY idx_subjects_group (group_name, sort_order),
    KEY idx_subjects_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mata pelajaran yang diajarkan di satu kelas, oleh satu guru.
-- teacher_id boleh NULL: pembagian tugas mengajar sering ditentukan belakangan.
CREATE TABLE IF NOT EXISTS class_subjects (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id      BIGINT UNSIGNED NOT NULL,
    subject_id    BIGINT UNSIGNED NOT NULL,
    teacher_id    BIGINT UNSIGNED NULL,

    weekly_hours  TINYINT UNSIGNED NOT NULL DEFAULT 2,
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,

    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_class_subjects_pair (class_id, subject_id),
    KEY idx_class_subjects_teacher (teacher_id),
    KEY idx_class_subjects_subject (subject_id),
    CONSTRAINT fk_class_subjects_class FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_class_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_class_subjects_teacher FOREIGN KEY (teacher_id) REFERENCES teachers (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK yang ditunda dari migrasi 002.
-- ON DELETE SET NULL: kalau kelas dihapus, siswa tetap ada tapi tanpa kelas.
-- Menghapus siswa karena kelasnya dihapus adalah kehilangan data.
ALTER TABLE students
    ADD CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- @DOWN

ALTER TABLE students DROP FOREIGN KEY fk_students_class;

DROP TABLE IF EXISTS class_subjects;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS academic_years;
