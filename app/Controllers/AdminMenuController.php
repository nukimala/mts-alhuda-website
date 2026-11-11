<?php

class AdminMenuController
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

        $location = trim((string) ($_GET['location'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        $where = ['m.deleted_at IS NULL'];
        $params = [];

        if ($location !== '' && in_array($location, ['utama', 'footer', 'mobile'], true)) {
            $where[] = 'm.location = ?';
            $params[] = $location;
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\\/', '\\\\_'], $q) . '%';
            $where[] = '(m.label LIKE ? OR m.url LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT m.*, parent.label as parent_label
                FROM menu m
                LEFT JOIN menu parent ON parent.id = m.parent_id
                WHERE {$whereSql}
                ORDER BY m.location ASC, m.sort_order ASC, m.label ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $menus = $stmt->fetchAll();

        $locations = ['utama', 'footer', 'mobile'];
        $countAll = (int) $pdo->query("SELECT COUNT(*) FROM menu WHERE deleted_at IS NULL")->fetchColumn();

        $data = [
            'title' => 'Kelola Menu | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'menu',
            'menu' => $menus,
            'locations' => $locations,
            'current_location' => $location,
            'q' => $q,
            'count_all' => $countAll,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/menu/index.php';
    }

    public function create(): void
    {
        $user = $this->guard();

        // Ambil semua menu yang sudah ada untuk parent (hanya yang aktif)
        $pdo = Database::connection();
        $parents = $pdo->query("SELECT id, label FROM menu WHERE deleted_at IS NULL AND is_active = 1 ORDER BY label ASC")->fetchAll();

        $data = [
            'title' => 'Tambah Menu Baru | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'menu',
            'parents' => $parents,
            'locations' => ['utama', 'footer', 'mobile'],
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/menu/create.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/menu/create'));
            return;
        }

        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $parentId = $_POST['parent_id'] ?? 0;
        $location = trim((string) ($_POST['location'] ?? 'utama'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $target = trim((string) ($_POST['target'] ?? 'self'));

        if ($label === '') {
            $_SESSION['_flash_error'] = 'Label menu wajib diisi.';
            $_SESSION['_flash_old'] = $_POST;
            header('Location: ' . base_url('admin/menu/create'));
            return;
        }

        if ($url === '') {
            $_SESSION['_flash_error'] = 'URL menu wajib diisi.';
            $_SESSION['_flash_old'] = $_POST;
            header('Location: ' . base_url('admin/menu/create'));
            return;
        }

        if (!in_array($location, ['utama', 'footer', 'mobile'], true)) {
            $location = 'utama';
        }

        if (!in_array($target, ['self', 'blank'], true)) {
            $target = 'self';
        }

        $pdo = Database::connection();

        // Validasi parent_id
        if ($parentId !== 0) {
            $stmt = $pdo->prepare("SELECT id FROM menu WHERE id = ? AND deleted_at IS NULL AND is_active = 1");
            $stmt->execute([$parentId]);
            if (!$stmt->fetch()) {
                $_SESSION['_flash_error'] = 'Parent menu tidak valid.';
                header('Location: ' . base_url('admin/menu/create'));
                return;
            }
        }

        // Cek duplikat: label+url+titik resmi tidak boleh sama
        $check = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE label = ? AND url = ? AND deleted_at IS NULL AND id != ?");
        $check->execute([$label, $url, $parentId]);
        if ((int) $check->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Menu dengan label dan URL ini sudah ada.';
            header('Location: ' . base_url('admin/menu/create'));
            return;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO menu (title, label, url, parent_id, location, sort_order, target, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$label, $label, $url, $parentId !== 0 ? (int) $parentId : null, $location, $sortOrder, $target, $isActive]);

        $menuId = (int) $pdo->lastInsertId();

        Auth::log('menu.create', 'menu', $menuId, "Menambah menu: {$label} (url: {$url})");

        $_SESSION['_flash_success'] = 'Menu berhasil ditambahkan.';
        header('Location: ' . base_url('admin/menu'));
    }

    public function edit(int $id): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $menu = $stmt->fetch();

        if (!$menu) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $parents = $pdo->query("SELECT id, label FROM menu WHERE deleted_at IS NULL AND is_active = 1 AND id != ? ORDER BY label ASC", [$id])->fetchAll();

        $data = [
            'title' => 'Edit Menu | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'menu',
            'menu' => $menu,
            'parents' => $parents,
            'locations' => ['utama', 'footer', 'mobile'],
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_flash_old'] ?? [],
        ];

        unset($_SESSION['_flash_error'], $_SESSION['_flash_old']);

        require_once __DIR__ . '/../Views/admin/menu/edit.php';
    }

    public function update(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/menu/edit/' . $id));
            return;
        }

        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $parentId = $_POST['parent_id'] ?? 0;
        $location = trim((string) ($_POST['location'] ?? 'utama'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $target = trim((string) ($_POST['target'] ?? 'self'));

        if ($label === '') {
            $_SESSION['_flash_error'] = 'Label menu wajib diisi.';
            header('Location: ' . base_url('admin/menu/edit/' . $id));
            return;
        }

        if ($url === '') {
            $_SESSION['_flash_error'] = 'URL menu wajib diisi.';
            header('Location: ' . base_url('admin/menu/edit/' . $id));
            return;
        }

        if (!in_array($location, ['utama', 'footer', 'mobile'], true)) {
            $location = 'utama';
        }

        if (!in_array($target, ['self', 'blank'], true)) {
            $target = 'self';
        }

        $pdo = Database::connection();

        // Cek duplikat dengan menu lain (kecuali yang sedang diedit)
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM menu WHERE label = ? AND url = ? AND deleted_at IS NULL AND id != ?"
        );
        $check->execute([$label, $url, $id]);
        if ((int) $check->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Menu dengan label dan URL ini sudah ada.';
            header('Location: ' . base_url('admin/menu/edit/' . $id));
            return;
        }

        // Validasi parent_id
        if ($parentId !== 0) {
            $stmt = $pdo->prepare("SELECT id FROM menu WHERE id = ? AND deleted_at IS NULL AND is_active = 1");
            $stmt->execute([$parentId]);
            if (!$stmt->fetch()) {
                $_SESSION['_flash_error'] = 'Parent menu tidak valid.';
                header('Location: ' . base_url('admin/menu/edit/' . $id));
                return;
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE menu SET title = ?, label = ?, url = ?, parent_id = ?, location = ?, sort_order = ?, target = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$label, $label, $url, $parentId !== 0 ? (int) $parentId : null, $location, $sortOrder, $target, $isActive, $id]);

        Auth::log('menu.update', 'menu', $id, "Memperbarui menu: {$label} (url: {$url})");

        $_SESSION['_flash_success'] = 'Menu berhasil diperbarui.';
        header('Location: ' . base_url('admin/menu'));
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/menu'));
            return;
        }

        $pdo = Database::connection();

        // Cegah cascade cascade: jangan hapus menu yang masih punya child
        $childCheck = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE parent_id = ? AND deleted_at IS NULL");
        $childCheck->execute([$id]);
        if ((int) $childCheck->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Menu ini masih memiliki submenu. Hapus submenu terlebih dahulu.';
            header('Location: ' . base_url('admin/menu'));
            return;
        }

        $stmt = $pdo->prepare("UPDATE menu SET deleted_at = NOW(), is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        Auth::log('menu.delete', 'menu', $id, "Menghapus menu ID {$id}");

        $_SESSION['_flash_success'] = 'Menu berhasil dihapus.';
        header('Location: ' . base_url('admin/menu'));
    }
}
