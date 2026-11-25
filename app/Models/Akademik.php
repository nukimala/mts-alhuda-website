<?php

require_once __DIR__ . '/../Support/Database.php';

class Akademik
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Ambil tahun ajaran aktif saat ini.
     */
    public function getActiveYear(): ?array
    {
        $stmt = $this->pdo->query("SELECT * FROM tahun_akademik ORDER BY is_active DESC, name DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Ambil semua tahun akademik untuk opsi filter.
     */
    public function getAllAcademicYears(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tahun_akademik ORDER BY is_active DESC, name DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar kelas untuk tahun akademik tertentu.
     */
    public function getClassesByYear(int $yearId = 0): array
    {
        if ($yearId > 0) {
            $stmt = $this->pdo->prepare("SELECT * FROM kelas WHERE academic_year_id = ? ORDER BY level ASC, name ASC");
            $stmt->execute([$yearId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($classes)) {
                return $classes;
            }
        }

        return $this->pdo->query("SELECT * FROM kelas ORDER BY level ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar jadwal pelajaran per kelas dan tahun akademik.
     */
    public function getSchedulesByClass(int $classId, int $yearId = 0): array
    {
        if ($classId <= 0) {
            return [];
        }

        $sql = "SELECT s.*, cs.class_id, sub.name as subject_name, sub.code as subject_code, COALESCE(u.name, 'Guru') as teacher_name
                FROM jadwal s
                JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
                JOIN mata_pelajaran sub ON sub.id = cs.subject_id
                LEFT JOIN guru t ON t.id = s.teacher_id
                LEFT JOIN pengguna u ON u.id = t.user_id
                WHERE cs.class_id = ? AND (s.academic_year_id = ? OR ? = 0)
                ORDER BY s.day_of_week ASC, s.start_time ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$classId, $yearId, $yearId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil pengaturan publik website.
     */
    public function getPublicSettings(): array
    {
        return $this->pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Ambil semua mata pelajaran aktif.
     */
    public function getAllSubjects(): array
    {
        return $this->pdo->query("SELECT * FROM mata_pelajaran WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua guru aktif.
     */
    public function getAllTeachers(): array
    {
        $sql = "SELECT t.id, u.name, t.nip 
                FROM guru t 
                JOIN pengguna u ON u.id = t.user_id 
                WHERE u.deleted_at IS NULL AND u.is_active = 1 
                ORDER BY u.name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pastikan relasi mata pelajaran dan kelas ada di tabel mata_pelajaran_kelas.
     */
    public function getOrCreateClassSubject(int $classId, int $subjectId, int $teacherId): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM mata_pelajaran_kelas WHERE class_id = ? AND subject_id = ?");
        $stmt->execute([$classId, $subjectId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            return (int) $existingId;
        }

        $insert = $this->pdo->prepare("INSERT INTO mata_pelajaran_kelas (class_id, subject_id, teacher_id, weekly_hours, is_active, created_at, updated_at) VALUES (?, ?, ?, 2, 1, NOW(), NOW())");
        $insert->execute([$classId, $subjectId, $teacherId]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Cek apakah ada jadwal guru yang bentrok pada jam dan hari yang sama.
     */
    public function hasTeacherClash(int $teacherId, int $yearId, int $dayOfWeek, string $startTime, string $endTime): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM jadwal 
                                     WHERE teacher_id = ? AND academic_year_id = ? AND day_of_week = ? 
                                     AND (start_time < ? AND end_time > ?)");
        $stmt->execute([$teacherId, $yearId, $dayOfWeek, $endTime, $startTime]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah ada jadwal kelas yang bentrok pada jam dan hari yang sama.
     */
    public function hasClassClash(int $classId, int $yearId, int $dayOfWeek, string $startTime, string $endTime): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM jadwal s
                                     JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
                                     WHERE cs.class_id = ? AND s.academic_year_id = ? AND s.day_of_week = ?
                                     AND (s.start_time < ? AND s.end_time > ?)");
        $stmt->execute([$classId, $yearId, $dayOfWeek, $endTime, $startTime]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tambah jadwal baru.
     */
    public function addSchedule(array $payload): int
    {
        $sql = "INSERT INTO jadwal (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time, room, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $payload['class_subject_id'],
            $payload['teacher_id'],
            $payload['academic_year_id'],
            $payload['day_of_week'],
            $payload['start_time'],
            $payload['end_time'],
            $payload['room'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Hapus jadwal berdasarkan ID.
     */
    public function deleteSchedule(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM jadwal WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
