<?php

class AdminFasilitasController
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

    /**
     * Slug unik untuk fasilitas. Skema produksi mewajibkan slug NOT NULL + UNIQUE.
     */
    private function slugFor(string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if ($base === '') {
            $base = 'fasilitas';
        }

        $pdo = Database::connection();
        $slug = $base;
        $i = 2;

        while (true) {
            $sql = 'SELECT COUNT(*) FROM fasilitas WHERE slug = ?';
            $params = [$slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $ignoreId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $i;
            $i++;
        }
    }

    public function index(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $q = trim((string) ($_GET['q'] ?? ''));
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT * FROM fasilitas WHERE {$whereSql} ORDER BY sort_order ASC, name ASC");
        $stmt->execute($params);
        $facilities = $stmt->fetchAll();

        $data = [
            'title' => 'Kelola Fasilitas | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'fasilitas',
            'facilities' => $facilities,
            'fasilitas' => $facilities,
            'q' => $q,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/fasilitas/index.php';
    }

    public function create(): void
    {
        $user = $this->guard();

        $data = [
            'title' => 'Tambah Fasilitas Baru | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'fasilitas',
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/fasilitas/create.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/fasilitas/create'));
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim((string) ($_POST['image_path'] ?? ''));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'Nama fasilitas wajib diisi.';
            header('Location: ' . base_url('admin/fasilitas/create'));
            return;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/fasilitas';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'fasilitas_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $imagePath = 'uploads/fasilitas/' . $filename;
                }
            }
        }

        $pdo = Database::connection();

        // `icon` tidak ada di skema produksi; nilainya disimpan ke `category`.
        $category = $icon !== '' ? $icon : 'Sarana Pembelajaran';
        $slug = $this->slugFor($name);

        $stmt = $pdo->prepare("INSERT INTO fasilitas (name, nama_fasilitas, slug, category, description, deskripsi, image_path, gambar, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $name,
            $name,
            $slug,
            $category,
            $description !== '' ? $description : null,
            $description !== '' ? $description : null,
            $imagePath !== '' ? $imagePath : null,
            $imagePath !== '' ? $imagePath : null,
            $sortOrder,
            $isActive,
        ]);

        Auth::log('fasilitas.create', 'fasilitas', (int) $pdo->lastInsertId(), "Menambah fasilitas: {$name}");

        $_SESSION['_flash_success'] = 'Fasilitas berhasil ditambahkan.';
        header('Location: ' . base_url('admin/fasilitas'));
    }

    public function edit(int $id): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT * FROM fasilitas WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $facility = $stmt->fetch();

        if (!$facility) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $data = [
            'title' => 'Edit Fasilitas | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'fasilitas',
            'facility' => $facility,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/fasilitas/edit.php';
    }

    public function update(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url("admin/fasilitas/edit/{$id}"));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM fasilitas WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $facility = $stmt->fetch();

        if (!$facility) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim((string) ($_POST['image_path'] ?? ($facility['image_path'] ?? '')));

        if ($name === '') {
            $_SESSION['_flash_error'] = 'Nama fasilitas wajib diisi.';
            header('Location: ' . base_url("admin/fasilitas/edit/{$id}"));
            return;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/fasilitas';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'fasilitas_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $imagePath = 'uploads/fasilitas/' . $filename;
                }
            }
        }

        $category = $icon !== '' ? $icon : 'Sarana Pembelajaran';
        $slug = $this->slugFor($name, $id);

        $updateStmt = $pdo->prepare("UPDATE fasilitas SET name = ?, nama_fasilitas = ?, slug = ?, category = ?, description = ?, deskripsi = ?, image_path = ?, gambar = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([
            $name,
            $name,
            $slug,
            $category,
            $description !== '' ? $description : null,
            $description !== '' ? $description : null,
            $imagePath !== '' ? $imagePath : null,
            $imagePath !== '' ? $imagePath : null,
            $sortOrder,
            $isActive,
            $id,
        ]);

        Auth::log('fasilitas.update', 'fasilitas', $id, "Memperbarui fasilitas: {$name}");

        $_SESSION['_flash_success'] = 'Perubahan fasilitas berhasil disimpan.';
        header('Location: ' . base_url('admin/fasilitas'));
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/fasilitas'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE fasilitas SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);

        Auth::log('fasilitas.delete', 'fasilitas', $id, "Menghapus fasilitas ID {$id}");

        $_SESSION['_flash_success'] = 'Fasilitas berhasil dihapus.';
        header('Location: ' . base_url('admin/fasilitas'));
    }
}
