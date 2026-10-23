# 13 — Panduan Eksekusi Tim

Dokumen ini untuk anggota tim yang melanjutkan pekerjaan: apa yang **sudah selesai**,
apa yang **belum**, dan bagaimana mengerjakannya supaya semua orang ikut paham —
bukan sekadar menyalin kode.

---

## 1. Kondisi awal: apa yang sudah jadi

| Bagian | Status | Keterangan |
|---|---|---|
| Skema database (42 tabel, 14 migrasi, seeder) | **Selesai** | Jangan didesain ulang |
| Autentikasi web (session + CSRF) | **Selesai** | `app/Support/Auth.php`, `Csrf.php` |
| Dashboard admin + ganti sandi | **Selesai** | `AdminController`, `AuthController` |
| API v1 (konten, akademik, auth token) | **Selesai** | 80 uji otomatis lulus |
| Website publik | **Masih statis** | Controller hanya `require` view, belum baca DB |
| CRUD panel admin | **Kosong** | Berkas 0 byte sudah disiapkan sebagai kerangka |
| Aplikasi Flutter | **Belum ada** | Tim mobile, pakai API v1 |

**Konsekuensi penting:** database bukan lagi bahan diskusi. Kalau butuh kolom/tabel
baru, buat migrasi baru bernomor lanjut (`015_...sql`) dan minta review — jangan
mengedit migrasi yang sudah pernah dijalankan di mesin lain.

---

## 2. Prinsip pembagian kerja

**Jangan** membagi berdasarkan lapisan ("kamu semua Model, kamu semua View").
Cara itu membuat tidak ada yang pernah melihat alur lengkap, dan bagian paling
sulit (menghubungkan data ke tampilan) tidak dipelajari siapa pun.

**Pakai modul vertikal:** satu orang memiliki satu modul dari query sampai tampilan.

```
Database  ->  Model  ->  Controller  ->  View  ->  Browser
   (sudah)     (isi)      (isi)         (isi)
```

Setiap orang menyelesaikan minimal **satu modul sampai jalan di browser** sebelum
mengambil modul berikutnya.

---

## 3. Tahap 0 — Onboarding (semua orang, sekali saja, ± 1 hari)

### 3.1 Siapkan proyek

```bash
# Laragon
cd C:\laragon\www
git clone <url-repo> mts-alhuda-website
cd mts-alhuda-website
```

### 3.2 Nyalakan Apache + MySQL

Laragon: tombol **Start All**. Pastikan port **80** dan **3306** bebas.

### 3.3 Satu perintah

```bash
# Laragon
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe tools\setup.php

# atau, kalau `php` sudah ada di PATH
php tools\setup.php
```

Perintah ini membuat database, menjalankan 14 migrasi, mengisi data contoh, dan
membuat akun admin.

### 3.4 Buka tiga alamat

```
Website      http://localhost/mts-alhuda-website/
Panel admin  http://localhost/mts-alhuda-website/admin/login
API health   http://localhost/mts-alhuda-website/api/v1/health
```

Kalau `health` membalas `"migrasi":14`, lingkungan sudah benar.

### 3.5 Jalankan tiga skrip uji (baseline)

```bash
php tools\schema-test.php    # harus 67/67
php tools\db-check.php       # harus 30/30
php tools\api-test.php       # harus 80/80
```

Kalau ada yang gagal **sebelum** menulis kode, itu masalah lingkungan — perbaiki
dulu, jangan lanjut. Kalau gagal **setelah** menulis kode, itu regresi dari
perubahan sendiri.

### 3.6 Baca lima berkas ini

1. `README.md` — cara menjalankan
2. `blueprint-madrasahku/01-arsitektur.md` — pembagian folder
3. `blueprint-madrasahku/06-erd.md` — relasi tabel
4. `blueprint-madrasahku/12-dokumentasi-api.md` — kontrak untuk Flutter
5. `DESIGN.md` — warna, tipografi, komponen

### 3.7 Latihan membaca data (30 menit)

Buka database lewat HeidiSQL/TablePlus/phpMyAdmin, lalu jalankan manual:

```sql
SELECT id, title, slug, status, published_at
FROM posts
ORDER BY published_at DESC;

SELECT COUNT(*) FROM users WHERE role = 'guru' AND deleted_at IS NULL;
```

Tujuannya: sebelum menulis PHP, semua orang sudah melihat bentuk data aslinya.

---

## 4. Tahap 1 — Modul contoh bersama: Berita (baca saja, ± 2 hari)

Satu modul dikerjakan **bersama** (live coding / diskusi), semua mengikuti.
Setelah ini barulah modul lain dibagi. Tujuannya: semua orang melihat satu
contoh lengkap yang benar, lalu meniru polanya — bukan menebak.

### 4.1 Model — `app/Models/Berita.php`

Berkas ini sekarang kosong. Isi dengan dua fungsi:

```php
<?php

class Berita
{
    /** Daftar berita yang boleh dilihat publik. */
    public static function published(int $limit = 9, int $offset = 0): array
    {
        $sql = "SELECT id, title, slug, excerpt, cover_path, published_at
                FROM posts
                WHERE status = 'published'
                  AND deleted_at IS NULL
                  AND published_at <= NOW()
                ORDER BY published_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Satu berita berdasarkan slug, atau null kalau tidak ada. */
    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, title, slug, excerpt, content, cover_path, published_at
             FROM posts
             WHERE slug = :slug
               AND status = 'published'
               AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
```

Hal yang harus dipahami, bukan dihafal:

- `status = 'published'` dan `deleted_at IS NULL` **wajib** ada di setiap query publik.
  Tanpa itu, draf dan berita terhapus ikut tampil.
- `published_at <= NOW()` mencegah berita yang dijadwalkan terbit muncul lebih awal.
- `LIMIT` di-bind sebagai `PARAM_INT`. Kalau ditempel sebagai string, MySQL menolak.
- Tidak ada string input yang disambung ke SQL. Selalu `prepare` + parameter.

### 4.2 Controller — `app/Controllers/BeritaController.php`

```php
public function index(): void
{
    $data = [
        'title' => 'Berita & Artikel | MTs Al-Huda Gondang',
        'posts' => Berita::published(9, 0),
    ];

    require_once __DIR__ . '/../Views/berita/index.php';
}

public function detail(string $slug = ''): void
{
    $post = Berita::findBySlug($slug);

    if ($post === null) {
        http_response_code(404);
        require_once __DIR__ . '/../Views/errors/404.php';
        return;
    }

    $data = [
        'title' => $post['title'] . ' | MTs Al-Huda Gondang',
        'post'  => $post,
    ];

    require_once __DIR__ . '/../Views/berita/detail.php';
}
```

### 4.3 View — `app/Views/berita/index.php`

```php
<?php if ($data['posts'] === []): ?>
    <p class="empty">Belum ada berita yang diterbitkan.</p>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($data['posts'] as $post): ?>
            <article class="post-card">
                <?php if (!empty($post['cover_path'])): ?>
                    <img src="<?= e(asset($post['cover_path'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                <?php endif; ?>

                <h2>
                    <a href="<?= e(url('berita/' . $post['slug'])) ?>">
                        <?= e($post['title']) ?>
                    </a>
                </h2>

                <p><?= e($post['excerpt'] ?? '') ?></p>

                <time datetime="<?= e($post['published_at']) ?>">
                    <?= e(date('d M Y', strtotime($post['published_at']))) ?>
                </time>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

Aturan output: **setiap** nilai dinamis lewat `e()` (sudah tersedia di
`app/helpers.php`). Satu `<?= $post['title'] ?>` tanpa `e()` sudah cukup untuk
menyisipkan skrip ke halaman.

### 4.4 Uji manual

Buka:

```
http://localhost/mts-alhuda-website/berita
http://localhost/mts-alhuda-website/berita/<slug-dari-database>
http://localhost/mts-alhuda-website/berita/slug-yang-tidak-ada   -> harus 404
```

Uji juga: ubah satu berita jadi `draft` lewat database, muat ulang halaman —
berita itu harus **hilang** dari daftar.

### 4.5 Commit

```bash
git checkout -b feat/berita-publik
git add app/Models/Berita.php app/Controllers/BeritaController.php app/Views/berita
git commit -m "tampilkan daftar dan detail berita dari database"
```

---

## 5. Tahap 2 — Pembagian modul

Setelah modul contoh selesai, bagi seperti ini (1 orang 2–3 modul):

| Modul | Berkas utama | Tingkat | Catatan |
|---|---|---|---|
| Berita publik | `Berita` + `Views/berita/*` | Mudah | sudah jadi contoh |
| Fasilitas publik | `Fasilitas` + `Views/fasilitas/*` | Mudah | tabel `facilities` |
| Galeri publik | `Galeri` + `Views/galeri/*` | Mudah | tabel `galleries` + `media` |
| Profil & halaman statis | `Profil` + `Views/profil/*` | Sedang | tabel `pages` |
| Agenda & pengumuman | `Agenda` (baru) | Sedang | `agendas`, `announcements` |
| Kontak (kirim pesan) | `Kontak` | Sedang | `contact_messages`, validasi + CSRF |
| Akademik publik | `Akademik` | Sedang | `schedules` + `class_subjects` |
| Admin CRUD Berita | `AdminBeritaController` + `Views/admin/berita/*` | Sedang | form, upload, CSRF |
| Admin CRUD Fasilitas | `AdminFasilitasController` + view | Sedang | pola sama dengan berita |
| Admin CRUD Galeri | `AdminGaleriController` + view | Sedang | upload banyak gambar |
| Admin CRUD Menu | `MenuController` + view | Sedang | `menus`, urutan |

**Urutan yang disarankan:** satu modul publik dulu, baru modul admin untuk tabel
yang sama. Jadi orang yang menulis daftar berita juga menulis form tambah berita —
dia melihat kedua sisi data.

---

## 6. Tahap 3 — Aturan yang tidak boleh dilanggar

1. **Jangan mengedit migrasi lama.** Butuh perubahan skema → file baru `015_...sql`
   dengan blok `@UP` dan `@DOWN`, seperti contoh yang ada.
2. **Semua query pakai `Database::connection()` + `prepare`.** Tidak ada input
   yang disambung ke string SQL.
3. **Semua output lewat `e()`.** Tidak ada pengecualian untuk data dari database.
4. **Form admin wajib CSRF.** Token dibuat di view, diverifikasi di controller.
5. **Cek hak akses di server**, bukan hanya menyembunyikan tombol di view.
6. **Data yang bukan milik pengguna → 404, bukan 403.** Jangan membocorkan bahwa
   data itu ada.
7. **Boolean dari query string dicek `isset()` dulu** sebelum `filter_var`.
   `filter_var(null, FILTER_VALIDATE_BOOLEAN)` menghasilkan `false`, bukan `null`.
8. **Jangan commit** `config/database.local.php`, `backup/`, `.env`, `public/uploads/`.
   Sudah di-`.gitignore` — jangan dihapus dari sana.
9. **Selalu filter `deleted_at IS NULL`** untuk tabel yang punya kolom itu.
10. **Sebelum PR:** jalankan `schema-test.php`, `db-check.php`, `api-test.php`.

---

## 7. Tahap 4 — Flutter (bisa jalan paralel sejak hari pertama)

Tim mobile **tidak menunggu** website selesai. API sudah tersedia dan kontraknya
ada di `12-dokumentasi-api.md`.

### 7.1 Yang perlu disepakati di awal

- Base URL per lingkungan: lokal `http://192.168.100.22/mts-alhuda-website/api/v1`
  (pakai IP LAN, bukan `localhost` — HP tidak bisa mengakses `localhost` komputer).
- Format respons: JSON dengan pembungkus `data` dan `meta` untuk pagination.
- Semua waktu ISO 8601 + zona `+07:00`.
- Semua URL media sudah absolut — aplikasi tidak perlu menyusun URL sendiri.

### 7.2 Alur autentikasi

1. `POST /auth/login` → dapat `access_token` (30 menit) + `refresh_token` (30 hari).
2. Simpan **refresh token** di secure storage; access token boleh di memori.
3. Saat endpoint membalas `401`, panggil `POST /auth/refresh`.
4. Refresh **selalu menghasilkan token baru** (rotasi). Token lama langsung mati —
   simpan yang baru, jangan yang lama.
5. `POST /auth/logout` saat keluar.

### 7.3 Urutan layar yang disarankan

| # | Layar | Endpoint |
|---|---|---|
| 1 | Login | `POST /auth/login` |
| 2 | Beranda | `GET /posts?limit=5`, `GET /announcements` |
| 3 | Daftar berita | `GET /posts?page=1` |
| 4 | Detail berita | `GET /posts/{slug}` |
| 5 | Jadwal | `GET /schedules` (perlu token) |
| 6 | Tugas | `GET /assignments`, `GET /assignments/{publicId}` |
| 7 | Materi | `GET /materials` |
| 8 | Profil | `GET /me` |

### 7.4 Yang tidak boleh dilakukan tim mobile

- Menambah atau mengubah bentuk respons API sendiri. Kalau kurang field, ajukan
  ke pemilik API — kontrak di dokumen 12 harus tetap satu versi.
- Menyimpan access token di `SharedPreferences` biasa.
- Memakai `http://` di produksi.

---

## 8. Ritme kerja dan cara belajar

### Branch dan review

```
main                    <- selalu lulus 3 skrip uji
  feat/berita-publik    <- satu modul, satu orang
  feat/admin-berita     <- modul berikutnya
```

Setiap PR minimal satu reviewer. Reviewer **bertanya**, tidak langsung membetulkan:

- "Kenapa query ini tidak menyaring `deleted_at`?"
- "Kalau `$post` null, halaman ini jadi apa?"
- "Kalau judul berisi tanda kutip, tampilannya rusak tidak?"
- "Berapa query yang jalan untuk satu halaman ini?"

Kalau pemilik PR tidak bisa menjawab, modul belum selesai — bukan berarti gagal,
tapi perlu dibahas dulu.

### Pembagian peran saat modul dikerjakan

- **Pemilik** menulis kode dan menjelaskan di PR.
- **Pendamping** menguji di mesin sendiri (bukan hanya membaca diff) dan
  menulis satu skenario uji tambahan.
- Setiap dua modul, peran ditukar.

### Definition of Done per modul

- [ ] Data diambil dari database, bukan teks statis
- [ ] Hanya menampilkan data yang boleh dilihat publik (`status`, `deleted_at`)
- [ ] Semua output memakai `e()`
- [ ] Data kosong ditangani (tampil pesan, bukan error)
- [ ] Slug/ID yang tidak ada → 404
- [ ] Halaman admin memakai CSRF dan cek peran
- [ ] Tampil benar di lebar 360 px dan 1280 px
- [ ] `schema-test`, `db-check`, `api-test` tetap lulus
- [ ] PR dijelaskan: apa yang diubah dan kenapa

---

## 9. Contoh jadwal 4 minggu

| Minggu | Web | Mobile |
|---|---|---|
| 1 | Onboarding + modul contoh Berita bersama | Setup proyek, login, beranda |
| 2 | Berita, fasilitas, galeri, profil | Daftar + detail berita, pengumuman |
| 3 | Admin CRUD berita + fasilitas | Jadwal, tugas, materi |
| 4 | Admin CRUD galeri + menu, kontak | Profil, refresh token, UAT |

Minggu 4 diakhiri dengan **UAT memakai mockup** di
`blueprint-madrasahku/mockup/` sebagai acuan tampilan.

---

## 10. Cara memastikan semua benar-benar paham

Di akhir tiap modul, setiap orang harus bisa menjawab tanpa membuka kode:

1. Tabel apa yang dibaca, dan kenapa tabel itu?
2. Query mana yang paling mahal, dan indeks apa yang membantunya?
3. Apa yang terjadi kalau tabelnya kosong?
4. Apa yang terjadi kalau pengguna mengetik `<script>alert(1)</script>`?
5. Di mana pengecekan hak akses dilakukan?
6. Kalau data ini mau diubah lewat admin, berkas apa saja yang disentuh?

Kalau keenam pertanyaan ini terjawab, modulnya benar-benar dikuasai — bukan
sekadar berhasil tampil.
