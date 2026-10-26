<?php
/**
 * Uji alur modul Berita panel admin lewat HTTP sungguhan.
 *
 *   php tools/admin-berita-test.php
 *
 * Yang diuji bukan "halaman terbuka", tetapi perilaku yang bisa salah tanpa
 * terlihat: penjagaan sesi, token CSRF, validasi, slug unik, unggah berkas,
 * dan pelepasan slug setelah berita dihapus.
 *
 * Skrip ini membuat berita contoh, lalu menghapusnya lagi di akhir. Tidak
 * ada data contoh yang tertinggal.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

$ROOT = dirname(__DIR__);
require $ROOT . '/app/Support/Database.php';
require $ROOT . '/app/helpers.php';

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

$BASE = getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8080';
$USERNAME = getenv('TEST_ADMIN_USER') ?: 'admin';
$PASSWORD = getenv('TEST_ADMIN_PASS') ?: 'Admin#AlHuda2026';

$pass = 0;
$fail = 0;
$failures = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $failures;

    if ($ok) {
        $pass++;
        echo "  [OK]   {$label}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
        return;
    }

    $fail++;
    $failures[] = $label . ($detail !== '' ? " — {$detail}" : '');
    echo "  [GAGAL] {$label}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

function section(string $title): void
{
    echo PHP_EOL . str_repeat('-', 70) . PHP_EOL . $title . PHP_EOL . str_repeat('-', 70) . PHP_EOL;
}

/**
 * Klien HTTP dengan cookie jar manual.
 */
final class Client
{
    private string $base;
    private string $jar;

    public function __construct(string $base)
    {
        $this->base = rtrim($base, '/');
        $this->jar = tempnam(sys_get_temp_dir(), 'mtsjar');
    }

    public function __destruct()
    {
        if (is_file($this->jar)) {
            @unlink($this->jar);
        }
    }

    /**
     * @param array<string,string>|null $post
     * @param array<string,string>|null $files path => ['name'=>..,'tmp'=>..]
     * @return array{status:int,body:string,headers:string,location:string}
     */
    public function request(string $path, ?array $post = null, ?array $files = null, ?string $raw = null): array
    {
        $url = $this->base . '/' . ltrim($path, '/');
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'MTS-Admin-Test/1.0',
        ]);

        if ($raw !== null) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $raw);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        } elseif ($files !== null && $files !== []) {
            $fields = $post ?? [];
            foreach ($files as $field => $info) {
                $fields[$field] = new CURLFile($info['tmp'], $info['type'] ?? 'image/jpeg', $info['name']);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } elseif ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return ['status' => 0, 'body' => '', 'headers' => '', 'location' => '', 'error' => $error];
        }

        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $headers = substr((string) $response, 0, $headerSize);
        $body = substr((string) $response, $headerSize);

        $location = '';
        if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
            $location = trim($m[1]);
        }

        return ['status' => $status, 'body' => $body, 'headers' => $headers, 'location' => $location];
    }

    /**
     * Ambil token CSRF dari body HTML.
     */
    public static function csrf(string $html): ?string
    {
        if (preg_match('/name="_csrf"\s+value="([a-f0-9]{64})"/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}

function postIdBySlug(string $slug): ?int
{
    $stmt = Database::connection()->prepare('SELECT id FROM posts WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();

    return $id === false ? null : (int) $id;
}

function countPosts(string $where = '1=1'): int
{
    return (int) Database::connection()->query('SELECT COUNT(*) FROM posts WHERE ' . $where)->fetchColumn();
}

$client = new Client($BASE);
$createdIds = [];
$uploadedPaths = [];

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "UJI MODUL BERITA PANEL ADMIN" . PHP_EOL;
echo "Basis: {$BASE}" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

// ---------------------------------------------------------------------------
section('1. Penjagaan akses tanpa login');

$r = $client->request('admin/berita');
check('GET /admin/berita tanpa sesi dialihkan ke login', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->request('admin/berita/create');
check('GET /admin/berita/create tanpa sesi dialihkan', $r['status'] === 302, 'status ' . $r['status']);

$r = $client->request('admin/berita/1/edit');
check('GET /admin/berita/1/edit tanpa sesi dialihkan', $r['status'] === 302, 'status ' . $r['status']);

$r = $client->request('admin/berita/1/delete');
check('GET delete tanpa sesi tidak menghapus (302 ke login)', $r['status'] === 302, 'status ' . $r['status']);

// ---------------------------------------------------------------------------
section('2. Login admin');

$r = $client->request('admin/login');
check('GET /admin/login 200', $r['status'] === 200, 'status ' . $r['status']);

$token = Client::csrf($r['body']);
check('Token CSRF ada di formulir login', $token !== null);

$r = $client->request('admin/login', ['_csrf' => (string) $token, 'identifier' => $USERNAME, 'password' => 'salah-sengaja']);
check('Login dengan sandi salah ditolak', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->request('admin/login');
$token = Client::csrf($r['body']);
$r = $client->request('admin/login', ['_csrf' => (string) $token, 'identifier' => $USERNAME, 'password' => $PASSWORD]);
check('Login dengan sandi benar dialihkan ke /admin', $r['status'] === 302 && !str_contains($r['location'], 'login'), 'status ' . $r['status'] . ' -> ' . $r['location']);

// ---------------------------------------------------------------------------
section('3. Halaman daftar');

$r = $client->request('admin/berita');
check('GET /admin/berita 200', $r['status'] === 200, 'status ' . $r['status']);
check('Judul halaman "Kelola Berita" tampil', str_contains($r['body'], 'Kelola Berita'));
check('Kolom Kategori ada', str_contains($r['body'], 'Kategori'));
check('Kolom Penulis ada', str_contains($r['body'], 'Penulis'));
check('Tombol Tambah Berita ada', str_contains($r['body'], 'Tambah Berita'));
check('Sidebar menandai Berita aktif', str_contains($r['body'], 'side-link on'));
check('Tidak ada tautan mati "Soon" untuk Berita', !preg_match('/is-locked[^>]*>\s*<span class="mi">article/', $r['body']));

$r = $client->request('admin/berita?q=Contoh');
check('Pencarian menyaring hasil', $r['status'] === 200 && str_contains($r['body'], 'Contoh Berita'));

$r = $client->request('admin/berita?q=zzz-tidak-ada-zzz');
check('Pencarian tanpa hasil menampilkan empty state', str_contains($r['body'], 'Tidak ada berita yang cocok'));

$r = $client->request('admin/berita?status=draft');
check('Filter status draft bekerja', $r['status'] === 200 && str_contains($r['body'], 'Contoh Draf Belum Terbit'));

$r = $client->request('admin/berita?status=published');
check('Filter status terbit menyembunyikan draft', !str_contains($r['body'], 'Contoh Draf Belum Terbit'));

// ---------------------------------------------------------------------------
section('4. Formulir berita baru');

$r = $client->request('admin/berita/create');
check('GET /admin/berita/create 200', $r['status'] === 200, 'status ' . $r['status']);
check('Field Judul ada', str_contains($r['body'], 'name="title"'));
check('Field Slug ada', str_contains($r['body'], 'name="slug"'));
check('Field Ringkasan ada', str_contains($r['body'], 'name="excerpt"'));
check('Field Konten ada', str_contains($r['body'], 'name="content"'));
check('Select Status ada', str_contains($r['body'], 'name="status"'));
check('Select Kategori ada', str_contains($r['body'], 'name="category"'));
check('Field Meta Title ada', str_contains($r['body'], 'name="meta_title"'));
check('Field Meta Description ada', str_contains($r['body'], 'name="meta_description"'));
check('Input berkas cover ada', str_contains($r['body'], 'name="cover"'));
check('Formulir memakai enctype multipart', str_contains($r['body'], 'multipart/form-data'));

$token = Client::csrf($r['body']);

// ---------------------------------------------------------------------------
section('5. Validasi');

$before = countPosts();

$r = $client->request('admin/berita', [
    '_csrf' => (string) $token,
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'status' => 'draft',
    'category' => 'umum',
]);
check('Judul kosong ditolak (422)', $r['status'] === 422, 'status ' . $r['status']);
check('Pesan galat judul tampil', str_contains($r['body'], 'Judul berita wajib diisi'));
check('Pesan galat isi tampil', str_contains($r['body'], 'Isi berita tidak boleh kosong'));
check('Tidak ada baris baru dibuat', countPosts() === $before, 'sebelum ' . $before . ' sesudah ' . countPosts());

$r = $client->request('admin/berita', [
    '_csrf' => 'token-palsu-yang-tidak-cocok',
    'title' => 'Uji CSRF',
    'slug' => 'uji-csrf',
    'content' => '<p>isi</p>',
    'status' => 'draft',
]);
check('Token CSRF salah ditolak (302 ke form)', $r['status'] === 302, 'status ' . $r['status']);
check('Tidak ada baris dibuat dengan CSRF salah', postIdBySlug('uji-csrf') === null);

$r = $client->request('admin/berita/create');
$token = Client::csrf($r['body']);

$r = $client->request('admin/berita', [
    '_csrf' => (string) $token,
    'title' => str_repeat('A', 121),
    'slug' => 'judul-terlalu-panjang',
    'content' => '<p>isi</p>',
    'status' => 'draft',
]);
check('Judul > 120 karakter ditolak (422)', $r['status'] === 422, 'status ' . $r['status']);
check('Pesan menyebut jumlah karakter', str_contains($r['body'], '121 karakter'));
check('Isian tetap ada setelah galat', str_contains($r['body'], str_repeat('A', 121)));

// ---------------------------------------------------------------------------
section('6. Simpan berita baru');

$r = $client->request('admin/berita/create');
$token = Client::csrf($r['body']);

$title = 'Uji Otomatis Berita ' . date('His');
$r = $client->request('admin/berita', [
    '_csrf' => (string) $token,
    'title' => $title,
    'slug' => '',
    'excerpt' => 'Ringkasan uji otomatis.',
    'content' => '<p>Paragraf pertama uji otomatis.</p><p>Paragraf kedua.</p>',
    'status' => 'published',
    'category' => 'kegiatan',
    'meta_title' => 'Meta judul uji',
    'meta_description' => 'Meta deskripsi uji otomatis.',
]);

check('Simpan berita baru dialihkan (302)', $r['status'] === 302, 'status ' . $r['status']);
check('Dialihkan ke halaman sunting', str_contains($r['location'], '/edit'), $r['location']);

$slug = 'uji-otomatis-berita-' . strtolower(date('His'));
$id = postIdBySlug($slug);
check('Baris tersimpan dengan slug otomatis dari judul', $id !== null, 'slug ' . $slug);

if ($id !== null) {
    $createdIds[] = $id;

    $stmt = Database::connection()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    check('Status tersimpan published', ($row['status'] ?? '') === 'published');
    check('Kategori tersimpan kegiatan', ($row['category'] ?? '') === 'kegiatan');
    check('Meta title tersimpan', ($row['meta_title'] ?? '') === 'Meta judul uji');
    check('Meta description tersimpan', ($row['meta_description'] ?? '') === 'Meta deskripsi uji otomatis.');
    check('author_id terisi', (int) ($row['author_id'] ?? 0) > 0, 'author ' . ($row['author_id'] ?? '-'));
    check('public_id terisi UUID', preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', (string) ($row['public_id'] ?? '')) === 1);
    check('published_at terisi otomatis', !empty($row['published_at']), (string) ($row['published_at'] ?? ''));
    check('excerpt tersimpan', ($row['excerpt'] ?? '') === 'Ringkasan uji otomatis.');

    // Slug duplikat harus diberi akhiran, bukan ditolak.
    $r = $client->request('admin/berita/create');
    $token2 = Client::csrf($r['body']);
    $r = $client->request('admin/berita', [
        '_csrf' => (string) $token2,
        'title' => $title,
        'slug' => $slug,
        'content' => '<p>Duplikat slug.</p>',
        'status' => 'draft',
    ]);
    check('Slug duplikat tidak membuat galat 500', $r['status'] === 302, 'status ' . $r['status']);

    $dupId = null;
    $stmt = Database::connection()->prepare("SELECT id, slug FROM posts WHERE slug LIKE ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$slug . '%']);
    $dup = $stmt->fetch();

    if ($dup !== false && (int) $dup['id'] !== $id) {
        $dupId = (int) $dup['id'];
        $createdIds[] = $dupId;
        check('Slug duplikat diberi akhiran -2', (string) $dup['slug'] === $slug . '-2', (string) $dup['slug']);
    } else {
        check('Slug duplikat diberi akhiran -2', false, 'baris duplikat tidak ditemukan');
    }
}

// ---------------------------------------------------------------------------
section('7. Halaman sunting');

if ($id !== null) {
    $r = $client->request('admin/berita/' . $id . '/edit');
    check('GET sunting 200', $r['status'] === 200, 'status ' . $r['status']);
    check('Judul terisi di formulir', str_contains($r['body'], e($title)));
    check('Kategori terpilih kegiatan', (bool) preg_match('/value="kegiatan"\s+selected/', $r['body']));
    check('Status terpilih published', (bool) preg_match('/value="published"\s+selected/', $r['body']));
    check('Peringatan berita tayang tampil', str_contains($r['body'], 'sedang'));

    $r = $client->request('admin/berita/999999/edit');
    check('ID tidak ada dialihkan ke daftar', $r['status'] === 302, 'status ' . $r['status']);
}

// ---------------------------------------------------------------------------
section('8. Ubah berita');

if ($id !== null) {
    $r = $client->request('admin/berita/' . $id . '/edit');
    $token = Client::csrf($r['body']);

    $r = $client->request('admin/berita/' . $id, [
        '_csrf' => (string) $token,
        'title' => $title . ' (diubah)',
        'slug' => $slug,
        'excerpt' => 'Ringkasan setelah diubah.',
        'content' => '<p>Isi setelah diubah.</p>',
        'status' => 'draft',
        'category' => 'prestasi',
        'meta_title' => 'Meta setelah diubah',
        'meta_description' => 'Deskripsi setelah diubah.',
    ]);

    check('Update dialihkan (302)', $r['status'] === 302, 'status ' . $r['status']);

    $stmt = Database::connection()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    check('Judul berubah', ($row['title'] ?? '') === $title . ' (diubah)');
    check('Status berubah jadi draft', ($row['status'] ?? '') === 'draft');
    check('Kategori berubah jadi prestasi', ($row['category'] ?? '') === 'prestasi');
    check('Meta title berubah', ($row['meta_title'] ?? '') === 'Meta setelah diubah');
    check('Isi berubah', str_contains((string) ($row['content'] ?? ''), 'Isi setelah diubah'));
    check('Slug tetap', ($row['slug'] ?? '') === $slug);
}

// ---------------------------------------------------------------------------
section('9. Unggah gambar cover');

if ($id !== null) {
    // Buat PNG 2x2 yang sah tanpa pustaka gambar.
    $pngPath = tempnam(sys_get_temp_dir(), 'mtscover') . '.png';
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAACddGYaAAAAF0lEQVQI12P4//8/AzYEYgBp'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
        . 'DAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwM'
    );
    // PNG minimal yang sah. GD tidak selalu tersedia (ekstensi php_gd bisa mati
    // di XAMPP), jadi dipakai PNG 2x2 yang ditulis langsung sebagai byte.
    if (function_exists('imagecreatetruecolor')) {
        $img = imagecreatetruecolor(64, 36);
        imagefill($img, 0, 0, imagecolorallocate($img, 21, 128, 61));
        imagepng($img, $pngPath);
        imagedestroy($img);
    } else {
        file_put_contents($pngPath, $png);
    }

    // Dimensi sebenarnya dibaca dari berkasnya, bukan ditebak. Kalau GD tidak
    // ada, berkasnya 2x2 — dan uji harus mengikuti kenyataan itu.
    $expectedSize = @getimagesize($pngPath) ?: [0, 0];

    $r = $client->request('admin/berita/' . $id . '/edit');
    $token = Client::csrf($r['body']);

    $r = $client->request('admin/berita/' . $id, [
        '_csrf' => (string) $token,
        'title' => $title . ' (diubah)',
        'slug' => $slug,
        'content' => '<p>Isi setelah diubah.</p>',
        'status' => 'draft',
        'category' => 'prestasi',
    ], [
        'cover' => ['name' => 'foto uji.png', 'tmp' => $pngPath, 'type' => 'image/png'],
    ]);

    check('Unggah cover berhasil (302)', $r['status'] === 302, 'status ' . $r['status']);

    $stmt = Database::connection()->prepare('SELECT cover_path FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $coverPath = (string) $stmt->fetchColumn();

    check('cover_path tersimpan', $coverPath !== '', $coverPath);
    check('Berkas cover ada di disk', $coverPath !== '' && is_file($ROOT . '/public/' . $coverPath), $coverPath);
    check('Cover tersimpan di folder uploads/berita', str_starts_with($coverPath, 'uploads/berita/'), $coverPath);
    check('Nama berkas diacak, bukan nama asli', !str_contains($coverPath, 'foto uji'), basename($coverPath));

    if ($coverPath !== '') {
        $uploadedPaths[] = $coverPath;

        $stmt = Database::connection()->prepare('SELECT * FROM media WHERE path = ? LIMIT 1');
        $stmt->execute([$coverPath]);
        $media = $stmt->fetch();

        check('Baris media tercatat', $media !== false);
        if ($media !== false) {
            check('MIME media image/png', ($media['mime_type'] ?? '') === 'image/png', (string) ($media['mime_type'] ?? ''));
            check(
                'Dimensi media tercatat sesuai berkas',
                (int) ($media['width'] ?? 0) === (int) $expectedSize[0]
                    && (int) ($media['height'] ?? 0) === (int) $expectedSize[1],
                ($media['width'] ?? '?') . 'x' . ($media['height'] ?? '?') . ' vs ' . $expectedSize[0] . 'x' . $expectedSize[1]
            );
            check('Checksum media 64 hex', preg_match('/^[a-f0-9]{64}$/', (string) ($media['checksum'] ?? '')) === 1);
            check('folder media = berita', ($media['folder'] ?? '') === 'berita');
        }

        // Berkas berbahaya harus ditolak.
        $evilPath = tempnam(sys_get_temp_dir(), 'mtsevil') . '.php';
        file_put_contents($evilPath, '<?php echo "pwned";');

        $r = $client->request('admin/berita/' . $id . '/edit');
        $token = Client::csrf($r['body']);
        $r = $client->request('admin/berita/' . $id, [
            '_csrf' => (string) $token,
            'title' => $title . ' (diubah)',
            'slug' => $slug,
            'content' => '<p>Isi setelah diubah.</p>',
            'status' => 'draft',
            'category' => 'prestasi',
        ], [
            'cover' => ['name' => 'shell.php', 'tmp' => $evilPath, 'type' => 'image/png'],
        ]);

        check('Unggah .php ditolak (422)', $r['status'] === 422, 'status ' . $r['status']);
        check('Pesan menolak jenis berkas', str_contains($r['body'], 'tidak diizinkan'));

        // Berkas yang menyamar: ekstensi .png tapi isinya PHP.
        $fakePath = tempnam(sys_get_temp_dir(), 'mtsfake') . '.png';
        file_put_contents($fakePath, '<?php echo "pwned";');

        $r = $client->request('admin/berita/' . $id . '/edit');
        $token = Client::csrf($r['body']);
        $r = $client->request('admin/berita/' . $id, [
            '_csrf' => (string) $token,
            'title' => $title . ' (diubah)',
            'slug' => $slug,
            'content' => '<p>Isi setelah diubah.</p>',
            'status' => 'draft',
            'category' => 'prestasi',
        ], [
            'cover' => ['name' => 'palsu.png', 'tmp' => $fakePath, 'type' => 'image/png'],
        ]);

        check('Berkas .png berisi PHP ditolak (422)', $r['status'] === 422, 'status ' . $r['status']);
        check('Pesan menyebut isi tidak cocok', str_contains($r['body'], 'tidak cocok dengan ekstensinya'));

        @unlink($evilPath);
        @unlink($fakePath);
    }

    @unlink($pngPath);
}

// ---------------------------------------------------------------------------
section('10. Ubah status cepat');

if ($id !== null) {
    $r = $client->request('admin/berita');
    $token = Client::csrf($r['body']);

    $r = $client->request('admin/berita/' . $id . '/status', [
        '_csrf' => (string) $token,
        'status' => 'published',
    ]);

    check('Ubah status dialihkan (302)', $r['status'] === 302, 'status ' . $r['status']);

    $stmt = Database::connection()->prepare('SELECT status, published_at FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    check('Status jadi published', ($row['status'] ?? '') === 'published');
    check('published_at terisi saat terbit', !empty($row['published_at']));

    $r = $client->request('admin/berita');
    $token = Client::csrf($r['body']);
    $r = $client->request('admin/berita/' . $id . '/status', [
        '_csrf' => (string) $token,
        'status' => 'status-ngawur',
    ]);
    check('Status tidak dikenal ditolak (302)', $r['status'] === 302, 'status ' . $r['status']);

    $stmt = Database::connection()->prepare('SELECT status FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    check('Status tidak berubah oleh nilai ngawur', (string) $stmt->fetchColumn() === 'published');
}

// ---------------------------------------------------------------------------
section('11. Berita muncul di website publik');

if ($id !== null) {
    $r = $client->request('berita/' . $slug);
    check('Halaman publik berita 200', $r['status'] === 200, 'status ' . $r['status']);
    check('Judul tampil di halaman publik', str_contains($r['body'], e($title . ' (diubah)')));

    $r = $client->request('berita');
    check('Daftar berita publik memuat berita baru', str_contains($r['body'], e($title . ' (diubah)')));
}

// ---------------------------------------------------------------------------
section('12. Hapus berita dan peleposan slug');

if ($id !== null) {
    $r = $client->request('admin/berita');
    $token = Client::csrf($r['body']);

    $r = $client->request('admin/berita/' . $id . '/delete', ['_csrf' => (string) $token]);
    check('Hapus dialihkan ke daftar (302)', $r['status'] === 302 && str_contains($r['location'], 'admin/berita'), 'status ' . $r['status']);

    $stmt = Database::connection()->prepare('SELECT deleted_at FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $deletedAt = $stmt->fetchColumn();

    check('Baris ditandai deleted_at (hapus lunak)', $deletedAt !== null && $deletedAt !== false, (string) $deletedAt);
    check('Slug dilepas (slug_live NULL)', postIdBySlug($slug) === null);

    $r = $client->request('berita/' . $slug);
    check('Halaman publik berita terhapus jadi 404', $r['status'] === 404, 'status ' . $r['status']);

    // Slug yang baru dilepas harus bisa dipakai lagi.
    $r = $client->request('admin/berita/create');
    $token = Client::csrf($r['body']);
    $r = $client->request('admin/berita', [
        '_csrf' => (string) $token,
        'title' => $title . ' (diubah)',
        'slug' => $slug,
        'content' => '<p>Dipakai ulang.</p>',
        'status' => 'draft',
    ]);

    check('Slug bekas berita terhapus bisa dipakai lagi', $r['status'] === 302, 'status ' . $r['status']);

    $reusedId = postIdBySlug($slug);
    check('Berita baru memakai slug yang sama', $reusedId !== null && $reusedId !== $id, 'id ' . (string) $reusedId);

    if ($reusedId !== null) {
        $createdIds[] = $reusedId;
    }
}

// ---------------------------------------------------------------------------
section('13. Route yang tidak ada');

$r = $client->request('admin/berita/abc/edit');
check('/admin/berita/abc/edit -> 404', $r['status'] === 404, 'status ' . $r['status']);

$r = $client->request('admin/berita/1/ngawur');
check('/admin/berita/1/ngawur -> 404', $r['status'] === 404, 'status ' . $r['status']);

$r = $client->request('admin/modul-belum-ada');
check('/admin/modul-belum-ada -> 404', $r['status'] === 404, 'status ' . $r['status']);

$r = $client->request('admin/berita/1/delete');
check('GET /admin/berita/1/delete tidak menghapus (302)', $r['status'] === 302, 'status ' . $r['status']);

// ---------------------------------------------------------------------------
section('14. Bersihkan data uji');

$pdo = Database::connection();

foreach ($createdIds as $cleanupId) {
    $pdo->prepare('DELETE FROM media WHERE path IN (SELECT cover_path FROM posts WHERE id = ?)')->execute([$cleanupId]);
    $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$cleanupId]);
}

foreach ($uploadedPaths as $path) {
    $absolute = $ROOT . '/public/' . $path;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

$pdo->prepare("DELETE FROM activity_logs WHERE action LIKE 'post.%' AND entity = 'posts'")->execute();

check('Data uji dibersihkan', countPosts("title LIKE 'Uji Otomatis Berita %'") === 0);

// ---------------------------------------------------------------------------
echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "HASIL: {$pass} lulus, {$fail} gagal" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

if ($fail > 0) {
    echo PHP_EOL . 'Yang gagal:' . PHP_EOL;
    foreach ($failures as $failure) {
        echo '  - ' . $failure . PHP_EOL;
    }
    exit(1);
}

echo PHP_EOL . 'Semua uji modul berita lulus.' . PHP_EOL;
exit(0);
