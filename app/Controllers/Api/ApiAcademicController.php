<?php
/**
 * API v1 — data akademik untuk aplikasi.
 *
 * Jadwal, tugas, dan materi. Semua butuh token. Isinya dibatasi oleh peran
 * pemilik token:
 *
 *   guru    → jadwal dan tugas yang dia ampu
 *   siswa   → jadwal dan tugas kelasnya, plus status pengumpulannya sendiri
 *   staf    → semuanya (admin, operator, kepala, akreditasi)
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
        // sah (baru mendaftar), bukan galat — jadi daftar kosong.
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

        if ($scope['type'] === 'teacher') {
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
            "SELECT s.id, s.day_of_week, s.start_time, s.end_time, s.room,
                    c.id AS class_id, c.name AS class_name, c.level,
                    sub.code AS subject_code, sub.name AS subject_name,
                    y.name AS year_name,
                    tu.name AS teacher_name
             FROM schedules s
             JOIN class_subjects cs ON cs.id = s.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             JOIN subjects sub      ON sub.id = cs.subject_id
             JOIN academic_years y  ON y.id = s.academic_year_id
             LEFT JOIN teachers t   ON t.id = s.teacher_id
             LEFT JOIN users tu     ON tu.id = t.user_id
             WHERE {$whereSql}
             ORDER BY s.day_of_week ASC, s.start_time ASC, c.name ASC"
        );
        $stmt->execute($args);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'          => (int) $row['id'],
                'day_of_week' => (int) $row['day_of_week'],
                'day_name'    => ApiFormat::dayName((int) $row['day_of_week']),
                'start_time'  => ApiFormat::time($row['start_time']),
                'end_time'    => ApiFormat::time($row['end_time']),
                'room'        => $row['room'],
                'class'       => [
                    'id'    => (int) $row['class_id'],
                    'name'  => (string) $row['class_name'],
                    'level' => (int) $row['level'],
                ],
                'subject'     => [
                    'code' => (string) $row['subject_code'],
                    'name' => (string) $row['subject_name'],
                ],
                'teacher_name' => $row['teacher_name'],
                'academic_year' => (string) $row['year_name'],
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

        if ($scope['type'] === 'teacher') {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        } elseif (isset($_GET['class_id']) && (int) $_GET['class_id'] > 0) {
            $where[] = 'cs.class_id = ?';
            $args[] = (int) $_GET['class_id'];
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
             FROM assignments a
             JOIN class_subjects cs ON cs.id = a.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        // Kolom pengumpulan hanya relevan untuk siswa. Untuk guru, yang
        // berguna adalah ringkasan berapa yang sudah masuk.
        $studentJoin = '';
        $studentArgs = [];
        $summarySelect = '(SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submission_count,
                          (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.graded_at IS NULL) AS ungraded_count';

        if ($scope['type'] === 'student') {
            $studentJoin = 'LEFT JOIN submissions my ON my.assignment_id = a.id AND my.student_id = ?';
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
                    {$summarySelect}
             FROM assignments a
             JOIN class_subjects cs ON cs.id = a.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             JOIN subjects sub      ON sub.id = cs.subject_id
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

        if ($scope['type'] === 'teacher') {
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
            $studentJoin = 'LEFT JOIN submissions my ON my.assignment_id = a.id AND my.student_id = ?';
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
                    (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submission_count,
                    (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.graded_at IS NULL) AS ungraded_count
                    {$studentSelect}
             FROM assignments a
             JOIN class_subjects cs ON cs.id = a.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             JOIN subjects sub      ON sub.id = cs.subject_id
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

        if ($scope['type'] === 'teacher') {
            $where[] = 'cs.teacher_id = ?';
            $args[] = $scope['teacher_id'];
        } elseif ($scope['type'] === 'student') {
            $where[] = 'cs.class_id = ?';
            $args[] = $scope['class_id'];
        } elseif (isset($_GET['class_id']) && (int) $_GET['class_id'] > 0) {
            $where[] = 'cs.class_id = ?';
            $args[] = (int) $_GET['class_id'];
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM materials m
             JOIN class_subjects cs ON cs.id = m.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT m.id, m.title, m.description, m.file_path, m.external_url, m.published_at,
                    c.id AS class_id, c.name AS class_name,
                    sub.code AS subject_code, sub.name AS subject_name,
                    u.name AS author_name
             FROM materials m
             JOIN class_subjects cs ON cs.id = m.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             JOIN subjects sub      ON sub.id = cs.subject_id
             LEFT JOIN users u      ON u.id = m.created_by
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
            $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
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

            return ['type' => 'teacher', 'teacher_id' => (int) $teacherId];
        }

        if ($role === 'siswa') {
            $stmt = $pdo->prepare('SELECT id, class_id FROM students WHERE user_id = ? LIMIT 1');
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
}
