<?php

require_once __DIR__ . '/../Support/Database.php';

class Presensi
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Ambil semua data guru aktif untuk dropdown dan referensi.
     */
    public function getTeachers(): array
    {
        $sql = "SELECT t.id as teacher_id, t.nip, u.name as teacher_name, u.email 
                FROM guru t 
                JOIN pengguna u ON u.id = t.user_id 
                WHERE u.deleted_at IS NULL AND u.is_active = 1 
                ORDER BY u.name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil rekaman presensi harian seluruh guru berdasarkan tanggal dan filter.
     */
    public function getAttendanceRecords(string $date, string $status = '', string $q = ''): array
    {
        $sql = "SELECT a.*, t.id as teacher_id, t.nip, u.name as teacher_name, u.email
                FROM guru t
                JOIN pengguna u ON u.id = t.user_id
                LEFT JOIN presensi_guru a ON a.teacher_id = t.id AND a.attendance_date = ? AND a.attendance_type = 'daily'
                WHERE u.deleted_at IS NULL AND u.is_active = 1";

        $params = [$date];

        if ($status !== '' && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'dinas', 'alpa'], true)) {
            $sql .= ' AND a.status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $sql .= ' AND (u.name LIKE ? OR t.nip LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik presensi pada tanggal tertentu (hadir, terlambat, izin/sakit/dinas, belum absen).
     */
    public function getDailyStats(string $date, int $totalTeachers): array
    {
        $stmtHadir = $this->pdo->prepare("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = ? AND status = 'hadir' AND attendance_type = 'daily'");
        $stmtHadir->execute([$date]);
        $statHadir = (int) $stmtHadir->fetchColumn();

        $stmtTerlambat = $this->pdo->prepare("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = ? AND status = 'terlambat' AND attendance_type = 'daily'");
        $stmtTerlambat->execute([$date]);
        $statTerlambat = (int) $stmtTerlambat->fetchColumn();

        $stmtIzin = $this->pdo->prepare("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = ? AND status IN ('izin', 'sakit', 'dinas') AND attendance_type = 'daily'");
        $stmtIzin->execute([$date]);
        $statIzin = (int) $stmtIzin->fetchColumn();

        $stmtRecorded = $this->pdo->prepare("SELECT COUNT(DISTINCT teacher_id) FROM presensi_guru WHERE attendance_date = ? AND attendance_type = 'daily'");
        $stmtRecorded->execute([$date]);
        $totalRecorded = (int) $stmtRecorded->fetchColumn();

        $statBelum = max(0, $totalTeachers - $totalRecorded);

        return [
            'stat_hadir' => $statHadir,
            'stat_terlambat' => $statTerlambat,
            'stat_izin' => $statIzin,
            'stat_belum' => $statBelum,
        ];
    }

    /**
     * Simpan atau perbarui catatan presensi (koreksi manual admin).
     */
    public function saveOrUpdate(array $data): bool
    {
        $teacherId = (int) $data['teacher_id'];
        $date = $data['date'];
        $status = $data['status'];
        $checkInTime = trim((string) ($data['check_in_time'] ?? ''));
        $checkOutTime = trim((string) ($data['check_out_time'] ?? ''));
        $note = $data['note'] ?? '';
        $verifiedBy = $data['verified_by'] ?? null;

        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $checkInTime)) {
            $checkInTime = '';
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $checkOutTime)) {
            $checkOutTime = '';
        }

        $isPresentStatus = in_array($status, ['hadir', 'terlambat'], true);
        if ($checkInTime !== '') {
            $checkInAt = $date . ' ' . (strlen($checkInTime) === 5 ? $checkInTime . ':00' : $checkInTime);
        } else {
            $checkInAt = $isPresentStatus ? ($date . ' 07:00:00') : null;
        }

        $checkOutAt = $checkOutTime !== ''
            ? ($date . ' ' . (strlen($checkOutTime) === 5 ? $checkOutTime . ':00' : $checkOutTime))
            : null;
        $noteValue = $note !== '' ? $note : null;

        $jamMasuk = $checkInAt !== null ? substr($checkInAt, 11, 8) : null;
        $jamPulang = $checkOutAt !== null ? substr($checkOutAt, 11, 8) : null;

        $existingStmt = $this->pdo->prepare("SELECT id FROM presensi_guru WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily' LIMIT 1");
        $existingStmt->execute([$teacherId, $date]);
        $existingId = $existingStmt->fetchColumn();

        if ($existingId) {
            $stmt = $this->pdo->prepare(
                "UPDATE presensi_guru
                 SET guru_id = ?, tanggal = ?, check_in_at = ?, jam_masuk = ?, check_out_at = ?, jam_pulang = ?,
                     status = ?, notes = ?, keterangan = ?, verified_by = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            return $stmt->execute([
                $teacherId,
                $date,
                $checkInAt,
                $jamMasuk,
                $checkOutAt,
                $jamPulang,
                $status,
                $noteValue,
                $noteValue,
                $verifiedBy,
                $existingId,
            ]);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO presensi_guru
                (teacher_id, guru_id, attendance_date, tanggal, attendance_type, check_in_at, jam_masuk, check_out_at, jam_pulang, status, notes, keterangan, verified_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'daily', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        return $stmt->execute([
            $teacherId,
            $teacherId,
            $date,
            $date,
            $checkInAt,
            $jamMasuk,
            $checkOutAt,
            $jamPulang,
            $status,
            $noteValue,
            $noteValue,
            $verifiedBy,
        ]);
    }
}
