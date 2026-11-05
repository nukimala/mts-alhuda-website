<?php

class AdminAkreditasiController
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

    public function dashboard(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        // Get active period
        $period = $pdo->query("SELECT * FROM periode_akreditasi WHERE is_active = 1 LIMIT 1")->fetch();
        if (!$period) {
            $period = $pdo->query("SELECT * FROM periode_akreditasi ORDER BY id DESC LIMIT 1")->fetch();
        }

        $periodId = (int) ($period['id'] ?? 0);

        // Get components and progress
        $components = $pdo->query("SELECT c.*, 
                                   (SELECT COUNT(*) FROM butir_akreditasi b WHERE b.component_id = c.id) as total_butir,
                                   (SELECT COUNT(*) FROM butir_akreditasi b WHERE b.component_id = c.id AND b.status IN ('lengkap','terverifikasi')) as completed_butir,
                                   (SELECT COUNT(*) FROM butir_akreditasi b WHERE b.component_id = c.id AND b.status = 'kosong') as empty_butir,
                                   (SELECT COUNT(DISTINCT eb.evidence_id) FROM butir_akreditasi b JOIN bukti_butir eb ON eb.butir_id = b.id WHERE b.component_id = c.id) as total_evidence
                                   FROM komponen_akreditasi c
                                   WHERE c.period_id = {$periodId}
                                   ORDER BY c.sort_order ASC, c.number ASC")->fetchAll();

        $totalButirs = (int) $pdo->query("SELECT COUNT(*) FROM butir_akreditasi b JOIN komponen_akreditasi c ON c.id = b.component_id WHERE c.period_id = {$periodId}")->fetchColumn();
        $completedButirs = (int) $pdo->query("SELECT COUNT(*) FROM butir_akreditasi b JOIN komponen_akreditasi c ON c.id = b.component_id WHERE c.period_id = {$periodId} AND b.status IN ('lengkap','terverifikasi')")->fetchColumn();
        $emptyButirs = (int) $pdo->query("SELECT COUNT(*) FROM butir_akreditasi b JOIN komponen_akreditasi c ON c.id = b.component_id WHERE c.period_id = {$periodId} AND b.status = 'kosong'")->fetchColumn();
        $totalEvidence = (int) $pdo->query("SELECT COUNT(*) FROM bukti WHERE period_id = {$periodId} AND deleted_at IS NULL")->fetchColumn();

        $overallProgress = $totalButirs > 0 ? (int) round(($completedButirs / $totalButirs) * 100) : 0;

        // Gap analysis: butir where status is 'kosong' or 'proses'
        $gapButirs = $pdo->query("SELECT b.*, c.name as component_name, c.code as component_code,
                                  (SELECT COUNT(*) FROM bukti_butir eb WHERE eb.butir_id = b.id) as evidence_count,
                                  (SELECT COUNT(*) FROM entri_dka d WHERE d.butir_id = b.id) as dka_count
                                  FROM butir_akreditasi b
                                  JOIN komponen_akreditasi c ON c.id = b.component_id
                                  WHERE c.period_id = {$periodId} AND b.status IN ('kosong', 'proses')
                                  ORDER BY c.number ASC, b.number ASC
                                  LIMIT 10")->fetchAll();

        $data = [
            'title' => 'Dashboard Kesiapan Akreditasi | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'akreditasi',
            'sub_menu' => '',
            'period' => $period,
            'components' => $components,
            'total_butirs' => $totalButirs,
            'completed_butirs' => $completedButirs,
            'empty_butirs' => $emptyButirs,
            'total_evidence' => $totalEvidence,
            'overall_progress' => $overallProgress,
            'gap_butirs' => $gapButirs,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/akreditasi/dashboard.php';
    }

    public function bukti(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $period = $pdo->query("SELECT * FROM periode_akreditasi WHERE is_active = 1 LIMIT 1")->fetch();
        $periodId = (int) ($period['id'] ?? 1);

        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $compId = (int) ($_GET['comp_id'] ?? 0);

        $where = ['e.period_id = ?', 'e.deleted_at IS NULL'];
        $params = [$periodId];

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(e.title LIKE ? OR e.description LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && in_array($status, ['draft', 'valid', 'expired', 'rejected'], true)) {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT e.*, u.name as uploader_name,
                GROUP_CONCAT(CONCAT(c.code, '.', b.code, ':', b.id) SEPARATOR ', ') as mapped_butirs,
                COUNT(eb.id) as mapping_count
                FROM bukti e
                LEFT JOIN pengguna u ON u.id = e.uploaded_by
                LEFT JOIN bukti_butir eb ON eb.evidence_id = e.id
                LEFT JOIN butir_akreditasi b ON b.id = eb.butir_id
                LEFT JOIN komponen_akreditasi c ON c.id = b.component_id
                WHERE {$whereSql}
                GROUP BY e.id
                ORDER BY e.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $evidences = $stmt->fetchAll();

        // Components & butirs for modal mapping
        $components = $pdo->query("SELECT * FROM komponen_akreditasi WHERE period_id = {$periodId} ORDER BY number ASC")->fetchAll();
        $allButirs = $pdo->query("SELECT b.id, b.code, b.butir_text, c.name as comp_name, c.code as comp_code 
                                  FROM butir_akreditasi b 
                                  JOIN komponen_akreditasi c ON c.id = b.component_id 
                                  WHERE c.period_id = {$periodId} 
                                  ORDER BY c.number ASC, b.number ASC")->fetchAll();

        $statTotal = count($evidences);
        $statMapped = 0;
        foreach ($evidences as $ev) {
            if ((int) $ev['mapping_count'] > 0) $statMapped++;
        }
        $statUnmapped = $statTotal - $statMapped;

        $data = [
            'title' => 'Bank Bukti Akreditasi | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'akreditasi',
            'sub_menu' => 'bukti',
            'period' => $period,
            'evidences' => $evidences,
            'components' => $components,
            'all_butirs' => $allButirs,
            'stat_total' => $statTotal,
            'stat_mapped' => $statMapped,
            'stat_unmapped' => $statUnmapped,
            'q' => $q,
            'status' => $status,
            'comp_id' => $compId,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/akreditasi/bukti.php';
    }

    public function storeBukti(): void
    {
        $user = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/akreditasi/bukti'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $externalUrl = trim((string) ($_POST['external_url'] ?? ''));
        $validUntil = trim((string) ($_POST['valid_until'] ?? ''));
        $periodId = (int) ($_POST['period_id'] ?? 1);
        $butirIds = (array) ($_POST['butir_ids'] ?? []);

        if ($title === '') {
            $_SESSION['_flash_error'] = 'Judul dokumen bukti wajib diisi.';
            header('Location: ' . base_url('admin/akreditasi/bukti'));
            return;
        }

        $filePath = null;
        $fileSize = 0;
        $mimeType = null;
        $checksum = null;

        if (isset($_FILES['bukti_file']) && $_FILES['bukti_file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['bukti_file']['tmp_name'];
            $fileSize = (int) filesize($tmpPath);
            $mimeType = mime_content_type($tmpPath);
            $checksum = hash_file('sha256', $tmpPath);

            $ext = strtolower(pathinfo($_FILES['bukti_file']['name'], PATHINFO_EXTENSION));
            $targetDir = __DIR__ . '/../../public/uploads/bukti';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $filename = 'bukti_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($tmpPath, $targetDir . '/' . $filename)) {
                $filePath = 'uploads/bukti/' . $filename;
            }
        }

        if ($filePath === null && $externalUrl === '') {
            $_SESSION['_flash_error'] = 'Unggah berkas bukti atau sertakan tautan dokumen eksternal.';
            header('Location: ' . base_url('admin/akreditasi/bukti'));
            return;
        }

        $publicId = bin2hex(random_bytes(16));
        $publicId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($publicId, 4));

        $pdo = Database::connection();
        $stmt = $pdo->prepare("INSERT INTO bukti (public_id, period_id, title, description, file_path, external_url, mime_type, file_size, checksum, valid_until, status, uploaded_by, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valid', ?, NOW(), NOW())");
        $stmt->execute([
            $publicId,
            $periodId,
            $title,
            $description !== '' ? $description : null,
            $filePath,
            $externalUrl !== '' ? $externalUrl : null,
            $mimeType,
            $fileSize,
            $checksum,
            $validUntil !== '' ? $validUntil : null,
            $user['id'] ?? null,
        ]);

        $evidenceId = (int) $pdo->lastInsertId();

        // Link to selected butirs
        if (!empty($butirIds)) {
            $linkStmt = $pdo->prepare("INSERT IGNORE INTO bukti_butir (evidence_id, butir_id, linked_by, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($butirIds as $bId) {
                $bId = (int) $bId;
                if ($bId > 0) {
                    $linkStmt->execute([$evidenceId, $bId, $user['id'] ?? null]);
                    // Update butir status to 'proses' if 'kosong'
                    $pdo->exec("UPDATE butir_akreditasi SET status = 'proses', updated_at = NOW() WHERE id = {$bId} AND status = 'kosong'");
                }
            }
        }

        AuditLog::write('bukti.create', 'bukti', $evidenceId, "Unggah dokumen bukti akreditasi: {$title}");

        $_SESSION['_flash_success'] = 'Dokumen bukti berhasil ditambahkan dan ditautkan.';
        header('Location: ' . base_url('admin/akreditasi/bukti'));
    }

    public function deleteBukti(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/akreditasi/bukti'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE bukti SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);

        AuditLog::write('bukti.delete', 'bukti', $id, "Menghapus bukti akreditasi ID {$id}");

        $_SESSION['_flash_success'] = 'Dokumen bukti berhasil dihapus.';
        header('Location: ' . base_url('admin/akreditasi/bukti'));
    }

    public function dka(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $period = $pdo->query("SELECT * FROM periode_akreditasi WHERE is_active = 1 LIMIT 1")->fetch();
        $periodId = (int) ($period['id'] ?? 1);

        $selectedButirId = isset($_GET['butir_id']) ? (int) $_GET['butir_id'] : 0;

        // Fetch all components with butirs
        $components = $pdo->query("SELECT * FROM komponen_akreditasi WHERE period_id = {$periodId} ORDER BY number ASC")->fetchAll();

        $allButirs = $pdo->query("SELECT b.*, c.name as comp_name, c.code as comp_code,
                                  (SELECT COUNT(*) FROM bukti_butir eb WHERE eb.butir_id = b.id) as evidence_count,
                                  (SELECT COUNT(*) FROM entri_dka d WHERE d.butir_id = b.id) as dka_count
                                  FROM butir_akreditasi b
                                  JOIN komponen_akreditasi c ON c.id = b.component_id
                                  WHERE c.period_id = {$periodId}
                                  ORDER BY c.number ASC, b.number ASC")->fetchAll();

        if ($selectedButirId <= 0 && !empty($allButirs)) {
            $selectedButirId = (int) $allButirs[0]['id'];
        }

        // Current selected butir
        $currButir = null;
        foreach ($allButirs as $b) {
            if ((int) $b['id'] === $selectedButirId) {
                $currButir = $b;
                break;
            }
        }

        // Get latest DKA entry for this butir
        $dkaEntry = null;
        $linkedEvidence = [];
        if ($currButir) {
            $dkaStmt = $pdo->prepare("SELECT * FROM entri_dka WHERE butir_id = ? ORDER BY version DESC LIMIT 1");
            $dkaStmt->execute([$selectedButirId]);
            $dkaEntry = $dkaStmt->fetch();

            $evStmt = $pdo->prepare("SELECT e.* FROM bukti e 
                                    JOIN bukti_butir eb ON eb.evidence_id = e.id 
                                    WHERE eb.butir_id = ? AND e.deleted_at IS NULL 
                                    ORDER BY e.created_at DESC");
            $evStmt->execute([$selectedButirId]);
            $linkedEvidence = $evStmt->fetchAll();
        }

        $data = [
            'title' => 'Editor DKA — Deskripsi Kondisi Aktual | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'akreditasi',
            'sub_menu' => 'dka',
            'period' => $period,
            'components' => $components,
            'all_butirs' => $allButirs,
            'selected_butir_id' => $selectedButirId,
            'curr_butir' => $currButir,
            'dka_entry' => $dkaEntry,
            'linked_evidence' => $linkedEvidence,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/akreditasi/dka.php';
    }

    public function saveDka(): void
    {
        $user = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/akreditasi/dka'));
            return;
        }

        $butirId = (int) ($_POST['butir_id'] ?? 0);
        $content = trim((string) ($_POST['content'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));

        if ($butirId <= 0 || $content === '') {
            $_SESSION['_flash_error'] = 'Isian narasi DKA tidak boleh kosong.';
            header('Location: ' . base_url('admin/akreditasi/dka?butir_id=' . $butirId));
            return;
        }

        if (mb_strlen($content) > 1000) {
            $_SESSION['_flash_error'] = 'Narasi DKA maksimal 1.000 karakter.';
            header('Location: ' . base_url('admin/akreditasi/dka?butir_id=' . $butirId));
            return;
        }

        if (!in_array($status, ['draft', 'final', 'revisi'], true)) {
            $status = 'draft';
        }

        $pdo = Database::connection();

        // Check if draft entry exists
        $check = $pdo->prepare("SELECT id, version FROM entri_dka WHERE butir_id = ? AND status = 'draft'");
        $check->execute([$butirId]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE entri_dka SET content = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $status, $user['id'] ?? null, $existing['id']]);
        } else {
            $vStmt = $pdo->prepare("SELECT IFNULL(MAX(version), 0) + 1 FROM entri_dka WHERE butir_id = ?");
            $vStmt->execute([$butirId]);
            $nextVersion = (int) $vStmt->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO entri_dka (butir_id, content, version, status, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$butirId, $content, $nextVersion, $status, $user['id'] ?? null]);
        }

        // Update butir status
        $newButirStatus = ($status === 'final') ? 'lengkap' : 'proses';
        $pdo->exec("UPDATE butir_akreditasi SET status = '{$newButirStatus}', updated_at = NOW() WHERE id = {$butirId}");

        AuditLog::write('dka.save', 'entri_dka', $butirId, "Menyimpan DKA butir ID {$butirId} ({$status})");

        $_SESSION['_flash_success'] = 'Deskripsi Kondisi Aktual (DKA) berhasil disimpan.';
        header('Location: ' . base_url('admin/akreditasi/dka?butir_id=' . $butirId));
    }
}
