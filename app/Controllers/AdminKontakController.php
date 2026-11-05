<?php

/**
 * Controller Panel Admin untuk Manajemen Pesan Kontak & Kotak Masuk Pengunjung.
 */
class AdminKontakController
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

        $status = trim((string) ($_GET['status'] ?? 'semua'));
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        // Query count per status
        $counts = [
            'semua' => (int) $pdo->query("SELECT COUNT(*) FROM pesan_kontak")->fetchColumn(),
            'baru' => (int) $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status = 'baru'")->fetchColumn(),
            'dibaca' => (int) $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status = 'dibaca'")->fetchColumn(),
            'dibalas' => (int) $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status = 'dibalas'")->fetchColumn(),
        ];

        // Base where
        $where = [];
        $params = [];

        if (in_array($status, ['baru', 'dibaca', 'dibalas', 'arsip'], true)) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($q !== '') {
            $where[] = "(name LIKE :q OR email LIKE :q OR phone LIKE :q OR subject LIKE :q OR message LIKE :q)";
            $params[':q'] = "%$q%";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Hitung total filtered
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pesan_kontak $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $limit));

        // Fetch data
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as replied_by_name 
            FROM pesan_kontak p
            LEFT JOIN pengguna u ON u.id = p.replied_by
            $whereSql
            ORDER BY (p.status = 'baru') DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            'title' => 'Kotak Masuk Pesan | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'kontak',
            'sub_menu' => '',
            'messages' => $messages,
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/kontak/index.php';
    }

    public function markRead(int $id): void
    {
        $this->guard();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/kontak'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE pesan_kontak SET status = 'dibaca', updated_at = NOW() WHERE id = :id AND status = 'baru'");
        $stmt->execute([':id' => $id]);

        $_SESSION['_flash_success'] = 'Status pesan berhasil diubah menjadi sudah dibaca.';
        header('Location: ' . base_url('admin/kontak'));
    }

    public function markReplied(int $id): void
    {
        $user = $this->guard();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/kontak'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE pesan_kontak 
            SET status = 'dibalas', replied_at = NOW(), replied_by = :uid, updated_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':uid' => $user['id'] ?? null,
        ]);

        $_SESSION['_flash_success'] = 'Pesan telah ditandai sebagai sudah dibalas.';
        header('Location: ' . base_url('admin/kontak'));
    }

    public function delete(int $id): void
    {
        $this->guard();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/kontak'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM pesan_kontak WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['_flash_success'] = 'Pesan kontak berhasil dihapus.';
        header('Location: ' . base_url('admin/kontak'));
    }
}
