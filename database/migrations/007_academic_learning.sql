-- ============================================================================
-- 007 — Akademik: pembelajaran
-- schedules, materials, assignments, submissions
--
-- Keputusan K-2: `schedules.teacher_id` sengaja didenormalisasi.
-- Tanpa itu, pengecekan bentrok guru harus JOIN tiga tabel
-- (schedules -> class_subjects -> teacher). Dengan kolom ini, pengecekan
-- menjadi satu index lookup.
--
-- Keputusan K-11: `schedules.academic_year_id` juga didenormalisasi.
-- Alasannya berbeda: UNIQUE bentrok guru HARUS memperhitungkan tahun ajaran.
-- Tanpa itu, jadwal tahun ini akan memblokir jadwal tahun depan pada jam
-- dan hari yang sama — padahal itu wajar dan sah.
--
-- Batas yang jujur: UNIQUE di sini menangkap bentrok yang JAM MULAINYA sama
-- persis. Bentrok sebagian (08:00-09:30 vs 08:30-10:00) tidak bisa ditangkap
-- index biasa dan harus divalidasi aplikasi sebelum insert. Ini dicatat
-- supaya tidak ada yang mengira DB sudah menjamin semuanya.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS schedules (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    class_subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id       BIGINT UNSIGNED NOT NULL,      -- K-2: denormalisasi
    academic_year_id BIGINT UNSIGNED NOT NULL,      -- K-11: untuk UNIQUE bentrok

    day_of_week      TINYINT UNSIGNED NOT NULL,     -- 1=Senin ... 7=Minggu
    start_time       TIME            NOT NULL,
    end_time         TIME            NOT NULL,
    room             VARCHAR(60)     NULL,
    is_active        TINYINT(1)      NOT NULL DEFAULT 1,

    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_schedules_teacher_slot (teacher_id, academic_year_id, day_of_week, start_time),
    KEY idx_schedules_class_day (class_subject_id, day_of_week, start_time),
    KEY idx_schedules_year_day (academic_year_id, day_of_week, start_time),
    CONSTRAINT fk_schedules_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_schedules_teacher FOREIGN KEY (teacher_id) REFERENCES teachers (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_schedules_year FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_schedules_day CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT chk_schedules_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS materials (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    class_subject_id BIGINT UNSIGNED NOT NULL,
    title            VARCHAR(200)    NOT NULL,
    description      TEXT            NULL,
    file_path        VARCHAR(255)    NULL,
    external_url     VARCHAR(255)    NULL,

    is_published     TINYINT(1)      NOT NULL DEFAULT 1,
    published_at     DATETIME        NULL,

    created_by       BIGINT UNSIGNED NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_materials_class (class_subject_id, is_published, created_at),
    KEY idx_materials_author (created_by),
    CONSTRAINT fk_materials_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_materials_author FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_materials_source CHECK (file_path IS NOT NULL OR external_url IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tugas. `max_score` dipakai sebagai batas atas penilaian.
-- Sesuai lingkup: nilai hanya untuk tugas dan umpan balik, bukan rapor lengkap.
CREATE TABLE IF NOT EXISTS assignments (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id        CHAR(36)        NULL,          -- K-7

    class_subject_id BIGINT UNSIGNED NOT NULL,
    title            VARCHAR(200)    NOT NULL,
    description      TEXT            NULL,
    attachment_path  VARCHAR(255)    NULL,

    due_at           DATETIME        NULL,
    max_score        DECIMAL(5,2)    NOT NULL DEFAULT 100.00,
    is_published     TINYINT(1)      NOT NULL DEFAULT 1,

    created_by       BIGINT UNSIGNED NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_assignments_public (public_id),
    KEY idx_assignments_class_due (class_subject_id, due_at),
    KEY idx_assignments_open (is_published, due_at),
    KEY idx_assignments_author (created_by),
    CONSTRAINT fk_assignments_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_assignments_author FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_assignments_score CHECK (max_score > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu siswa hanya boleh mengumpulkan satu kali per tugas.
-- UNIQUE (assignment_id, student_id) yang menjaminnya, bukan kode aplikasi.
CREATE TABLE IF NOT EXISTS submissions (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id     CHAR(36)        NULL,             -- K-7

    assignment_id BIGINT UNSIGNED NOT NULL,
    student_id    BIGINT UNSIGNED NOT NULL,

    file_path     VARCHAR(255)    NULL,
    note          TEXT            NULL,
    submitted_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    score         DECIMAL(5,2)    NULL,
    feedback      TEXT            NULL,
    graded_at     DATETIME        NULL,
    graded_by     BIGINT UNSIGNED NULL,

    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_submissions_public (public_id),
    UNIQUE KEY uq_submissions_pair (assignment_id, student_id),
    KEY idx_submissions_student (student_id, submitted_at),
    KEY idx_submissions_ungraded (assignment_id, graded_at),
    KEY idx_submissions_grader (graded_by),
    CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES students (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_grader FOREIGN KEY (graded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_submissions_score CHECK (score IS NULL OR score >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS schedules;
