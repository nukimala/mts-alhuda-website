<?php
/**
 * Berita (tabel posts) — sisi tulis dan baca panel admin.
 *
 * Kelas ini tidak memutuskan siapa yang boleh menulis; itu tugas AdminGate.
 * Yang diurus di sini hanya bentuk data: kolom apa yang ditulis, bagaimana
 * slug dijaga unik, dan bagaimana daftar difilter.
 *
 * Catatan penting soal `slug_live`:
 * kolom itu generated column (IF(deleted_at IS NULL, slug, NULL)) dengan
 * UNIQUE. Karena itu INSERT/UPDATE tidak boleh menyertakannya — MySQL akan
 * menolak. Keunikannya otomatis: baris yang dihapus melepas slug-nya.
 *
 * Catatan soal `category`, `meta_title`, `meta_description`:
 * ketiganya ditambahkan migrasi 015. Kalau database belum menjalankannya,
 * semua method di sini tetap bekerja tanpa kolom itu — bukan error 500.
 * Pemeriksaannya lewat Schema, hasilnya di-cache per request.
 */

class Post
{
    /** Kategori yang ditawarkan di formulir. Nilainya bebas, ini hanya saran. */
    public const CATEGORIES = [
        'prestasi'   => 'Prestasi Santri & Lembaga',
        'kegiatan'   => 'Kegiatan Madrasah',
        'pengumuman' => 'Pengumuman Resmi',
        'riset'      => 'Opini & Riset Pendidik',
        'ppdb'       => 'PPDB',
        'umum'       => 'Umum',
    ];

    public const STATUSES = [
        'draft'     => 'Draft',
        'published' => 'Terbit',
        'archived'  => 'Arsip',
    ];

    /**
     * Daftar berita untuk tabel admin.
     *
     * @param array{q?:string,status?:string,category?:string,sort?:string,page?:int,per_page?:int} $filters
     * @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int,per_page:int}
     */
    public static function paginate(array $filters = []): array
    {
        $hasCategory = Schema::hasColumn('posts', 'category');

        $where = ['p.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Satu placeholder hanya boleh dipakai sekali: koneksi ini memakai
            // prepared statement asli (EMULATE_PREPARES = false), dan MySQL
            // menolak nama parameter yang diulang.
            $where[] = '(p.title LIKE :q1 OR p.slug LIKE :q2 OR p.excerpt LIKE :q3)';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && isset(self::STATUSES[$status])) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        $category = (string) ($filters['category'] ?? '');
        if ($category !== '' && $hasCategory) {
            $where[] = 'p.category = :category';
            $params[':category'] = $category;
        }

        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $orderBy = match ((string) ($filters['sort'] ?? 'terbaru')) {
            'terlama' => 'p.published_at IS NULL, p.published_at ASC, p.id ASC',
            'dibaca'  => 'p.views DESC, p.id DESC',
            'judul'   => 'p.title ASC',
            default   => 'p.published_at IS NULL, p.published_at DESC, p.id DESC',
        };

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $pdo = Database::connection();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM posts p' . $sqlWhere);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));

        if ($page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * $perPage;

        $columns = 'p.id, p.title, p.slug, p.excerpt, p.cover_path, p.status,
                    p.is_featured, p.views, p.published_at, p.created_at, p.updated_at,
                    p.author_id, u.name AS author_name';
        $columns .= $hasCategory ? ', p.category' : ", 'umum' AS category";

        $stmt = $pdo->prepare(
            'SELECT ' . $columns . '
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id'
            . $sqlWhere
            . ' ORDER BY ' . $orderBy
            . ' LIMIT :limit OFFSET :offset'
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
     * Jumlah berita per status — untuk keterangan di kepala halaman.
     *
     * @return array<string,int>
     */
    public static function statusCounts(): array
    {
        $counts = ['draft' => 0, 'published' => 0, 'archived' => 0, 'semua' => 0];

        try {
            $rows = Database::connection()->query(
                'SELECT status, COUNT(*) AS n FROM posts WHERE deleted_at IS NULL GROUP BY status'
            )->fetchAll();

            foreach ($rows as $row) {
                $counts[(string) $row['status']] = (int) $row['n'];
                $counts['semua'] += (int) $row['n'];
            }
        } catch (PDOException $e) {
            // Biarkan nol.
        }

        return $counts;
    }

    /**
     * Satu berita lengkap, termasuk yang sudah dihapus (untuk pesan 404 yang jelas).
     *
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $hasCategory = Schema::hasColumn('posts', 'category');
        $hasMeta = Schema::hasColumn('posts', 'meta_title');

        $columns = 'p.*, u.name AS author_name';
        $columns .= $hasCategory ? '' : ", 'umum' AS category";
        $columns .= $hasMeta ? '' : ", NULL AS meta_title, NULL AS meta_description";

        $stmt = Database::connection()->prepare(
            'SELECT ' . $columns . '
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             WHERE p.id = ? AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Simpan berita baru. Mengembalikan ID barunya.
     *
     * @param array<string,mixed> $input Sudah divalidasi pemanggil.
     */
    public static function create(array $input): int
    {
        $pdo = Database::connection();

        $slug = self::uniqueSlug((string) $input['slug'], null);
        $status = self::normalizeStatus((string) ($input['status'] ?? 'draft'));
        $publishedAt = self::normalizePublishedAt($input['published_at'] ?? null, $status);

        $columns = ['public_id', 'title', 'slug', 'excerpt', 'content', 'cover_path',
            'status', 'is_featured', 'author_id', 'published_at'];
        $values = [
            self::uuid(),
            mb_substr(trim((string) $input['title']), 0, 200),
            $slug,
            self::nullableText($input['excerpt'] ?? null, 400),
            (string) ($input['content'] ?? ''),
            self::nullableText($input['cover_path'] ?? null, 255),
            $status,
            !empty($input['is_featured']) ? 1 : 0,
            Auth::id(),
            $publishedAt,
        ];

        if (Schema::hasColumn('posts', 'category')) {
            $columns[] = 'category';
            $values[] = self::normalizeCategory((string) ($input['category'] ?? ''));
        }

        if (Schema::hasColumn('posts', 'meta_title')) {
            $columns[] = 'meta_title';
            $columns[] = 'meta_description';
            $values[] = self::nullableText($input['meta_title'] ?? null, 200);
            $values[] = self::nullableText($input['meta_description'] ?? null, 320);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $stmt = $pdo->prepare(
            'INSERT INTO posts (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
        );
        $stmt->execute($values);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Perbarui berita. Mengembalikan slug final (bisa berubah kalau bentrok).
     *
     * @param array<string,mixed> $input
     */
    public static function update(int $id, array $input): string
    {
        $slug = self::uniqueSlug((string) $input['slug'], $id);
        $status = self::normalizeStatus((string) ($input['status'] ?? 'draft'));
        $publishedAt = self::normalizePublishedAt($input['published_at'] ?? null, $status);

        $assignments = [
            'title = ?',
            'slug = ?',
            'excerpt = ?',
            'content = ?',
            'cover_path = ?',
            'status = ?',
            'is_featured = ?',
            'published_at = ?',
        ];

        $values = [
            mb_substr(trim((string) $input['title']), 0, 200),
            $slug,
            self::nullableText($input['excerpt'] ?? null, 400),
            (string) ($input['content'] ?? ''),
            self::nullableText($input['cover_path'] ?? null, 255),
            $status,
            !empty($input['is_featured']) ? 1 : 0,
            $publishedAt,
        ];

        if (Schema::hasColumn('posts', 'category')) {
            $assignments[] = 'category = ?';
            $values[] = self::normalizeCategory((string) ($input['category'] ?? ''));
        }

        if (Schema::hasColumn('posts', 'meta_title')) {
            $assignments[] = 'meta_title = ?';
            $assignments[] = 'meta_description = ?';
            $values[] = self::nullableText($input['meta_title'] ?? null, 200);
            $values[] = self::nullableText($input['meta_description'] ?? null, 320);
        }

        $values[] = $id;

        Database::connection()
            ->prepare('UPDATE posts SET ' . implode(', ', $assignments) . ' WHERE id = ? AND deleted_at IS NULL')
            ->execute($values);

        return $slug;
    }

    /**
     * Hapus lunak. Slug langsung dilepas oleh generated column `slug_live`,
     * jadi judul yang sama bisa dipakai lagi.
     */
    public static function delete(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE posts SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL')
            ->execute([$id]);
    }

    /**
     * Ubah status saja — dipakai tombol cepat di tabel.
     */
    public static function setStatus(int $id, string $status): void
    {
        $status = self::normalizeStatus($status);

        $stmt = Database::connection()->prepare(
            'UPDATE posts SET status = ?, published_at = CASE
                WHEN ? = \'published\' AND published_at IS NULL THEN NOW()
                ELSE published_at
             END
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$status, $status, $id]);
    }

    /**
     * Slug unik: kalau sudah dipakai berita lain, tambahkan -2, -3, dan
     * seterusnya. Baris yang dihapus tidak dihitung karena slug_live NULL.
     */
    public static function uniqueSlug(string $slug, ?int $excludeId): string
    {
        $base = Format::slug($slug);

        if ($base === '') {
            $base = 'berita';
        }

        $base = mb_substr($base, 0, 180);

        $pdo = Database::connection();
        $candidate = $base;
        $counter = 1;

        // Batas 100 percobaan: mencegah loop panjang kalau ada data aneh.
        while ($counter <= 100) {
            $sql = 'SELECT id FROM posts WHERE slug = ? AND deleted_at IS NULL';
            $params = [$candidate];

            if ($excludeId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $excludeId;
            }

            $sql .= ' LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->fetchColumn() === false) {
                return $candidate;
            }

            $counter++;
            $candidate = $base . '-' . $counter;
        }

        // Praktis tidak terjadi. Beri akhiran acak supaya tetap unik.
        return $base . '-' . bin2hex(random_bytes(3));
    }

    /**
     * Berita yang belum terbit tapi tanggalnya sudah lewat — untuk pengingat.
     */
    public static function scheduledDue(int $limit = 5): array
    {
        try {
            $stmt = Database::connection()->prepare(
                "SELECT id, title, slug, published_at
                 FROM posts
                 WHERE status = 'draft' AND deleted_at IS NULL
                   AND published_at IS NOT NULL AND published_at <= NOW()
                 ORDER BY published_at ASC
                 LIMIT :limit"
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Pembantu internal
    // -----------------------------------------------------------------------

    private static function normalizeStatus(string $status): string
    {
        return isset(self::STATUSES[$status]) ? $status : 'draft';
    }

    private static function normalizeCategory(string $category): string
    {
        $category = Format::slug($category);

        if ($category === '') {
            return 'umum';
        }

        return mb_substr($category, 0, 60);
    }

    /**
     * Aturan tanggal terbit:
     *   - status draft/arsip: tanggal tetap disimpan (dipakai sebagai jadwal)
     *   - status terbit tanpa tanggal: diisi sekarang, supaya tidak hilang
     *     dari daftar publik yang mengurutkan berdasarkan published_at
     */
    private static function normalizePublishedAt(mixed $value, string $status): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value !== '') {
            $ts = strtotime($value);

            if ($ts !== false) {
                return date('Y-m-d H:i:s', $ts);
            }
        }

        return $status === 'published' ? date('Y-m-d H:i:s') : null;
    }

    private static function nullableText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }

    /**
     * UUID v4 tanpa dependensi.
     */
    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
