# Panduan Demo & Menyambungkan UI ke Database

**Proyek:** Sistem Informasi MTs Al-Huda Gondang
**Untuk:** demo tim + rekan yang memegang tampilan (UI)
**Database:** MySQL/MariaDB, `mts_alhuda`, 42 tabel, 14 migrasi
**API online:** `https://mts-alhuda.shulkhimaulana.my.id/api/v1/health`

---

## 0. Ringkasan: siapa mengerjakan apa

| Bagian | Pemilik | Status |
|---|---|---|
| Skema database (42 tabel, migrasi, seeder) | **kamu** | selesai |
| API v1 (auth, konten publik, akademik baca-saja) | **kamu** | selesai & sudah online |
| Panel admin (login, dashboard, ganti sandi) | **kamu** | jalan |
| Tampilan website publik | teman-teman | selesai, **masih statis** |
| Menyambungkan tampilan ke database | teman-teman + kamu | **pekerjaan demo ini** |
| CRUD admin (berita, fasilitas, galeri, menu) | teman-teman | controller & model masih kosong |

> **Temuan penting:** `HomeController`, `BeritaController`, `ProfilController`, dan view publik
> saat ini hanya mengirim `title` lalu menampilkan data contoh yang ditulis langsung di view.
> File `app/Models/*.php` dan `app/Controllers/Admin*Controller.php` masih **0 byte**.
> Jadi demo belum bisa disebut "sistem informasi" sampai minimal daftar berita, detail berita,
> dan satu halaman statis membaca database.

---

## 1. Gambaran arsitektur (yang harus dipahami teman UI)

```
                    ┌──────────────────────────────────────────┐
Browser  ────────►  │ public/index.php (satu-satunya pintu)     │
                    │   → Controller → Model → PDO → MySQL      │
                    │   → View (HTML/CSS yang sudah dibuat)     │
                    └──────────────────────────────────────────┘

                    ┌──────────────────────────────────────────┐
Aplikasi mobile ──► │ /api/v1/*  → ApiRouter → Controller Api   │
/ JavaScript        │   → PDO → MySQL → JSON                    │
                    └──────────────────────────────────────────┘

phpMyAdmin/HeidiSQL ──► MySQL langsung (hanya untuk memeriksa data)
```

Aturan besarnya:

- **Tampilan web tidak boleh berisi query SQL.** Query ada di `app/Models/`.
- **Aplikasi mobile/JS tidak menyambung ke MySQL.** Lewat `/api/v1/*`.
- **Browser tidak pernah tahu password database.** Password hanya di `config/database.local.php`
  di komputer server.
- View hanya menerima `$data` dan mencetak. Tidak ada logika bisnis di view.

---

## 2. Tiga jalur integrasi — pilih sesuai kebutuhan

| Jalur | Cocok untuk | Cara | Risiko |
|---|---|---|---|
| **A. Controller → Model → View** | Website PHP yang sudah ada | Query PDO di model, view diisi `$data` | Rendah — **disarankan** |
| **B. REST API** | Aplikasi Flutter, halaman yang di-render JS, integrasi pihak lain | `fetch()` ke `/api/v1/*` + token | Rendah, tapi butuh token untuk data akademik |
| **C. Klien database langsung** | Mengembangkan & memeriksa data (HeidiSQL/phpMyAdmin) | Akun `db-share.php` | **Jangan** dipakai aplikasi/browser |

Yang **tidak boleh**:

- JavaScript di browser membuka koneksi MySQL (mustahil tanpa membocorkan kredensial).
- Flutter menyambung PDO/MySQL langsung.
- Satu akun database dipakai bersama seluruh tim.
- Memakai `root` untuk pekerjaan sehari-hari.

---

## 3. Jalur A — menyambungkan halaman PHP ke database (paling penting)

Contoh nyata: mengubah halaman **Berita** dari data statis menjadi data dari tabel `posts`.
Pola yang sama dipakai untuk Fasilitas, Galeri, Profil, PPDB, dan Beranda.

### 3.1 Pastikan koneksi database siap

Di komputer masing-masing:

```bash
# 1. Nyalakan MySQL (Laragon: Start All / XAMPP: Start MySQL)
# 2. Cek migrasi
php tools/migrate.php status

# 3. Kalau database belum ada
php tools/setup.php

# 4. Isi data contoh (berita, guru, jadwal, tugas)
php tools/migrate.php seed --demo
```

Kalau port MySQL bukan 3306, buat `config/database.local.php`:

```php
<?php
// config/database.local.php — TIDAK ikut git
return [
    'host'     => '127.0.0.1',
    'port'     => 3307,          // sesuaikan
    'database' => 'mts_alhuda',
    'username' => 'root',
    'password' => '',
];
```

Jangan pernah meng-commit file ini.

### 3.2 Buat model (satu file = satu tabel/domain)

Buat `app/Models/Post.php`:

```php
<?php

class Post
{
    /** Berita terbit untuk beranda/daftar berita. */
    public static function published(int $limit = 9): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, title, slug, excerpt, cover_path, published_at
             FROM posts
             WHERE status = ? AND deleted_at IS NULL
             ORDER BY is_featured DESC, published_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, 'published');
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Satu berita berdasarkan slug; null kalau tidak ada. */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT * FROM posts
             WHERE slug = ? AND status = ? AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$slug, 'published']);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
```

Nama kelas **harus sama dengan nama file** (`Post` → `Post.php`) karena autoloader
memetakan kelas ke folder `app/Models/`.

### 3.3 Ubah controller agar mengirim data

`app/Controllers/BeritaController.php`:

```php
<?php

class BeritaController
{
    public function index(): void
    {
        $data = [
            'title' => 'Berita & Artikel | MTs Al-Huda Gondang',
            'posts' => Post::published(9),
        ];

        require_once __DIR__ . '/../Views/berita/index.php';
    }

    public function detail(string $slug = ''): void
    {
        $post = Post::findBySlug($slug);

        if ($post === null) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        $data = [
            'title' => $post['title'] . ' | MTs Al-Huda Gondang',
            'post'  => $post,
        ];

        require_once __DIR__ . '/../Views/berita/detail.php';
    }
}
```

### 3.4 Ubah view: hapus array contoh, ganti loop

`app/Views/berita/index.php` — buang `$berita = [...]` yang lama, lalu:

```php
<?php if ($data['posts'] === []): ?>
    <p class="text-slate-500">Belum ada berita yang diterbitkan.</p>
<?php endif; ?>

<div class="info-grid">
    <?php foreach ($data['posts'] as $post): ?>
        <article class="info-card">
            <small class="card-date">
                <?= e(date('j F Y', strtotime((string) $post['published_at']))) ?>
            </small>
            <h3><?= e($post['title']) ?></h3>
            <p><?= e($post['excerpt'] ?? '') ?></p>
            <a class="card-link" href="<?= base_url('berita/' . $post['slug']) ?>">
                Baca selengkapnya &rsaquo;
            </a>
        </article>
    <?php endforeach; ?>
</div>
```

Detail berita (`app/Views/berita/detail.php`):

```php
<h1><?= e($data['post']['title']) ?></h1>
<time><?= e(date('j F Y', strtotime((string) $data['post']['published_at']))) ?></time>
<div class="prose"><?= $data['post']['content'] /* HTML dari editor admin, sudah dipercaya */ ?></div>
```

> `content` boleh dicetak mentah **hanya karena** diisi admin lewat panel.
> Semua teks lain yang berasal dari database/input pengguna wajib lewat `e()`.

### 3.5 Pola yang sama untuk halaman lain

| Halaman | Model | Query inti | Filter wajib |
|---|---|---|---|
| Beranda | `Post`, `Agenda`, `Announcement` | berita unggulan + agenda mendatang | `status='published'`, `deleted_at IS NULL` |
| Berita | `Post` | daftar + detail per slug | `status='published'` |
| Profil / Akademik / PPDB | `Page` | satu baris per slug | `status='published'`, `deleted_at IS NULL` |
| Fasilitas | `Facility` | daftar fasilitas | `is_active=1`, `deleted_at IS NULL` |
| Galeri | `Gallery` | daftar foto per album | `is_active=1`, `deleted_at IS NULL` |
| Kontak | `Setting` + `ContactMessage` | kontak publik; simpan pesan | `is_public=1` |
| Menu/navbar | `Menu` | menu `utama`/`footer`/`mobile` | `is_active=1` |
| PPDB dokumen | `Ppdb` | daftar dokumen unduhan | `is_active=1` |
| Panel admin | `Admin`/per-domain | hitung statistik, CRUD | ikuti kolom di migrasi |

Kolom yang sering dipakai ada di `database/migrations/004_cms_content.sql`,
`005_cms_support.sql`, `006_academic_core.sql`, dan `007_academic_learning.sql`.

---

## 4. Jalur B — lewat REST API (mobile & JS)

Basis: `https://mts-alhuda.shulkhimaulana.my.id/api/v1`
(di laptop: `http://localhost/mts-alhuda-website/api/v1`)

### 4.1 Endpoint yang tersedia

| Metode | Endpoint | Token | Keterangan |
|---|---|---|---|
| GET | `/health` | — | status server + database |
| GET | `/settings/public` | — | hanya setting `is_public=1` |
| GET | `/posts` | — | berita terbit; `?q=&featured=&page=&per_page=` |
| GET | `/posts/{slug}` | — | detail berita (menambah `views`) |
| GET | `/announcements` | — | pengumuman; sasaran ikut peran token, `?page=&per_page=` |
| GET | `/agendas` | — | agenda; `?past=1` untuk riwayat |
| GET | `/facilities` | — | fasilitas aktif, `?page=&per_page=` |
| GET | `/galleries` | — | galeri; `?album=` untuk satu kegiatan |
| GET | `/pages/{slug}` | — | halaman statis (mis. `profil`) |
| POST | `/auth/login` | — | dapat `data.tokens.access_token` + `refresh_token` |
| POST | `/auth/refresh` | refresh | rotasi token |
| POST | `/auth/logout` | access | cabut token |
| GET | `/me` | access | profil pengguna |
| GET | `/schedules` | access | jadwal sesuai peran; `?class_id=` untuk staf |
| GET | `/assignments` | access | tugas sesuai peran; `?class_id=&open=1` |
| GET | `/assignments/{public_id}` | access | detail tugas (UUID `public_id`) |
| GET | `/materials` | access | materi sesuai peran; `?class_id=` |

Semua daftar memakai `?page=&per_page=` (maksimal 100) dan mengembalikan `meta`:
`page`, `per_page`, `total`, `total_pages`, `has_next`.

### 4.2 Alur login

```bash
# 1. Login
curl -X POST https://mts-alhuda.shulkhimaulana.my.id/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"guru","password":"SandiAnda"}'

# Respons
# {
#   "success": true,
#   "data": {
#     "tokens": {
#       "access_token": "...",
#       "refresh_token": "...",
#       "token_type": "Bearer",
#       "access_expires_at": "2026-10-29 08:20:00",
#       "refresh_expires_at": "2026-11-28 07:50:00"
#     },
#     "user": { "id": 3, "username": "guru", "role": "guru", ... }
#   }
# }

# 2. Pakai token
curl https://mts-alhuda.shulkhimaulana.my.id/api/v1/schedules \
  -H "Authorization: Bearer <access_token>"

# 3. Sebelum 30 menit, tukar refresh token
curl -X POST https://mts-alhuda.shulkhimaulana.my.id/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"<refresh_token>"}'
```

Aturan token:

- Access token **30 menit**, refresh token **30 hari** dan **selalu berotasi**.
- Token lama langsung mati setelah refresh.
- Database hanya menyimpan **hash SHA-256**, token asli tidak pernah disimpan.
- Login dibatasi **10 percobaan / 5 menit / IP** (`rate_limits`).
- Akun terkunci sementara setelah 5 sandi salah.

### 4.3 Contoh di JavaScript (halaman yang butuh data dinamis)

```js
const API = 'https://mts-alhuda.shulkhimaulana.my.id/api/v1';

async function loadPosts() {
  const res = await fetch(`${API}/posts?per_page=6`, {
    headers: { Accept: 'application/json' },
  });
  const json = await res.json();

  if (!json.success) {
    console.error(json.error.code, json.error.message);
    return;
  }

  document.querySelector('#daftar-berita').innerHTML = json.data.posts
    .map((p) => `
      <article class="info-card">
        <h3>${escapeHtml(p.title)}</h3>
        <p>${escapeHtml(p.excerpt ?? '')}</p>
        <a href="/berita/${encodeURIComponent(p.slug)}">Baca selengkapnya</a>
      </article>`)
    .join('');
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}
```

Bentuk respons seragam:

```json
{ "success": true,  "data": { ... }, "meta": { ... } }
{ "success": false, "error": { "code": "NOT_FOUND", "message": "..." } }
```

Kode galat yang perlu ditangani: `VALIDATION_ERROR` (422), `UNAUTHORIZED` (401),
`FORBIDDEN` (403), `NOT_FOUND` (404), `METHOD_NOT_ALLOWED` (405),
`RATE_LIMITED` (429), `SERVER_ERROR` (500).

---

## 5. Jalur C — teman menyambung ke database kamu (LAN)

Gunakan **hanya saat pengembangan**, bukan dari aplikasi.

### 5.1 Buat akun untuk setiap orang

Di komputer pemilik database:

```bash
php tools/db-share.php info
php tools/db-share.php create --user=budi --role=developer
php tools/db-share.php create --user=sari --role=readonly
php tools/db-share.php list
php tools/db-share.php test --user=budi
```

| Peran | Hak | Untuk |
|---|---|---|
| `readonly` | `SELECT` | melihat data, demo, pembimbing |
| `app` | `SELECT INSERT UPDATE DELETE` | aplikasi produksi |
| `developer` | + `CREATE ALTER DROP INDEX` | yang mengubah skema |
| `admin` | semua pada satu database | operator database |

Tool ini **tidak pernah** memberi hak global atau `GRANT OPTION`.

### 5.2 Yang diserahkan ke teman

```
Host     : 192.168.100.22   (hasil `db-share.php info`, bukan 127.0.0.1)
Port     : 3306 atau 3307   (lihat info di komputer server)
Database : mts_alhuda
User     : budi
Password : (yang dibuat tool)
```

Mereka mengisi itu di HeidiSQL/phpMyAdmin/TablePlus, **bukan** di kode aplikasi.

### 5.3 Kalau tidak bisa tersambung

```powershell
Get-Process mysqld -ErrorAction SilentlyContinue
Get-NetTCPConnection -LocalPort 3306 -State Listen
Get-NetFirewallRule -DisplayName "*mysql*" | Select DisplayName,Enabled,Direction,Action
Select-String -Path C:\xampp\mysql\bin\my.ini -Pattern "bind-address"
```

Kalau `Get-NetTCPConnection` hanya menunjukkan `127.0.0.1`, ubah `bind-address=0.0.0.0`
di `my.ini` lalu restart MySQL. Ini membuka database ke LAN — hanya lakukan saat demo.

---

## 6. Aturan wajib saat menyentuh database

1. **Prepared statement, tanpa kecuali.** Jangan menyambung string SQL dengan input.
   ```php
   // BENAR
   $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ?');
   $stmt->execute([$slug]);

   // SALAH — SQL injection
   $pdo->query("SELECT * FROM posts WHERE slug = '$slug'");
   ```
2. **Query di model, bukan di view.** View hanya mencetak `$data`.
3. **Selalu hormati soft delete** untuk `users`, `posts`, `pages`, `galleries`, `facilities`,
   `evidence`: tambahkan `deleted_at IS NULL`.
4. **Hormati status.** Konten publik memakai `status='published'` (berita/halaman/pengumuman)
   atau `is_active=1` (fasilitas/galeri/menu). Tanpa filter ini, draft ikut tampil.
5. **Escape semua output** dengan `e()`.
6. **Jangan mengubah migrasi yang sudah jalan.** Perubahan skema = file migrasi baru
   (`015_...sql`) dengan bagian `-- @UP` dan `-- @DOWN`.
7. **Jangan memakai `root`** untuk pekerjaan tim; buat akun lewat `db-share.php`.
8. **Jangan commit** `config/database.local.php`, dump produksi, atau kredensial.
9. **Jangan simpan token mentah.** Token API sudah otomatis di-hash.
10. **Pagination maksimal 100.** Jangan menarik seluruh tabel ke halaman publik.

---

## 7. Alur demo (durasi 12–15 menit)

### 7.1 Persiapan H-1 (wajib, jangan dadakan)

```bash
# 1. Nyalakan MySQL + Apache
# 2. Pastikan skema dan data contoh ada
php tools/migrate.php status
php tools/migrate.php seed --demo

# 3. Uji integritas
php tools/schema-test.php       # 67 uji
php tools/db-check.php          # 30 pemeriksaan

# 4. Uji API end-to-end
php tools/api-test.php http://localhost/mts-alhuda-website admin "SandiAdminAnda"
#    Catatan: skrip ini membaca database lokal untuk membandingkan hasil API.
#    Jalankan terhadap API lokal yang memakai database yang sama.
#    Untuk API online, uji manual dengan curl/PowerShell (bagian 10).

# 5. Bersihkan sisa uji (rate limit & token)
mysql -u root -e "USE mts_alhuda; DELETE FROM rate_limits; DELETE FROM api_tokens;"

# 6. Backup sebelum demo
mysqldump -u root mts_alhuda > backup/mts_alhuda_sebelum_demo.sql
```

Checklist barang:

- [ ] Database lokal berisi data contoh (3 berita, 3 kelas, 2 jadwal, 2 tugas, 1 materi)
- [ ] Akun `admin` bisa login dan **sudah melewati** halaman ganti sandi
- [ ] Akun `guru` dan `siswa` untuk demo pembatasan peran
- [ ] Halaman berita & profil sudah membaca database (**minimal 3 halaman**)
- [ ] Tab browser: website, admin, phpMyAdmin/HeidiSQL, dokumentasi API
- [ ] Postman/curl untuk `/health`, `/posts`, `/auth/login`, `/schedules`
- [ ] Video/screenshot cadangan kalau internet atau laptop bermasalah
- [ ] Dump database di folder `backup/` untuk restore cepat

> Demo **sebaiknya lokal**, bukan online. Online dipakai sebagai bukti bahwa sistem
> sudah ter-deploy, tetapi jangan bergantung pada koneksi internet saat presentasi.

### 7.2 Susunan demo

| Waktu | Adegan | Yang ditunjukkan | Kalimat kunci |
|---|---|---|---|
| 0:00–1:30 | Pembuka | Arsitektur: UI → controller → model → MySQL; mobile → API | "Tampilan dan data dipisah. UI tidak menyentuh database langsung." |
| 1:30–4:00 | Website dari database | Beranda (berita terbaru), daftar berita, detail berita, profil | "Isi ini bukan hardcode. Saya tunjukkan sumbernya." |
| 4:00–6:30 | **Bukti database** | Buka HeidiSQL/phpMyAdmin di samping browser, tunjukkan baris `posts` yang sama | "Satu baris di tabel ini = satu kartu berita di halaman." |
| 6:30–9:30 | Admin mengubah data | Login admin → dashboard (angka nyata) → ubah judul berita → refresh website | "Perubahan langsung terlihat tanpa edit kode." |
| 9:30–11:30 | API untuk mobile | `/health`, `/posts`, login guru, `/schedules`; login siswa → jadwal berbeda | "Aplikasi mobile memakai API yang sama; hak akses dibatasi server." |
| 11:30–13:00 | Keamanan & kualitas | Password di-hash, token di-hash, rate limit, validasi 422, 405 | "Bukan hanya tampilan — datanya dijaga." |
| 13:00–15:00 | Penutup & tanya jawab | Peta 42 tabel + status pekerjaan berikutnya | "Yang tersisa: CRUD admin dan halaman lain." |

### 7.3 Skenario cadangan (kalau UI belum tersambung)

Urutan demo tetap jalan tanpa berbohong:

1. Tampilkan UI sebagai **mockup** (jelas dikatakan "tampilan final").
2. Demo **database**: struktur 42 tabel, relasi, data contoh, constraint (UNIQUE, FK, CHECK).
3. Demo **API online** dengan curl/Postman.
4. Demo **panel admin** (dashboard sudah membaca database).
5. Tutup dengan rencana integrasi: 3 halaman prioritas, estimasi, penanggung jawab.

Jangan menampilkan halaman statis sebagai "sudah dari database". Kalau ditanya, jawab jujur:
"Tampilan selesai; integrasi data sedang dikerjakan, dan API-nya sudah hidup."

### 7.4 Pertanyaan yang kemungkinan muncul

| Pertanyaan | Jawaban singkat |
|---|---|
| Kenapa tidak pakai Firebase? | Tanpa biaya bulanan, data milik madrasah, tidak bergantung pihak ketiga. |
| Apakah mobile menyambung MySQL? | Tidak. Lewat REST API + Bearer token. |
| Bagaimana kalau data salah? | Constraint database + validasi server + audit log. |
| Apakah bisa dipakai bersamaan? | Ya, InnoDB + transaksi; UNIQUE mencegah data ganda. |
| Kenapa 42 tabel? | Dipisah per domain: identitas, CMS, akademik, presensi, notifikasi, akreditasi. |
| Apakah sudah online? | Ya: `https://mts-alhuda.shulkhimaulana.my.id` (API v1 aktif). |

---

## 8. Akun & data demo

| Akun | Username | Sandi | Catatan |
|---|---|---|---|
| Admin | `admin` | sesuai saat `setup.php` | wajib ganti sandi saat login pertama |
| Operator | `operator` | `Contoh#AlHuda2026` | `must_change_password = 1` |
| Guru | `guru` | `Contoh#AlHuda2026` | punya jadwal & tugas sendiri |
| Siswa | `siswa` | `Contoh#AlHuda2026` | hanya kelasnya |

Data contoh (`seed --demo`):

- 4 pengguna (admin, operator, guru, siswa)
- 3 berita, 2 halaman (`profil`, `akademik`), 6 fasilitas, 8 menu
- 13 mata pelajaran, 3 kelas, 1 tahun ajaran, 3 kelas-mapel
- 2 jadwal, 2 tugas, 1 materi
- 1 pengumuman, 1 agenda, kerangka akreditasi

Perintah bantuan:

```bash
php tools/migrate.php status
php tools/migrate.php fresh --force     # HANYA di lokal — menghapus data
php tools/migrate.php seed --demo
php tools/schema-test.php
php tools/db-check.php --verbose
php tools/api-test.php http://localhost/mts-alhuda-website admin "SandiAdminAnda"
```

---

## 9. Troubleshooting cepat

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Tidak ada server MySQL/MariaDB di port ...` | MySQL belum jalan | Start MySQL (Laragon/XAMPP) |
| `Access denied for user 'root'` | root punya sandi | isi `password` di `config/database.local.php` |
| `Unknown database 'mts_alhuda'` | belum setup | `php tools/setup.php` |
| Semua halaman 404 | `mod_rewrite` mati | aktifkan di `httpd.conf` |
| Halaman tanpa CSS | folder bukan di `htdocs`/`www` | pindahkan atau sesuaikan `base_url` |
| `Class "Database" not found` | membuka `public/index.php` langsung | akses lewat Apache |
| Data tidak muncul padahal ada di DB | filter `status`/`deleted_at`/`is_active` | periksa WHERE |
| `403` di API padahal login | peran tidak berhak atas data itu | uji dengan akun yang sesuai |
| `429` saat login | rate limit 10/5 menit | tunggu 5 menit atau bersihkan `rate_limits` |
| Akun terkunci | 5 sandi salah | reset `failed_login_attempts` & `locked_until` |
| API mengembalikan HTML | request ke URL salah (bukan `/api/v1`) | cek path & HTTPS |

---

## 10. Hasil uji API online (29 Oktober 2026)

Diuji langsung ke `https://mts-alhuda.shulkhimaulana.my.id`:

| Uji | Hasil |
|---|---|
| `GET /api/v1/health` | 200 — database `terhubung`, timezone `Asia/Jakarta` |
| `GET /api/v1/settings/public` | 200 — hanya setting publik |
| `GET /api/v1/posts`, `/announcements`, `/agendas`, `/facilities`, `/galleries` | 200 |
| `GET /api/v1/posts/{slug}`, `/pages/profil` | 200 |
| `GET /api/v1/me` tanpa token | 401 JSON |
| `GET /api/v1/schedules`, `/assignments`, `/materials` tanpa token | 401 JSON |
| Versi/endpoint salah | 400/404 JSON |
| Metode salah | 405 + header `Allow` |
| Validasi login kosong | 422 + detail field |
| CORS preflight | 204, `Access-Control-Allow-Origin: *` |
| `per_page=1000` | dibatasi menjadi 100 |
| HTTP → HTTPS | 301 redirect |
| Header keamanan | `X-Content-Type-Options: nosniff`, `Cache-Control: no-store` |

Catatan hasil uji:

- `/api/v1/pages/ppdb` → 404. Perlu dicek apakah halaman PPDB memang disimpan di tabel `pages`
  atau hanya view.
- `health` online melaporkan `migrasi: 15`, sedangkan repo lokal punya 14 file migrasi.
  Sinkronkan sebelum demo agar tidak ada pertanyaan "kenapa beda".
- Uji login dengan sandi salah pernah dilakukan sekali; sisa 4 percobaan sebelum akun terkunci.

---

## 11. Rencana kerja menuju demo (usul)

| Prioritas | Pekerjaan | Pemilik | Estimasi |
|---|---|---|---|
| 1 | Model + controller berita (daftar & detail) | teman UI + kamu review | 1–2 jam |
| 2 | Halaman profil & akademik dari tabel `pages` | teman UI | 1 jam |
| 3 | Fasilitas & galeri dari database | teman UI | 1–2 jam |
| 4 | Menu/navbar dari tabel `menus` | teman UI | 1 jam |
| 5 | CRUD admin berita (create/edit/hapus) | teman UI + kamu | 3–4 jam |
| 6 | Uji `schema-test`, `db-check`, `api-test` | kamu | 30 menit |
| 7 | Latihan demo 2× + rekaman cadangan | seluruh tim | 1 jam |

Definisi selesai untuk integrasi:

- Tidak ada array data contoh di view.
- Tidak ada query di view.
- Halaman menampilkan data yang benar-benar ada di tabel.
- Mengubah baris di database mengubah halaman setelah refresh.
- Data draft/tidak aktif/terhapus tidak muncul.
- Semua output di-escape.

---

## 12. Lampiran: 42 tabel per domain

| Domain | Tabel |
|---|---|
| Sistem | `schema_migrations`, `schema_seeders` |
| Fondasi | `settings` |
| Identitas | `users`, `teachers`, `students` |
| Keamanan | `api_tokens`, `password_resets`, `activity_logs`, `rate_limits` |
| CMS konten | `posts`, `pages`, `galleries`, `facilities`, `announcements`, `agendas` |
| CMS pendukung | `menus`, `contact_messages`, `ppdb_documents`, `media` |
| Akademik inti | `academic_years`, `classes`, `subjects`, `class_subjects` |
| Pembelajaran | `schedules`, `materials`, `assignments`, `submissions` |
| Presensi | `teacher_attendances` |
| Notifikasi | `notifications` |
| Akreditasi struktur | `accreditation_periods`, `accreditation_components`, `accreditation_butirs`, `accreditation_indicators` |
| Akreditasi bukti | `evidence`, `evidence_butir`, `dka_entries`, `indicator_checks` |
| Akreditasi dokumen | `main_documents`, `tasks`, `accreditation_results`, `follow_ups` |

Dokumen pendukung:

- `database/README.md` — konvensi, perintah, keputusan skema
- `blueprint-madrasahku/12-dokumentasi-api.md` — contoh `curl` lengkap
- `README.md` — setup dari nol
- `DESIGN.md` — spesifikasi tampilan

---

**Prinsip penutup:** tampilan boleh berubah kapan saja; **database adalah sumber kebenaran**.
Selama UI membaca lewat controller/model atau API, penggantian desain tidak menyentuh data.
