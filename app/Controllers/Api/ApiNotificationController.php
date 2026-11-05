<?php
/**
 * API v1 — Notifikasi In-App untuk Aplikasi Mobile MADRASAHKU.
 *
 * Mengakomodasi:
 * - GET  /api/v1/notifications         (daftar notifikasi pengguna + unread count)
 * - POST /api/v1/notifications/{id}/read (tandai satu notifikasi sudah dibaca)
 * - POST /api/v1/notifications/read-all  (tandai semua notifikasi sudah dibaca)
 */

class ApiNotificationController
{
    /**
     * @return array<string,mixed>|null
     */
    private function requireUser(): ?array
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized();
            return null;
        }

        return $user;
    }

    /**
     * GET /api/v1/notifications
     */
    public function index(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $userId = (int) $user['id'];
        $pdo = Database::connection();

        // Hitung notifikasi belum dibaca
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
        $unreadStmt->execute([$userId]);
        $unreadCount = (int) $unreadStmt->fetchColumn();

        $page = ApiFormat::paginate();

        $stmt = $pdo->prepare(
            "SELECT id, title, body, link, type, is_read, read_at, created_at
             FROM notifikasi
             WHERE user_id = ?
             ORDER BY is_read ASC, created_at DESC
             LIMIT {$page['per_page']} OFFSET {$page['offset']}"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'         => (int) $r['id'],
                'title'      => (string) $r['title'],
                'body'       => (string) $r['body'],
                'link'       => $r['link'],
                'type'       => $r['type'],
                'is_read'    => (int) $r['is_read'] === 1,
                'read_at'    => ApiFormat::iso($r['read_at']),
                'created_at' => ApiFormat::iso($r['created_at']),
            ];
        }

        $countTotal = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ?");
        $countTotal->execute([$userId]);
        $total = (int) $countTotal->fetchColumn();

        ApiResponse::json([
            'unread_count'  => $unreadCount,
            'notifications' => $items,
        ], 200, ['meta' => ApiFormat::meta($total, $page['page'], $page['per_page'])]);
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function markRead(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Notifikasi tidak ditemukan.');
            return;
        }

        $userId = (int) $user['id'];
        $pdo = Database::connection();

        $stmt = $pdo->prepare("UPDATE notifikasi SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        ApiResponse::ok(['message' => 'Notifikasi telah ditandai dibaca.']);
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function markAllRead(): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $userId = (int) $user['id'];
        $pdo = Database::connection();

        $stmt = $pdo->prepare("UPDATE notifikasi SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);

        ApiResponse::ok(['message' => 'Semua notifikasi telah ditandai dibaca.']);
    }
}
