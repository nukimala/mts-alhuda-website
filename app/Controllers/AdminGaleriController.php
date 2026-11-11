<?php

class AdminGaleriController
{
    private function guard(): array
    {
        Auth::start();
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            exit();
        }

        $user = Auth::user();
        if ($user !== null && (int) ($user['must_change_password'] ?? 0) === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            exit();
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $album = trim((string) ($_GET['album'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($album !== '') {
            $where[] = 'album = ?';
            $params[] = $album;
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(title LIKE ? OR caption LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $albums = $pdo->query(
            "SELECT DISTINCT album FROM galeri WHERE deleted_at IS NULL AND album IS NOT NULL AND album != '' ORDER BY album ASC",
        )->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT * FROM galeri WHERE {$whereSql} ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $data = [
            'title' => 'Galeri Foto & Dokumentasi | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'galeri',
            'items' => $items,
            'albums' => $albums,
            'album' => $album,
            'q' => $q,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/galeri/index.php';
    }

    public function create(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $albums = $pdo->query(
            "SELECT DISTINCT album FROM galeri WHERE deleted_at IS NULL AND album IS NOT NULL AND album != '' ORDER BY album ASC",
        )->fetchAll(PDO::FETCH_COLUMN);

        $data = [
            'title' => 'Tambah Foto Galeri | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'galeri',
            'albums' => $albums,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/galeri/create.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/galeri/create'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $album = trim((string) ($_POST['album'] ?? ''));
        if ($album === '' && !empty($_POST['new_album'])) {
            $album = trim((string) $_POST['new_album']);
        }
        $caption = trim((string) ($_POST['caption'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim((string) ($_POST['image_path'] ?? ''));

        if ($title === '') {
            $_SESSION['_flash_error'] = 'Judul foto wajib diisi.';
            header('Location: ' . base_url('admin/galeri/create'));
            return;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/galeri';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'galeri_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $imagePath = 'uploads/galeri/' . $filename;
                }
            }
        }

        if ($imagePath === '') {
            $_SESSION['_flash_error'] = 'Berkas foto wajib diunggah atau path berkas harus diisi.';
            header('Location: ' . base_url('admin/galeri/create'));
            return;
        }

        $pdo = Database::connection();
        // Kolom kembar (Inggris + Indonesia) diisi bersamaan.
        $stmt = $pdo->prepare("INSERT INTO galeri (album, title, judul, image_path, gambar, caption, keterangan, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $album !== '' ? $album : 'Kegiatan Madrasah',
            $title,
            $title,
            $imagePath,
            $imagePath,
            $caption !== '' ? $caption : null,
            $caption !== '' ? $caption : null,
            $sortOrder,
            $isActive,
        ]);

        Auth::log('galeri.create', 'galeri', (int) $pdo->lastInsertId(), "Menambah foto galeri: {$title}");

        $_SESSION['_flash_success'] = 'Foto berhasil ditambahkan ke galeri.';
        header('Location: ' . base_url('admin/galeri'));
    }

    public function edit(int $id): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM galeri WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            $errorController = new ErrorController();
            $errorController->notFound();
            return;
        }

        $albums = $pdo->query(
            "SELECT DISTINCT album FROM galeri WHERE deleted_at IS NULL AND album IS NOT NULL AND album != '' ORDER BY album ASC",
        )->fetchAll(PDO::FETCH_COLUMN);

        $data = [
            'title' => 'Edit Foto Galeri | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'galeri',
            'item' => $item,
            'albums' => $albums,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/galeri/edit.php';
    }

    public function update(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url("admin/galeri/edit/{$id}"));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM galeri WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            $errorController = new ErrorController();
            $errorController->notFound();
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $album = trim((string) ($_POST['album'] ?? ''));
        if ($album === '' && !empty($_POST['new_album'])) {
            $album = trim((string) $_POST['new_album']);
        }
        $caption = trim((string) ($_POST['caption'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim((string) ($_POST['image_path'] ?? $item['image_path'] ?? ''));

        if ($title === '') {
            $_SESSION['_flash_error'] = 'Judul foto wajib diisi.';
            header('Location: ' . base_url("admin/galeri/edit/{$id}"));
            return;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/galeri';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'galeri_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $imagePath = 'uploads/galeri/' . $filename;
                }
            }
        }

        $updateStmt = $pdo->prepare("UPDATE galeri SET album = ?, title = ?, judul = ?, image_path = ?, gambar = ?, caption = ?, keterangan = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([
            $album !== '' ? $album : null,
            $title,
            $title,
            $imagePath,
            $imagePath,
            $caption !== '' ? $caption : null,
            $caption !== '' ? $caption : null,
            $sortOrder,
            $isActive,
            $id,
        ]);

        Auth::log('galeri.update', 'galeri', $id, "Memperbarui foto galeri: {$title}");

        $_SESSION['_flash_success'] = 'Perubahan foto galeri berhasil disimpan.';
        header('Location: ' . base_url('admin/galeri'));
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/galeri'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE galeri SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);

        Auth::log('galeri.delete', 'galeri', $id, "Menghapus foto galeri ID {$id}");

        $_SESSION['_flash_success'] = 'Foto berhasil dihapus dari galeri.';
        header('Location: ' . base_url('admin/galeri'));
    }
}
