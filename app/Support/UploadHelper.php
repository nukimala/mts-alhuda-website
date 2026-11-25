<?php
/**
 * Helper unggahan berkas aman dengan validasi ukuran, ekstensi, dan MIME type aktual (finfo).
 */

class UploadHelper
{
    /** @var array<string, list<string>> */
    private const IMAGE_MIMES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /** @var array<string, list<string>> */
    private const DOC_MIMES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /**
     * Unggah gambar aman (maks default 5 MB).
     *
     * @param array<string,mixed> $file
     * @return array{ok:bool, path?:string, mime?:string, size?:int, error?:string}
     */
    public static function storeImage(array $file, string $subDir, string $prefix, int $maxBytes = 5242880): array
    {
        return self::store($file, $subDir, $prefix, self::IMAGE_MIMES, $maxBytes);
    }

    /**
     * Unggah dokumen aman (maks default 10 MB).
     *
     * @param array<string,mixed> $file
     * @return array{ok:bool, path?:string, mime?:string, size?:int, error?:string}
     */
    public static function storeDocument(array $file, string $subDir, string $prefix, int $maxBytes = 10485760): array
    {
        return self::store($file, $subDir, $prefix, self::DOC_MIMES, $maxBytes);
    }

    /**
     * @param array<string,mixed> $file
     * @param array<string, list<string>> $allowedMap
     * @return array{ok:bool, path?:string, mime?:string, size?:int, error?:string}
     */
    private static function store(array $file, string $subDir, string $prefix, array $allowedMap, int $maxBytes): array
    {
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Unggahan berkas gagal atau tidak lengkap.'];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'error' => 'Berkas unggahan tidak valid.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = round($maxBytes / 1048576, 1);
            return ['ok' => false, 'error' => "Ukuran berkas tidak boleh melebihi {$maxMb} MB."];
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset($allowedMap[$ext])) {
            return ['ok' => false, 'error' => 'Format ekstensi berkas tidak diizinkan.'];
        }

        $detectedMime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = (string) finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
            }
        }

        if ($detectedMime !== '' && !in_array($detectedMime, $allowedMap[$ext], true)) {
            return ['ok' => false, 'error' => 'Isi berkas tidak sesuai dengan format yang diizinkan.'];
        }

        // Validasi tambahan khusus gambar
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && @getimagesize($tmpPath) === false) {
            return ['ok' => false, 'error' => 'Berkas gambar rusak atau tidak valid.'];
        }

        $safeSubDir = trim($subDir, '/\\');
        $targetDir = __DIR__ . '/../../public/uploads/' . $safeSubDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            return ['ok' => false, 'error' => 'Gagal menyimpan berkas ke server.'];
        }

        return [
            'ok'   => true,
            'path' => 'uploads/' . $safeSubDir . '/' . $filename,
            'mime' => $detectedMime !== '' ? $detectedMime : 'application/octet-stream',
            'size' => (int) filesize($destination),
        ];
    }
}
