-- ============================================================================
-- 008 — Presensi guru
-- teacher_attendances
--
-- Ini tabel paling rawan bug di seluruh skema. Alasannya:
--
-- Presensi harian tidak punya jadwal (`schedule_id` NULL). UNIQUE biasa
-- seperti (teacher_id, attendance_date, schedule_id) TIDAK mencegah duplikat,
-- karena di SQL NULL tidak pernah sama dengan NULL. Guru yang sama bisa
-- tercatat masuk dua kali di tanggal yang sama tanpa satu pun error keluar.
--
-- Solusi (K-4): generated column `unique_key` yang menggabungkan semua kolom
-- pembeda, dengan IFNULL(schedule_id, 0) supaya NULL jadi nilai nyata.
-- UNIQUE pada kolom itu menutup lubangnya.
--
-- Presensi per jam mengajar juga tercakup: schedule_id terisi, jadi
-- kuncinya berbeda dari presensi harian di tanggal yang sama.
--
-- Waktu check-in/check-out diisi dari jam server (NOW()), bukan input klien.
-- Jam perangkat siswa/guru tidak pernah dipercaya.
-- ============================================================================

-- @UP

CREATE TABLE IF NOT EXISTS teacher_attendances (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    teacher_id      BIGINT UNSIGNED NOT NULL,
    schedule_id     BIGINT UNSIGNED NULL,           -- NULL = presensi harian

    attendance_date DATE            NOT NULL,
    attendance_type ENUM('daily','session') NOT NULL DEFAULT 'daily',

    check_in_at     DATETIME        NULL,
    check_out_at    DATETIME        NULL,

    status          ENUM('hadir','terlambat','izin','sakit','dinas','alpa') NOT NULL DEFAULT 'hadir',
    note            VARCHAR(255)    NULL,

    -- Metode pencatatan. `manual` = diisi operator, bukan guru sendiri.
    -- Tidak ada QR, tidak ada presensi siswa, sesuai lingkup.
    method          ENUM('self','operator','system') NOT NULL DEFAULT 'self',

    verified_by     BIGINT UNSIGNED NULL,
    verified_at     DATETIME        NULL,

    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    unique_key      VARCHAR(96) AS (
        CONCAT(teacher_id, '-', attendance_date, '-', attendance_type, '-', IFNULL(schedule_id, 0))
    ) STORED,

    PRIMARY KEY (id),
    UNIQUE KEY uq_teacher_attendances_key (unique_key),
    -- Rekap harian: berapa guru hadir pada tanggal tertentu
    KEY idx_attendance_daily (attendance_date, status),
    KEY idx_attendance_teacher (teacher_id, attendance_date),
    KEY idx_attendance_schedule (schedule_id, attendance_date),
    KEY idx_attendance_unverified (verified_at, attendance_date),
    -- ON DELETE RESTRICT, bukan CASCADE. MySQL 8 melarang CASCADE/SET NULL
    -- pada base column dari STORED generated column:
    --   ERROR 1215: Cannot add foreign key constraint
    -- `teacher_id` dipakai di `unique_key` (generated), jadi aksinya harus
    -- RESTRICT. Kebetulan itu juga perilaku yang benar: presensi adalah
    -- catatan audit. Menghapus guru tidak boleh menghapus riwayat
    -- kehadirannya diam-diam. Guru yang tidak lagi mengajar cukup
    -- dinonaktifkan lewat users.is_active = 0.
    CONSTRAINT fk_attendance_teacher FOREIGN KEY (teacher_id) REFERENCES teachers (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    -- ON DELETE RESTRICT, bukan SET NULL. Kalau jadwal dihapus dan schedule_id
    -- jadi NULL, presensi per jam mengajar berubah jadi presensi harian dan
    -- CHECK di bawah langsung gagal. Lebih baik penghapusan ditolak: jadwal
    -- yang punya riwayat presensi cukup dinonaktifkan (is_active = 0).
    --
    -- ON UPDATE RESTRICT, bukan CASCADE. Ini bukan pilihan gaya, melainkan
    -- syarat MySQL 8:
    --   ERROR 3823: Column 'schedule_id' cannot be used in a check constraint
    --   'chk_attendance_type': needed in a foreign key constraint
    --   'fk_attendance_schedule' referential action.
    -- MySQL melarang kolom yang dipakai CHECK ikut punya aksi FK CASCADE.
    -- MariaDB mengizinkannya, jadi perbedaan ini baru muncul saat pindah
    -- engine. Tidak ada yang hilang: `id` adalah AUTO_INCREMENT yang tidak
    -- pernah berubah, sehingga CASCADE di sini tidak pernah terpicu.
    CONSTRAINT fk_attendance_schedule FOREIGN KEY (schedule_id) REFERENCES schedules (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attendance_verifier FOREIGN KEY (verified_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_attendance_type CHECK (
        (attendance_type = 'daily'   AND schedule_id IS NULL) OR
        (attendance_type = 'session' AND schedule_id IS NOT NULL)
    ),
    CONSTRAINT chk_attendance_checkout CHECK (
        check_out_at IS NULL OR check_in_at IS NULL OR check_out_at >= check_in_at
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN

DROP TABLE IF EXISTS teacher_attendances;
