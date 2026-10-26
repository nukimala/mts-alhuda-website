<?php
/**
 * Uji alur modul Pengguna & Role panel admin lewat HTTP sungguhan.
 *
 *   php tools/admin-pengguna-test.php
 *
 * Yang diuji adalah perilaku yang bisa salah tanpa terlihat:
 *   - penjagaan sesi dan token CSRF,
 *   - validasi isian dan email ganda,
 *   - sandi sementara yang hanya tampil sekali,
 *   - akun tidak bisa menonaktifkan atau menghapus dirinya sendiri,
 *   - admin aktif terakhir tidak bisa diturunkan atau dihapus,
 *   - menonaktifkan akun mencabut token API miliknya,
 *   - role tanpa izin tulis tidak melihat tombol tulis dan tidak bisa
 *     memakainya lewat POST langsung.
 *
 * Skrip ini membuat akun contoh lalu menghapusnya lagi di akhir.
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
     * @return array{status:int,body:string,headers:string,location:string}
     */
    public function request(string $path, ?array $post = null): array
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

        if ($post !== null) {
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

    /**
     * Login dan kembalikan respons akhirnya.
     */
    public function login(string $identifier, string $password): array
    {
        $r = $this->request('admin/login');
        $token = self::csrf($r['body']);

        return $this->request('admin/login', [
            '_csrf' => (string) $token,
            'identifier' => $identifier,
            'password' => $password,
        ]);
    }
}

// ---------------------------------------------------------------------------
// Pembantu database
// ---------------------------------------------------------------------------

function db(): PDO
{
    return Database::connection();
}

function userIdByEmail(string $email): ?int
{
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();

    return $id === false ? null : (int) $id;
}

function userRow(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function teacherRow(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM teachers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function activeTokenCount(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM api_tokens WHERE user_id = ? AND revoked_at IS NULL');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function seedApiToken(int $userId): void
{
    $hash = bin2hex(random_bytes(32));

    db()->prepare(
        'INSERT INTO api_tokens (user_id, access_token_hash, refresh_token_hash, device_name,
                                 platform, access_expires_at, refresh_expires_at)
         VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY))'
    )->execute([$userId, $hash, bin2hex(random_bytes(32)), 'Uji Otomatis', 'web']);
}

/** Ambil sandi sementara dari kotak kuning di formulir. */
function tempPassword(string $html): ?string
{
    if (preg_match('/data-temp-value>([^<]+)</', $html, $m)) {
        return trim(html_entity_decode($m[1], ENT_QUOTES));
    }

    return null;
}

$stamp = date('YmdHis');
$createdIds = [];

$emailA = 'uji.pengguna.a.' . $stamp . '@contoh.local';
$emailB = 'uji.pengguna.b.' . $stamp . '@contoh.local';
$emailC = 'uji.pengguna.c.' . $stamp . '@contoh.local';
$emailKepala = 'uji.kepala.' . $stamp . '@contoh.local';

$client = new Client($BASE);

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "UJI MODUL PENGGUNA & ROLE PANEL ADMIN" . PHP_EOL;
echo "Basis: {$BASE}" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

// ===========================================================================
section('1. Penjagaan akses tanpa login');

$r = $client->request('admin/pengguna');
check('GET /admin/pengguna tanpa sesi dialihkan ke login', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna/create');
check('GET /admin/pengguna/create tanpa sesi dialihkan', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna/1/edit');
check('GET /admin/pengguna/1/edit tanpa sesi dialihkan', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna', ['name' => 'Penyusup', 'email' => 'penyusup@contoh.local', 'role' => 'admin']);
check('POST /admin/pengguna tanpa sesi ditolak', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);
check('Akun penyusup tidak terbuat', userIdByEmail('penyusup@contoh.local') === null);

$r = $client->request('admin/pengguna/3/nonaktifkan');
check('GET aksi nonaktifkan tanpa sesi tidak menjalankan apa pun', $r['status'] === 302, 'status ' . $r['status']);

// ===========================================================================
section('2. Login admin');

$r = $client->request('admin/login');
check('GET /admin/login 200', $r['status'] === 200, 'status ' . $r['status']);

$token = Client::csrf($r['body']);
check('Token CSRF ada di formulir login', $token !== null);

$r = $client->request('admin/login', ['_csrf' => (string) $token, 'identifier' => $USERNAME, 'password' => 'salah-sengaja']);
check('Login dengan sandi salah ditolak', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$r = $client->login($USERNAME, $PASSWORD);
check('Login dengan sandi benar dialihkan ke /admin', $r['status'] === 302 && !str_contains($r['location'], 'login'), 'status ' . $r['status'] . ' -> ' . $r['location']);

// ===========================================================================
section('3. Halaman daftar');

$r = $client->request('admin/pengguna');
check('GET /admin/pengguna 200', $r['status'] === 200, 'status ' . $r['status']);
check('Judul "Kelola Pengguna" tampil', str_contains($r['body'], 'Kelola Pengguna'));
check('Sidebar menandai Pengguna aktif', str_contains($r['body'], 'side-link on'));
check('Tautan Pengguna di sidebar tidak lagi terkunci', !preg_match('/is-locked[^>]*>\s*<span class="mi">group</', $r['body']));
check('Tombol Tambah Pengguna ada', str_contains($r['body'], 'Tambah Pengguna'));
check('Chip saringan role ada', str_contains($r['body'], 'role-chips'));
check('Chip "Semua" ada', str_contains($r['body'], 'chip-count'));
check('Chip role Super Admin ada', str_contains($r['body'], 'Super Admin'));
check('Chip role Tim Akreditasi ada', str_contains($r['body'], 'Tim Akreditasi'));
check('Kotak pencarian ada', str_contains($r['body'], 'name="q"'));
check('Saringan status ada', str_contains($r['body'], 'name="status"'));
check('Kartu catatan keamanan tampil untuk admin', str_contains($r['body'], 'Catatan Keamanan Akun'));
check('Kolom Nama &amp; Identitas ada', str_contains($r['body'], 'Nama &amp; Identitas'));
check('Kolom Terakhir Login ada', str_contains($r['body'], 'Terakhir Login'));
check('Tombol "Impor dari Excel" tidak dipasang (belum ada fiturnya)', !str_contains($r['body'], 'Impor dari Excel'));
check('Kotak centang massal tidak dipasang (belum ada aksi massal)', !str_contains($r['body'], 'type="checkbox" name="ids[]"'));

// ===========================================================================
section('4. Saringan dan pencarian');

$r = $client->request('admin/pengguna?q=' . urlencode('Administrator'));
check('Pencarian nama menyaring hasil', $r['status'] === 200 && str_contains($r['body'], 'Administrator Madrasah'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna?q=zzz-tidak-ada-zzz');
check('Pencarian tanpa hasil menampilkan empty state', str_contains($r['body'], 'Tidak ada pengguna yang cocok'));

$r = $client->request('admin/pengguna?role=guru');
check('Saringan role guru bekerja', $r['status'] === 200 && str_contains($r['body'], 'Guru Contoh'));
check('Saringan role guru menyembunyikan operator', !str_contains($r['body'], 'Operator Contoh'));

$r = $client->request('admin/pengguna?role=tidak-ada');
check('Role tak dikenal diabaikan, halaman tetap 200', $r['status'] === 200, 'status ' . $r['status']);

$r = $client->request('admin/pengguna?status=aktif');
check('Saringan status aktif bekerja', $r['status'] === 200 && str_contains($r['body'], 'Administrator Madrasah'));

$r = $client->request('admin/pengguna?halaman=99');
check('Halaman di luar jangkauan tidak error', $r['status'] === 200, 'status ' . $r['status']);

// ===========================================================================
section('5. Formulir akun baru');

$r = $client->request('admin/pengguna/create');
check('GET /admin/pengguna/create 200', $r['status'] === 200, 'status ' . $r['status']);
check('Field Nama Lengkap ada', str_contains($r['body'], 'name="name"'));
check('Field Email ada', str_contains($r['body'], 'name="email"'));
check('Field Nama Pengguna ada', str_contains($r['body'], 'name="username"'));
check('Field Telepon ada', str_contains($r['body'], 'name="phone"'));
check('Select Role ada', str_contains($r['body'], 'name="role"'));
check('Field NIP ada', str_contains($r['body'], 'name="nip"'));
check('Select Jenis Kelamin ada', str_contains($r['body'], 'name="gender"'));
check('Blok NIP ditandai khusus guru', str_contains($r['body'], 'data-guru-only'));
check('Tidak ada kolom sandi di formulir', !preg_match('/name="password"/', $r['body']));
check('Keterangan sandi sementara otomatis ada', str_contains($r['body'], 'Buat kata sandi sementara otomatis'));
check('Role siswa tidak bisa dipilih (dibuat lewat PPDB)', !str_contains($r['body'], 'value="siswa"'));

$token = Client::csrf($r['body']);
check('Token CSRF ada di formulir akun', $token !== null);

$before = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();

// ===========================================================================
section('6. Validasi isian');

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => '',
    'email' => '',
    'username' => '',
    'role' => 'guru',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Nama kosong ditolak', $r['status'] === 422, 'status ' . $r['status']);
check('Pesan "Nama lengkap wajib diisi" muncul', str_contains($r['body'], 'Nama lengkap wajib diisi'));
check('Pesan email wajib muncul', str_contains($r['body'], 'Email wajib diisi'));

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Email Salah',
    'email' => 'bukan-email',
    'username' => '',
    'role' => 'guru',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Format email salah ditolak', $r['status'] === 422 && str_contains($r['body'], 'Format email tidak valid'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Email Ganda',
    'email' => 'admin@mts-alhuda.sch.id',
    'username' => '',
    'role' => 'guru',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Email yang sudah dipakai ditolak', $r['status'] === 422 && str_contains($r['body'], 'sudah dipakai akun lain'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Telepon Salah',
    'email' => $emailC,
    'username' => '',
    'role' => 'guru',
    'phone' => 'telepon-salah',
    'nip' => '',
    'gender' => 'L',
]);
check('Nomor telepon tidak wajar ditolak', $r['status'] === 422 && str_contains($r['body'], 'Nomor telepon hanya boleh angka'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji NIP Salah',
    'email' => $emailC,
    'username' => '',
    'role' => 'guru',
    'phone' => '',
    'nip' => 'NIP-SALAH',
    'gender' => 'L',
]);
check('NIP berisi huruf ditolak', $r['status'] === 422 && str_contains($r['body'], 'NIP hanya boleh angka'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Role Palsu',
    'email' => $emailC,
    'username' => '',
    'role' => 'dewa',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Role tak dikenal ditolak', $r['status'] === 422 && str_contains($r['body'], 'Role tidak dikenal'), 'status ' . $r['status']);

check('Tidak ada akun terbuat dari isian tidak valid', (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === $before);

// ===========================================================================
section('7. CSRF');

$r = $client->request('admin/pengguna', [
    '_csrf' => str_repeat('0', 64),
    'name' => 'Uji CSRF',
    'email' => 'uji.csrf.' . $stamp . '@contoh.local',
    'username' => '',
    'role' => 'guru',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('POST dengan token CSRF salah ditolak', $r['status'] === 302 && str_contains($r['location'], 'create'), 'status ' . $r['status']);
check('Akun dari permintaan CSRF palsu tidak terbuat', userIdByEmail('uji.csrf.' . $stamp . '@contoh.local') === null);

// ===========================================================================
section('8. Membuat akun guru');

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Guru Satu',
    'email' => $emailA,
    'username' => '',
    'role' => 'guru',
    'phone' => '0812 3456 7890',
    'nip' => '19900101 202001 1 001',
    'gender' => 'P',
]);

check('POST akun baru dialihkan ke halaman sunting', $r['status'] === 302 && str_contains($r['location'], '/edit'), 'status ' . $r['status'] . ' -> ' . $r['location']);

$idA = userIdByEmail($emailA);
check('Akun guru terbuat di database', $idA !== null);

if ($idA !== null) {
    $createdIds[] = $idA;
    $row = userRow($idA);

    check('Role tersimpan sebagai guru', ($row['role'] ?? '') === 'guru', (string) ($row['role'] ?? '-'));
    check('Label role tersimpan "Guru"', ($row['role_label'] ?? '') === 'Guru', (string) ($row['role_label'] ?? '-'));
    check('Akun baru wajib ganti sandi', (int) $row['must_change_password'] === 1);
    check('Akun baru langsung aktif', (int) $row['is_active'] === 1);
    check('Username dibuat dari email', ($row['username'] ?? '') !== '' && ($row['username'] ?? '') !== null, (string) ($row['username'] ?? '-'));
    check('Telepon tersimpan', ($row['phone'] ?? '') === '0812 3456 7890', (string) ($row['phone'] ?? '-'));
    check('Sandi tersimpan sebagai hash, bukan teks biasa', strlen((string) $row['password_hash']) > 50 && !str_contains((string) $row['password_hash'], 'Aman'));

    $teacher = teacherRow($idA);
    check('Profil guru dibuat untuk role guru', $teacher !== null);
    check('NIP tersimpan di profil guru', ($teacher['nip'] ?? '') === '19900101 202001 1 001', (string) ($teacher['nip'] ?? '-'));
    check('Jenis kelamin tersimpan', ($teacher['gender'] ?? '') === 'P', (string) ($teacher['gender'] ?? '-'));

    // --- sandi sementara tampil sekali -------------------------------------
    $r = $client->request('admin/pengguna/' . $idA . '/edit');
    check('Halaman sunting terbuka setelah pembuatan', $r['status'] === 200, 'status ' . $r['status']);

    $temp = tempPassword($r['body']);
    check('Kotak sandi sementara tampil sekali', str_contains($r['body'], 'data-temp-password') && $temp !== null, (string) $temp);
    check('Sandi sementara tidak masuk pesan sukses biasa', !preg_match('/notice-success[^>]*>(?:(?!<\/div>).)*' . preg_quote((string) $temp, '/') . '/s', $r['body']));

    $r2 = $client->request('admin/pengguna/' . $idA . '/edit');
    check('Muat ulang halaman: kotak sandi sementara hilang', !str_contains($r2['body'], 'data-temp-password'));

    // --- sandi sementara benar-benar bisa dipakai -------------------------
    if ($temp !== null) {
        $fresh = new Client($BASE);
        $r3 = $fresh->login($emailA, $temp);
        check(
            'Login dengan sandi sementara dialihkan ke ganti-sandi',
            $r3['status'] === 302 && str_contains($r3['location'], 'ganti-sandi'),
            'status ' . $r3['status'] . ' -> ' . $r3['location']
        );

        $bad = new Client($BASE);
        $r4 = $bad->login($emailA, 'sandi-ngawur-123');
        check('Sandi ngawur tetap ditolak', $r4['status'] === 302 && str_contains($r4['location'], 'login'), 'status ' . $r4['status']);
    }
}

// ===========================================================================
section('9. Membuat akun non-guru');

$r = $client->request('admin/pengguna/create');
$token = Client::csrf($r['body']);

$r = $client->request('admin/pengguna', [
    '_csrf' => (string) $token,
    'name' => 'Uji Operator Dua',
    'email' => $emailB,
    'username' => 'uji-operator-dua',
    'role' => 'operator',
    'phone' => '',
    'nip' => '19990101 202101 1 009',
    'gender' => 'L',
]);

$idB = userIdByEmail($emailB);
check('Akun operator terbuat', $idB !== null && $r['status'] === 302, 'status ' . $r['status']);

if ($idB !== null) {
    $createdIds[] = $idB;
    $row = userRow($idB);

    check('Role tersimpan sebagai operator', ($row['role'] ?? '') === 'operator', (string) ($row['role'] ?? '-'));
    check('Label role tersimpan "Operator Madrasah"', ($row['role_label'] ?? '') === 'Operator Madrasah', (string) ($row['role_label'] ?? '-'));
    check('Username manual dipakai apa adanya', ($row['username'] ?? '') === 'uji-operator-dua', (string) ($row['username'] ?? '-'));
    check('NIP diabaikan untuk role non-guru (tidak ada baris guru)', teacherRow($idB) === null);
}

// ===========================================================================
section('10. Menyunting akun');

$r = $client->request('admin/pengguna/' . $idB . '/edit');
check('GET sunting akun 200', $r['status'] === 200, 'status ' . $r['status']);
check('Kartu Akses menampilkan status akun', str_contains($r['body'], 'kv-list'));
check('Nilai email terisi di formulir', str_contains($r['body'], e($emailB)));
check('Tombol Setel Ulang Sandi ada', str_contains($r['body'], 'Setel Ulang Sandi'));

$token = Client::csrf($r['body']);

$r = $client->request('admin/pengguna/' . $idB, [
    '_csrf' => (string) $token,
    'name' => 'Uji Operator Dua (diubah)',
    'email' => $emailB,
    'username' => 'uji-operator-dua',
    'role' => 'operator',
    'phone' => '081200000000',
    'nip' => '',
    'gender' => 'L',
]);
check('Simpan perubahan dialihkan kembali ke formulir', $r['status'] === 302 && str_contains($r['location'], '/edit'), 'status ' . $r['status']);

$row = userRow($idB);
check('Nama terbaru tersimpan', ($row['name'] ?? '') === 'Uji Operator Dua (diubah)', (string) ($row['name'] ?? '-'));
check('Telepon terbaru tersimpan', ($row['phone'] ?? '') === '081200000000', (string) ($row['phone'] ?? '-'));
check('Email tidak berubah', ($row['email'] ?? '') === $emailB);

// email milik akun lain
$r = $client->request('admin/pengguna/' . $idB . '/edit');
$token = Client::csrf($r['body']);
$r = $client->request('admin/pengguna/' . $idB, [
    '_csrf' => (string) $token,
    'name' => 'Uji Operator Dua',
    'email' => 'admin@mts-alhuda.sch.id',
    'username' => '',
    'role' => 'operator',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Menyunting ke email milik akun lain ditolak', $r['status'] === 422 && str_contains($r['body'], 'sudah dipakai akun lain'), 'status ' . $r['status']);
check('Email lama tidak ikut berubah', (string) (userRow($idB)['email'] ?? '') === $emailB);

// ===========================================================================
section('11. Perpindahan role dan profil guru');

$r = $client->request('admin/pengguna/' . $idA . '/edit');
$token = Client::csrf($r['body']);

$r = $client->request('admin/pengguna/' . $idA, [
    '_csrf' => (string) $token,
    'name' => 'Uji Guru Satu',
    'email' => $emailA,
    'username' => (string) (userRow($idA)['username'] ?? ''),
    'role' => 'operator',
    'phone' => '',
    'nip' => '19900101 202001 1 001',
    'gender' => 'P',
]);
check('Perubahan role tersimpan', $r['status'] === 302, 'status ' . $r['status']);
check('Role berubah menjadi operator', (string) (userRow($idA)['role'] ?? '') === 'operator', (string) (userRow($idA)['role'] ?? '-'));
check('Profil guru dilepas saat role bukan guru lagi', teacherRow($idA) === null);

$r = $client->request('admin/pengguna/' . $idA . '/edit');
$token = Client::csrf($r['body']);
$r = $client->request('admin/pengguna/' . $idA, [
    '_csrf' => (string) $token,
    'name' => 'Uji Guru Satu',
    'email' => $emailA,
    'username' => (string) (userRow($idA)['username'] ?? ''),
    'role' => 'guru',
    'phone' => '',
    'nip' => '19900101 202001 1 002',
    'gender' => 'P',
]);
check('Kembali ke guru memulihkan profil guru', teacherRow($idA) !== null);
check('NIP baru tersimpan', (string) (teacherRow($idA)['nip'] ?? '') === '19900101 202001 1 002', (string) (teacherRow($idA)['nip'] ?? '-'));

// ===========================================================================
section('12. Penjagaan akun sendiri');

$me = userIdByEmail('admin@mts-alhuda.sch.id');
check('Akun admin uji ditemukan', $me !== null);

$r = $client->request('admin/pengguna/' . $me . '/edit');
check('Halaman sunting akun sendiri 200', $r['status'] === 200, 'status ' . $r['status']);
check('Peringatan "Ini akun Anda sendiri" tampil', str_contains($r['body'], 'Ini akun Anda sendiri'));

$r = $client->request('admin/pengguna/' . $me . '/nonaktifkan', ['_csrf' => (string) Client::csrf($client->request('admin/pengguna')['body'])]);
check('Menonaktifkan akun sendiri ditolak', $r['status'] === 302, 'status ' . $r['status']);
check('Akun sendiri tetap aktif', (int) (userRow($me)['is_active'] ?? 0) === 1);

$token = Client::csrf($client->request('admin/pengguna')['body']);
$r = $client->request('admin/pengguna/' . $me . '/delete', ['_csrf' => (string) $token]);
check('Menghapus akun sendiri ditolak', $r['status'] === 302, 'status ' . $r['status']);

$selfRow = userRow($me);
check(
    'Akun sendiri masih ada setelah percobaan hapus',
    $selfRow !== null && array_key_exists('deleted_at', $selfRow) && $selfRow['deleted_at'] === null
);

// admin aktif terakhir tidak boleh diturunkan
$r = $client->request('admin/pengguna/' . $me . '/edit');
check('Peringatan "satu-satunya admin aktif" tampil', str_contains($r['body'], 'satu-satunya admin aktif'));

$token = Client::csrf($r['body']);
$r = $client->request('admin/pengguna/' . $me, [
    '_csrf' => (string) $token,
    'name' => (string) (userRow($me)['name'] ?? 'Administrator'),
    'email' => 'admin@mts-alhuda.sch.id',
    'username' => (string) (userRow($me)['username'] ?? 'admin'),
    'role' => 'operator',
    'phone' => '',
    'nip' => '',
    'gender' => 'L',
]);
check('Menurunkan role admin terakhir ditolak', $r['status'] === 422, 'status ' . $r['status']);
check('Pesan penolakan admin terakhir muncul', str_contains($r['body'], 'satu-satunya admin aktif'));
check('Role admin tidak berubah', (string) (userRow($me)['role'] ?? '') === 'admin', (string) (userRow($me)['role'] ?? '-'));

$r = $client->request('admin/pengguna/' . $me . '/nonaktifkan', ['_csrf' => (string) Client::csrf($client->request('admin/pengguna')['body'])]);
check('Menonaktifkan admin terakhir ditolak', $r['status'] === 302 && (int) (userRow($me)['is_active'] ?? 0) === 1);

// ===========================================================================
section('13. Menonaktifkan dan mengaktifkan akun');

seedApiToken($idB);
check('Token API contoh terpasang', activeTokenCount($idB) === 1);

$token = Client::csrf($client->request('admin/pengguna')['body']);
$r = $client->request('admin/pengguna/' . $idB . '/nonaktifkan', ['_csrf' => (string) $token]);
check('POST nonaktifkan dialihkan kembali', $r['status'] === 302, 'status ' . $r['status']);
check('Akun menjadi nonaktif', (int) (userRow($idB)['is_active'] ?? 1) === 0);
check('Token API akun nonaktif dicabut', activeTokenCount($idB) === 0);

$r = $client->request('admin/pengguna');
check('Status Nonaktif tampil di daftar', str_contains($r['body'], 'Nonaktif'));

$blocked = new Client($BASE);
$r = $blocked->login($emailB, 'apa-saja');
check('Akun nonaktif tidak bisa masuk', $r['status'] === 302 && str_contains($r['location'], 'login'), 'status ' . $r['status']);

$token = Client::csrf($r2 = $client->request('admin/pengguna')['body']);
$r = $client->request('admin/pengguna/' . $idB . '/aktifkan', ['_csrf' => (string) $token]);
check('POST aktifkan dialihkan kembali', $r['status'] === 302, 'status ' . $r['status']);
check('Akun kembali aktif', (int) (userRow($idB)['is_active'] ?? 0) === 1);

$r = $client->request('admin/pengguna/' . $idB . '/aktifkan');
check('Aksi aktifkan lewat GET tidak menjalankan apa pun', $r['status'] === 302 && (int) (userRow($idB)['is_active'] ?? 0) === 1, 'status ' . $r['status']);

// ===========================================================================
section('14. Setel ulang sandi');

$token = Client::csrf($client->request('admin/pengguna')['body']);
$r = $client->request('admin/pengguna/' . $idB . '/reset', ['_csrf' => (string) $token]);
check('POST setel ulang sandi dialihkan ke formulir', $r['status'] === 302 && str_contains($r['location'], '/edit'), 'status ' . $r['status'] . ' -> ' . $r['location']);

$r = $client->request('admin/pengguna/' . $idB . '/edit');
$tempReset = tempPassword($r['body']);
check('Sandi sementara hasil setel ulang tampil', $tempReset !== null, (string) $tempReset);
check('Akun wajib ganti sandi setelah setel ulang', (int) (userRow($idB)['must_change_password'] ?? 0) === 1);

$r = $client->request('admin/pengguna/' . $idB . '/edit');
check('Sandi sementara hasil setel ulang hilang setelah muat ulang', !str_contains($r['body'], 'data-temp-password'));

if ($tempReset !== null) {
    $fresh = new Client($BASE);
    $r = $fresh->login($emailB, $tempReset);
    check(
        'Login dengan sandi hasil setel ulang dialihkan ke ganti-sandi',
        $r['status'] === 302 && str_contains($r['location'], 'ganti-sandi'),
        'status ' . $r['status'] . ' -> ' . $r['location']
    );
}

// ===========================================================================
section('15. Buka kunci akun');

db()->prepare('UPDATE users SET failed_login_attempts = 5, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?')
    ->execute([$idB]);

check('Akun dalam keadaan terkunci', (string) (userRow($idB)['locked_until'] ?? '') !== '');

$r = $client->request('admin/pengguna');
check('Status Terkunci tampil di daftar', str_contains($r['body'], 'Terkunci'));

$token = Client::csrf($r['body']);
$r = $client->request('admin/pengguna/' . $idB . '/buka-kunci', ['_csrf' => (string) $token]);
check('POST buka kunci dialihkan kembali', $r['status'] === 302, 'status ' . $r['status']);

$row = userRow($idB);
check('Kunci dibuka (locked_until kosong)', ($row['locked_until'] ?? null) === null);
check('Hitungan percobaan gagal direset', (int) ($row['failed_login_attempts'] ?? 99) === 0);
check('Sandi tidak berubah oleh buka kunci', (int) ($row['must_change_password'] ?? 0) === 1);

// ===========================================================================
section('16. Role tanpa izin tulis (kepala madrasah)');

$hash = password_hash('Kepala#Uji2026', PASSWORD_DEFAULT);
db()->prepare(
    'INSERT INTO users (name, email, username, password_hash, role, role_label, is_active, must_change_password)
     VALUES (?, ?, ?, ?, ?, ?, 1, 0)'
)->execute(['Uji Kepala Madrasah', $emailKepala, 'uji-kepala-' . $stamp, $hash, 'kepala', 'Kepala Madrasah']);

$idKepala = userIdByEmail($emailKepala);
check('Akun kepala contoh terbuat', $idKepala !== null);

if ($idKepala !== null) {
    $createdIds[] = $idKepala;

    $kepala = new Client($BASE);
    $r = $kepala->login($emailKepala, 'Kepala#Uji2026');
    check('Login kepala madrasah berhasil', $r['status'] === 302 && !str_contains($r['location'], 'login'), 'status ' . $r['status']);

    $r = $kepala->request('admin/pengguna');
    check('Kepala bisa melihat daftar pengguna', $r['status'] === 200, 'status ' . $r['status']);
    check('Kepala tidak melihat tombol Tambah Pengguna', !str_contains($r['body'], 'Tambah Pengguna'));
    check('Kepala tidak melihat kolom Aksi', !str_contains($r['body'], '>Aksi<'));
    check('Kepala tidak melihat kartu catatan keamanan', !str_contains($r['body'], 'Catatan Keamanan Akun'));

    $r = $kepala->request('admin/pengguna/create');
    check('Kepala dialihkan dari halaman tambah', $r['status'] === 302 && str_contains($r['location'], 'admin/pengguna'), 'status ' . $r['status'] . ' -> ' . $r['location']);

    $emailX = 'uji.tolak.' . $stamp . '@contoh.local';
    $r = $kepala->request('admin/pengguna', [
        '_csrf' => str_repeat('a', 64),
        'name' => 'Uji Tolak Kepala',
        'email' => $emailX,
        'username' => '',
        'role' => 'admin',
        'phone' => '',
        'nip' => '',
        'gender' => 'L',
    ]);
    check('Kepala tidak bisa membuat akun lewat POST langsung', userIdByEmail($emailX) === null, 'status ' . $r['status']);

    $r = $kepala->request('admin/pengguna/' . $idB . '/nonaktifkan', ['_csrf' => str_repeat('b', 64)]);
    check('Kepala tidak bisa menonaktifkan akun lewat POST langsung', (int) (userRow($idB)['is_active'] ?? 0) === 1, 'status ' . $r['status']);
}

// ===========================================================================
section('17. Halaman yang tidak ada');

$r = $client->request('admin/pengguna/abc/edit');
check('Segmen bukan angka menghasilkan 404', $r['status'] === 404, 'status ' . $r['status']);

$r = $client->request('admin/pengguna/999999/edit');
check('Akun yang tidak ada dialihkan ke daftar', $r['status'] === 302 && str_contains($r['location'], 'admin/pengguna'), 'status ' . $r['status']);

$r = $client->request('admin/pengguna/999999/nonaktifkan', ['_csrf' => (string) Client::csrf($client->request('admin/pengguna')['body'])]);
check('Aksi pada akun yang tidak ada tidak error', $r['status'] === 302, 'status ' . $r['status']);

$r = $client->request('admin/pengguna/3/status');
check('URL status pada modul tanpa status dialihkan, bukan fatal', $r['status'] === 302, 'status ' . $r['status']);

// ===========================================================================
section('18. Menghapus akun');

$token = Client::csrf($client->request('admin/pengguna')['body']);
$r = $client->request('admin/pengguna/' . $idB . '/delete', ['_csrf' => (string) $token]);
check('POST hapus akun dialihkan ke daftar', $r['status'] === 302, 'status ' . $r['status']);

$row = userRow($idB);
check('Akun dihapus lunak (deleted_at terisi)', ($row['deleted_at'] ?? null) !== null);
check('Akun terhapus tidak aktif', (int) ($row['is_active'] ?? 1) === 0);

$r = $client->request('admin/pengguna?q=' . urlencode('Uji Operator Dua'));
// Nama masih muncul di pesan sukses "Akun ... dihapus", jadi yang diperiksa
// adalah baris tabelnya, bukan seluruh halaman.
check('Akun terhapus tidak muncul di daftar', !str_contains($r['body'], 'user-name'));

$r = $client->request('admin/pengguna/' . $idB . '/edit');
check('Sunting akun terhapus dialihkan ke daftar', $r['status'] === 302 && str_contains($r['location'], 'admin/pengguna'), 'status ' . $r['status']);

// ===========================================================================
section('19. Bersih-bersih');

$removed = 0;
foreach (array_filter($createdIds) as $id) {
    db()->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM teachers WHERE user_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    $removed++;
}

check('Akun contoh dihapus dari database', $removed === count(array_filter($createdIds)), $removed . ' akun');
check('Tidak ada akun uji yang tertinggal', userIdByEmail($emailA) === null && userIdByEmail($emailB) === null && userIdByEmail($emailKepala) === null);
check('Admin asli masih utuh', (int) (userRow((int) $me)['is_active'] ?? 0) === 1);

// ===========================================================================
echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "HASIL: {$pass} lulus, {$fail} gagal" . PHP_EOL;

if ($fail > 0) {
    echo PHP_EOL . 'Yang gagal:' . PHP_EOL;
    foreach ($failures as $f) {
        echo '  - ' . $f . PHP_EOL;
    }
}

echo str_repeat('=', 70) . PHP_EOL;
exit($fail === 0 ? 0 : 1);
