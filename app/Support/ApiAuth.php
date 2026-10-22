<?php
/**
 * Autentikasi API berbasis Bearer token.
 *
 * Aturan yang dipegang:
 *   - Token asli hanya dikirim SEKALI ke klien. Database hanya menyimpan
 *     hash SHA-256. Kalau database bocor, isinya tidak bisa dipakai masuk.
 *   - Access token 30 menit, refresh token 30 hari.
 *   - Refresh selalu berotasi: token lama dicabut, pasangan baru diterbitkan.
 *   - Setiap token terikat ke satu perangkat (device_name + platform).
 *   - Respons selalu JSON. Tidak pernah HTML, tidak pernah stack trace.
 */

class ApiAuth
{
    /** @var array<string,mixed>|null */
    private static ?array $current = null;

    /**
     * Terbitkan pasangan token baru.
     *
     * @return array{access_token:string, refresh_token:string, access_expires_at:string, refresh_expires_at:string}
     */
    public static function issue(int $userId, string $deviceName, string $platform, ?string $ip, ?string $agent): array
    {
        $pdo = Database::connection();

        $accessPlain = self::randomToken();
        $refreshPlain = self::randomToken();

        $accessMinutes = (int) self::setting('security_api_access_ttl', '30');
        $refreshDays = (int) self::setting('security_api_refresh_ttl', '30');

        $accessExpires = (new DateTimeImmutable('+' . $accessMinutes . ' minutes'))->format('Y-m-d H:i:s');
        $refreshExpires = (new DateTimeImmutable('+' . $refreshDays . ' days'))->format('Y-m-d H:i:s');

        if (!in_array($platform, ['android', 'ios', 'web', 'unknown'], true)) {
            $platform = 'unknown';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO api_tokens
                (user_id, access_token_hash, refresh_token_hash, device_name, platform,
                 ip_address, user_agent, access_expires_at, refresh_expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            hash('sha256', $accessPlain),
            hash('sha256', $refreshPlain),
            mb_substr($deviceName !== '' ? $deviceName : 'Perangkat tidak dikenal', 0, 120),
            $platform,
            $ip,
            mb_substr((string) $agent, 0, 255),
            $accessExpires,
            $refreshExpires,
        ]);

        return [
            'access_token'       => $accessPlain,
            'refresh_token'      => $refreshPlain,
            'access_expires_at'  => $accessExpires,
            'refresh_expires_at' => $refreshExpires,
        ];
    }

    /**
     * Rotasi: pakai refresh token lama untuk menerbitkan pasangan baru.
     * Token lama dicabut. Kalau token lama dipakai lagi, permintaan ditolak.
     */
    public static function rotate(string $refreshPlain, ?string $ip, ?string $agent): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, user_id, device_name, platform
             FROM api_tokens
             WHERE refresh_token_hash = ?
               AND revoked_at IS NULL
               AND refresh_expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $refreshPlain)]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        // Akun masih aktif?
        $check = $pdo->prepare('SELECT is_active FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $check->execute([(int) $row['user_id']]);
        $active = $check->fetchColumn();

        if ($active === false || (int) $active !== 1) {
            return null;
        }

        $pdo->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE id = ?')
            ->execute([(int) $row['id']]);

        return self::issue(
            (int) $row['user_id'],
            (string) $row['device_name'],
            (string) $row['platform'],
            $ip,
            $agent
        ) + ['user_id' => (int) $row['user_id']];
    }

    /**
     * Verifikasi access token dari header Authorization.
     *
     * @return array<string,mixed>|null  data pengguna, atau null kalau tidak sah
     */
    public static function authenticate(): ?array
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $header = self::bearerHeader();
        if ($header === null) {
            return null;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT t.id AS token_id, t.user_id, u.id, u.name, u.email, u.username,
                    u.role, u.role_label, u.must_change_password
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.access_token_hash = ?
               AND t.revoked_at IS NULL
               AND t.access_expires_at > NOW()
               AND u.is_active = 1
               AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $header)]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        // Catat pemakaian terakhir. Tidak setiap request — cukup sekali per menit,
        // supaya tidak ada UPDATE di setiap panggilan.
        $pdo->prepare(
            'UPDATE api_tokens SET last_used_at = NOW()
             WHERE id = ? AND (last_used_at IS NULL OR last_used_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE))'
        )->execute([(int) $row['token_id']]);

        self::$current = $row;

        return $row;
    }

    public static function revokeCurrent(): void
    {
        $user = self::authenticate();
        if ($user === null) {
            return;
        }

        Database::connection()
            ->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE id = ?')
            ->execute([(int) $user['token_id']]);
    }

    public static function revokeAllForUser(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'UPDATE api_tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL'
        );
        $stmt->execute([$userId]);

        return $stmt->rowCount();
    }

    private static function bearerHeader(): ?string
    {
        $raw = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $raw = (string) $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $raw = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $raw = (string) $value;
                    break;
                }
            }
        }

        if ($raw === null || $raw === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($raw), $m)) {
            return null;
        }

        return trim($m[1]);
    }

    /**
     * 32 byte acak, dikodekan base64url supaya aman di header dan URL.
     */
    private static function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function setting(string $key, ?string $default = null): ?string
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
        } catch (PDOException $e) {
            return $default;
        }

        return ($value === false || $value === null || $value === '') ? $default : (string) $value;
    }
}
