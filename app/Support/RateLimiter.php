<?php
/**
 * Rate limiter sederhana berbasis database.
 *
 * Dipakai untuk mencegah brute force login API dan penyalahgunaan endpoint.
 * Tanpa Redis, tanpa memori bersama — cukup tabel `batas_akses`.
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
            $now = time();

            // Kolom waktu di `batas_akses` produksi bertipe INT (epoch Unix),
            // bukan DATETIME. Membandingkan dengan NOW() akan error.
            $pdo->exec('DELETE FROM batas_akses WHERE expires_at < ' . (int) $now . ' LIMIT 500');

            // Skema produksi memakai `tokens`/`last_replenished_at`, bukan `attempts`.
            $stmt = $pdo->prepare(
                'SELECT tokens, expires_at FROM batas_akses WHERE bucket = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            $remaining = max(0, $maxAttempts - 1);

            if ($row === false) {
                $pdo->prepare(
                    'INSERT INTO batas_akses (bucket, tokens, last_replenished_at, expires_at)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE tokens = VALUES(tokens), last_replenished_at = VALUES(last_replenished_at), expires_at = VALUES(expires_at)'
                )->execute([$key, $remaining, $now, $now + $windowSeconds]);

                return ['allowed' => true, 'remaining' => $remaining, 'retry_after' => 0];
            }

            if ((float) $row['tokens'] <= 0) {
                return [
                    'allowed'     => false,
                    'remaining'   => 0,
                    'retry_after' => max(0, (int) $row['expires_at'] - $now),
                ];
            }

            $pdo->prepare(
                'UPDATE batas_akses SET tokens = GREATEST(tokens - 1, 0), last_replenished_at = ? WHERE bucket = ?'
            )->execute([$now, $key]);

            return [
                'allowed'     => true,
                'remaining'   => max(0, (int) $row['tokens'] - 1),
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
                ->prepare('DELETE FROM batas_akses WHERE bucket = ?')
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
