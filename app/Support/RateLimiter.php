<?php
/**
 * Rate limiter sederhana berbasis database.
 *
 * Dipakai untuk mencegah brute force login API dan penyalahgunaan endpoint.
 * Tanpa Redis, tanpa memori bersama — cukup tabel `rate_limits`.
 *
 * Catatan: tabel ini dibuat oleh migrasi 013. Kalau belum ada, limiter
 * melewati pemeriksaan dan mencatatnya di log. API tetap jalan.
 */

class RateLimiter
{
    /**
     * @return array{allowed:bool, remaining:int, retry_after:int}
     */
    public static function hit(string $key, int $maxAttempts, int $windowSeconds): array
    {
        try {
            $pdo = Database::connection();

            $pdo->prepare(
                'DELETE FROM rate_limits WHERE expires_at < NOW() LIMIT 500'
            )->execute();

            $stmt = $pdo->prepare(
                'SELECT attempts, expires_at FROM rate_limits WHERE bucket = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if ($row === false) {
                $pdo->prepare(
                    'INSERT INTO rate_limits (bucket, attempts, expires_at)
                     VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
                     ON DUPLICATE KEY UPDATE attempts = 1, expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)'
                )->execute([$key, $windowSeconds, $windowSeconds]);

                return ['allowed' => true, 'remaining' => $maxAttempts - 1, 'retry_after' => 0];
            }

            if ((int) $row['attempts'] >= $maxAttempts) {
                $retry = max(0, (int) (strtotime((string) $row['expires_at']) - time()));

                return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retry];
            }

            $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE bucket = ?')
                ->execute([$key]);

            return [
                'allowed'     => true,
                'remaining'   => max(0, $maxAttempts - (int) $row['attempts'] - 1),
                'retry_after' => 0,
            ];
        } catch (PDOException $e) {
            // Tabel belum ada. Jangan gagalkan request karena limiter.
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
        }
    }

    public static function clear(string $key): void
    {
        try {
            Database::connection()
                ->prepare('DELETE FROM rate_limits WHERE bucket = ?')
                ->execute([$key]);
        } catch (PDOException $e) {
            // diabaikan
        }
    }

    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            $value = (string) $_SERVER[$header];

            if ($header === 'HTTP_X_FORWARDED_FOR' && strpos($value, ',') !== false) {
                $value = trim(explode(',', $value)[0]);
            }

            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }
}
