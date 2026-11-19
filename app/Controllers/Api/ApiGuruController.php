<?php
/**
 * API v1 — Manajemen Data Guru.
 *
 * Mendukung:
 * - GET    /api/v1/guru        (Daftar guru, filter q, bidang_studi, is_active)
 * - GET    /api/v1/guru/{id}   (Detail guru)
 * - POST   /api/v1/guru        (Tambah guru baru)
 * - PUT    /api/v1/guru/{id}   (Ubah data guru)
 * - DELETE /api/v1/guru/{id}   (Hapus data guru)
 */

class ApiGuruController
{
    private function requireUser(): ?array
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized('Silakan login terlebih dahulu.');
            return null;
        }
        return $user;
    }

    /**
     * GET /api/v1/guru
     */
    public function index(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $where = ['t.deleted_at IS NULL'];
        $args = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(t.nama_lengkap LIKE ? OR t.nip LIKE ? OR t.nuptk LIKE ? OR t.bidang_studi LIKE ? OR t.subject_specialty LIKE ?)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $mapel = trim((string) ($_GET['bidang_studi'] ?? $_GET['mapel'] ?? ''));
        if ($mapel !== '') {
            $where[] = '(t.bidang_studi = ? OR t.subject_specialty = ?)';
            $args[] = $mapel;
            $args[] = $mapel;
        }

        if (isset($_GET['is_active'])) {
            $active = (int) $_GET['is_active'];
            $where[] = 't.is_active = ?';
            $args[] = $active;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT t.id, t.user_id, t.nip, t.nuptk, t.gelar_depan, t.nama_lengkap, t.gelar_belakang,
                       t.gender, t.jenis_kelamin, t.phone, t.no_hp, t.address, t.alamat, t.foto_url,
                       t.employment_status, t.status_kepegawaian, t.subject_specialty, t.bidang_studi,
                       t.is_active, u.email, u.username
                FROM guru t
                LEFT JOIN pengguna u ON u.id = t.user_id
                WHERE {$whereSql}
                ORDER BY t.is_active DESC, t.nama_lengkap ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = $this->formatRow($r);
        }

        ApiResponse::ok($items);
    }

    /**
     * GET /api/v1/guru/{id}
     */
    public function detail(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID guru tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT t.id, t.user_id, t.nip, t.nuptk, t.gelar_depan, t.nama_lengkap, t.gelar_belakang,
                    t.gender, t.jenis_kelamin, t.phone, t.no_hp, t.address, t.alamat, t.foto_url,
                    t.employment_status, t.status_kepegawaian, t.subject_specialty, t.bidang_studi,
                    t.is_active, u.email, u.username
             FROM guru t
             LEFT JOIN pengguna u ON u.id = t.user_id
             WHERE t.id = ? AND t.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            ApiResponse::notFound('Guru tidak ditemukan.');
            return;
        }

        ApiResponse::ok($this->formatRow($row));
    }

    /**
     * POST /api/v1/guru
     */
    public function store(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $input = ApiResponse::input();
        $nama = ApiResponse::str($input, 'nama_lengkap') ?: ApiResponse::str($input, 'nama');
        $mapel = ApiResponse::str($input, 'bidang_studi') ?: ApiResponse::str($input, 'mapel');
        $nip = ApiResponse::str($input, 'nip') ?: ApiResponse::str($input, 'nip_nuptk');
        $nuptk = ApiResponse::str($input, 'nuptk');
        $email = ApiResponse::str($input, 'email');
        $phone = ApiResponse::str($input, 'no_hp') ?: ApiResponse::str($input, 'telepon');
        $gender = in_array(strtoupper(ApiResponse::str($input, 'gender', 'L')), ['L', 'P'], true) ? strtoupper(ApiResponse::str($input, 'gender', 'L')) : 'L';
        $alamat = ApiResponse::str($input, 'alamat') ?: ApiResponse::str($input, 'address');
        $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 || $input['is_active'] === true ? 1 : 0) : 1;

        if ($nama === '') {
            ApiResponse::validation(['nama_lengkap' => 'Nama lengkap wajib diisi.']);
            return;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            // Jika ada email atau ingin dibuatkan akun pengguna
            $userId = null;
            if ($email !== '') {
                $checkUser = $pdo->prepare("SELECT id FROM pengguna WHERE email = ? LIMIT 1");
                $checkUser->execute([$email]);
                $existingUserId = $checkUser->fetchColumn();

                if ($existingUserId !== false) {
                    $userId = (int) $existingUserId;
                } else {
                    $username = explode('@', $email)[0];
                    // Unikkan username jika bentrok
                    $uCheck = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
                    $uCheck->execute([$username]);
                    if ((int) $uCheck->fetchColumn() > 0) {
                        $username .= '_' . rand(100, 999);
                    }

                    $pStmt = $pdo->prepare(
                        "INSERT INTO pengguna (name, nama, email, username, password_hash, role, is_active, must_change_password, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 'guru', 1, 0, NOW(), NOW())"
                    );
                    $pStmt->execute([
                        $nama,
                        $nama,
                        $email,
                        $username,
                        password_hash('123', PASSWORD_BCRYPT),
                    ]);
                    $userId = (int) $pdo->lastInsertId();
                }
            } else {
                // Buat dummy pengguna agar relasi FK user_id terpenuhi
                $dummyUsername = 'guru_' . time() . '_' . rand(10, 99);
                $dummyEmail = $dummyUsername . '@madrasahku.local';
                $pStmt = $pdo->prepare(
                    "INSERT INTO pengguna (name, nama, email, username, password_hash, role, is_active, must_change_password, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 'guru', 1, 0, NOW(), NOW())"
                );
                $pStmt->execute([
                    $nama,
                    $nama,
                    $dummyEmail,
                    $dummyUsername,
                    password_hash('123', PASSWORD_BCRYPT),
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO guru (user_id, nama_lengkap, nip, nuptk, gender, jenis_kelamin, phone, no_hp, address, alamat, subject_specialty, bidang_studi, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $userId,
                $nama,
                $nip !== '' ? $nip : null,
                $nuptk !== '' ? $nuptk : null,
                $gender,
                $gender,
                $phone !== '' ? $phone : null,
                $phone !== '' ? $phone : null,
                $alamat !== '' ? $alamat : null,
                $alamat !== '' ? $alamat : null,
                $mapel !== '' ? $mapel : null,
                $mapel !== '' ? $mapel : null,
                $isActive,
            ]);

            $newId = (int) $pdo->lastInsertId();
            $pdo->commit();

            AuditLog::write((int) $user['id'], 'guru.create', 'guru', $newId, "Tambah data guru {$nama}", RateLimiter::clientIp());

            // Fetch created data
            $fetchStmt = $pdo->prepare(
                "SELECT t.id, t.user_id, t.nip, t.nuptk, t.gelar_depan, t.nama_lengkap, t.gelar_belakang,
                        t.gender, t.jenis_kelamin, t.phone, t.no_hp, t.address, t.alamat, t.foto_url,
                        t.employment_status, t.status_kepegawaian, t.subject_specialty, t.bidang_studi,
                        t.is_active, u.email, u.username
                 FROM guru t
                 LEFT JOIN pengguna u ON u.id = t.user_id
                 WHERE t.id = ? LIMIT 1"
            );
            $fetchStmt->execute([$newId]);
            $createdRow = $fetchStmt->fetch();

            ApiResponse::created($this->formatRow($createdRow));
        } catch (Throwable $e) {
            $pdo->rollBack();
            ApiResponse::error('SERVER_ERROR', 'Gagal menambahkan guru: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/guru/{id}
     */
    public function update(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID guru tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id, user_id FROM guru WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$id]);
        $existing = $check->fetch();

        if ($existing === false) {
            ApiResponse::notFound('Guru tidak ditemukan.');
            return;
        }

        $input = ApiResponse::input();
        $nama = ApiResponse::str($input, 'nama_lengkap') ?: ApiResponse::str($input, 'nama');
        $mapel = ApiResponse::str($input, 'bidang_studi') ?: ApiResponse::str($input, 'mapel');
        $nip = ApiResponse::str($input, 'nip') ?: ApiResponse::str($input, 'nip_nuptk');
        $nuptk = ApiResponse::str($input, 'nuptk');
        $email = ApiResponse::str($input, 'email');
        $phone = ApiResponse::str($input, 'no_hp') ?: ApiResponse::str($input, 'telepon');
        $gender = in_array(strtoupper(ApiResponse::str($input, 'gender', 'L')), ['L', 'P'], true) ? strtoupper(ApiResponse::str($input, 'gender', 'L')) : 'L';
        $alamat = ApiResponse::str($input, 'alamat') ?: ApiResponse::str($input, 'address');
        $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 || $input['is_active'] === true ? 1 : 0) : 1;

        if ($nama === '') {
            ApiResponse::validation(['nama_lengkap' => 'Nama lengkap wajib diisi.']);
            return;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "UPDATE guru
                 SET nama_lengkap = ?,
                     nip = ?,
                     nuptk = ?,
                     gender = ?,
                     jenis_kelamin = ?,
                     phone = ?,
                     no_hp = ?,
                     address = ?,
                     alamat = ?,
                     subject_specialty = ?,
                     bidang_studi = ?,
                     is_active = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([
                $nama,
                $nip !== '' ? $nip : null,
                $nuptk !== '' ? $nuptk : null,
                $gender,
                $gender,
                $phone !== '' ? $phone : null,
                $phone !== '' ? $phone : null,
                $alamat !== '' ? $alamat : null,
                $alamat !== '' ? $alamat : null,
                $mapel !== '' ? $mapel : null,
                $mapel !== '' ? $mapel : null,
                $isActive,
                $id,
            ]);

            // Update nama dan email di pengguna jika ada
            $userId = (int) ($existing['user_id'] ?? 0);
            if ($userId > 0) {
                if ($email !== '') {
                    $uStmt = $pdo->prepare("UPDATE pengguna SET name = ?, nama = ?, email = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $uStmt->execute([$nama, $nama, $email, $isActive, $userId]);
                } else {
                    $uStmt = $pdo->prepare("UPDATE pengguna SET name = ?, nama = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $uStmt->execute([$nama, $nama, $isActive, $userId]);
                }
            }

            $pdo->commit();

            AuditLog::write((int) $user['id'], 'guru.update', 'guru', $id, "Update data guru ID {$id} ({$nama})", RateLimiter::clientIp());

            $fetchStmt = $pdo->prepare(
                "SELECT t.id, t.user_id, t.nip, t.nuptk, t.gelar_depan, t.nama_lengkap, t.gelar_belakang,
                        t.gender, t.jenis_kelamin, t.phone, t.no_hp, t.address, t.alamat, t.foto_url,
                        t.employment_status, t.status_kepegawaian, t.subject_specialty, t.bidang_studi,
                        t.is_active, u.email, u.username
                 FROM guru t
                 LEFT JOIN pengguna u ON u.id = t.user_id
                 WHERE t.id = ? LIMIT 1"
            );
            $fetchStmt->execute([$id]);
            $updatedRow = $fetchStmt->fetch();

            ApiResponse::ok($this->formatRow($updatedRow));
        } catch (Throwable $e) {
            $pdo->rollBack();
            ApiResponse::error('SERVER_ERROR', 'Gagal memperbarui guru: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/guru/{id}
     */
    public function delete(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID guru tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id, user_id, nama_lengkap FROM guru WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$id]);
        $row = $check->fetch();

        if ($row === false) {
            ApiResponse::notFound('Guru tidak ditemukan.');
            return;
        }

        // Soft delete guru
        $del = $pdo->prepare("UPDATE guru SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
        $del->execute([$id]);

        // Nonaktifkan akun pengguna terkait jika ada
        if (!empty($row['user_id'])) {
            $pdo->prepare("UPDATE pengguna SET is_active = 0, deleted_at = NOW() WHERE id = ?")->execute([(int) $row['user_id']]);
        }

        AuditLog::write((int) $user['id'], 'guru.delete', 'guru', $id, "Hapus data guru ID {$id} ({$row['nama_lengkap']})", RateLimiter::clientIp());

        ApiResponse::ok(['message' => 'Data guru berhasil dihapus.']);
    }

    private function formatRow(array $r): array
    {
        $isActive = (int) $r['is_active'] === 1;
        $nip = (string) ($r['nip'] ?? '');
        $nuptk = (string) ($r['nuptk'] ?? '');
        $nipNuptk = $nip !== '' ? $nip : $nuptk;
        $mapel = (string) ($r['bidang_studi'] ?? $r['subject_specialty'] ?? '');
        $phone = (string) ($r['no_hp'] ?? $r['phone'] ?? '');

        return [
            'id'                 => (int) $r['id'],
            'user_id'            => $r['user_id'] !== null ? (int) $r['user_id'] : null,
            'nama'               => (string) $r['nama_lengkap'],
            'nama_lengkap'       => (string) $r['nama_lengkap'],
            'nip'                => $nip !== '' ? $nip : null,
            'nuptk'              => $nuptk !== '' ? $nuptk : null,
            'nip_nuptk'          => $nipNuptk,
            'gelar_depan'        => $r['gelar_depan'] ?? null,
            'gelar_belakang'     => $r['gelar_belakang'] ?? null,
            'gender'             => $r['gender'] ?? $r['jenis_kelamin'] ?? 'L',
            'jenis_kelamin'      => $r['jenis_kelamin'] ?? $r['gender'] ?? 'L',
            'no_hp'              => $phone,
            'phone'              => $phone,
            'telepon'            => $phone,
            'email'              => (string) ($r['email'] ?? ''),
            'alamat'             => (string) ($r['alamat'] ?? $r['address'] ?? ''),
            'address'            => (string) ($r['address'] ?? $r['alamat'] ?? ''),
            'foto_url'           => ApiFormat::mediaUrl($r['foto_url'] ?? null),
            'bidang_studi'       => $mapel,
            'subject_specialty'  => $mapel,
            'mapel'              => $mapel,
            'status_kepegawaian' => (string) ($r['status_kepegawaian'] ?? $r['employment_status'] ?? ''),
            'is_active'          => $isActive ? 1 : 0,
            'aktif'              => $isActive,
        ];
    }
}
