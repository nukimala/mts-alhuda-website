<?php
/**
 * Penerima unggahan berkas.
 *
 * Satu-satunya tempat di sistem ini yang menulis berkas dari input pengguna.
 * Karena itu aturannya ketat dan tidak boleh dilonggarkan di tempat lain:
 *
 *   1. Ekstensi diperiksa terhadap daftar putih per jenis berkas.
 *   2. Tipe MIME asli dibaca dari ISI berkas (finfo), bukan dari header
 *      yang dikirim browser. Header bisa dikarang; isi berkas tidak.
 *   3. Nama berkas dibuat ulang dari nol. Nama asli hanya disimpan sebagai
 *      keterangan. Tanpa ini, nama seperti "foto.php.jpg" atau
 *      "../../index.php" akan menulis di luar folder unggahan.
 *   4. Ukuran dibatasi sebelum berkas dipindahkan.
 *   5. Berkas disimpan di public/uploads/<folder>/ yang punya .htaccess
 *      sendiri untuk mematikan eksekusi PHP.
 *
 * Kegagalan selalu melempar RuntimeException berisi pesan Indonesia yang
 * siap ditampilkan ke pengguna.
 */

class Media
{
    /** Batas ukuran bawaan: 3 MB. */
    public const MAX_BYTES = 3 * 1024 * 1024;

    /** Folder yang boleh dipakai. Membatasi tulisan ke lokasi yang dikenal. */
    private const FOLDERS = ['berita', 'galeri', 'fasilitas', 'dokumen', 'profil', 'umum'];

    /** Ekstensi => MIME yang diharapkan. */
    private const IMAGE_TYPES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
    ];

    private const DOCUMENT_TYPES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    /**
     * Simpan satu berkas unggahan.
     *
     * @param array<string,mixed> $file  Satu entri dari $_FILES.
     * @param array{kind?:string,folder?:string,max_bytes?:int,alt?:string,required?:bool} $options
     * @return array<string,mixed>|null  Baris media yang tersimpan, atau null
     *                                   kalau tidak ada berkas dikirim.
     */
    public static function store(array $file, array $options = []): ?array
    {
        $kind = $options['kind'] ?? 'image';
        $folder = $options['folder'] ?? 'umum';
        $maxBytes = (int) ($options['max_bytes'] ?? self::MAX_BYTES);
        $required = (bool) ($options['required'] ?? false);

        if (!in_array($folder, self::FOLDERS, true)) {
            throw new RuntimeException('Folder unggahan tidak dikenal: ' . $folder);
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException('Berkas wajib dipilih.');
            }

            return null;
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('Berkas terlalu besar untuk server. Maksimal ' . Format::bytes($maxBytes) . '.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Berkas gagal diunggah (kode ' . $error . '). Coba lagi.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('Berkas sementara tidak ditemukan. Coba unggah ulang.');
        }

        $size = (int) filesize($tmp);

        if ($size <= 0) {
            throw new RuntimeException('Berkas kosong.');
        }

        if ($size > $maxBytes) {
            throw new RuntimeException(
                'Ukuran berkas ' . Format::bytes($size) . ' melebihi batas ' . Format::bytes($maxBytes) . '.'
            );
        }

        $originalName = (string) ($file['name'] ?? 'berkas');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $whitelist = $kind === 'document'
            ? self::DOCUMENT_TYPES
            : self::IMAGE_TYPES;

        if (!isset($whitelist[$extension])) {
            throw new RuntimeException(
                'Jenis berkas tidak diizinkan. Yang diterima: ' . strtoupper(implode(', ', array_keys($whitelist))) . '.'
            );
        }

        $mime = self::detectMime($tmp);

        if (!in_array($mime, $whitelist[$extension], true)) {
            throw new RuntimeException(
                'Isi berkas tidak cocok dengan ekstensinya (' . $extension . ' / ' . $mime . ').'
            );
        }

        // Berkas gambar wajib bisa dibaca sebagai gambar, dan dimensinya dicatat.
        $width = null;
        $height = null;

        if ($kind !== 'document') {
            $info = @getimagesize($tmp);

            if ($info === false) {
                throw new RuntimeException('Berkas gambar tidak valid atau rusak.');
            }

            $width = (int) $info[0];
            $height = (int) $info[1];

            if ($width < 1 || $height < 1) {
                throw new RuntimeException('Dimensi gambar tidak terbaca.');
            }
        }

        $directory = self::absoluteDir($folder);
        self::ensureDir($directory);

        $baseName = self::safeBaseName($originalName);
        $fileName = $baseName . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . '/' . $fileName;

        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Berkas gagal dipindahkan ke folder unggahan. Periksa izin tulis folder.');
        }

        @chmod($target, 0644);

        $relativePath = 'uploads/' . $folder . '/' . $fileName;
        $checksum = hash_file('sha256', $target) ?: null;

        $row = [
            'path'        => $relativePath,
            'file_name'   => mb_substr($originalName, 0, 255),
            'mime_type'   => $mime,
            'extension'   => $extension,
            'file_size'   => $size,
            'checksum'    => $checksum,
            'width'       => $width,
            'height'      => $height,
            'alt'         => mb_substr(trim((string) ($options['alt'] ?? '')), 0, 255),
            'folder'      => $folder,
            'uploaded_by' => Auth::id(),
        ];

        self::record($row);

        return $row;
    }

    /**
     * Hapus berkas fisik dan barisnya di tabel media.
     *
     * Dipakai saat pengguna menghapus gambar cover atau foto galeri.
     * Kegagalan menghapus berkas tidak menggagalkan aksi utama.
     */
    public static function delete(?string $relativePath): void
    {
        $relativePath = trim((string) $relativePath);

        if ($relativePath === '') {
            return;
        }

        // Hanya boleh menyentuh isi uploads/. Tanpa ini, nilai dari database
        // yang sudah diubah bisa dipakai untuk menghapus berkas sistem.
        if (strpos($relativePath, 'uploads/') !== 0 || strpos($relativePath, '..') !== false) {
            return;
        }

        $absolute = dirname(__DIR__, 2) . '/public/' . $relativePath;

        if (is_file($absolute)) {
            @unlink($absolute);
        }

        try {
            Database::connection()
                ->prepare('DELETE FROM media WHERE path = ? LIMIT 1')
                ->execute([$relativePath]);
        } catch (PDOException $e) {
            // Baris media boleh tertinggal; yang penting berkasnya sudah hilang.
        }
    }

    /**
     * Apakah path ini menunjuk berkas yang benar-benar ada.
     */
    public static function exists(?string $relativePath): bool
    {
        $relativePath = trim((string) $relativePath);

        if ($relativePath === '' || strpos($relativePath, '..') !== false) {
            return false;
        }

        return is_file(dirname(__DIR__, 2) . '/public/' . ltrim($relativePath, '/'));
    }

    /**
     * MIME dari isi berkas. finfo hampir selalu ada; kalau tidak, pakai
     * mime_content_type sebagai cadangan.
     */
    private static function detectMime(string $path): string
    {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);

            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);

            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Nama dasar yang aman: hanya huruf, angka, tanda hubung.
     */
    private static function safeBaseName(string $originalName): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $name = Format::slug($name);

        if ($name === '') {
            $name = 'berkas';
        }

        return mb_substr($name, 0, 60);
    }

    private static function absoluteDir(string $folder): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/' . $folder;
    }

    private static function ensureDir(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder unggahan tidak bisa dibuat. Periksa izin tulis.');
        }
    }

    /**
     * Catat baris media. Gagal mencatat tidak boleh membatalkan unggahan:
     * berkasnya sudah ada di disk dan tetap bisa dipakai lewat path.
     *
     * @param array<string,mixed> $row
     */
    private static function record(array $row): void
    {
        if (!Schema::hasTable('media')) {
            return;
        }

        try {
            Database::connection()
                ->prepare(
                    'INSERT INTO media
                        (path, file_name, mime_type, extension, file_size, checksum,
                         width, height, alt, folder, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )
                ->execute([
                    $row['path'],
                    $row['file_name'],
                    $row['mime_type'],
                    $row['extension'],
                    $row['file_size'],
                    $row['checksum'],
                    $row['width'],
                    $row['height'],
                    $row['alt'],
                    $row['folder'],
                    $row['uploaded_by'],
                ]);
        } catch (PDOException $e) {
            // Diabaikan dengan sengaja.
        }
    }
}
