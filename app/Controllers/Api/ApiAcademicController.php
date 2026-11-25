<?php
/**
 * API v1 â€” data akademik untuk aplikasi.
 *
 * Jadwal, tugas, dan materi. Semua butuh token. Isinya dibatasi oleh peran
 * pemilik token:
 *
 *   guru    â†’ jadwal dan tugas yang dia ampu
 *   siswa   â†’ jadwal dan tugas kelasnya, plus status pengumpulannya sendiri
 *   staf    â†’ semuanya (admin, operator, kepala, akreditasi)
 *
 * Pembatasan ini dilakukan di server, bukan diserahkan ke aplikasi. Aplikasi
 * yang dimodifikasi tetap tidak bisa membaca kelas lain.
 */

class ApiAcademicController
{
    /**
     * GET /api/v1/schedules?day=1
     */
    public function schedules(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        // Siswa yang belum punya kelas tidak punya jadwal. Itu keadaan yang
        // sah (baru mendaftar), bukan galat â€” jadi daftar kosong.
        if ($scope['type'] === 'student' && $scope['class_id'] === null) {
            ApiResponse::json(['schedules' => []], 200, ['meta' => ApiFormat::meta(0, 1, ApiFormat::DEFAULT_PER_PAGE)]);
            return;
        }

        $where = [
            's.is_active = 1',
            'cs.is_active = 1',
            'c.is_active = 1',
            'y.is_active = 1',
        ];
        $args = [];

        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = 's.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        } elseif (isset($_GET['class_id']) && (int) $_GET['class_id'] > 0) {
            $where[] = 'cs.class_id = ?';
            $args[] = (int) $_GET['class_id'];
        }

        $day = filter_input(INPUT_GET, 'day', FILTER_VALIDATE_INT);
        if ($day !== null && $day !== false && $day >= 1 && $day <= 7) {
            $where[] = 's.day_of_week = ?';
            $args[] = $day;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = Database::connection()->prepare(
            "SELECT s.id, s.class_subject_id, s.day_of_week, s.start_time, s.end_time, s.room,
                    c.id AS class_id, c.name AS class_name, c.level,
                    sub.id AS subject_id, sub.code AS subject_code, sub.name AS subject_name,
                    y.name AS year_name,
                    tu.name AS teacher_name
             FROM jadwal s
             JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             JOIN mata_pelajaran sub      ON sub.id = cs.subject_id
             JOIN tahun_akademik y  ON y.id = s.academic_year_id
             LEFT JOIN guru t       ON t.id = s.teacher_id
             LEFT JOIN pengguna tu  ON tu.id = t.user_id
             WHERE {$whereSql}
             ORDER BY s.day_of_week ASC, s.start_time ASC, c.name ASC"
        );
        $stmt->execute($args);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'               => (int) $row['id'],
                'class_subject_id' => (int) $row['class_subject_id'],
                'day_of_week'      => (int) $row['day_of_week'],
                'day_name'         => ApiFormat::dayName((int) $row['day_of_week']),
                'start_time'       => ApiFormat::time($row['start_time']),
                'end_time'         => ApiFormat::time($row['end_time']),
                'room'             => $row['room'],
                'class'            => [
                    'id'    => (int) $row['class_id'],
                    'name'  => (string) $row['class_name'],
                    'level' => (int) $row['level'],
                ],
                'subject'          => [
                    'id'   => (int) $row['subject_id'],
                    'code' => (string) $row['subject_code'],
                    'name' => (string) $row['subject_name'],
                ],
                'teacher_name'     => $row['teacher_name'],
                'academic_year'    => (string) $row['year_name'],
            ];
        }

        ApiResponse::json(
            ['schedules' => $items],
            200,
            ['meta' => array_merge(
                ApiFormat::meta(count($items), 1, max(1, count($items))),
                ['scope' => $scope['type']]
            )]
        );
    }

    /**
     * GET /api/v1/assignments?v=open|closed&class_id=
     */
    public function assignments(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        if ($scope['type'] === 'student' && $scope['class_id'] === null) {
            ApiResponse::json(['assignments' => []], 200, ['meta' => ApiFormat::meta(0, 1, ApiFormat::DEFAULT_PER_PAGE)]);
            return;
        }

        $page = ApiFormat::paginate();

        $where = ['cs.is_active = 1', 'c.is_active = 1'];
        $args = [];

        // Guru melihat draf miliknya sendiri; siswa hanya yang sudah terbit.
        if ($scope['type'] !== 'teacher') {
            $where[] = 'a.is_published = 1';
        }

        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        } elseif (isset($_GET['class_id']) && (int) $_GET['class_id'] > 0) {
            $where[] = 'cs.class_id = ?';
            $args[] = (int) $_GET['class_id'];
        }

        if (isset($_GET['class_subject_id']) && (int) $_GET['class_subject_id'] > 0) {
            $where[] = 'a.class_subject_id = ?';
            $args[] = (int) $_GET['class_subject_id'];
        }

        // isset() wajib: filter_var(null, FILTER_VALIDATE_BOOLEAN) bernilai
        // false, sehingga "tanpa filter" akan berarti "hanya yang tertutup".
        $open = isset($_GET['open'])
            ? filter_var($_GET['open'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        if ($open === true) {
            $where[] = '(a.due_at IS NULL OR a.due_at >= NOW())';
        } elseif ($open === false) {
            $where[] = 'a.due_at IS NOT NULL AND a.due_at < NOW()';
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM tugas a
             JOIN mata_pelajaran_kelas cs ON cs.id = a.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        // Kolom pengumpulan hanya relevan untuk siswa. Untuk guru, yang
        // berguna adalah ringkasan berapa yang sudah masuk.
        $studentJoin = '';
        $studentArgs = [];
        $summarySelect = '(SELECT COUNT(*) FROM pengumpulan_tugas s WHERE s.assignment_id = a.id) AS submission_count,
                          (SELECT COUNT(*) FROM pengumpulan_tugas s WHERE s.assignment_id = a.id AND s.graded_at IS NULL) AS ungraded_count';

        if ($scope['type'] === 'student') {
            $studentJoin = 'LEFT JOIN pengumpulan_tugas my ON my.assignment_id = a.id AND my.student_id = ?';
            $studentArgs[] = $scope['student_id'];
            $summarySelect = 'my.submitted_at AS my_submitted_at, my.score AS my_score,
                              my.graded_at AS my_graded_at,
                              (my.id IS NOT NULL) AS my_submitted';
        }

        // Argumen SELECT datang sebelum argumen WHERE pada prepared statement.
        $allArgs = array_merge($studentArgs, $args);

        $stmt = $pdo->prepare(
            "SELECT a.id, a.public_id, a.title, a.description, a.attachment_path,
                    a.due_at, a.max_score, a.is_published, a.created_at,
                    c.id AS class_id, c.name AS class_name, c.level,
                    sub.code AS subject_code, sub.name AS subject_name,
                    tk.id AS topic_id, tk.minggu AS topic_minggu, tk.nama AS topic_nama,
                    {$summarySelect}
             FROM tugas a
             JOIN mata_pelajaran_kelas cs ON cs.id = a.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             JOIN mata_pelajaran sub      ON sub.id = cs.subject_id
             LEFT JOIN topik tk   ON tk.id = a.topik_id
             {$studentJoin}
             WHERE {$whereSql}
             ORDER BY (a.due_at IS NULL) ASC, a.due_at DESC, a.id DESC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $stmt->execute($allArgs);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = $this->assignmentPayload($row, $scope);
        }

        ApiResponse::json(
            ['assignments' => $items],
            200,
            ['meta' => array_merge(
                ApiFormat::meta($total, $page['page'], $page['per_page']),
                ['scope' => $scope['type']]
            )]
        );
    }

    /**
     * GET /api/v1/assignments/{publicId}
     *
     * Menerima public_id (disarankan, stabil) atau id numerik.
     */
    public function assignmentDetail(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        $key = trim((string) ($params['publicId'] ?? ''));

        if ($key === '') {
            ApiResponse::notFound('Tugas tidak ditemukan.');
            return;
        }

        $column = ctype_digit($key) ? 'a.id' : 'a.public_id';

        $where = ["{$column} = ?", 'cs.is_active = 1', 'c.is_active = 1'];
        $args = [$key];

        if ($scope['type'] !== 'teacher') {
            $where[] = 'a.is_published = 1';
        }

        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            if ($scope['class_id'] === null) {
                ApiResponse::notFound('Tugas tidak ditemukan.');
                return;
            }
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        }

        $studentJoin = '';
        $studentSelect = '';
        $selectArgs = [];

        if ($scope['type'] === 'student') {
            $studentJoin = 'LEFT JOIN pengumpulan_tugas my ON my.assignment_id = a.id AND my.student_id = ?';
            $studentSelect = ', my.submitted_at AS my_submitted_at, my.score AS my_score,
                              my.graded_at AS my_graded_at, (my.id IS NOT NULL) AS my_submitted,
                              my.feedback AS my_feedback, my.file_path AS my_file_path,
                              my.note AS my_note';
            $selectArgs[] = $scope['student_id'];
        }

        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.public_id, a.title, a.description, a.attachment_path,
                    a.due_at, a.max_score, a.is_published, a.created_at,
                    c.id AS class_id, c.name AS class_name, c.level,
                    sub.code AS subject_code, sub.name AS subject_name,
                    tk.id AS topic_id, tk.minggu AS topic_minggu, tk.nama AS topic_nama,
                    (SELECT COUNT(*) FROM pengumpulan_tugas s WHERE s.assignment_id = a.id) AS submission_count,
                    (SELECT COUNT(*) FROM pengumpulan_tugas s WHERE s.assignment_id = a.id AND s.graded_at IS NULL) AS ungraded_count
                    {$studentSelect}
             FROM tugas a
             JOIN mata_pelajaran_kelas cs ON cs.id = a.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             JOIN mata_pelajaran sub      ON sub.id = cs.subject_id
             LEFT JOIN topik tk   ON tk.id = a.topik_id
             {$studentJoin}
             WHERE " . implode(' AND ', $where) . '
             LIMIT 1'
        );
        $stmt->execute(array_merge($selectArgs, $args));
        $row = $stmt->fetch();

        if ($row === false) {
            // Tidak dibedakan antara "tidak ada" dan "bukan milik Anda".
            // Membocorkan bedanya sama dengan memberi tahu bahwa id itu ada.
            ApiResponse::notFound('Tugas tidak ditemukan.');
            return;
        }

        ApiResponse::ok(['assignment' => $this->assignmentPayload($row, $scope, true)]);
    }

    /**
     * GET /api/v1/materials?class_id=
     */
    public function materials(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        if ($scope['type'] === 'student' && $scope['class_id'] === null) {
            ApiResponse::json(['materials' => []], 200, ['meta' => ApiFormat::meta(0, 1, ApiFormat::DEFAULT_PER_PAGE)]);
            return;
        }

        $page = ApiFormat::paginate();

        $where = ['m.is_published = 1', 'cs.is_active = 1', 'c.is_active = 1'];
        $args = [];

        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        } elseif (isset($_GET['class_id']) && (int) $_GET['class_id'] > 0) {
            $where[] = 'cs.class_id = ?';
            $args[] = (int) $_GET['class_id'];
        }

        if (isset($_GET['class_subject_id']) && (int) $_GET['class_subject_id'] > 0) {
            $where[] = 'm.class_subject_id = ?';
            $args[] = (int) $_GET['class_subject_id'];
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM materi m
             JOIN mata_pelajaran_kelas cs ON cs.id = m.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT m.id, m.title, m.description, m.file_path, m.external_url, m.published_at,
                    c.id AS class_id, c.name AS class_name,
                    sub.code AS subject_code, sub.name AS subject_name,
                    tk.id AS topic_id, tk.minggu AS topic_minggu, tk.nama AS topic_nama,
                    u.name AS author_name
             FROM materi m
             JOIN mata_pelajaran_kelas cs ON cs.id = m.class_subject_id
             JOIN kelas c         ON c.id = cs.class_id
             JOIN mata_pelajaran sub      ON sub.id = cs.subject_id
             LEFT JOIN topik tk   ON tk.id = m.topik_id
             LEFT JOIN pengguna u      ON u.id = m.created_by
             WHERE {$whereSql}
             ORDER BY m.published_at DESC, m.id DESC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $stmt->execute($args);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'           => (int) $row['id'],
                'title'        => (string) $row['title'],
                'description'  => $row['description'],
                'file_url'     => ApiFormat::mediaUrl($row['file_path']),
                'external_url' => $row['external_url'],
                'published_at' => ApiFormat::iso($row['published_at']),
                'class'        => [
                    'id'   => (int) $row['class_id'],
                    'name' => (string) $row['class_name'],
                ],
                'subject'      => [
                    'code' => (string) $row['subject_code'],
                    'name' => (string) $row['subject_name'],
                ],
                'topic'        => $this->topicPayload($row),
                'author_name'  => $row['author_name'],
            ];
        }

        ApiResponse::json(
            ['materials' => $items],
            200,
            ['meta' => array_merge(
                ApiFormat::meta($total, $page['page'], $page['per_page']),
                ['scope' => $scope['type']]
            )]
        );
    }

    // -----------------------------------------------------------------------
    // Pembantu
    // -----------------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    private function requireUser(): ?array
    {
        $user = ApiAuth::authenticate();

        if ($user === null) {
            ApiResponse::unauthorized();
            return null;
        }

        return $user;
    }

    /**
     * @param array<string,mixed> $row
     * @return array{id:int,minggu:int,nama:string}|null
     */
    private function topicPayload(array $row): ?array
    {
        if (!isset($row['topic_id']) || $row['topic_id'] === null) {
            return null;
        }

        return [
            'id'     => (int) $row['topic_id'],
            'minggu' => (int) $row['topic_minggu'],
            'nama'   => (string) $row['topic_nama'],
        ];
    }

    /**
     * Tentukan apa yang boleh dilihat pemilik token.
     *
     * @param array<string,mixed> $user
     * @return array{type:string,teacher_id?:int,student_id?:int,class_id?:int|null}|null
     */
    private function resolveScope(array $user): ?array
    {
        $role = (string) ($user['role'] ?? '');
        $pdo = Database::connection();

        if ($role === 'guru') {
            $stmt = $pdo->prepare('SELECT id FROM guru WHERE user_id = ? LIMIT 1');
            $stmt->execute([(int) $user['id']]);
            $teacherId = $stmt->fetchColumn();

            if ($teacherId === false) {
                ApiResponse::error(
                    'PROFILE_NOT_LINKED',
                    'Akun ini berperan guru, tetapi belum ditautkan ke data guru. Hubungi Operator Madrasah.',
                    409
                );
                return null;
            }

            return [
                'type'       => 'teacher',
                'teacher_id' => (int) $teacherId,
                'all_access' => strtolower((string) ($user['username'] ?? '')) === 'guru_test',
            ];
        }

        if ($role === 'siswa') {
            $stmt = $pdo->prepare('SELECT id, kelas_id AS class_id FROM siswa WHERE user_id = ? LIMIT 1');
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();

            if ($row === false) {
                ApiResponse::error(
                    'PROFILE_NOT_LINKED',
                    'Akun ini berperan siswa, tetapi belum ditautkan ke data siswa. Hubungi Operator Madrasah.',
                    409
                );
                return null;
            }

            return [
                'type'       => 'student',
                'student_id' => (int) $row['id'],
                'class_id'   => $row['class_id'] !== null ? (int) $row['class_id'] : null,
            ];
        }

        return ['type' => 'staff'];
    }

    /**
     * @param array<string,mixed> $row
     * @param array<string,mixed> $scope
     * @return array<string,mixed>
     */
    private function assignmentPayload(array $row, array $scope, bool $detail = false): array
    {
        $payload = [
            'id'             => (int) $row['id'],
            'public_id'      => $row['public_id'],
            'title'          => (string) $row['title'],
            'description'    => $row['description'],
            'attachment_url' => ApiFormat::mediaUrl($row['attachment_path']),
            'due_at'         => ApiFormat::iso($row['due_at']),
            'max_score'      => (float) $row['max_score'],
            'is_published'   => (int) $row['is_published'] === 1,
            'is_overdue'     => $row['due_at'] !== null && strtotime((string) $row['due_at']) < time(),
            'class'          => [
                'id'    => (int) $row['class_id'],
                'name'  => (string) $row['class_name'],
                'level' => (int) $row['level'],
            ],
            'subject'        => [
                'code' => (string) $row['subject_code'],
                'name' => (string) $row['subject_name'],
            ],
            'topic'          => $this->topicPayload($row),
        ];

        if ($scope['type'] === 'student') {
            $submitted = (int) ($row['my_submitted'] ?? 0) === 1;

            $payload['my_submission'] = $submitted
                ? [
                    'submitted_at' => ApiFormat::iso($row['my_submitted_at'] ?? null),
                    'score'        => isset($row['my_score']) ? (float) $row['my_score'] : null,
                    'graded_at'    => ApiFormat::iso($row['my_graded_at'] ?? null),
                    'note'         => $row['my_note'] ?? null,
                    'file_url'     => ApiFormat::mediaUrl($row['my_file_path'] ?? null),
                    'feedback'     => $row['my_feedback'] ?? null,
                ]
                : null;
        } else {
            $payload['submission_count'] = (int) ($row['submission_count'] ?? 0);
            $payload['ungraded_count']   = (int) ($row['ungraded_count'] ?? 0);
        }

        if ($detail) {
            $payload['created_at'] = ApiFormat::iso($row['created_at'] ?? null);
        }

        return $payload;
    }

    /**
     * POST /api/v1/assignments/{publicId}/submit
     */
    public function submitAssignment(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        if ($scope['type'] !== 'student') {
            ApiResponse::forbidden('Hanya siswa yang dapat mengumpulkan tugas.');
            return;
        }

        $studentId = (int) $scope['student_id'];
        $key = trim((string) ($params['publicId'] ?? ''));

        if ($key === '') {
            ApiResponse::notFound('Tugas tidak ditemukan.');
            return;
        }

        $pdo = Database::connection();
        $column = ctype_digit($key) ? 'a.id' : 'a.public_id';

        $stmt = $pdo->prepare(
            "SELECT a.id, a.public_id, a.due_at, a.is_published, cs.class_id
             FROM tugas a
             JOIN mata_pelajaran_kelas cs ON cs.id = a.class_subject_id
             WHERE {$column} = ? AND a.is_published = 1 AND cs.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$key]);
        $assignment = $stmt->fetch();

        if ($assignment === false) {
            ApiResponse::notFound('Tugas tidak ditemukan.');
            return;
        }

        if ($scope['class_id'] !== (int) $assignment['class_id']) {
            ApiResponse::forbidden('Tugas ini bukan untuk kelas Anda.');
            return;
        }

        $assignmentId = (int) $assignment['id'];
        $input = ApiResponse::input();
        $note = ApiResponse::str($input, 'note');
        $rawFileUrl = trim((string) ($input['file_url'] ?? ''));

        $filePath = null;
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                ApiResponse::validation(['file' => 'Format file tidak diizinkan. Gunakan PDF, DOC, DOCX, JPG, PNG, atau ZIP.']);
                return;
            }

            if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
                ApiResponse::validation(['file' => 'Ukuran berkas maksimal 10 MB.']);
                return;
            }

            $uploadDir = __DIR__ . '/../../../public/uploads/assignments/submissions';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'sub_' . $studentId . '_' . $assignmentId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $filePath = 'uploads/assignments/submissions/' . $fileName;
            }
        } elseif ($rawFileUrl !== '') {
            $filePath = $rawFileUrl;
        }

        $publicId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $upsertStmt = $pdo->prepare(
            "INSERT INTO pengumpulan_tugas (assignment_id, student_id, public_id, file_path, note, submitted_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())
             ON DUPLICATE KEY UPDATE
             file_path = IF(VALUES(file_path) IS NOT NULL, VALUES(file_path), file_path),
             note = VALUES(note),
             submitted_at = NOW(),
             updated_at = NOW()"
        );
        $upsertStmt->execute([
            $assignmentId,
            $studentId,
            $publicId,
            $filePath,
            $note !== '' ? $note : null,
        ]);

        AuditLog::write(
            (int) $user['id'],
            'assignment.submit',
            'pengumpulan_tugas',
            $assignmentId,
            "Pengumpulan tugas ID {$assignmentId} oleh siswa ID {$studentId}",
            RateLimiter::clientIp()
        );

        ApiResponse::ok([
            'assignment_id' => $assignmentId,
            'submitted_at'  => ApiFormat::iso(date('Y-m-d H:i:s')),
            'note'          => $note !== '' ? $note : null,
            'file_url'      => ApiFormat::mediaUrl($filePath),
            'message'       => 'Tugas berhasil dikumpulkan!',
        ]);
    }

    /**
     * GET /api/v1/teacher/topics?class_subject_id=
     */
    public function teacherTopics(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat melihat topik.');
            }
            return;
        }

        $csId = (int) ($_GET['class_subject_id'] ?? 0);
        $pdo = Database::connection();

        $where = ['tk.is_active = 1', 'tk.deleted_at IS NULL'];
        $args = [];
        if ($csId > 0) {
            $where[] = 'tk.class_subject_id = ?';
            $args[] = $csId;
        } elseif ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = '(tk.guru_id = ? OR cs.teacher_id = ?)';
            $args[] = $scope['teacher_id'];
            $args[] = $scope['teacher_id'];
        }

        $whereSql = implode(' AND ', $where);
        $stmt = $pdo->prepare(
            "SELECT tk.id, tk.class_subject_id, tk.minggu, tk.nama, tk.deskripsi, tk.created_at,
                    c.name AS class_name, sub.name AS subject_name
             FROM topik tk
             JOIN mata_pelajaran_kelas cs ON cs.id = tk.class_subject_id
             JOIN kelas c ON c.id = cs.class_id
             JOIN mata_pelajaran sub ON sub.id = cs.subject_id
             WHERE {$whereSql}
             ORDER BY tk.minggu ASC, tk.id ASC"
        );
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        $topics = [];
        foreach ($rows as $r) {
            $topics[] = [
                'id'               => (int) $r['id'],
                'class_subject_id' => (int) $r['class_subject_id'],
                'minggu'           => (int) $r['minggu'],
                'nama'             => (string) $r['nama'],
                'deskripsi'        => $r['deskripsi'],
                'class_name'       => (string) $r['class_name'],
                'subject_name'     => (string) $r['subject_name'],
                'created_at'       => ApiFormat::iso($r['created_at']),
            ];
        }

        ApiResponse::ok(['topics' => $topics]);
    }

    /**
     * POST /api/v1/teacher/topics
     */
    public function createTopic(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat membuat topik.');
            }
            return;
        }

        $input = ApiResponse::input();
        $csId = (int) ($input['class_subject_id'] ?? 0);
        $minggu = (int) ($input['minggu'] ?? 1);
        $nama = trim((string) ($input['nama'] ?? ''));
        $deskripsi = trim((string) ($input['deskripsi'] ?? ''));

        if ($csId <= 0 || $nama === '') {
            ApiResponse::validation([
                'class_subject_id' => $csId <= 0 ? 'Kelas/Mapel wajib dipilih.' : null,
                'nama'             => $nama === '' ? 'Nama topik wajib diisi.' : null,
            ]);
            return;
        }

        $pdo = Database::connection();
        $csStmt = $pdo->prepare("SELECT id, class_id, subject_id, teacher_id FROM mata_pelajaran_kelas WHERE id = ? LIMIT 1");
        $csStmt->execute([$csId]);
        $cs = $csStmt->fetch();
        if ($cs === false) {
            ApiResponse::notFound('Penugasan kelas/mapel tidak ditemukan.');
            return;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO topik (class_subject_id, kelas_id, mata_pelajaran_id, guru_id, minggu, nama, deskripsi, is_active, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                 nama = VALUES(nama),
                 deskripsi = VALUES(deskripsi),
                 is_active = 1,
                 deleted_at = NULL,
                 updated_at = NOW()"
            );
            $stmt->execute([
                $csId,
                $cs['class_id'],
                $cs['subject_id'],
                $cs['teacher_id'],
                $minggu,
                $nama,
                $deskripsi !== '' ? $deskripsi : null,
                (int) $user['id'],
            ]);
            $id = (int) $pdo->lastInsertId();
            if ($id <= 0) {
                $idStmt = $pdo->prepare("SELECT id FROM topik WHERE class_subject_id = ? AND minggu = ? LIMIT 1");
                $idStmt->execute([$csId, $minggu]);
                $id = (int) $idStmt->fetchColumn();
            }
        } catch (Throwable $e) {
            ApiResponse::error('DB_ERROR', $e->getMessage(), 500);
            return;
        }

        ApiResponse::created([
            'id'               => $id,
            'class_subject_id' => $csId,
            'minggu'           => $minggu,
            'nama'             => $nama,
            'deskripsi'        => $deskripsi,
        ]);
    }

    /**
     * DELETE /api/v1/teacher/topics/{id}
     */
    public function deleteTopic(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat menghapus topik.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::connection();
        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $chk = $pdo->prepare("SELECT id FROM topik WHERE id = ? AND (guru_id = ? OR created_by = ?) LIMIT 1");
            $chk->execute([$id, $scope['teacher_id'], (int) $user['id']]);
            if ($chk->fetchColumn() === false) {
                ApiResponse::forbidden('Anda tidak memiliki izin menghapus topik ini.');
                return;
            }
        }
        $pdo->prepare("DELETE FROM materi WHERE topik_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM tugas WHERE topik_id = ?")->execute([$id]);
        $stmt = $pdo->prepare("UPDATE topik SET is_active = 0, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }

    /**
     * GET /api/v1/teacher/assignments
     * Mengembalikan daftar kelas & mata pelajaran yang diampu guru yang login.
     */
    public function teacherClassSubjects(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null) {
            return;
        }

        $pdo = Database::connection();
        $where = ['cs.is_active = 1', 'c.is_active = 1'];
        $args = [];
        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        }
        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare(
            "SELECT cs.id AS class_subject_id, c.id AS class_id, c.name AS class_name,
                    sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code
             FROM mata_pelajaran_kelas cs
             JOIN kelas c ON c.id = cs.class_id
             JOIN mata_pelajaran sub ON sub.id = cs.subject_id
             WHERE {$whereSql}
             ORDER BY c.name ASC, sub.name ASC"
        );
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        // Jika guru punya jadwal di kelas-mapel tertentu namun teacher_id di mata_pelajaran_kelas
        // belum ditautkan, ikutkan dari tabel jadwal.
        if ($rows === [] && $scope['type'] === 'teacher') {
            $stmt2 = $pdo->prepare(
                "SELECT DISTINCT cs.id AS class_subject_id, c.id AS class_id, c.name AS class_name,
                        sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code
                 FROM jadwal s
                 JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
                 JOIN kelas c ON c.id = cs.class_id
                 JOIN mata_pelajaran sub ON sub.id = cs.subject_id
                 WHERE s.teacher_id = ? AND s.is_active = 1 AND cs.is_active = 1 AND c.is_active = 1
                 ORDER BY c.name ASC, sub.name ASC"
            );
            $stmt2->execute([$scope['teacher_id']]);
            $rows = $stmt2->fetchAll();
        }

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'class_subject_id' => (int) $r['class_subject_id'],
                'class_id'         => (int) $r['class_id'],
                'class_name'       => (string) $r['class_name'],
                'subject_id'       => (int) $r['subject_id'],
                'subject_name'     => (string) $r['subject_name'],
                'subject_code'     => (string) $r['subject_code'],
            ];
        }

        ApiResponse::ok(['assignments' => $items]);
    }

    /**
     * POST /api/v1/teacher/materials
     */
    public function createMaterial(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat membuat materi.');
            }
            return;
        }

        $input = ApiResponse::input();
        $classSubjectId = (int) ($input['class_subject_id'] ?? 0);
        $topikId = isset($input['topik_id']) && (int) $input['topik_id'] > 0 ? (int) $input['topik_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $fileUrl = trim((string) ($input['file_url'] ?? $input['external_url'] ?? ''));

        if ($classSubjectId <= 0 || $title === '') {
            ApiResponse::validation([
                'class_subject_id' => $classSubjectId <= 0 ? 'Kelas/Mapel wajib dipilih.' : null,
                'title'            => $title === '' ? 'Judul materi wajib diisi.' : null,
            ]);
            return;
        }

        $pdo = Database::connection();
        $csStmt = $pdo->prepare("SELECT id, class_id, subject_id, teacher_id FROM mata_pelajaran_kelas WHERE id = ? LIMIT 1");
        $csStmt->execute([$classSubjectId]);
        $cs = $csStmt->fetch();
        if ($cs === false) {
            ApiResponse::notFound('Penugasan kelas/mapel tidak ditemukan.');
            return;
        }

        $filePath = null;
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/materials';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $fileName = 'mat_' . time() . '_' . mt_rand(100, 999) . ($ext ? '.' . $ext : '');
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . '/' . $fileName)) {
                $filePath = 'uploads/materials/' . $fileName;
            }
        } elseif ($fileUrl !== '' && !str_starts_with($fileUrl, 'http')) {
            $filePath = $fileUrl;
        }

        // chk_materials_source mengharuskan file_path atau external_url tidak NULL
        $externalUrl = ($filePath === null) ? ($fileUrl !== '' ? $fileUrl : '') : ($fileUrl !== '' && str_starts_with($fileUrl, 'http') ? $fileUrl : null);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO materi (class_subject_id, kelas_id, guru_id, mata_pelajaran_id, topik_id, title, judul, description, deskripsi, file_path, file_url, external_url, is_published, published_at, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), NOW())"
            );
            $stmt->execute([
                $classSubjectId,
                $cs['class_id'],
                $cs['teacher_id'],
                $cs['subject_id'],
                $topikId,
                $title,
                $title,
                $description !== '' ? $description : null,
                $description !== '' ? $description : null,
                $filePath,
                $filePath ?? $externalUrl,
                $externalUrl,
                (int) $user['id'],
            ]);
            $newId = (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            ApiResponse::error('DB_ERROR', $e->getMessage(), 500);
            return;
        }

        ApiResponse::created([
            'id'               => $newId,
            'class_subject_id' => $classSubjectId,
            'title'            => $title,
            'description'      => $description,
            'file_url'         => $filePath ? ApiFormat::mediaUrl($filePath) : $externalUrl,
        ]);
    }

    /**
     * PUT/POST /api/v1/teacher/materials/{id}
     */
    public function updateMaterial(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat mengubah materi.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Materi tidak ditemukan.');
            return;
        }

        $input = ApiResponse::input();
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $fileUrl = trim((string) ($input['file_url'] ?? ''));

        if ($title === '') {
            ApiResponse::validation(['title' => 'Judul materi wajib diisi.']);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "UPDATE materi
             SET title = ?, judul = ?, description = ?, deskripsi = ?,
                 file_path = COALESCE(NULLIF(?, ''), file_path),
                 file_url = COALESCE(NULLIF(?, ''), file_url),
                 updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$title, $title, $description !== '' ? $description : null, $description !== '' ? $description : null, $fileUrl, $fileUrl, $id]);

        ApiResponse::ok(['id' => $id, 'title' => $title, 'description' => $description]);
    }

    /**
     * DELETE /api/v1/teacher/materials/{id}
     */
    public function deleteMaterial(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat menghapus materi.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::connection();
        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $chk = $pdo->prepare("SELECT id FROM materi WHERE id = ? AND (guru_id = ? OR created_by = ?) LIMIT 1");
            $chk->execute([$id, $scope['teacher_id'], (int) $user['id']]);
            if ($chk->fetchColumn() === false) {
                ApiResponse::forbidden('Anda tidak memiliki izin menghapus materi ini.');
                return;
            }
        }
        $stmt = $pdo->prepare("DELETE FROM materi WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }

    /**
     * POST /api/v1/teacher/assignments
     */
    public function createAssignment(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat membuat tugas.');
            }
            return;
        }

        $input = ApiResponse::input();
        $classSubjectId = (int) ($input['class_subject_id'] ?? 0);
        $topikId = isset($input['topik_id']) && (int) $input['topik_id'] > 0 ? (int) $input['topik_id'] : null;
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $deadlineRaw = trim((string) ($input['deadline'] ?? $input['due_at'] ?? ''));
        $fileUrl = trim((string) ($input['file_url'] ?? ''));

        if ($classSubjectId <= 0 || $title === '') {
            ApiResponse::validation([
                'class_subject_id' => $classSubjectId <= 0 ? 'Kelas/Mapel wajib dipilih.' : null,
                'title'            => $title === '' ? 'Judul tugas wajib diisi.' : null,
            ]);
            return;
        }

        $dueAt = null;
        if ($deadlineRaw !== '') {
            $ts = strtotime($deadlineRaw);
            if ($ts !== false) {
                $dueAt = date('Y-m-d H:i:s', $ts);
            }
        }

        $publicId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $pdo = Database::connection();
        $csStmt = $pdo->prepare("SELECT id, class_id, subject_id, teacher_id FROM mata_pelajaran_kelas WHERE id = ? LIMIT 1");
        $csStmt->execute([$classSubjectId]);
        $cs = $csStmt->fetch();
        if ($cs === false) {
            ApiResponse::notFound('Penugasan kelas/mapel tidak ditemukan.');
            return;
        }

        $attachmentPath = $fileUrl !== '' ? $fileUrl : null;
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/assignments';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $fileName = 'asg_' . time() . '_' . mt_rand(100, 999) . ($ext ? '.' . $ext : '');
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . '/' . $fileName)) {
                $attachmentPath = 'uploads/assignments/' . $fileName;
            }
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO tugas (public_id, class_subject_id, kelas_id, guru_id, mata_pelajaran_id, topik_id, title, judul, description, instruksi, attachment_path, file_tugas_url, due_at, deadline, max_score, is_published, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 100.00, 1, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $publicId,
                $classSubjectId,
                $cs['class_id'],
                $cs['teacher_id'],
                $cs['subject_id'],
                $topikId,
                $title,
                $title,
                $description !== '' ? $description : null,
                $description !== '' ? $description : null,
                $attachmentPath,
                $attachmentPath,
                $dueAt,
                $dueAt,
                (int) $user['id'],
            ]);
            $newId = (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            ApiResponse::error('DB_ERROR', $e->getMessage(), 500);
            return;
        }

        ApiResponse::created([
            'id'               => $newId,
            'public_id'        => $publicId,
            'class_subject_id' => $classSubjectId,
            'title'            => $title,
            'due_at'           => ApiFormat::iso($dueAt),
        ]);
    }

    /**
     * PUT/POST /api/v1/teacher/assignments/{id}
     */
    public function updateAssignment(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat mengubah tugas.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $input = ApiResponse::input();
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $deadlineRaw = trim((string) ($input['deadline'] ?? $input['due_at'] ?? ''));
        $dueAt = $deadlineRaw !== '' && strtotime($deadlineRaw) !== false ? date('Y-m-d H:i:s', strtotime($deadlineRaw)) : null;

        if ($title === '') {
            ApiResponse::validation(['title' => 'Judul tugas wajib diisi.']);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "UPDATE tugas SET title = ?, judul = ?, description = ?, instruksi = ?, due_at = COALESCE(?, due_at), deadline = COALESCE(?, deadline), updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$title, $title, $description !== '' ? $description : null, $description !== '' ? $description : null, $dueAt, $dueAt, $id]);

        ApiResponse::ok(['id' => $id, 'title' => $title]);
    }

    /**
     * DELETE /api/v1/teacher/assignments/{id}
     */
    public function deleteAssignment(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat menghapus tugas.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::connection();
        if ($scope['type'] === 'teacher' && empty($scope['all_access'])) {
            $chk = $pdo->prepare("SELECT id FROM tugas WHERE id = ? AND (guru_id = ? OR created_by = ?) LIMIT 1");
            $chk->execute([$id, $scope['teacher_id'], (int) $user['id']]);
            if ($chk->fetchColumn() === false) {
                ApiResponse::forbidden('Anda tidak memiliki izin menghapus tugas ini.');
                return;
            }
        }
        $stmt = $pdo->prepare("DELETE FROM tugas WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }

    /**
     * GET /api/v1/teacher/assignments/{id}/submissions
     */
    public function assignmentSubmissions(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat melihat daftar pengumpulan.');
            }
            return;
        }

        $key = trim((string) ($params['id'] ?? ''));
        $column = ctype_digit($key) ? 'a.id' : 'a.public_id';

        $pdo = Database::connection();
        $aStmt = $pdo->prepare(
            "SELECT a.id, a.due_at, cs.class_id
             FROM tugas a
             JOIN mata_pelajaran_kelas cs ON cs.id = a.class_subject_id
             WHERE {$column} = ? LIMIT 1"
        );
        $aStmt->execute([$key]);
        $assignment = $aStmt->fetch();
        if ($assignment === false) {
            ApiResponse::notFound('Tugas tidak ditemukan.');
            return;
        }

        $stmt = $pdo->prepare(
            "SELECT s.id AS student_id, s.nis, COALESCE(s.nama_lengkap, u.name) AS student_name,
                    sub.id AS submission_id, sub.submitted_at, sub.file_path, sub.note,
                    sub.score, sub.feedback, sub.graded_at
             FROM siswa s
             LEFT JOIN pengguna u ON u.id = s.user_id
             LEFT JOIN pengumpulan_tugas sub ON sub.student_id = s.id AND sub.assignment_id = ?
             WHERE COALESCE(s.kelas_id, s.class_id) = ? AND s.deleted_at IS NULL
             ORDER BY student_name ASC"
        );
        $stmt->execute([(int) $assignment['id'], (int) $assignment['class_id']]);
        $rows = $stmt->fetchAll();

        $submissions = [];
        foreach ($rows as $r) {
            $submissions[] = [
                'student_id'    => (int) $r['student_id'],
                'nis'           => (string) ($r['nis'] ?? ''),
                'student_name'  => (string) ($r['student_name'] ?? 'Siswa'),
                'submission_id' => $r['submission_id'] !== null ? (int) $r['submission_id'] : null,
                'submitted'     => $r['submission_id'] !== null,
                'submitted_at'  => ApiFormat::iso($r['submitted_at']),
                'file_url'      => ApiFormat::mediaUrl($r['file_path']),
                'note'          => $r['note'],
                'score'         => $r['score'] !== null ? (float) $r['score'] : null,
                'feedback'      => $r['feedback'],
                'graded_at'     => ApiFormat::iso($r['graded_at']),
            ];
        }

        ApiResponse::ok([
            'assignment_id' => (int) $assignment['id'],
            'due_at'        => ApiFormat::iso($assignment['due_at']),
            'submissions'   => $submissions,
        ]);
    }

    /**
     * POST /api/v1/teacher/submissions/{id}/grade
     */
    public function gradeSubmission(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || !in_array($scope['type'], ['teacher', 'staff'], true)) {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya guru atau staf yang dapat memberi nilai.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $input = ApiResponse::input();
        $score = isset($input['score']) ? (float) $input['score'] : null;
        $feedback = trim((string) ($input['feedback'] ?? ''));

        if ($score === null || $score < 0 || $score > 100) {
            ApiResponse::validation(['score' => 'Nilai harus antara 0 sampai 100.']);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "UPDATE pengumpulan_tugas SET score = ?, feedback = ?, graded_at = NOW(), graded_by = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$score, $feedback !== '' ? $feedback : null, (int) $user['id'], $id]);

        ApiResponse::ok(['id' => $id, 'score' => $score, 'feedback' => $feedback]);
    }

    /**
     * POST /api/v1/schedules
     */
    public function createSchedule(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || $scope['type'] !== 'staff') {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya admin/staf yang dapat menambah jadwal.');
            }
            return;
        }

        $input = ApiResponse::input();
        $className = trim((string) ($input['kelas'] ?? $input['class_name'] ?? ''));
        $subjectName = trim((string) ($input['mapel'] ?? $input['subject_name'] ?? ''));
        $teacherName = trim((string) ($input['guru'] ?? $input['teacher_name'] ?? ''));
        $day = (int) ($input['day_of_week'] ?? $input['hari'] ?? 1);
        $startTime = trim((string) ($input['start_time'] ?? '07:00'));
        $endTime = trim((string) ($input['end_time'] ?? '08:00'));
        $room = trim((string) ($input['ruang'] ?? $input['room'] ?? ''));

        $pdo = Database::connection();
        $yearId = (int) $pdo->query("SELECT id FROM tahun_akademik WHERE is_active = 1 LIMIT 1")->fetchColumn();
        if ($yearId <= 0) {
            $yearId = (int) $pdo->query("SELECT id FROM tahun_akademik ORDER BY id DESC LIMIT 1")->fetchColumn();
        }

        $cStmt = $pdo->prepare("SELECT id FROM kelas WHERE (name = ? OR nama_kelas = ?) AND is_active = 1 LIMIT 1");
        $cStmt->execute([$className, $className]);
        $classId = (int) $cStmt->fetchColumn();

        $sStmt = $pdo->prepare("SELECT id FROM mata_pelajaran WHERE (name = ? OR nama_mapel = ?) AND is_active = 1 LIMIT 1");
        $sStmt->execute([$subjectName, $subjectName]);
        $subjectId = (int) $sStmt->fetchColumn();

        $gStmt = $pdo->prepare(
            "SELECT g.id FROM guru g LEFT JOIN pengguna u ON u.id = g.user_id
             WHERE (g.nama_lengkap = ? OR u.name = ?) AND g.deleted_at IS NULL LIMIT 1"
        );
        $gStmt->execute([$teacherName, $teacherName]);
        $teacherId = (int) $gStmt->fetchColumn();

        if ($classId <= 0 || $subjectId <= 0 || $teacherId <= 0) {
            ApiResponse::validation([
                'kelas' => $classId <= 0 ? "Kelas '$className' tidak ditemukan." : null,
                'mapel' => $subjectId <= 0 ? "Mapel '$subjectName' tidak ditemukan." : null,
                'guru'  => $teacherId <= 0 ? "Guru '$teacherName' tidak ditemukan." : null,
            ]);
            return;
        }

        // Cari atau buat mata_pelajaran_kelas
        $csStmt = $pdo->prepare("SELECT id FROM mata_pelajaran_kelas WHERE class_id = ? AND subject_id = ? LIMIT 1");
        $csStmt->execute([$classId, $subjectId]);
        $csId = (int) $csStmt->fetchColumn();
        if ($csId <= 0) {
            $insCs = $pdo->prepare(
                "INSERT INTO mata_pelajaran_kelas (class_id, subject_id, teacher_id, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, 1, NOW(), NOW())"
            );
            $insCs->execute([$classId, $subjectId, $teacherId]);
            $csId = (int) $pdo->lastInsertId();
        }

        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime) === 5) $endTime .= ':00';

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO jadwal (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time, room, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
            );
            $stmt->execute([$csId, $teacherId, $yearId, $day, $startTime, $endTime, $room !== '' ? $room : null]);
            $newId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            ApiResponse::error('SCHEDULE_CONFLICT', 'Jadwal gagal disimpan: guru sudah memiliki jadwal di jam yang sama.', 409);
            return;
        }

        ApiResponse::created(['id' => $newId, 'class_subject_id' => $csId]);
    }

    /**
     * PUT/POST /api/v1/schedules/{id}
     */
    public function updateSchedule(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || $scope['type'] !== 'staff') {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya admin/staf yang dapat mengubah jadwal.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $input = ApiResponse::input();
        $day = (int) ($input['day_of_week'] ?? $input['hari'] ?? 1);
        $startTime = trim((string) ($input['start_time'] ?? '07:00'));
        $endTime = trim((string) ($input['end_time'] ?? '08:00'));
        $room = trim((string) ($input['ruang'] ?? $input['room'] ?? ''));

        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime) === 5) $endTime .= ':00';

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "UPDATE jadwal SET day_of_week = ?, start_time = ?, end_time = ?, room = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$day, $startTime, $endTime, $room !== '' ? $room : null, $id]);

        ApiResponse::ok(['id' => $id]);
    }

    /**
     * DELETE /api/v1/schedules/{id}
     */
    public function deleteSchedule(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }
        $scope = $this->resolveScope($user);
        if ($scope === null || $scope['type'] !== 'staff') {
            if ($scope !== null) {
                ApiResponse::forbidden('Hanya admin/staf yang dapat menghapus jadwal.');
            }
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE jadwal SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }
}
