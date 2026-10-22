<?php
/**
 * Pembantu respons JSON.
 *
 * Semua keluaran API lewat kelas ini supaya bentuk responsnya konsisten:
 *   { "success": true,  "data": ... }
 *   { "success": false, "error": { "code": "...", "message": "..." } }
 */

class ApiResponse
{
    /** @var array<string,mixed> */
    public static array $meta = [];

    public static function json(mixed $data, int $status = 200, array $extra = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $body = ['success' => $status < 400];

        if ($status < 400) {
            $body['data'] = $data;
        } else {
            $body['error'] = $data;
        }

        if ($extra !== []) {
            $body = array_merge($body, $extra);
        }

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function ok(mixed $data = null, int $status = 200): void
    {
        self::json($data, $status);
    }

    public static function created(mixed $data = null): void
    {
        self::json($data, 201);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): void
    {
        $payload = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $payload['details'] = $details;
        }

        self::json($payload, $status);
    }

    public static function unauthorized(string $message = 'Sesi berakhir. Silakan masuk kembali.'): void
    {
        self::error('UNAUTHORIZED', $message, 401);
    }

    public static function forbidden(string $message = 'Anda tidak punya akses ke sini.'): void
    {
        self::error('FORBIDDEN', $message, 403);
    }

    public static function notFound(string $message = 'Data tidak ditemukan.'): void
    {
        self::error('NOT_FOUND', $message, 404);
    }

    public static function validation(array $errors, string $message = 'Data yang dikirim belum lengkap atau tidak valid.'): void
    {
        self::error('VALIDATION_ERROR', $message, 422, $errors);
    }

    public static function tooMany(string $message = 'Terlalu banyak permintaan. Coba lagi sebentar.'): void
    {
        self::error('TOO_MANY_REQUESTS', $message, 429);
    }

    public static function serverError(string $message = 'Terjadi kesalahan di server.'): void
    {
        self::error('SERVER_ERROR', $message, 500);
    }

    /**
     * Baca body JSON. Kalau bukan JSON, coba form-encoded.
     *
     * @return array<string,mixed>
     */
    public static function input(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim((string) $raw) === '') {
            return $_POST;
        }

        $decoded = json_decode((string) $raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return $_POST;
    }

    /**
     * Ambil satu nilai dari input sebagai string yang sudah dirapikan.
     *
     * @param array<string,mixed> $input
     */
    public static function str(array $input, string $key, string $default = ''): string
    {
        $value = $input[$key] ?? $default;

        if (is_array($value)) {
            return $default;
        }

        return trim((string) $value);
    }
}
