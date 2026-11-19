<?php
/**
 * API v1 — Master Data Kelas & Mata Pelajaran.
 *
 * Digunakan untuk dropdown dan pemilihan data master di aplikasi mobile.
 */

class ApiMasterDataController
{
    private function requireUser(): ?array
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized('Silakan login terlebih dahulu.');
            return null;
        }
        return $user;
    }

    /**
     * GET /api/v1/kelas
     */
    public function kelas(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT id, name, nama_kelas, level, tingkat, room, ruangan, capacity
             FROM kelas
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY level ASC, name ASC"
        );
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? $r['nama_kelas'] ?? '');
            $items[] = [
                'id'         => (int) $r['id'],
                'name'       => $name,
                'nama_kelas' => $name,
                'level'      => (int) ($r['level'] ?? $r['tingkat'] ?? 7),
                'tingkat'    => (int) ($r['level'] ?? $r['tingkat'] ?? 7),
                'room'       => (string) ($r['room'] ?? $r['ruangan'] ?? ''),
            ];
        }

        ApiResponse::ok($items);
    }

    /**
     * GET /api/v1/mata-pelajaran
     */
    public function mataPelajaran(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT id, code, kode_mapel, name, nama_mapel, group_name, kelompok
             FROM mata_pelajaran
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order ASC, name ASC"
        );
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? $r['nama_mapel'] ?? '');
            $code = (string) ($r['code'] ?? $r['kode_mapel'] ?? '');
            $group = (string) ($r['group_name'] ?? $r['kelompok'] ?? 'umum');
            $items[] = [
                'id'         => (int) $r['id'],
                'code'       => $code,
                'kode_mapel' => $code,
                'name'       => $name,
                'nama_mapel' => $name,
                'group'      => $group,
            ];
        }

        ApiResponse::ok($items);
    }
}
