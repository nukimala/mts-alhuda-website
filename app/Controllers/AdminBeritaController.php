<?php

class AdminBeritaController
{
    private function guard(): array
    {
        Auth::start();
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }

        $user = Auth::user();
        if ($user !== null && (int) ($user['must_change_password'] ?? 0) === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $sort = trim((string) ($_GET['sort'] ?? 'latest'));

        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && in_array($status, ['draft', 'published', 'archived'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }

        $orderBy = match ($sort) {
            'oldest' => 'p.created_at ASC',
            'title_asc' => 'p.title ASC',
            default => 'p.created_at DESC',
        };

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM berita p WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $totalPages = max(1, (int) ceil($total / $perPage));

        $sql = "SELECT p.*, u.name as author_name
                FROM berita p
                LEFT JOIN pengguna u ON u.id = p.author_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        // Stat counts
        $statPublished = (int) $pdo->query("SELECT COUNT(*) FROM berita WHERE status = 'published' AND deleted_at IS NULL")->fetchColumn();
        $statDraft = (int) $pdo->query("SELECT COUNT(*) FROM berita WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn();

        $data = [
            'title' => 'Kelola Berita | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'berita',
            'berita' => $posts,
            'posts' => $posts,
            'total' => $total,
            'stat_published' => $statPublished,
            'stat_draft' => $statDraft,
            'page' => $page,
            'total_pages' => $totalPages,
            'q' => $q,
            'status' => $status,
            'sort' => $sort,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/berita/index.php';
    }

    public function create(): void
    {
        $user = $this->guard();

        $data = [
            'title' => 'Tambah Berita Baru | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'berita',
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/berita/create.php';
    }

    public function store(): void
    {
        $user = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/berita/create'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $coverPath = trim((string) ($_POST['cover_path'] ?? ''));

        if ($title === '' || $content === '') {
            $_SESSION['_flash_error'] = 'Judul dan konten berita wajib diisi.';
            $_SESSION['_flash_old'] = $_POST;
            header('Location: ' . base_url('admin/berita/create'));
            return;
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        // Handle file upload if present
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/berita';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'berita_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $coverPath = 'uploads/berita/' . $filename;
                }
            }
        }

        $pdo = Database::connection();

        // `uq_berita_slug` berlaku untuk semua baris (termasuk yang di-soft-delete),
        // jadi pengecekan juga harus menghitung baris terhapus.
        $check = $pdo->prepare("SELECT COUNT(*) FROM berita WHERE slug = ?");
        $check->execute([$slug]);
        if ((int) $check->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $publicId = bin2hex(random_bytes(16));
        $publicId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($publicId, 4));

        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

        // Skema produksi menyimpan kolom kembar (Inggris + Indonesia).
        // Keduanya wajib diisi agar admin dan aplikasi mobile membaca data yang sama.
        $coverValue = $coverPath !== '' ? $coverPath : null;

        $stmt = $pdo->prepare("INSERT INTO berita (public_id, title, judul, slug, excerpt, content, isi, cover_path, image_path, gambar, status, is_featured, author_id, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $publicId,
            $title,
            $title,
            $slug,
            $excerpt !== '' ? $excerpt : null,
            $content,
            $content,
            $coverValue,
            $coverValue,
            $coverValue,
            $status,
            $isFeatured,
            $user['id'] ?? null,
            $publishedAt,
        ]);

        Auth::log('berita.create', 'berita', (int) $pdo->lastInsertId(), "Membuat berita: {$title}");

        $_SESSION['_flash_success'] = 'Berita berhasil disimpan.';
        header('Location: ' . base_url('admin/berita'));
    }

    public function edit(int $id): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT * FROM berita WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $data = [
            'title' => 'Edit Berita | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'berita',
            'post' => $post,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/berita/edit.php';
    }

    public function update(int $id): void
    {
        $user = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url("admin/berita/edit/{$id}"));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM berita WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $coverPath = trim((string) ($_POST['cover_path'] ?? ($post['cover_path'] ?? '')));

        if ($title === '' || $content === '') {
            $_SESSION['_flash_error'] = 'Judul dan konten berita wajib diisi.';
            header('Location: ' . base_url("admin/berita/edit/{$id}"));
            return;
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Check slug conflict with other posts (termasuk yang sudah di-soft-delete,
        // karena unique index `uq_berita_slug` tetap menyimpan nilainya)
        $check = $pdo->prepare("SELECT COUNT(*) FROM berita WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
        if ((int) $check->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        // Handle file upload
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/berita';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'berita_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $coverPath = 'uploads/berita/' . $filename;
                }
            }
        }

        $publishedAt = $post['published_at'];
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $coverValue = $coverPath !== '' ? $coverPath : null;

        $updateStmt = $pdo->prepare("UPDATE berita SET title = ?, judul = ?, slug = ?, excerpt = ?, content = ?, isi = ?, cover_path = ?, image_path = ?, gambar = ?, status = ?, is_featured = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([
            $title,
            $title,
            $slug,
            $excerpt !== '' ? $excerpt : null,
            $content,
            $content,
            $coverValue,
            $coverValue,
            $coverValue,
            $status,
            $isFeatured,
            $publishedAt,
            $id,
        ]);

        Auth::log('berita.update', 'berita', $id, "Memperbarui berita: {$title}");

        $_SESSION['_flash_success'] = 'Perubahan berita berhasil disimpan.';
        header('Location: ' . base_url('admin/berita'));
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE berita SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);

        Auth::log('berita.delete', 'berita', $id, "Menghapus berita ID {$id}");

        $_SESSION['_flash_success'] = 'Berita berhasil dihapus.';
        header('Location: ' . base_url('admin/berita'));
    }
}
