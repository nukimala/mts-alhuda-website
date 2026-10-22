<?php
/**
 * Uji end-to-end API v1.
 *
 *   php tools/api-test.php
 *   php tools/api-test.php http://localhost/mts-alhuda-website
 *   php tools/api-test.php http://192.168.100.22/mts-alhuda-website
 *
 * Yang diuji:
 *   1.  health              — server + database hidup
 *   2.  settings publik     — hanya pengaturan is_public yang keluar
 *   3.  /me tanpa token     — 401, bukan HTML
 *   4.  /me token palsu     — 401
 *   5.  login sandi salah   — 401 dengan sisa percobaan
 *   6.  login benar         — dapat access + refresh token
 *   7.  /me dengan token    — 200, data pengguna benar
 *   8.  refresh             — pasangan token baru, token lama mati
 *   9.  logout              — token dicabut, /me jadi 401
 *   10. rate limit          — percobaan beruntun jadi 429
 *   11. kebocoran           — database hanya menyimpan hash, bukan token asli
 *   12. validasi input      — 422/404/405 keluar sebagai JSON
 *   13. konten publik       — berita, pengumuman, agenda, fasilitas, galeri
 *   14. akademik            — jadwal/tugas/materi + pembatasan per peran
 *
 * Skrip ini tidak mengubah data kecuali: last_login, api_tokens, rate_limits,
 * activity_logs, dan penghitung views pada posts. Tidak ada tabel lain yang
 * disentuh.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

require __DIR__ . '/../app/Support/Database.php';
require __DIR__ . '/../app/Support/RateLimiter.php';

date_default_timezone_set('Asia/Jakarta');

$BASE = rtrim($argv[1] ?? 'http://localhost/mts-alhuda-website', '/');

$IDENTIFIER = $argv[2] ?? 'admin';
$PASSWORD = $argv[3] ?? null;

$pass = 0;
$fail = 0;
$notes = [];

function line(string $char = '=', int $width = 74): string
{
    return str_repeat($char, $width);
}

function heading(string $text): void
{
    echo PHP_EOL . line() . PHP_EOL . $text . PHP_EOL . line() . PHP_EOL;
}

/**
 * @return array{status:int, body:string, json:mixed, headers:string}
 */
function http(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);

    $headers = ['Accept: application/json'];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return ['status' => 0, 'body' => '', 'json' => null, 'headers' => 'CURL ERROR: ' . $err];
    }

    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headerText = substr((string) $raw, 0, $headerSize);
    $bodyText = substr((string) $raw, $headerSize);

    return [
        'status'  => $status,
        'body'    => $bodyText,
        'json'    => json_decode($bodyText, true),
        'headers' => $headerText,
    ];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;

    if ($ok) {
        $pass++;
        echo '  [LULUS] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    } else {
        $fail++;
        echo '  [GAGAL] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    }
}

function note(string $text): void
{
    global $notes;
    $notes[] = $text;
}

function isJsonResponse(array $res): bool
{
    return is_array($res['json']);
}

// ---------------------------------------------------------------------------

heading('UJI API v1  —  ' . $BASE);

if ($PASSWORD === null) {
    echo '  Kata sandi untuk ' . $IDENTIFIER . ': ';
    $PASSWORD = trim((string) fgets(STDIN));
}

if ($PASSWORD === '') {
    fwrite(STDERR, "Kata sandi kosong. Batal.\n");
    exit(1);
}

// Uji rate limit di bagian akhir akan mengunci IP ini selama 5 menit.
// Untuk pengujian lokal, bersihkan dulu supaya skrip bisa dijalankan
// berkali-kali tanpa menunggu.
$host = (string) parse_url($BASE, PHP_URL_HOST);
if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
    foreach (['127.0.0.1', '::1', '0.0.0.0'] as $ipUji) {
        RateLimiter::clear('api-login:' . $ipUji);
    }
    echo '  (rate limit lokal dibersihkan)' . PHP_EOL;
}

// --- 1. health -------------------------------------------------------------

heading('1. Health check');

$res = http('GET', $BASE . '/api/v1/health');

check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);
check('Respons JSON', isJsonResponse($res), substr(trim($res['body']), 0, 80));
check(
    'Database terhubung',
    ($res['json']['data']['database'] ?? '') === 'terhubung',
    (string) ($res['json']['data']['database'] ?? '?')
);
check(
    'Content-Type JSON',
    stripos($res['headers'], 'Content-Type: application/json') !== false
);

// --- 2. settings publik ----------------------------------------------------

heading('2. Pengaturan publik');

$res = http('GET', $BASE . '/api/v1/settings/public');
$settings = $res['json']['data']['settings'] ?? [];

check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);
check('Ada isi', is_array($settings) && $settings !== [], count($settings) . ' kunci');

$pdo = Database::connection();

$nonPublic = $pdo->query('SELECT setting_key FROM settings WHERE is_public = 0')->fetchAll(PDO::FETCH_COLUMN);
$bocor = array_values(array_intersect(array_keys((array) $settings), $nonPublic));

check('Tidak membocorkan pengaturan privat', $bocor === [], $bocor === [] ? '' : implode(', ', $bocor));

$harusAda = ['site_name', 'site_full_name', 'contact_email'];
$hilang = array_values(array_diff($harusAda, array_keys((array) $settings)));
check('Kunci penting ada', $hilang === [], $hilang === [] ? '' : 'hilang: ' . implode(', ', $hilang));

// --- 3 & 4. tanpa token ----------------------------------------------------

heading('3. Akses tanpa token');

$res = http('GET', $BASE . '/api/v1/me');

check('HTTP 401', $res['status'] === 401, 'status ' . $res['status']);
check('Respons JSON, bukan HTML', isJsonResponse($res), substr(trim($res['body']), 0, 80));
check(
    'Kode galat UNAUTHORIZED',
    ($res['json']['error']['code'] ?? '') === 'UNAUTHORIZED',
    (string) ($res['json']['error']['code'] ?? '?')
);
check('Tidak ada HTML di badan', stripos($res['body'], '<br') === false && stripos($res['body'], '<b>') === false);

heading('4. Akses dengan token palsu');

$res = http('GET', $BASE . '/api/v1/me', null, str_repeat('a', 43));

check('HTTP 401', $res['status'] === 401, 'status ' . $res['status']);
check('Kode galat UNAUTHORIZED', ($res['json']['error']['code'] ?? '') === 'UNAUTHORIZED');

// --- 5. login salah --------------------------------------------------------

heading('5. Login dengan sandi salah');

$res = http('POST', $BASE . '/api/v1/auth/login', [
    'identifier' => $IDENTIFIER,
    'password'   => $PASSWORD . '-salah',
    'platform'   => 'android',
    'device_name'=> 'Uji Otomatis',
]);

check('HTTP 401', $res['status'] === 401, 'status ' . $res['status']);
check(
    'Kode INVALID_CREDENTIALS',
    ($res['json']['error']['code'] ?? '') === 'INVALID_CREDENTIALS',
    (string) ($res['json']['error']['code'] ?? '?')
);
check(
    'Pesan menyebut sisa percobaan',
    stripos((string) ($res['json']['error']['message'] ?? ''), 'Sisa') !== false,
    (string) ($res['json']['error']['message'] ?? '')
);

// --- 6. login benar --------------------------------------------------------

heading('6. Login benar');

$res = http('POST', $BASE . '/api/v1/auth/login', [
    'identifier'  => $IDENTIFIER,
    'password'    => $PASSWORD,
    'platform'    => 'android',
    'device_name' => 'Uji Otomatis',
]);

$tokens = $res['json']['data']['tokens'] ?? [];
$access = (string) ($tokens['access_token'] ?? '');
$refresh = (string) ($tokens['refresh_token'] ?? '');

check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);
check('Access token diterima', $access !== '', strlen($access) . ' karakter');
check('Refresh token diterima', $refresh !== '', strlen($refresh) . ' karakter');
check('Tipe token Bearer', ($tokens['token_type'] ?? '') === 'Bearer');
check('Access token beda dari refresh', $access !== $refresh && $access !== '' && $refresh !== '');
check('Data pengguna ikut', isset($res['json']['data']['user']['id']), (string) ($res['json']['data']['user']['name'] ?? '?'));

$userId = (int) ($res['json']['data']['user']['id'] ?? 0);

// --- 11a. token tidak tersimpan mentah ------------------------------------

heading('11. Pemeriksaan penyimpanan token');

$stored = $pdo->prepare('SELECT access_token_hash, refresh_token_hash FROM api_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$stored->execute([$userId]);
$row = $stored->fetch();

check('Baris token tersimpan', $row !== false);
check(
    'Access token TIDAK tersimpan mentah',
    $row !== false && (string) $row['access_token_hash'] !== $access
);
check(
    'Yang tersimpan adalah SHA-256 dari access token',
    $row !== false && (string) $row['access_token_hash'] === hash('sha256', $access)
);
check(
    'Yang tersimpan adalah SHA-256 dari refresh token',
    $row !== false && (string) $row['refresh_token_hash'] === hash('sha256', $refresh)
);
check(
    'Panjang hash 64 karakter',
    $row !== false && strlen((string) $row['access_token_hash']) === 64
);

// --- 7. /me dengan token ---------------------------------------------------

heading('7. Profil dengan token sah');

$res = http('GET', $BASE . '/api/v1/me', null, $access);

check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);
check(
    'ID pengguna cocok',
    (int) ($res['json']['data']['user']['id'] ?? 0) === $userId
);
// Catatan: 'must_change_password' mengandung kata 'password' tapi itu nama
// kolom yang memang boleh tampil. Yang tidak boleh: hash dan salt.
$bocorKolom = [];
foreach (['password_hash', 'hash', 'salt', 'access_token', 'refresh_token'] as $kata) {
    if (stripos($res['body'], $kata) !== false) {
        $bocorKolom[] = $kata;
    }
}
check('Tidak ada kolom rahasia di respons', $bocorKolom === [], implode(', ', $bocorKolom));

// last_used_at terisi
$used = $pdo->prepare('SELECT last_used_at FROM api_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$used->execute([$userId]);
$lastUsed = $used->fetchColumn();
check('last_used_at terisi', $lastUsed !== null && $lastUsed !== false, (string) $lastUsed);

// --- 8. refresh ------------------------------------------------------------

heading('8. Refresh token (rotasi)');

$res = http('POST', $BASE . '/api/v1/auth/refresh', ['refresh_token' => $refresh]);

$tokens2 = $res['json']['data']['tokens'] ?? [];
$access2 = (string) ($tokens2['access_token'] ?? '');
$refresh2 = (string) ($tokens2['refresh_token'] ?? '');

check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);
check('Access token baru', $access2 !== '' && $access2 !== $access);
check('Refresh token baru', $refresh2 !== '' && $refresh2 !== $refresh);

$res = http('POST', $BASE . '/api/v1/auth/refresh', ['refresh_token' => $refresh]);
check(
    'Refresh token LAMA ditolak',
    $res['status'] === 401 && ($res['json']['error']['code'] ?? '') === 'INVALID_REFRESH_TOKEN',
    'status ' . $res['status']
);

$res = http('GET', $BASE . '/api/v1/me', null, $access);
check('Access token LAMA ikut dicabut', $res['status'] === 401, 'status ' . $res['status']);

$res = http('GET', $BASE . '/api/v1/me', null, $access2);
check('Access token BARU berlaku', $res['status'] === 200, 'status ' . $res['status']);

// --- 9. logout -------------------------------------------------------------

heading('9. Logout');

$res = http('POST', $BASE . '/api/v1/auth/logout', [], $access2);
check('HTTP 200', $res['status'] === 200, 'status ' . $res['status']);

$res = http('GET', $BASE . '/api/v1/me', null, $access2);
check('Token mati setelah logout', $res['status'] === 401, 'status ' . $res['status']);

$res = http('POST', $BASE . '/api/v1/auth/refresh', ['refresh_token' => $refresh2]);
check(
    'Refresh token ikut mati setelah logout',
    $res['status'] === 401,
    'status ' . $res['status']
);

// --- 12. validasi input ----------------------------------------------------

heading('12. Validasi input');

$res = http('POST', $BASE . '/api/v1/auth/login', ['identifier' => '', 'password' => '']);
check('Body kosong → 422', $res['status'] === 422, 'status ' . $res['status']);
check('Kode VALIDATION_ERROR', ($res['json']['error']['code'] ?? '') === 'VALIDATION_ERROR');

$res = http('GET', $BASE . '/api/v1/tidak-ada');
check('Endpoint tidak dikenal → 404 JSON', $res['status'] === 404 && isJsonResponse($res), 'status ' . $res['status']);

$res = http('DELETE', $BASE . '/api/v1/health');
check('Metode salah → 405', $res['status'] === 405, 'status ' . $res['status']);
check('Header Allow dikirim', stripos($res['headers'], 'Allow:') !== false);

$res = http('GET', $BASE . '/api/v9/health');
check('Versi salah → 404 JSON', $res['status'] === 404 && isJsonResponse($res), 'status ' . $res['status']);

// --- 13. endpoint konten publik -------------------------------------------

heading('13. Endpoint konten publik');

$res = http('GET', $BASE . '/api/v1/posts?per_page=2');
$posts = $res['json']['data']['posts'] ?? [];

check('GET /posts → 200', $res['status'] === 200, 'status ' . $res['status']);
check('Ada berita terbit', is_array($posts) && $posts !== [], count((array) $posts) . ' baris');
check('per_page dihormati', count((array) $posts) <= 2, count((array) $posts) . ' baris');
check(
    'Meta halaman lengkap',
    isset($res['json']['meta']['total'], $res['json']['meta']['total_pages'], $res['json']['meta']['has_next'])
);

$drafSlug = (string) $pdo->query("SELECT slug FROM posts WHERE status = 'draft' LIMIT 1")->fetchColumn();
if ($drafSlug !== '') {
    check('Berita draf tidak ikut terkirim', strpos($res['body'], $drafSlug) === false, $drafSlug);
}

$res = http('GET', $BASE . '/api/v1/posts?per_page=5000');
$banyak = count((array) ($res['json']['data']['posts'] ?? []));
check('per_page dibatasi 100', $banyak <= 100, $banyak . ' baris');

$slugTerbit = (string) ($posts[0]['slug'] ?? '');
if ($slugTerbit !== '') {
    $res = http('GET', $BASE . '/api/v1/posts/' . rawurlencode($slugTerbit));
    check('GET /posts/{slug} → 200', $res['status'] === 200, 'status ' . $res['status']);
    check('Isi lengkap ikut', ($res['json']['data']['post']['content'] ?? '') !== '');
    check(
        'Waktu berformat ISO 8601 dengan offset',
        (bool) preg_match('/\+07:00$/', (string) ($res['json']['data']['post']['published_at'] ?? ''))
    );
}

$res = http('GET', $BASE . '/api/v1/posts/tidak-ada-sama-sekali');
check('Slug tidak ada → 404 JSON', $res['status'] === 404 && isJsonResponse($res), 'status ' . $res['status']);

foreach ([
    'announcements' => 'announcements',
    'agendas'       => 'agendas',
    'facilities'    => 'facilities',
    'galleries'     => 'galleries',
] as $path => $kunci) {
    $res = http('GET', $BASE . '/api/v1/' . $path);
    check(
        'GET /' . $path . ' → 200',
        $res['status'] === 200 && isset($res['json']['data'][$kunci]),
        'status ' . $res['status']
    );
}

$res = http('GET', $BASE . '/api/v1/pages/profil');
check(
    'GET /pages/{slug} → 200',
    $res['status'] === 200 && ($res['json']['data']['page']['title'] ?? '') !== '',
    'status ' . $res['status']
);

// --- 14. endpoint akademik & pembatasan peran ------------------------------

heading('14. Endpoint akademik dan pembatasan peran');

$res = http('GET', $BASE . '/api/v1/schedules');
check('Jadwal tanpa token → 401', $res['status'] === 401, 'status ' . $res['status']);
check('Kode UNAUTHORIZED', ($res['json']['error']['code'] ?? '') === 'UNAUTHORIZED');

$demoPassword = 'Contoh#AlHuda2026';

$guruId = $pdo->query(
    "SELECT t.id FROM users u JOIN teachers t ON t.user_id = u.id WHERE u.username = 'guru' LIMIT 1"
)->fetchColumn();

$siswaRow = $pdo->query(
    "SELECT s.id, s.class_id FROM users u JOIN students s ON s.user_id = u.id WHERE u.username = 'siswa' LIMIT 1"
)->fetch();

$demoLengkap = $guruId !== false && $siswaRow !== false;

if (!$demoLengkap) {
    note('Uji peran dilewati: akun contoh guru/siswa tidak ada (jalankan seed --demo).');
    echo '  [lewat] Uji peran butuh data contoh — jalankan: php tools/migrate.php seed --demo' . PHP_EOL;
} else {
    $loginDemo = static function (string $identifier) use ($BASE, $demoPassword): array {
        return http('POST', $BASE . '/api/v1/auth/login', [
            'identifier'  => $identifier,
            'password'    => $demoPassword,
            'platform'    => 'android',
            'device_name' => 'Uji Peran',
        ]);
    };

    $resGuru = $loginDemo('guru');
    $resSiswa = $loginDemo('siswa');

    $tokenGuru = (string) ($resGuru['json']['data']['tokens']['access_token'] ?? '');
    $tokenSiswa = (string) ($resSiswa['json']['data']['tokens']['access_token'] ?? '');

    check('Login guru contoh berhasil', $tokenGuru !== '', 'status ' . $resGuru['status']);
    check('Login siswa contoh berhasil', $tokenSiswa !== '', 'status ' . $resSiswa['status']);

    if ($tokenGuru !== '') {
        $res = http('GET', $BASE . '/api/v1/schedules', null, $tokenGuru);
        $items = $res['json']['data']['schedules'] ?? [];

        $harusnya = (int) $pdo->query(
            'SELECT COUNT(*)
             FROM schedules s
             JOIN class_subjects cs ON cs.id = s.class_subject_id
             JOIN classes c         ON c.id = cs.class_id
             JOIN academic_years y  ON y.id = s.academic_year_id
             WHERE s.is_active = 1 AND cs.is_active = 1 AND c.is_active = 1 AND y.is_active = 1
               AND s.teacher_id = ' . (int) $guruId
        )->fetchColumn();

        check(
            'Guru hanya menerima jadwalnya sendiri',
            count((array) $items) === $harusnya,
            count((array) $items) . ' dari ' . $harusnya . ' di database'
        );
        check('Scope dilaporkan guru', ($res['json']['meta']['scope'] ?? '') === 'teacher');

        $res = http('GET', $BASE . '/api/v1/assignments', null, $tokenGuru);
        $kodeGuru = $pdo->query(
            'SELECT DISTINCT sub.code
             FROM class_subjects cs
             JOIN subjects sub ON sub.id = cs.subject_id
             WHERE cs.teacher_id = ' . (int) $guruId
        )->fetchAll(PDO::FETCH_COLUMN);

        $semuaMilikGuru = true;
        foreach ((array) ($res['json']['data']['assignments'] ?? []) as $a) {
            if (!in_array($a['subject']['code'] ?? '', $kodeGuru, true)) {
                $semuaMilikGuru = false;
            }
        }

        check('Guru hanya menerima tugas yang dia ampu', $semuaMilikGuru);
    }

    if ($tokenSiswa !== '') {
        $kelasSiswa = (int) $siswaRow['class_id'];

        $res = http('GET', $BASE . '/api/v1/schedules', null, $tokenSiswa);
        $semuaKelasSendiri = true;
        foreach ((array) ($res['json']['data']['schedules'] ?? []) as $s) {
            if ((int) ($s['class']['id'] ?? 0) !== $kelasSiswa) {
                $semuaKelasSendiri = false;
            }
        }

        check('Siswa hanya menerima jadwal kelasnya', $semuaKelasSendiri);
        check('Scope dilaporkan siswa', ($res['json']['meta']['scope'] ?? '') === 'student');

        $res = http('GET', $BASE . '/api/v1/assignments', null, $tokenSiswa);
        $items = (array) ($res['json']['data']['assignments'] ?? []);

        $semuaKelasSendiri = true;
        $adaStatusKumpul = true;
        foreach ($items as $a) {
            if ((int) ($a['class']['id'] ?? 0) !== $kelasSiswa) {
                $semuaKelasSendiri = false;
            }
            if (!array_key_exists('my_submission', $a)) {
                $adaStatusKumpul = false;
            }
        }

        check('Siswa hanya menerima tugas kelasnya', $semuaKelasSendiri);
        check('Setiap tugas menyertakan status pengumpulan', $adaStatusKumpul);

        $res = http('GET', $BASE . '/api/v1/materials', null, $tokenSiswa);
        check(
            'Siswa bisa membaca materi kelasnya',
            $res['status'] === 200 && isset($res['json']['data']['materials']),
            'status ' . $res['status']
        );
    }

    if ($tokenGuru !== '') {
        $res = http('GET', $BASE . '/api/v1/assignments', null, $tokenGuru);
        $publicId = (string) ($res['json']['data']['assignments'][0]['public_id'] ?? '');

        if ($publicId !== '') {
            $res = http('GET', $BASE . '/api/v1/assignments/' . rawurlencode($publicId), null, $tokenGuru);
            check(
                'GET /assignments/{public_id} → 200',
                $res['status'] === 200 && ($res['json']['data']['assignment']['public_id'] ?? '') === $publicId,
                'status ' . $res['status']
            );

            $res = http('GET', $BASE . '/api/v1/assignments/' . rawurlencode($publicId));
            check(
                'Detail tugas tanpa token → 401',
                $res['status'] === 401,
                'status ' . $res['status']
            );
        }
    }
}

// --- 10. rate limit (paling akhir, karena mengunci IP) ---------------------

heading('10. Rate limit login');

$limited = false;
$codes = [];

for ($i = 1; $i <= 13; $i++) {
    $res = http('POST', $BASE . '/api/v1/auth/login', [
        'identifier' => 'tidak-ada-' . $i . '@contoh.invalid',
        'password'   => 'salah-terus',
        'platform'   => 'unknown',
    ]);

    $codes[] = $res['status'];

    if ($res['status'] === 429) {
        $limited = true;
        check('HTTP 429 setelah ' . $i . ' percobaan', true);
        check(
            'Kode TOO_MANY_REQUESTS',
            ($res['json']['error']['code'] ?? '') === 'TOO_MANY_REQUESTS',
            (string) ($res['json']['error']['code'] ?? '?')
        );
        check('Header Retry-After dikirim', stripos($res['headers'], 'Retry-After:') !== false);
        break;
    }
}

check('Rate limit aktif', $limited, 'kode: ' . implode(',', $codes));

// --- Ringkasan -------------------------------------------------------------

heading('RINGKASAN');

echo '  Lulus : ' . $pass . PHP_EOL;
echo '  Gagal : ' . $fail . PHP_EOL;
echo PHP_EOL;

if ($notes !== []) {
    foreach ($notes as $n) {
        echo '  catatan: ' . $n . PHP_EOL;
    }
    echo PHP_EOL;
}

if ($fail > 0) {
    echo '  ADA YANG GAGAL. Jangan serahkan API ke tim mobile sebelum beres.' . PHP_EOL . PHP_EOL;
    exit(1);
}

echo '  SEMUA LULUS. API siap dipakai.' . PHP_EOL . PHP_EOL;
exit(0);
