<?php
/**
 * API v1 — Presensi Kehadiran Guru untuk Aplikasi Mobile MADRASAHKU.
 *
 * Mengakomodasi:
 * - GET  /api/v1/attendance/today     (status presensi harian + jadwal mengajar hari ini)
 * - POST /api/v1/attendance/check-in  (check-in mandiri harian / per jam mengajar)
 * - POST /api/v1/attendance/check-out (check-out mandiri pulang)
 * - GET  /api/v1/attendance/history   (riwayat presensi bulanan/rentang tanggal)
 *
 * Menggunakan waktu server MySQL/PHP (NOW()) untuk mencegah manipulasi jam HP.
 */

class ApiAttendanceController
{
    /**
     * Pastikan pengguna terautentikasi dan memiliki profil guru.
     *
     * @return array{user:array<string,mixed>,teacher:array<string,mixed>}|null
     */
    private function requireTeacher(): ?array
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized();
            return null;
        }

        if (($user['role'] ?? '') !== 'guru') {
            ApiResponse::forbidden('Hanya guru yang memiliki akses ke modul presensi.');
            return null;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, user_id, nip, nuptk, subject_specialty FROM guru WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int) $user['id']]);
        $teacher = $stmt->fetch();

        if ($teacher === false) {
            ApiResponse::error(
                'PROFILE_NOT_LINKED',
                'Akun Anda belum ditautkan ke data guru. Hubungi Operator Madrasah.',
                409
            );
            return null;
        }

        return ['user' => $user, 'teacher' => $teacher];
    }

    /**
     * GET /api/v1/attendance/today
     *
     * Mengembalikan status presensi harian hari ini, daftar jadwal mengajar hari ini,
     * serta aturan/toleransi jam presensi dari pengaturan madrasah.
     */
    public function today(array $params = []): void
    {
        $auth = $this->requireTeacher();
        if ($auth === null) {
            return;
        }

        $teacher = $auth['teacher'];
        $teacherId = (int) $teacher['id'];
        $today = date('Y-m-d');
        $dayOfWeek = (int) date('N'); // 1 = Senin ... 7 = Minggu
        $currentTime = date('H:i:s');

        $pdo = Database::connection();

        // 1. Ambil pengaturan presensi
        $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name IN ('presensi', 'kontak', 'identitas')");
        $rawSettings = $settingsStmt->fetchAll();
        $settings = [];
        foreach ($rawSettings as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $checkInStart = $settings['attendance_check_in_time'] ?? '06:30:00';
        $toleranceMinutes = (int) ($settings['attendance_late_tolerance'] ?? 15);
        $checkOutStart = $settings['attendance_check_out_time'] ?? '14:00:00';
        $schoolLat = $settings['attendance_lat'] ?? $settings['contact_map_lat'] ?? null;
        $schoolLng = $settings['attendance_lng'] ?? $settings['contact_map_lng'] ?? null;
        $radiusMeters = (int) ($settings['attendance_radius_meters'] ?? 100);

        // Hitung batas terlambat harian (check_in_time + toleransi)
        $lateLimitTime = date('H:i:s', strtotime($checkInStart) + ($toleranceMinutes * 60));

        // 2. Presensi Harian Hari Ini
        $dailyStmt = $pdo->prepare(
            "SELECT id, attendance_date, attendance_type, check_in_at, check_out_at, status, notes
             FROM presensi_guru
             WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily'
             LIMIT 1"
        );
        $dailyStmt->execute([$teacherId, $today]);
        $dailyRecord = $dailyStmt->fetch();

        $dailyData = null;
        if ($dailyRecord !== false) {
            $dailyData = [
                'id'           => (int) $dailyRecord['id'],
                'date'         => $dailyRecord['attendance_date'],
                'check_in_at'  => ApiFormat::iso($dailyRecord['check_in_at']),
                'check_out_at' => ApiFormat::iso($dailyRecord['check_out_at']),
                'status'       => $dailyRecord['status'],
                'method'       => 'self',
                'note'         => $dailyRecord['notes'],
                'has_checked_in'  => !empty($dailyRecord['check_in_at']),
                'has_checked_out' => !empty($dailyRecord['check_out_at']),
            ];
        }

        // 3. Jadwal Mengajar Hari Ini
        $scheduleStmt = $pdo->prepare(
            "SELECT s.id, s.start_time, s.end_time, s.room,
                    c.name AS class_name, c.level AS class_level,
                    sub.code AS subject_code, sub.name AS subject_name,
                    p.id AS attendance_id, p.check_in_at, p.status AS attendance_status, p.notes AS attendance_note
             FROM jadwal s
             JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
             JOIN kelas c ON c.id = cs.class_id
             JOIN mata_pelajaran sub ON sub.id = cs.subject_id
             JOIN tahun_akademik y ON y.id = s.academic_year_id AND y.is_active = 1
             LEFT JOIN presensi_guru p ON p.schedule_id = s.id AND p.attendance_date = ? AND p.teacher_id = ?
             WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.is_active = 1
             ORDER BY s.start_time ASC"
        );
        $scheduleStmt->execute([$today, $teacherId, $teacherId, $dayOfWeek]);
        $scheduleRows = $scheduleStmt->fetchAll();

        $todaySchedules = [];
        foreach ($scheduleRows as $row) {
            $hasAttended = !empty($row['attendance_id']);
            $todaySchedules[] = [
                'schedule_id'   => (int) $row['id'],
                'start_time'    => ApiFormat::time($row['start_time']),
                'end_time'      => ApiFormat::time($row['end_time']),
                'room'          => $row['room'],
                'class_name'    => $row['class_name'],
                'class_level'   => (int) $row['class_level'],
                'subject_code'  => $row['subject_code'],
                'subject_name'  => $row['subject_name'],
                'has_attended'  => $hasAttended,
                'attendance'    => $hasAttended ? [
                    'id'          => (int) $row['attendance_id'],
                    'check_in_at' => ApiFormat::iso($row['check_in_at']),
                    'status'      => $row['attendance_status'],
                    'note'        => $row['attendance_note'],
                ] : null,
            ];
        }

        ApiResponse::ok([
            'date'         => $today,
            'day_name'     => ApiFormat::dayName($dayOfWeek),
            'server_time'  => ApiFormat::iso(date('Y-m-d H:i:s')),
            'rules'        => [
                'check_in_time'     => ApiFormat::time($checkInStart),
                'late_limit_time'   => ApiFormat::time($lateLimitTime),
                'check_out_time'    => ApiFormat::time($checkOutStart),
                'tolerance_minutes' => $toleranceMinutes,
                'school_latitude'   => $schoolLat !== null ? (float) $schoolLat : null,
                'school_longitude'  => $schoolLng !== null ? (float) $schoolLng : null,
                'radius_meters'     => $radiusMeters,
            ],
            'daily_attendance' => $dailyData,
            'schedules'        => $todaySchedules,
        ]);
    }

    /**
     * POST /api/v1/attendance/check-in
     * Body: {
     *   "type": "daily" | "session",
     *   "schedule_id": int (wajib jika type == "session"),
     *   "latitude": float (opsional),
     *   "longitude": float (opsional),
     *   "note": string (opsional)
     * }
     */
    public function checkIn(): void
    {
        $auth = $this->requireTeacher();
        if ($auth === null) {
            return;
        }

        $input = ApiResponse::input();
        $type = ApiResponse::str($input, 'type', 'daily');
        $scheduleId = isset($input['schedule_id']) && (int) $input['schedule_id'] > 0 ? (int) $input['schedule_id'] : null;
        $lat = isset($input['latitude']) && is_numeric($input['latitude']) ? (float) $input['latitude'] : null;
        $lng = isset($input['longitude']) && is_numeric($input['longitude']) ? (float) $input['longitude'] : null;
        $note = ApiResponse::str($input, 'note');

        if (!in_array($type, ['daily', 'session'], true)) {
            ApiResponse::validation(['type' => 'Tipe presensi harus "daily" atau "session".']);
            return;
        }

        if ($type === 'session' && $scheduleId === null) {
            ApiResponse::validation(['schedule_id' => 'Jadwal pelajaran (schedule_id) wajib diisi untuk presensi sesi mengajar.']);
            return;
        }

        $teacher = $auth['teacher'];
        $teacherId = (int) $teacher['id'];
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $currentTime = date('H:i:s');
        $pdo = Database::connection();

        // Ambil pengaturan batas waktu
        $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name = 'presensi'");
        $settings = [];
        foreach ($settingsStmt->fetchAll() as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $checkInStart = $settings['attendance_check_in_time'] ?? '06:30:00';
        $toleranceMinutes = (int) ($settings['attendance_late_tolerance'] ?? 15);
        $lateLimitTime = date('H:i:s', strtotime($checkInStart) + ($toleranceMinutes * 60));

        $status = 'hadir';

        if ($type === 'daily') {
            // Cek apakah sudah check-in harian hari ini
            $existStmt = $pdo->prepare("SELECT id FROM presensi_guru WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily' LIMIT 1");
            $existStmt->execute([$teacherId, $today]);
            if ($existStmt->fetchColumn() !== false) {
                ApiResponse::error('ALREADY_CHECKED_IN', 'Anda sudah melakukan presensi harian untuk hari ini.', 422);
                return;
            }

            // Hitung status hadir vs terlambat
            if ($currentTime > $lateLimitTime) {
                $status = 'terlambat';
            }

            $locNote = '';
            if ($lat !== null && $lng !== null) {
                $locNote = " [GPS: {$lat}, {$lng}]";
            }
            $finalNote = trim($note . $locNote);

            $insertStmt = $pdo->prepare(
                "INSERT INTO presensi_guru
                    (teacher_id, schedule_id, attendance_date, attendance_type, check_in_at, status, notes, created_at, updated_at)
                 VALUES (?, NULL, ?, 'daily', ?, ?, ?, NOW(), NOW())"
            );
            $insertStmt->execute([
                $teacherId,
                $today,
                $now,
                $status,
                $finalNote !== '' ? $finalNote : null,
            ]);
            $insertedId = (int) $pdo->lastInsertId();

            AuditLog::write(
                (int) $auth['user']['id'],
                'attendance.check_in.daily',
                'presensi_guru',
                $insertedId,
                "Presensi harian mandiri guru: {$status} @ {$now}",
                RateLimiter::clientIp()
            );

            ApiResponse::created([
                'id'              => $insertedId,
                'attendance_type' => 'daily',
                'attendance_date' => $today,
                'check_in_at'     => ApiFormat::iso($now),
                'status'          => $status,
                'method'          => 'self',
                'note'            => $finalNote !== '' ? $finalNote : null,
                'message'         => $status === 'terlambat' ? 'Presensi masuk berhasil dicatat (Terlambat).' : 'Presensi masuk berhasil dicatat (Tepat Waktu).',
            ]);
            return;
        }

        // Tipe = 'session'
        // Validasi jadwal
        $dayOfWeek = (int) date('N');
        $schedStmt = $pdo->prepare(
            "SELECT s.id, s.start_time, s.end_time, s.day_of_week, sub.name AS subject_name, c.name AS class_name
             FROM jadwal s
             JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
             JOIN kelas c ON c.id = cs.class_id
             JOIN mata_pelajaran sub ON sub.id = cs.subject_id
             WHERE s.id = ? AND s.teacher_id = ? AND s.is_active = 1
             LIMIT 1"
        );
        $schedStmt->execute([$scheduleId, $teacherId]);
        $schedule = $schedStmt->fetch();

        if ($schedule === false) {
            ApiResponse::notFound('Jadwal mengajar tidak ditemukan atau bukan milik Anda.');
            return;
        }

        if ((int) $schedule['day_of_week'] !== $dayOfWeek) {
            ApiResponse::error('INVALID_SCHEDULE_DAY', 'Jadwal ini bukan untuk hari ini.', 422);
            return;
        }

        // Cek duplikasi presensi sesi
        $existStmt = $pdo->prepare("SELECT id FROM presensi_guru WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'session' AND schedule_id = ? LIMIT 1");
        $existStmt->execute([$teacherId, $today, $scheduleId]);
        if ($existStmt->fetchColumn() !== false) {
            ApiResponse::error('ALREADY_CHECKED_IN', 'Anda sudah melakukan presensi untuk jam mengajar ini hari ini.', 422);
            return;
        }

        // Hitung status sesi: jika lewat dari jam mulai + 15 menit
        $sessionLateLimit = date('H:i:s', strtotime((string) $schedule['start_time']) + (15 * 60));
        if ($currentTime > $sessionLateLimit) {
            $status = 'terlambat';
        }

        $locNote = '';
        if ($lat !== null && $lng !== null) {
            $locNote = " [GPS: {$lat}, {$lng}]";
        }
        $finalNote = trim($note . $locNote);

        $insertStmt = $pdo->prepare(
            "INSERT INTO presensi_guru
                (teacher_id, schedule_id, attendance_date, attendance_type, check_in_at, status, notes, created_at, updated_at)
             VALUES (?, ?, ?, 'session', ?, ?, ?, NOW(), NOW())"
        );
        $insertStmt->execute([
            $teacherId,
            $scheduleId,
            $today,
            $now,
            $status,
            $finalNote !== '' ? $finalNote : null,
        ]);
        $insertedId = (int) $pdo->lastInsertId();

        AuditLog::write(
            (int) $auth['user']['id'],
            'attendance.check_in.session',
            'presensi_guru',
            $insertedId,
            "Presensi sesi mengajar mandiri guru ID Jadwal {$scheduleId}: {$status} @ {$now}",
            RateLimiter::clientIp()
        );

        ApiResponse::created([
            'id'              => $insertedId,
            'attendance_type' => 'session',
            'schedule_id'     => $scheduleId,
            'attendance_date' => $today,
            'check_in_at'     => ApiFormat::iso($now),
            'status'          => $status,
            'method'          => 'self',
            'schedule'        => [
                'subject' => $schedule['subject_name'],
                'class'   => $schedule['class_name'],
            ],
            'message'         => $status === 'terlambat' ? 'Presensi jam mengajar tercatat (Terlambat).' : 'Presensi jam mengajar tercatat (Tepat Waktu).',
        ]);
    }

    /**
     * POST /api/v1/attendance/check-out
     * Body: { "note": string (opsional) }
     */
    public function checkOut(): void
    {
        $auth = $this->requireTeacher();
        if ($auth === null) {
            return;
        }

        $teacher = $auth['teacher'];
        $teacherId = (int) $teacher['id'];
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $pdo = Database::connection();

        // Cari record presensi harian hari ini
        $stmt = $pdo->prepare("SELECT id, check_in_at, check_out_at FROM presensi_guru WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily' LIMIT 1");
        $stmt->execute([$teacherId, $today]);
        $record = $stmt->fetch();

        if ($record === false || empty($record['check_in_at'])) {
            ApiResponse::error('NOT_CHECKED_IN', 'Anda belum melakukan presensi masuk hari ini.', 422);
            return;
        }

        if (!empty($record['check_out_at'])) {
            ApiResponse::error('ALREADY_CHECKED_OUT', 'Presensi pulang hari ini sudah pernah dicatat sebelumnya.', 422);
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE presensi_guru SET check_out_at = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$now, (int) $record['id']]);

        AuditLog::write(
            (int) $auth['user']['id'],
            'attendance.check_out',
            'presensi_guru',
            (int) $record['id'],
            "Presensi pulang mandiri guru @ {$now}",
            RateLimiter::clientIp()
        );

        ApiResponse::ok([
            'id'           => (int) $record['id'],
            'check_in_at'  => ApiFormat::iso($record['check_in_at']),
            'check_out_at' => ApiFormat::iso($now),
            'message'      => 'Presensi pulang berhasil dicatat. Selamat beristirahat!',
        ]);
    }

    /**
     * GET /api/v1/attendance/history?month=2026-10&type=all|daily|session
     */
    public function history(array $params = []): void
    {
        $auth = $this->requireTeacher();
        if ($auth === null) {
            return;
        }

        $teacherId = (int) $auth['teacher']['id'];
        $month = trim((string) ($_GET['month'] ?? date('Y-m')));
        $type = trim((string) ($_GET['type'] ?? 'all'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $where = ['p.teacher_id = ?', "p.attendance_date LIKE ?"];
        $args = [$teacherId, $month . '-%'];

        if ($type === 'daily') {
            $where[] = "p.attendance_type = 'daily'";
        } elseif ($type === 'session') {
            $where[] = "p.attendance_type = 'session'";
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        // Ringkasan status
        $statStmt = $pdo->prepare(
            "SELECT status, COUNT(*) as cnt
             FROM presensi_guru p
             WHERE {$whereSql}
             GROUP BY status"
        );
        $statStmt->execute($args);
        $stats = [
            'hadir'     => 0,
            'terlambat' => 0,
            'izin'      => 0,
            'sakit'     => 0,
            'dinas'     => 0,
            'alpa'      => 0,
        ];
        foreach ($statStmt->fetchAll() as $s) {
            if (isset($stats[$s['status']])) {
                $stats[$s['status']] = (int) $s['cnt'];
            }
        }

        // Daftar presensi
        $page = ApiFormat::paginate();
        $listStmt = $pdo->prepare(
            "SELECT p.id, p.attendance_date, p.attendance_type, p.check_in_at, p.check_out_at,
                    p.status, p.notes,
                    s.start_time, s.end_time, s.room,
                    sub.name AS subject_name, c.name AS class_name
             FROM presensi_guru p
             LEFT JOIN jadwal s ON s.id = p.schedule_id
             LEFT JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
             LEFT JOIN kelas c ON c.id = cs.class_id
             LEFT JOIN mata_pelajaran sub ON sub.id = cs.subject_id
             WHERE {$whereSql}
             ORDER BY p.attendance_date DESC, p.check_in_at DESC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $listStmt->execute($args);
        $rows = $listStmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'              => (int) $r['id'],
                'date'            => $r['attendance_date'],
                'type'            => $r['attendance_type'],
                'check_in_at'     => ApiFormat::iso($r['check_in_at']),
                'check_out_at'    => ApiFormat::iso($r['check_out_at']),
                'status'          => $r['status'],
                'method'          => 'self',
                'note'            => $r['notes'],
                'schedule'        => $r['attendance_type'] === 'session' ? [
                    'subject'    => $r['subject_name'],
                    'class'      => $r['class_name'],
                    'time_range' => ApiFormat::time($r['start_time']) . ' - ' . ApiFormat::time($r['end_time']),
                    'room'       => $r['room'],
                ] : null,
            ];
        }

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM presensi_guru p WHERE {$whereSql}");
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        ApiResponse::json([
            'month'   => $month,
            'summary' => $stats,
            'items'   => $items,
        ], 200, ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]);
    }
}
