<?php
/**
 * Pembaca konten publik.
 *
 * Semua halaman publik mengambil data lewat kelas ini — bukan query langsung
 * di dalam view. Alasannya:
 *   - satu tempat untuk aturan "apa yang boleh tampil" (status published,
 *     deleted_at NULL, is_active = 1),
 *   - hasil per request di-cache di memori, jadi header + footer + isi halaman
 *     tidak menembak database berkali-kali untuk data yang sama,
 *   - kalau query berubah, view tidak perlu ikut diubah.
 *
 * Tidak ada satu pun method di sini yang menulis ke database. Kalau butuh
 * tulis, itu tugas controller admin.
 */

class Content
{
    /** @var array<string,mixed> */
    private static array $cache = [];

    // -----------------------------------------------------------------------
    // Pengaturan situs
    // -----------------------------------------------------------------------

    /**
     * Semua setting publik sebagai array asosiatif setting_key => setting_value.
     *
     * @return array<string,string>
     */
    public static function settings(): array
    {
        if (isset(self::$cache['settings'])) {
            return self::$cache['settings'];
        }

        $rows = Database::connection()
            ->query('SELECT setting_key, setting_value FROM settings WHERE is_public = 1')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        return self::$cache['settings'] = $rows ?: [];
    }

    /**
     * Ambil satu setting dengan nilai cadangan.
     */
    public static function setting(string $key, string $default = ''): string
    {
        $value = self::settings()[$key] ?? '';

        return $value !== '' ? $value : $default;
    }

    /**
     * Nomor telepon dirapikan jadi tautan wa.me (62... tanpa tanda baca).
     */
    public static function whatsapp(): string
    {
        $phone = self::setting('contact_whatsapp', self::setting('contact_phone'));

        if ($phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '0') === 0) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    // -----------------------------------------------------------------------
    // Navigasi
    // -----------------------------------------------------------------------

    /**
     * Menu utama yang aktif, sudah berurutan.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function menus(string $location = 'utama'): array
    {
        $key = 'menus:' . $location;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = Database::connection()->prepare(
            'SELECT label, url, target FROM menus
             WHERE is_active = 1 AND location = :loc
             ORDER BY sort_order, id'
        );
        $stmt->execute([':loc' => $location]);

        return self::$cache[$key] = $stmt->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Halaman statis (pages)
    // -----------------------------------------------------------------------

    /**
     * Satu halaman berdasarkan slug, atau null kalau belum ada.
     *
     * @return array<string,mixed>|null
     */
    public static function page(string $slug): ?array
    {
        $key = 'page:' . $slug;

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $stmt = Database::connection()->prepare(
            "SELECT title, content, meta_title, meta_description FROM pages
             WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return self::$cache[$key] = $row ?: null;
    }

    /**
     * Isi halaman sebagai HTML siap cetak.
     *
     * Kalau halaman belum diisi di admin, dipakai teks cadangan dari view
     * supaya halaman tidak pernah kosong di mata pengunjung.
     */
    public static function pageContent(string $slug, string $fallback = ''): string
    {
        $page = self::page($slug);
        $content = trim((string) ($page['content'] ?? ''));

        return $content !== '' ? $content : $fallback;
    }

    // -----------------------------------------------------------------------
    // Berita
    // -----------------------------------------------------------------------

    /**
     * Daftar berita terbit.
     *
     * @param array{limit?:int,offset?:int,featured?:bool} $options
     * @return array<int,array<string,mixed>>
     */
    public static function posts(array $options = []): array
    {
        $limit = max(1, min(60, (int) ($options['limit'] ?? 9)));
        $offset = max(0, (int) ($options['offset'] ?? 0));

        $sql = "SELECT id, title, slug, excerpt, cover_path, published_at, is_featured
                FROM posts
                WHERE status = 'published' AND deleted_at IS NULL";
        $params = [];

        if (!empty($options['featured'])) {
            $sql .= ' AND is_featured = 1';
        }

        $sql .= ' ORDER BY published_at DESC, id DESC LIMIT :limit OFFSET :offset';

        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Jumlah berita terbit — untuk paginasi.
     */
    public static function postCount(): int
    {
        if (isset(self::$cache['post_count'])) {
            return self::$cache['post_count'];
        }

        $total = (int) Database::connection()
            ->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND deleted_at IS NULL")
            ->fetchColumn();

        return self::$cache['post_count'] = $total;
    }

    /**
     * Satu berita berdasarkan slug.
     *
     * @return array<string,mixed>|null
     */
    public static function post(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, title, slug, excerpt, content, cover_path, published_at, views
             FROM posts
             WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Berita lain untuk bagian "baca juga".
     *
     * @return array<int,array<string,mixed>>
     */
    public static function relatedPosts(int $excludeId, int $limit = 3): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT title, slug, cover_path, published_at
             FROM posts
             WHERE status = 'published' AND deleted_at IS NULL AND id <> :id
             ORDER BY published_at DESC, id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Fasilitas, galeri, mata pelajaran
    // -----------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function facilities(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT name, description, image_path, icon FROM facilities
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order, id LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function galleries(int $limit = 24): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT album, title, image_path, caption FROM galleries
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order, id DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Mata pelajaran aktif, dikelompokkan per group_name.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function subjects(): array
    {
        if (isset(self::$cache['subjects'])) {
            return self::$cache['subjects'];
        }

        $rows = Database::connection()->query(
            'SELECT code, name, group_name FROM subjects
             WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $group = $row['group_name'] ?: 'lainnya';
            $grouped[$group][] = $row;
        }

        return self::$cache['subjects'] = $grouped;
    }

    // -----------------------------------------------------------------------
    // Pengumuman, agenda, dokumen PPDB
    // -----------------------------------------------------------------------

    /**
     * Pengumuman aktif — yang disematkan lebih dulu, lalu yang belum kedaluwarsa.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function announcements(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT title, slug, content, is_pinned, published_at
             FROM announcements
             WHERE status = 'published'
               AND (expires_at IS NULL OR expires_at >= NOW())
             ORDER BY is_pinned DESC, published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Agenda terdekat — hanya yang belum lewat.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function agendas(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT title, description, location, start_at, end_at, all_day
             FROM agendas
             WHERE status = 'published' AND start_at >= NOW()
             ORDER BY start_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Dokumen PPDB yang bisa diunduh.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function ppdbDocuments(int $limit = 12): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT title, description, category, file_path, file_type, file_size
             FROM ppdb_documents
             WHERE is_active = 1
             ORDER BY sort_order, id LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Statistik ringkas untuk beranda
    // -----------------------------------------------------------------------

    /**
     * Angka yang tampil di beranda. Dihitung dari database, bukan ditulis tangan.
     *
     * @return array<string,int|string>
     */
    public static function stats(): array
    {
        if (isset(self::$cache['stats'])) {
            return self::$cache['stats'];
        }

        $pdo = Database::connection();

        $count = static function (string $sql) use ($pdo): int {
            try {
                return (int) $pdo->query($sql)->fetchColumn();
            } catch (Throwable $e) {
                return 0;
            }
        };

        $grade = self::setting('accreditation_grade', 'A');

        return self::$cache['stats'] = [
            'posts' => self::postCount(),
            'facilities' => $count('SELECT COUNT(*) FROM facilities WHERE is_active = 1 AND deleted_at IS NULL'),
            'subjects' => $count('SELECT COUNT(*) FROM subjects WHERE is_active = 1'),
            'teachers' => $count('SELECT COUNT(*) FROM teachers WHERE deleted_at IS NULL'),
            'students' => $count('SELECT COUNT(*) FROM students WHERE deleted_at IS NULL'),
            'grade' => $grade,
        ];
    }

}
