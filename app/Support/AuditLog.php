<?php
/**
 * Penulis jejak audit yang tidak bergantung pada sesi PHP.
 *
 * Auth::log() memakai Auth::id(), yang membaca sesi. API tidak memakai sesi,
 * jadi butuh penulis sendiri yang menerima user_id secara eksplisit.
 *
 * Aturan: kegagalan menulis log TIDAK BOLEH menggagalkan aksi utama.
 */

class AuditLog
{
    public static function write(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        string $description = '',
        ?string $ip = null
    ): void {
        try {
            Database::connection()
                ->prepare(
                    'INSERT INTO activity_logs
                        (user_id, action, entity, entity_id, description, ip_address, user_agent)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                )
                ->execute([
                    $userId,
                    mb_substr($action, 0, 80),
                    $entity === null ? null : mb_substr($entity, 0, 60),
                    $entityId,
                    mb_substr($description, 0, 255),
                    $ip,
                    mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]);
        } catch (PDOException $e) {
            // Sengaja diabaikan. Log audit tidak boleh menjatuhkan request.
        }
    }
}
