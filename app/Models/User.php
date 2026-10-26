<?php
/**
 * Pengguna panel (tabel users) — sisi baca dan tulis.
 *
 * Kelas ini tidak memutuskan siapa yang boleh mengelola pengguna; itu tugas
 * AdminGate. Yang diurus di sini hanya bentuk data dan aturan yang melekat
 * pada akun:
 *
 *   - email dan username unik di antara baris yang belum dihapus,
 *   - akun baru selalu must_change_password = 1 (sandi sementara),
 *   - menonaktifkan akun mencabut token API miliknya,
 *   - akun tidak boleh menghapus atau menonaktifkan dirinya sendiri,
 *     dan admin terakhir tidak boleh dihapus/diturunkan.
 *
 * Catatan: `deleted_at` dipakai sebagai hapus lunak supaya jejak audit
 * (activity_logs.user_id) tidak menunjuk ke baris yang hilang.
 */

class User
{
    /** Role yang boleh masuk panel admin. */
    public const PANEL_ROLES = ['admin', 'operator', 'kepala', 'akreditasi'];

    /** Semua role yang dikenal sistem. */
    public const ROLES = [
        'admin'      => 'Super Admin',
        'operator'   => 'Operator Madrasah',
        'kepala'     => 'Kepala Madrasah',
        'akreditasi' => 'Tim Akreditasi',
        'guru'       => 'Guru',
        'siswa'      => 'Siswa',
    ];

    /** Role yang boleh dipilih di formulir panel. Siswa dibuat lewat PPDB. */
    public const ASSIGNABLE_ROLES = ['admin', 'operator', 'kepala', 'akreditasi', 'guru'];

    public const STATUSES = [
        'aktif'    => 'Aktif',
        'nonaktif' => 'Nonaktif',
        'terkunci' => 'Terkunci',
    ];

    /**
     * Daftar pengguna untuk tabel panel.
     *
     * @param array{q?:string,role?:string,status?:string,page?:int,per_page?:int} $filters
     * @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int,per_page:int}
     */
    public static function paginate(array $filters = []): array
    {
        $where = ['u.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Satu placeholder satu kali pakai: koneksi ini memakai prepared
            // statement asli, dan MySQL menolak nama parameter yang diulang.
            $where[] = '(u.name LIKE :q1 OR u.email LIKE :q2 OR u.username LIKE :q3)';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        $role = (string) ($filters['role'] ?? '');
        if ($role !== '' && isset(self::ROLES[$role])) {
            $where[] = 'u.role = :role';
            $params[':role'] = $role;
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'aktif') {
            $where[] = 'u.is_active = 1';
        } elseif ($status === 'nonaktif') {
            $where[] = 'u.is_active = 0';
        } elseif ($status === 'terkunci') {
            $where[] = 'u.locked_until IS NOT NULL AND u.locked_until > NOW()';
        }

        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $pdo = Database::connection();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users u' . $sqlWhere);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));

        if ($page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.username, u.role, u.role_label, u.avatar_path,
                    u.is_active, u.must_change_password, u.last_login_at, u.locked_until,
                    u.created_at,
                    (SELECT t.nip FROM teachers t WHERE t.user_id = u.id LIMIT 1) AS nip
             FROM users u'
            . $sqlWhere
            . ' ORDER BY u.is_active DESC, FIELD(u.role, \'admin\', \'operator\', \'kepala\', \'akreditasi\', \'guru\', \'siswa\'),
                       u.name ASC
               LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows'     => $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'pages'    => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * Jumlah pengguna per role — untuk keterangan di kepala halaman.
     *
     * @return array<string,int>
     */
    public static function roleCounts(): array
    {
        $counts = ['semua' => 0];

        foreach (array_keys(self::ROLES) as $role) {
            $counts[$role] = 0;
        }

        try {
            $rows = Database::connection()->query(
                'SELECT role, COUNT(*) AS n FROM users WHERE deleted_at IS NULL GROUP BY role'
            )->fetchAll();

            foreach ($rows as $row) {
                $role = (string) $row['role'];
                $counts[$role] = (int) $row['n'];
                $counts['semua'] += (int) $row['n'];
            }
        } catch (PDOException $e) {
            // Biarkan nol.
        }

        return $counts;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, (SELECT t.nip FROM teachers t WHERE t.user_id = u.id LIMIT 1) AS nip
             FROM users u
             WHERE u.id = ? AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Buat akun baru. Mengembalikan [id, password_sementara].
     *
     * Sandi sementara dibuat di sini dan HANYA dikembalikan sekali — tidak
     * disimpan dalam bentuk terbaca. Pemanggil wajib menampilkannya.
     *
     * @param array<string,mixed> $input Sudah divalidasi pemanggil.
     * @return array{id:int,password:string}
     */
    public static function create(array $input): array
    {
        $pdo = Database::connection();

        $password = self::generatePassword();
        $role = self::normalizeRole((string) ($input['role'] ?? 'guru'));

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, username, password_hash, role, role_label,
                                phone, is_active, must_change_password)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)'
        );

        $stmt->execute([
            mb_substr(trim((string) $input['name']), 0, 120),
            mb_strtolower(trim((string) $input['email'])),
            self::uniqueUsername((string) ($input['username'] ?? ''), (string) $input['email'], null),
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            self::ROLES[$role],
            self::nullableText($input['phone'] ?? null, 24),
        ]);

        $id = (int) $pdo->lastInsertId();

        // NIP hanya berlaku untuk guru. Kalau role lain, NIP diabaikan —
        // baris teachers untuk non-guru tidak masuk akal.
        $nip = trim((string) ($input['nip'] ?? ''));
        if ($role === 'guru' && $nip !== '') {
            self::saveTeacherProfile($id, $nip, (string) ($input['gender'] ?? 'L'));
        }

        return ['id' => $id, 'password' => $password];
    }

    /**
     * Perbarui data akun. Tidak menyentuh sandi.
     *
     * @param array<string,mixed> $input
     */
    public static function update(int $id, array $input): void
    {
        $current = self::find($id);

        if ($current === null) {
            return;
        }

        $role = self::normalizeRole((string) ($input['role'] ?? $current['role']));

        $stmt = Database::connection()->prepare(
            'UPDATE users
             SET name = ?, email = ?, username = ?, role = ?, role_label = ?, phone = ?
             WHERE id = ? AND deleted_at IS NULL'
        );

        $stmt->execute([
            mb_substr(trim((string) $input['name']), 0, 120),
            mb_strtolower(trim((string) $input['email'])),
            self::uniqueUsername((string) ($input['username'] ?? ''), (string) $input['email'], $id),
            $role,
            self::ROLES[$role],
            self::nullableText($input['phone'] ?? null, 24),
            $id,
        ]);

        $nip = trim((string) ($input['nip'] ?? ''));

        if ($role === 'guru') {
            if ($nip !== '') {
                self::saveTeacherProfile($id, $nip, (string) ($input['gender'] ?? 'L'));
            }
        } else {
            // Role berubah jadi bukan guru: profil guru dilepas supaya tidak
            // ada baris teachers yang menggantung pada akun non-guru.
            Database::connection()
                ->prepare('DELETE FROM teachers WHERE user_id = ?')
                ->execute([$id]);
        }
    }

    /**
     * Aktifkan / nonaktifkan akun.
     *
     * Menonaktifkan langsung memutus akses: Auth::user() membaca is_active
     * dari database pada setiap request, jadi sesi web yang sedang berjalan
     * berhenti berlaku tanpa perlu menyentuh tabel sesi. Token API dicabut
     * eksplisit karena pengecekannya berbeda jalur.
     */
    public static function setActive(int $id, bool $active): void
    {
        Database::connection()
            ->prepare(
                'UPDATE users SET is_active = ?, locked_until = NULL, failed_login_attempts = 0
                 WHERE id = ? AND deleted_at IS NULL'
            )
            ->execute([$active ? 1 : 0, $id]);

        if (!$active && class_exists('ApiAuth')) {
            ApiAuth::revokeAllForUser($id);
        }
    }

    /**
     * Setel ulang sandi ke sandi acak sementara.
     * Mengembalikan sandi itu — ditampilkan sekali, tidak disimpan terbaca.
     */
    public static function resetPassword(int $id): string
    {
        $password = self::generatePassword();

        Database::connection()
            ->prepare(
                'UPDATE users
                 SET password_hash = ?, must_change_password = 1,
                     failed_login_attempts = 0, locked_until = NULL
                 WHERE id = ? AND deleted_at IS NULL'
            )
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);

        if (class_exists('ApiAuth')) {
            ApiAuth::revokeAllForUser($id);
        }

        return $password;
    }

    /**
     * Buka kunci akun tanpa mengubah sandi.
     */
    public static function unlock(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?')
            ->execute([$id]);
    }

    /**
     * Hapus lunak.
     */
    public static function delete(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = ? AND deleted_at IS NULL')
            ->execute([$id]);

        if (class_exists('ApiAuth')) {
            ApiAuth::revokeAllForUser($id);
        }
    }

    /**
     * Jumlah admin aktif — dipakai untuk mencegah admin terakhir dihapus.
     */
    public static function activeAdminCount(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND deleted_at IS NULL")
            ->fetchColumn();
    }

    /**
     * Username unik. Kalau kosong, diturunkan dari bagian lokal email.
     */
    public static function uniqueUsername(string $username, string $email, ?int $excludeId): string
    {
        $base = Format::slug($username);

        if ($base === '') {
            $local = strstr($email, '@', true);
            $base = Format::slug($local === false ? '' : $local);
        }

        if ($base === '') {
            $base = 'pengguna';
        }

        $base = mb_substr($base, 0, 50);

        $pdo = Database::connection();
        $candidate = $base;
        $counter = 1;

        while ($counter <= 100) {
            $sql = 'SELECT id FROM users WHERE username = ? AND deleted_at IS NULL';
            $params = [$candidate];

            if ($excludeId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $excludeId;
            }

            $stmt = $pdo->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);

            if ($stmt->fetchColumn() === false) {
                return $candidate;
            }

            $counter++;
            $candidate = $base . $counter;
        }

        return $base . '-' . bin2hex(random_bytes(3));
    }

    /**
     * Apakah email sudah dipakai akun lain (yang belum dihapus).
     */
    public static function emailTaken(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = ? AND deleted_at IS NULL';
        $params = [mb_strtolower(trim($email))];

        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }

        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Sandi sementara yang mudah dibaca ulang tapi tidak mudah ditebak:
     * tiga kelompok kata+angka, tanpa karakter yang sering salah dibaca
     * (0/O, 1/l/I).
     */
    public static function generatePassword(): string
    {
        $words = [
            'Aman', 'Buku', 'Cahaya', 'Damai', 'Elok', 'Fajar', 'Guru', 'Hikmah',
            'Ilmu', 'Jaya', 'Karya', 'Lestari', 'Mulia', 'Nalar', 'Pintar', 'Qurani',
            'Rapi', 'Santri', 'Tulus', 'Unggul', 'Wibawa', 'Yakin', 'Zakat',
        ];

        $digits = '23456789';
        $pick = static fn (array $set): string => $set[random_int(0, count($set) - 1)];

        $part = static function () use ($words, $digits, $pick): string {
            return $pick($words) . $digits[random_int(0, strlen($digits) - 1)]
                . $digits[random_int(0, strlen($digits) - 1)];
        };

        return $part() . '-' . $part() . '-' . $part();
    }

    /**
     * Perkiraan kekuatan sandi: 0 lemah, 1 sedang, 2 kuat.
     */
    public static function strength(string $password): int
    {
        $length = mb_strlen($password);
        $variety = 0;

        foreach (['/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[^a-zA-Z0-9]/'] as $pattern) {
            if (preg_match($pattern, $password) === 1) {
                $variety++;
            }
        }

        if ($length >= 12 && $variety >= 3) {
            return 2;
        }

        if ($length >= 8 && $variety >= 2) {
            return 1;
        }

        return 0;
    }

    // -----------------------------------------------------------------------
    // Pembantu internal
    // -----------------------------------------------------------------------

    private static function saveTeacherProfile(int $userId, string $nip, string $gender): void
    {
        $pdo = Database::connection();
        $gender = $gender === 'P' ? 'P' : 'L';

        $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $existing = $stmt->fetchColumn();

        if ($existing === false) {
            $pdo->prepare('INSERT INTO teachers (user_id, nip, gender) VALUES (?, ?, ?)')
                ->execute([$userId, mb_substr($nip, 0, 30), $gender]);

            return;
        }

        $pdo->prepare('UPDATE teachers SET nip = ?, gender = ? WHERE user_id = ?')
            ->execute([mb_substr($nip, 0, 30), $gender, $userId]);
    }

    private static function normalizeRole(string $role): string
    {
        return isset(self::ROLES[$role]) ? $role : 'guru';
    }

    private static function nullableText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
