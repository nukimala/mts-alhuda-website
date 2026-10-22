<?php
/**
 * API v1 — konten publik.
 *
 * Berita, pengumuman, agenda, fasilitas, galeri, dan halaman statis. Sebagian
 * besar bisa dibaca tanpa autentikasi, sama seperti website. Yang membedakan
 * hanya bentuknya: JSON, bukan HTML.
 *
 * Aturan yang dipegang di sini:
 *   - Hanya data terbit yang keluar. Draf tidak pernah bocor ke publik.
 *   - Baris yang sudah di-soft-delete tidak pernah ikut.
 *   - Semua daftar memakai halaman (page/per_page), tidak pernah "kirim
 *     semuanya" — satu tabel bisa membesar tanpa batas.
 */

class ApiContentController
{
    /**
     * GET /api/v1/posts?page=1&per_page=20&q=kata&featured=1
     */
    public function posts(array $params = []): void
    {
        $page = ApiFormat::paginate();

        $where = ["p.status = 'published'", 'p.deleted_at IS NULL', 'p.published_at IS NOT NULL'];
        $args = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            // Escape wildcard supaya "100%" tidak berubah jadi "apa saja".
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ?)';
            $args[] = $like;
            $args[] = $like;
        }

        // isset() diperiksa lebih dulu. Tanpa itu, parameter yang tidak
        // dikirim akan dibaca filter_var(null, ...) sebagai string kosong —
        // dan string kosong adalah nilai "false" yang SAH menurut
        // FILTER_VALIDATE_BOOLEAN. Akibatnya "tanpa filter" berubah jadi
        // "hanya yang false", dan hasilnya kosong tanpa galat apa pun.
        $featured = isset($_GET['featured'])
            ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        if ($featured !== null) {
            $where[] = 'p.is_featured = ?';
            $args[] = $featured ? 1 : 0;
        }

        $whereSql = implode(' AND ', $where);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE {$whereSql}");
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        // LIMIT/OFFSET disisipkan sebagai angka yang sudah divalidasi, bukan
        // di-bind. Beberapa driver menolak parameter untuk LIMIT.
        $sql = "SELECT p.id, p.public_id, p.title, p.slug, p.excerpt, p.cover_path,
                       p.is_featured, p.views, p.published_at,
                       u.name AS author_name
                FROM posts p
                LEFT JOIN users u ON u.id = p.author_id
                WHERE {$whereSql}
                ORDER BY p.published_at DESC, p.id DESC
                LIMIT {$page['per_page']} OFFSET {$page['offset']}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        $posts = [];
        foreach ($stmt->fetchAll() as $row) {
            $posts[] = [
                'id'           => (int) $row['id'],
                'public_id'    => $row['public_id'],
                'title'        => (string) $row['title'],
                'slug'         => (string) $row['slug'],
                'excerpt'      => $row['excerpt'],
                'cover_url'    => ApiFormat::mediaUrl($row['cover_path']),
                'is_featured'  => (int) $row['is_featured'] === 1,
                'views'        => (int) $row['views'],
                'published_at' => ApiFormat::iso($row['published_at']),
                'author'       => $row['author_name'] !== null
                    ? ['name' => (string) $row['author_name']]
                    : null,
                'url'          => ApiFormat::webUrl('berita/' . $row['slug']),
            ];
        }

        ApiResponse::json(
            ['posts' => $posts],
            200,
            ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]
        );
    }

    /**
     * GET /api/v1/posts/{slug}
     *
     * Slug dipakai, bukan id, karena stabil dan bisa dipakai di tautan.
     * Setiap pembacaan menambah penghitung `views` — sama seperti versi web.
     */
    public function postDetail(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');

        if ($slug === '') {
            ApiResponse::notFound('Berita tidak ditemukan.');
            return;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            "SELECT p.id, p.public_id, p.title, p.slug, p.excerpt, p.content, p.cover_path,
                    p.is_featured, p.views, p.published_at,
                    u.name AS author_name
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             WHERE p.slug = ?
               AND p.status = 'published'
               AND p.deleted_at IS NULL
               AND p.published_at IS NOT NULL
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if ($row === false) {
            ApiResponse::notFound('Berita tidak ditemukan.');
            return;
        }

        $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')
            ->execute([(int) $row['id']]);

        ApiResponse::ok([
            'post' => [
                'id'           => (int) $row['id'],
                'public_id'    => $row['public_id'],
                'title'        => (string) $row['title'],
                'slug'         => (string) $row['slug'],
                'excerpt'      => $row['excerpt'],
                'content'      => (string) $row['content'],
                'cover_url'    => ApiFormat::mediaUrl($row['cover_path']),
                'is_featured'  => (int) $row['is_featured'] === 1,
                'views'        => (int) $row['views'] + 1,
                'published_at' => ApiFormat::iso($row['published_at']),
                'author'       => $row['author_name'] !== null
                    ? ['name' => (string) $row['author_name']]
                    : null,
                'url'          => ApiFormat::webUrl('berita/' . $row['slug']),
            ],
        ]);
    }

    /**
     * GET /api/v1/announcements
     *
     * Pengumuman untuk "semua" selalu terlihat. Pengumuman bertarget
     * (siswa/guru/wali) hanya ikut kalau peminta mengirim token yang sah
     * dengan peran yang cocok. Tanpa aturan ini, pengumuman internal bocor
     * ke siapa pun yang tahu alamat endpoint-nya.
     */
    public function announcements(array $params = []): void
    {
        $page = ApiFormat::paginate();

        $audiences = ['semua'];
        $user = ApiAuth::authenticate();

        if ($user !== null) {
            $role = (string) ($user['role'] ?? '');
            $map = [
                'siswa' => 'siswa',
                'guru'  => 'guru',
                'wali'  => 'wali',
            ];

            if (isset($map[$role])) {
                $audiences[] = $map[$role];
            } elseif (in_array($role, ['admin', 'operator', 'kepala', 'akreditasi'], true)) {
                // Staf melihat semuanya.
                $audiences = ['semua', 'siswa', 'guru', 'wali'];
            }
        }

        $placeholders = implode(', ', array_fill(0, count($audiences), '?'));

        $where = "a.status = 'published'
                  AND a.published_at IS NOT NULL
                  AND (a.expires_at IS NULL OR a.expires_at > NOW())
                  AND a.audience IN ({$placeholders})";

        $pdo = Database::connection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements a WHERE {$where}");
        $countStmt->execute($audiences);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT a.id, a.title, a.slug, a.content, a.audience, a.is_pinned, a.published_at
             FROM announcements a
             WHERE {$where}
             ORDER BY a.is_pinned DESC, a.published_at DESC, a.id DESC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $stmt->execute($audiences);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'           => (int) $row['id'],
                'title'        => (string) $row['title'],
                'slug'         => (string) $row['slug'],
                'content'      => (string) $row['content'],
                'audience'     => (string) $row['audience'],
                'is_pinned'    => (int) $row['is_pinned'] === 1,
                'published_at' => ApiFormat::iso($row['published_at']),
            ];
        }

        ApiResponse::json(
            ['announcements' => $items],
            200,
            ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]
        );
    }

    /**
     * GET /api/v1/agendas?past=0
     *
     * Bawaannya agenda yang belum lewat. `past=1` untuk riwayat.
     */
    public function agendas(array $params = []): void
    {
        $page = ApiFormat::paginate();

        // Lihat catatan di posts(): isset() wajib, karena filter_var(null)
        // menghasilkan false — bukan null.
        $past = isset($_GET['past'])
            ? filter_var($_GET['past'], FILTER_VALIDATE_BOOLEAN)
            : false;

        // "Belum lewat" = belum dimulai, ATAU sedang berlangsung (belum punya
        // end_at, atau end_at belum lewat). Versi pertama fungsi ini sempat
        // mencampur dua bentuk WHERE dan menyisakan baris mati; yang ini
        // sengaja dipisah supaya jelas mana yang dipakai.
        $where = $past
            ? "a.status = 'published' AND COALESCE(a.end_at, a.start_at) < NOW()"
            : "a.status = 'published'
               AND (a.start_at >= NOW() OR a.end_at IS NULL OR a.end_at >= NOW())";

        $pdo = Database::connection();

        $countStmt = $pdo->query("SELECT COUNT(*) FROM agendas a WHERE {$where}");
        $total = (int) $countStmt->fetchColumn();

        $order = $past ? 'a.start_at DESC' : 'a.start_at ASC';

        $stmt = $pdo->query(
            "SELECT a.id, a.title, a.description, a.location, a.start_at, a.end_at, a.all_day
             FROM agendas a
             WHERE {$where}
             ORDER BY {$order}
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'          => (int) $row['id'],
                'title'       => (string) $row['title'],
                'description' => $row['description'],
                'location'    => $row['location'],
                'start_at'    => ApiFormat::iso($row['start_at']),
                'end_at'      => ApiFormat::iso($row['end_at']),
                'all_day'     => (int) $row['all_day'] === 1,
            ];
        }

        ApiResponse::json(
            ['agendas' => $items],
            200,
            ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]
        );
    }

    /**
     * GET /api/v1/facilities
     */
    public function facilities(array $params = []): void
    {
        $page = ApiFormat::paginate();
        $pdo = Database::connection();

        $total = (int) $pdo->query(
            'SELECT COUNT(*) FROM facilities WHERE is_active = 1 AND deleted_at IS NULL'
        )->fetchColumn();

        $stmt = $pdo->query(
            "SELECT id, name, description, image_path, icon, sort_order
             FROM facilities
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order ASC, id ASC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'          => (int) $row['id'],
                'name'        => (string) $row['name'],
                'description' => $row['description'],
                'image_url'   => ApiFormat::mediaUrl($row['image_path']),
                'icon'        => $row['icon'],
            ];
        }

        ApiResponse::json(
            ['facilities' => $items],
            200,
            ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]
        );
    }

    /**
     * GET /api/v1/galleries?album=nama
     */
    public function galleries(array $params = []): void
    {
        $page = ApiFormat::paginate();

        $where = 'is_active = 1 AND deleted_at IS NULL';
        $args = [];

        $album = trim((string) ($_GET['album'] ?? ''));
        if ($album !== '') {
            $where .= ' AND album = ?';
            $args[] = $album;
        }

        $pdo = Database::connection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM galleries WHERE {$where}");
        $countStmt->execute($args);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id, album, title, image_path, caption, sort_order
             FROM galleries
             WHERE {$where}
             ORDER BY album ASC, sort_order ASC, id ASC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $stmt->execute($args);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id'        => (int) $row['id'],
                'album'     => $row['album'],
                'title'     => (string) $row['title'],
                'image_url' => ApiFormat::mediaUrl($row['image_path']),
                'caption'   => $row['caption'],
            ];
        }

        ApiResponse::json(
            ['galleries' => $items],
            200,
            ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]
        );
    }

    /**
     * GET /api/v1/pages/{slug}
     *
     * Halaman statis yang dikelola operator: profil, akademik, dan sebagainya.
     */
    public function pageDetail(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');

        if ($slug === '') {
            ApiResponse::notFound('Halaman tidak ditemukan.');
            return;
        }

        $stmt = Database::connection()->prepare(
            "SELECT slug, title, content, meta_title, meta_description, updated_at
             FROM pages
             WHERE slug = ? AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if ($row === false) {
            ApiResponse::notFound('Halaman tidak ditemukan.');
            return;
        }

        ApiResponse::ok([
            'page' => [
                'slug'             => (string) $row['slug'],
                'title'            => (string) $row['title'],
                'content'          => (string) $row['content'],
                'meta_title'       => $row['meta_title'],
                'meta_description' => $row['meta_description'],
                'updated_at'       => ApiFormat::iso($row['updated_at']),
            ],
        ]);
    }
}
