<?php

class AdminPpdbController
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

        // Get PPDB settings
        $settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name = 'ppdb'")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get Documents
        $documents = $pdo->query("SELECT * FROM dokumen_ppdb ORDER BY sort_order ASC, created_at DESC")->fetchAll();

        $totalDownloads = (int) $pdo->query("SELECT SUM(download_count) FROM dokumen_ppdb")->fetchColumn();

        $data = [
            'title' => 'Info & Dokumen PPDB | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'ppdb',
            'pengaturan' => $settingsRaw,
            'documents' => $documents,
            'total_downloads' => $totalDownloads,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/ppdb/index.php';
    }

    public function updateInfo(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        $pdo = Database::connection();

        $keys = [
            'ppdb_title' => trim((string) ($_POST['ppdb_title'] ?? '')),
            'ppdb_schedule' => trim((string) ($_POST['ppdb_schedule'] ?? '')),
            'ppdb_requirements' => trim((string) ($_POST['ppdb_requirements'] ?? '')),
            'ppdb_contact_wa' => trim((string) ($_POST['ppdb_contact_wa'] ?? '')),
            'ppdb_contact_email' => trim((string) ($_POST['ppdb_contact_email'] ?? '')),
            'ppdb_is_open' => isset($_POST['ppdb_is_open']) ? '1' : '0',
        ];

        $stmt = $pdo->prepare("INSERT INTO pengaturan (setting_key, setting_value, group_name, is_public, created_at, updated_at) 
                               VALUES (?, ?, 'ppdb', 1, NOW(), NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");

        foreach ($keys as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        AuditLog::write('pengaturan.update_ppdb', 'pengaturan', null, "Memperbarui informasi halaman PPDB");

        $_SESSION['_flash_success'] = 'Informasi PPDB berhasil diperbarui.';
        header('Location: ' . base_url('admin/ppdb'));
    }

    public function storeDocument(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'umum'));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $filePath = trim((string) ($_POST['file_path'] ?? ''));
        $fileType = 'PDF';
        $fileSize = 0;

        if ($title === '') {
            $_SESSION['_flash_error'] = 'Nama dokumen wajib diisi.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'], true)) {
                $targetDir = __DIR__ . '/../../public/uploads/ppdb';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'ppdb_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $targetDir . '/' . $filename)) {
                    $filePath = 'uploads/ppdb/' . $filename;
                    $fileType = strtoupper($ext);
                    $fileSize = (int) filesize($targetDir . '/' . $filename);
                }
            }
        }

        if ($filePath === '') {
            $_SESSION['_flash_error'] = 'Berkas dokumen wajib diunggah atau path berkas harus diisi.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("INSERT INTO dokumen_ppdb (title, description, category, file_path, file_type, file_size, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $stmt->execute([
            $title,
            $description !== '' ? $description : null,
            $category,
            $filePath,
            $fileType,
            $fileSize,
            $sortOrder,
        ]);

        AuditLog::write('ppdb.create_doc', 'dokumen_ppdb', (int) $pdo->lastInsertId(), "Menambah dokumen PPDB: {$title}");

        $_SESSION['_flash_success'] = 'Dokumen PPDB berhasil ditambahkan.';
        header('Location: ' . base_url('admin/ppdb'));
    }

    public function toggleDocument(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE dokumen_ppdb SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['_flash_success'] = 'Status dokumen berhasil diubah.';
        header('Location: ' . base_url('admin/ppdb'));
    }

    public function deleteDocument(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/ppdb'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM dokumen_ppdb WHERE id = ?");
        $stmt->execute([$id]);

        AuditLog::write('ppdb.delete_doc', 'dokumen_ppdb', $id, "Menghapus dokumen PPDB ID {$id}");

        $_SESSION['_flash_success'] = 'Dokumen berhasil dihapus.';
        header('Location: ' . base_url('admin/ppdb'));
    }
}
