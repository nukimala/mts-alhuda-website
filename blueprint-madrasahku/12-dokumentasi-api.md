# Dokumentasi API v1 — MTs Al-Huda Gondang

Dokumen ini untuk tim aplikasi (Flutter) dan siapa pun yang akan mengambil data
dari sistem tanpa menyentuh database langsung.

Versi: 1.2 — 23 Oktober 2026
Basis URL pengembangan: `http://localhost/mts-alhuda-website`

Port default repositori ini adalah **80** (Apache) dan **3306** (MySQL), jadi alamat
di atas berlaku apa adanya setelah `git clone` + `php tools/setup.php`. Kalau tumpukan
server Anda memakai port web lain, sesuaikan — `php tools/setup.php` mencetak alamat
yang benar di bagian akhir.

Untuk perangkat Android di jaringan yang sama (bukan emulator), ganti `localhost`
dengan IP LAN komputer server — jalankan `php tools/db-share.php info` untuk melihatnya.

---

## 1. Kenapa lewat API, bukan langsung ke database

Database hanya menerima koneksi dari dalam jaringan madrasah, dan hanya dari
akun yang dibuat khusus. Aplikasi mobile **tidak** memakai akun database.
Alasannya:

| Kalau aplikasi konek langsung ke MySQL | Kalau lewat API |
|---|---|
| Kredensial database ikut terpasang di APK — bisa dibongkar siapa pun | APK hanya menyimpan token milik satu pengguna |
| Satu akun dipakai semua pengguna, tidak ada jejak siapa mengambil apa | Setiap permintaan tercatat atas nama pengguna |
| Aturan akses harus ditulis ulang di setiap aplikasi | Aturan akses ditulis sekali di server |
| Ubah struktur tabel → semua aplikasi rusak | Struktur tabel berubah, bentuk JSON tetap |

Jadi: **database untuk server, API untuk aplikasi.**

---

## 2. Bentuk respons

Semua respons berformat JSON. Tidak pernah HTML, termasuk saat terjadi galat
fatal. Kalau server bermasalah, yang keluar tetap JSON.

### Berhasil

```json
{
  "success": true,
  "data": { }
}
```

### Gagal

```json
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Nama pengguna atau kata sandi salah."
  }
}
```

Galat validasi menambahkan `details`:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Data yang dikirim belum lengkap atau tidak valid.",
    "details": { "password": "Wajib diisi." }
  }
}
```

**Aturan untuk tim Flutter:** jangan pernah menampilkan `message` mentah ke
pengguna akhir tanpa pertimbangan. Pesan itu berbahasa Indonesia dan cukup
manusiawi, tapi untuk beberapa kode lebih baik pakai teks sendiri. Yang
**wajib** dipakai untuk percabangan logika adalah `error.code`, bukan `message`.

---

## 3. Daftar kode galat

| HTTP | `error.code` | Arti | Yang harus dilakukan aplikasi |
|---|---|---|---|
| 400 | `VERSION_REQUIRED` | URL tanpa versi | Perbaiki URL |
| 401 | `UNAUTHORIZED` | Token tidak ada, salah, atau kedaluwarsa | Coba refresh; kalau refresh gagal, ke layar masuk |
| 401 | `INVALID_CREDENTIALS` | Nama pengguna / sandi salah | Tampilkan pesan, sisa percobaan ada di `message` |
| 401 | `INVALID_REFRESH_TOKEN` | Refresh token sudah mati | Hapus token lokal, ke layar masuk |
| 403 | `ACCOUNT_INACTIVE` | Akun dinonaktifkan operator | Tampilkan pesan, jangan coba lagi |
| 404 | `ENDPOINT_NOT_FOUND` | Endpoint tidak ada | Bug di aplikasi — perbaiki URL |
| 404 | `VERSION_UNSUPPORTED` | Versi API tidak dikenal | Perbaiki URL |
| 404 | `NOT_FOUND` | Data yang diminta tidak ada, atau bukan milik pengguna | Tampilkan "tidak ditemukan"; jangan tampilkan bedanya |
| 405 | `METHOD_NOT_ALLOWED` | Metode HTTP salah | Perbaiki pemanggilan |
| 409 | `PROFILE_NOT_LINKED` | Akun guru/siswa belum ditautkan ke data guru/siswa | Tampilkan pesan; hubungi Operator Madrasah |
| 422 | `VALIDATION_ERROR` | Ada isian yang kurang / tidak valid | Tandai kolom sesuai `details` |
| 423 | `ACCOUNT_LOCKED` | Akun dikunci sementara | Tampilkan pesan; jangan coba lagi sampai waktunya habis |
| 429 | `TOO_MANY_REQUESTS` | Terlalu banyak percobaan | Tunggu sesuai header `Retry-After` (detik) |
| 500 | `SERVER_ERROR` | Kesalahan server | Tampilkan pesan umum; laporkan ke backend |

---

## 4. Autentikasi

Dua token, dua umur, dua fungsi.

| Token | Umur | Dipakai untuk | Disimpan di mana |
|---|---|---|---|
| `access_token` | 30 menit | Setiap permintaan berautentikasi | Memori aplikasi saja |
| `refresh_token` | 30 hari | Hanya untuk minta access token baru | Penyimpanan aman perangkat |

**Jangan simpan `access_token` di penyimpanan permanen.** Umurnya pendek, dan
kalau perangkat hilang, token itu mati sendiri dalam 30 menit.

**`refresh_token` disimpan permanen**, tapi di penyimpanan terenkripsi
(`flutter_secure_storage` di Android/iOS — bukan `SharedPreferences` biasa).

### Kenapa refresh selalu berotasi

Setiap kali refresh dipakai, server menerbitkan pasangan baru dan **mematikan
yang lama**. Kalau ada dua pihak memakai refresh token yang sama, yang kedua
pasti gagal. Itu bukan bug — itu cara server mendeteksi token yang dicuri.

Konsekuensinya untuk aplikasi: **setelah refresh, simpan token baru sebelum
melakukan apa pun lagi.** Kalau aplikasi crash di antara "terima respons" dan
"simpan token", pengguna harus masuk ulang.

### Alur di aplikasi

```
Buka aplikasi
  ├─ ada refresh_token tersimpan?
  │    ├─ tidak → layar masuk
  │    └─ ya → POST /auth/refresh
  │              ├─ 200 → simpan pasangan baru → lanjut
  │              └─ 401 → hapus token → layar masuk
  │
  └─ setiap permintaan berautentikasi:
        kirim Authorization: Bearer <access_token>
          ├─ 200 → tampilkan
          └─ 401 → refresh sekali → ulangi permintaan
                     └─ refresh gagal → layar masuk
```

**Penting:** hanya refresh **sekali** per permintaan yang gagal. Kalau setelah
refresh masih 401, jangan ulangi lagi — ke layar masuk. Tanpa batas ini,
aplikasi bisa masuk lingkaran permintaan tanpa henti.

---

## 5. Endpoint

### 5.1 `GET /api/v1/health`

Tanpa autentikasi. Untuk memastikan backend hidup sebelum melaporkan bug.

```bash
curl -s http://localhost/mts-alhuda-website/api/v1/health
```

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "time": "2026-10-23T01:28:48+07:00",
    "timezone": "Asia/Jakarta",
    "database": "terhubung",
    "server": "8.4.3",
    "migrasi": 14
  }
}
```

Kalau `status` bernilai `degraded`, database tidak terhubung. Laporkan ke
backend, jangan cari bug di aplikasi.

---

### 5.2 `GET /api/v1/settings/public`

Tanpa autentikasi. Nama madrasah, kontak, jam operasional. Dipakai untuk layar
tentang, footer, dan halaman kontak.

```bash
curl -s http://localhost/mts-alhuda-website/api/v1/settings/public
```

```json
{
  "success": true,
  "data": {
    "settings": {
      "site_name": "MTs Al-Huda",
      "site_subtitle": "Gondang",
      "site_full_name": "Madrasah Tsanawiyah Al-Huda Gondang",
      "accreditation_grade": "A",
      "contact_address": "Gondang, Kabupaten Tulungagung, Jawa Timur",
      "contact_email": "info@mts-alhuda.sch.id",
      "contact_hours": "Senin - Sabtu, 07.00 - 15.10 WIB",
      "meta_title": "MTs Al-Huda Gondang — Madrasah Berakhlak",
      "meta_description": "..."
    }
  }
}
```

Hanya pengaturan bertanda publik yang keluar. Kunci yang nilainya kosong tetap
dikirim sebagai string kosong — aplikasi harus menanganinya, jangan menganggap
selalu terisi.

---

### 5.3 `POST /api/v1/auth/login`

Tanpa autentikasi.

**Body:**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `identifier` | string | ya | Email **atau** username |
| `password` | string | ya | Kata sandi |
| `device_name` | string | tidak | Nama perangkat, tampil di daftar sesi. Maks 120 karakter |
| `platform` | string | tidak | `android`, `ios`, `web`, atau `unknown`. Selain itu jadi `unknown` |

```bash
curl -s -X POST http://localhost/mts-alhuda-website/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
        "identifier": "admin",
        "password": "KataSandiAnda",
        "device_name": "Redmi Note 12",
        "platform": "android"
      }'
```

**Berhasil — 200:**

```json
{
  "success": true,
  "data": {
    "tokens": {
      "access_token": "Xq3...43 karakter...",
      "refresh_token": "kP9...43 karakter...",
      "token_type": "Bearer",
      "access_expires_at": "2026-10-23 02:00:00",
      "refresh_expires_at": "2026-11-22 01:30:00"
    },
    "user": {
      "id": 1,
      "name": "Administrator Madrasah",
      "email": "admin@mts-alhuda.sch.id",
      "username": "admin",
      "role": "admin",
      "role_label": "Super Admin",
      "must_change_password": false
    }
  }
}
```

**Gagal — 401:**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Nama pengguna atau kata sandi salah. Sisa 4 percobaan sebelum akun dikunci sementara."
  }
}
```

**Terkunci — 423:**

```json
{
  "success": false,
  "error": {
    "code": "ACCOUNT_LOCKED",
    "message": "Terlalu banyak percobaan gagal. Akun dikunci 15 menit."
  }
}
```

**Batas:** 10 percobaan per 5 menit per alamat IP. Lewat itu, semua percobaan
dari IP tersebut ditolak dengan 429 — termasuk yang sandinya benar. Ini
disengaja: mencegah tebak sandi beruntun.

**Catatan penting untuk aplikasi:** kalau `must_change_password` bernilai
`true`, arahkan pengguna ke layar ganti sandi **sebelum** masuk ke beranda.
Token tetap berlaku, tapi isi sistem belum boleh diakses.

---

### 5.4 `POST /api/v1/auth/refresh`

Tanpa header Authorization. Yang dikirim adalah refresh token di body.

**Body:**

| Field | Tipe | Wajib |
|---|---|---|
| `refresh_token` | string | ya |

```bash
curl -s -X POST http://localhost/mts-alhuda-website/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"kP9...43 karakter..."}'
```

**Berhasil — 200:** bentuknya sama dengan respons login (`tokens` + `user`).

**Gagal — 401:**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_REFRESH_TOKEN",
    "message": "Sesi sudah berakhir. Silakan masuk kembali."
  }
}
```

**Setelah berhasil, token lama langsung mati.** Simpan pasangan baru segera.

---

### 5.5 `POST /api/v1/auth/logout`

Butuh `Authorization: Bearer <access_token>`. Mencabut token yang sedang
dipakai **dan** refresh token pasangannya. Setelah ini, keduanya tidak berlaku.

```bash
curl -s -X POST http://localhost/mts-alhuda-website/api/v1/auth/logout \
  -H "Authorization: Bearer Xq3..."
```

```json
{ "success": true, "data": { "message": "Berhasil keluar." } }
```

Aplikasi tetap harus menghapus token lokal, walaupun permintaan ini gagal
(misalnya karena tidak ada sinyal). Logout lokal tidak boleh menunggu server.

---

### 5.6 `GET /api/v1/me`

Butuh `Authorization: Bearer <access_token>`. Profil pengguna yang sedang masuk.

```bash
curl -s http://localhost/mts-alhuda-website/api/v1/me \
  -H "Authorization: Bearer Xq3..."
```

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator Madrasah",
      "email": "admin@mts-alhuda.sch.id",
      "username": "admin",
      "role": "admin",
      "role_label": "Super Admin",
      "must_change_password": false
    }
  }
}
```

---

### 5.7 Cara membaca daftar

Semua endpoint daftar memakai bentuk yang sama. Tidak ada endpoint yang
mengirim seluruh tabel sekaligus.

**Parameter:**

| Parameter | Bawaan | Batas | Keterangan |
|---|---|---|---|
| `page` | 1 | ≥ 1 | Halaman ke berapa |
| `per_page` | 20 | 1–100 | Jumlah baris per halaman. Lebih dari 100 dipotong jadi 100, bukan ditolak |

**Respons selalu menyertakan `meta`:**

```json
{
  "success": true,
  "data": { "posts": [ ] },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 42,
    "total_pages": 3,
    "has_next": true
  }
}
```

**Aturan untuk tim Flutter:** berhenti memuat halaman berikutnya saat
`has_next` bernilai `false`. Jangan menghitung sendiri dari `total`, karena
`total` bisa berubah di antara dua permintaan.

---

### 5.8 `GET /api/v1/posts`

Tanpa autentikasi. Berita yang sudah terbit, terbaru lebih dulu.

| Parameter | Keterangan |
|---|---|
| `q` | Cari pada judul dan ringkasan. `%` dan `_` diperlakukan sebagai huruf biasa |
| `featured` | `1`/`true` hanya berita unggulan, `0`/`false` hanya non-unggulan |

```bash
curl -s "http://localhost/mts-alhuda-website/api/v1/posts?per_page=2&q=contoh"
```

```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 3,
        "public_id": null,
        "title": "Contoh Berita Ketiga",
        "slug": "contoh-berita-ketiga",
        "excerpt": "Berita contoh ketiga, paling baru.",
        "cover_url": null,
        "is_featured": false,
        "views": 0,
        "published_at": "2026-10-20T14:15:00+07:00",
        "author": { "name": "Operator Contoh" },
        "url": "http://localhost/mts-alhuda-website/berita/contoh-berita-ketiga"
      }
    ]
  },
  "meta": { "page": 1, "per_page": 2, "total": 3, "total_pages": 2, "has_next": true }
}
```

Draf, arsip, dan berita yang sudah dihapus **tidak pernah** muncul di sini.

---

### 5.9 `GET /api/v1/posts/{slug}`

Tanpa autentikasi. Satu berita lengkap dengan `content` (HTML dari panel admin).

```bash
curl -s http://localhost/mts-alhuda-website/api/v1/posts/contoh-berita-ketiga
```

Bentuknya sama dengan item di atas, ditambah `content`. **Setiap pembacaan
menambah `views` satu.** Jadi `views` di respons sudah termasuk kunjungan ini.

Slug yang tidak ada — atau berita yang belum terbit — menghasilkan `404`
dengan kode `NOT_FOUND`. Keduanya sengaja tidak dibedakan.

---

### 5.10 `GET /api/v1/announcements`

Tanpa autentikasi, tetapi **hasilnya bergantung pada token kalau ada**.

| Peminta | Yang diterima |
|---|---|
| Tanpa token | Hanya pengumuman `semua` |
| Siswa | `semua` + `siswa` |
| Guru | `semua` + `guru` |
| Staff (admin/operator/kepala/akreditasi) | Semua sasaran |

Pengumuman yang sudah lewat `expires_at` tidak pernah dikirim. Yang
disematkan (`is_pinned`) selalu di atas.

`content` ikut di daftar karena jumlah pengumuman biasanya sedikit. Kalau
kelak membengkak, endpoint ini yang pertama perlu dipisah.

---

### 5.11 `GET /api/v1/agendas`

Tanpa autentikasi. Bawaannya agenda yang belum lewat — termasuk yang sedang
berlangsung.

| Parameter | Keterangan |
|---|---|
| `past` | `1`/`true` untuk riwayat (urut terbaru dulu) |

---

### 5.12 `GET /api/v1/facilities` dan `GET /api/v1/galleries`

Tanpa autentikasi. Fasilitas aktif dan foto galeri aktif.

`/galleries` menerima `album=` untuk menyaring satu kegiatan.

```json
{
  "id": 1,
  "name": "Ruang Kelas",
  "description": "Kelas yang bersih dan terang.",
  "image_url": "http://localhost/mts-alhuda-website/uploads/facilities/kelas.jpg",
  "icon": "classroom"
}
```

`image_url`/`file_url` selalu URL lengkap, bukan path relatif. Kalau datanya
kosong, nilainya `null` — aplikasi wajib menangani gambar yang tidak ada.

---

### 5.13 `GET /api/v1/pages/{slug}`

Tanpa autentikasi. Halaman statis yang dikelola operator: `profil`,
`akademik`, dan seterusnya.

```json
{
  "success": true,
  "data": {
    "page": {
      "slug": "profil",
      "title": "Profil Madrasah",
      "content": "<p>...</p>",
      "meta_title": null,
      "meta_description": null,
      "updated_at": "2026-10-23T05:48:12+07:00"
    }
  }
}
```

Kalau aplikasi menampilkan HTML, gunakan renderer HTML yang **mematikan
skrip**. `content` berasal dari panel admin, tetapi tetap tidak boleh
dipercaya begitu saja.

---

### 5.14 `GET /api/v1/schedules`

**Butuh token.** Jadwal pelajaran.

| Peran | Yang diterima |
|---|---|
| `guru` | Hanya jadwal yang dia ampu |
| `siswa` | Hanya jadwal kelasnya |
| Staff | Semua jadwal aktif |

Filter tambahan: `day=1..7` (1 = Senin).

```bash
curl -s http://localhost/mts-alhuda-website/api/v1/schedules \
  -H "Authorization: Bearer TOKEN"
```

```json
{
  "success": true,
  "data": {
    "schedules": [
      {
        "id": 9,
        "day_of_week": 1,
        "day_name": "Senin",
        "start_time": "07:30",
        "end_time": "09:00",
        "room": "Ruang 7A",
        "class": { "id": 1, "name": "7A", "level": 7 },
        "subject": { "code": "MTK", "name": "Matematika" },
        "teacher_name": "Guru Contoh",
        "academic_year": "2025/2026"
      }
    ]
  },
  "meta": { "page": 1, "per_page": 2, "total": 2, "total_pages": 1, "has_next": false, "scope": "teacher" }
}
```

**`meta.scope`** memberi tahu aplikasi cara menampilkan: `teacher`, `student`,
atau `staff`. Kalau guru membuka layar yang sama dengan siswa, judulnya bisa
dibedakan tanpa menebak dari isi.

Yang dikirim hanya jadwal pada **tahun ajaran aktif**. `start_time` dan
`end_time` berformat `HH:MM` — tanpa detik, karena detiknya selalu nol.

---

### 5.15 `GET /api/v1/assignments`

**Butuh token.** Tugas beserta status pengumpulan.

| Peran | Yang diterima | Tambahan di tiap item |
|---|---|---|
| `guru` | Tugas pada kelas-mapel yang dia ampu, **termasuk draf** | `submission_count`, `ungraded_count` |
| `siswa` | Hanya tugas terbit di kelasnya | `my_submission` |
| Staff | Semua tugas terbit | `submission_count`, `ungraded_count` |

Filter tambahan: `open=1` (belum lewat tenggat), `open=0` (sudah lewat),
`class_id=` (khusus staff).

```json
{
  "id": 3,
  "public_id": "bb4b0c18-ce6c-11f1-9083-38142812b636",
  "title": "Tugas Contoh: Operasi Bilangan Bulat",
  "description": "Kerjakan 10 soal ...",
  "attachment_url": null,
  "due_at": "2026-11-10T23:59:59+07:00",
  "max_score": 100,
  "is_published": true,
  "is_overdue": false,
  "class": { "id": 1, "name": "7A", "level": 7 },
  "subject": { "code": "MTK", "name": "Matematika" },
  "my_submission": null
}
```

`my_submission` untuk siswa:

```json
{
  "submitted_at": "2026-11-08T20:14:00+07:00",
  "score": 92.5,
  "graded_at": "2026-11-12T09:00:00+07:00",
  "note": "Foto kurang tajam di halaman 2.",
  "file_url": "http://localhost/mts-alhuda-website/uploads/submissions/x.pdf",
  "feedback": "Pekerjaan rapi."
}
```

`null` berarti belum mengumpulkan. `score` dan `feedback` bernilai `null`
selama belum dinilai — itu bukan nol.

---

### 5.16 `GET /api/v1/assignments/{public_id}`

**Butuh token.** Satu tugas, dengan pembatasan peran yang sama.

**Pakai `public_id`, bukan `id`.** `public_id` adalah UUID yang stabil dan
tidak membocorkan berapa banyak data di database. `id` numerik masih diterima
untuk kemudahan saat menguji, tetapi jangan dipakai di aplikasi.

Tugas yang bukan milik pengguna menghasilkan `404` — bukan `403`. Membedakan
keduanya sama dengan memberi tahu bahwa id tersebut memang ada.

---

### 5.17 `GET /api/v1/materials`

**Butuh token.** Materi pelajaran. Pembatasan peran sama dengan jadwal.

```json
{
  "id": 1,
  "title": "Materi Contoh: Bilangan Bulat",
  "description": "Ringkasan sifat operasi bilangan bulat.",
  "file_url": null,
  "external_url": "https://contoh.invalid/materi/bilangan-bulat.pdf",
  "published_at": "2026-10-05T08:00:00+07:00",
  "class": { "id": 1, "name": "7A" },
  "subject": { "code": "MTK", "name": "Matematika" },
  "author_name": "Operator Contoh"
}
```

Salah satu dari `file_url` dan `external_url` selalu terisi — database
menolak materi tanpa sumber.

---

## 6. Nilai `role` yang mungkin muncul

| `role` | Arti | Label bawaan |
|---|---|---|
| `admin` | Super admin — semua hak | Super Admin |
| `operator` | Operator madrasah — kelola konten & data | Operator Madrasah |
| `kepala` | Kepala madrasah — lihat laporan | Kepala Madrasah |
| `guru` | Guru — presensi & nilai | Guru |
| `siswa` | Siswa — lihat nilai sendiri | Siswa |
| `akreditasi` | Tim akreditasi — isi instrumen | Tim Akreditasi |

Pakai `role` untuk percabangan, `role_label` untuk tampilan. `role_label` bisa
diubah operator, `role` tidak.

---

## 7. Yang belum tersedia

API v1 sekarang mencakup autentikasi, pengaturan publik, konten publik, dan
data akademik baca-saja (jadwal, tugas, materi). Yang **belum** ada:

| Belum ada | Catatan |
|---|---|
| Kirim pengumpulan tugas | `POST /assignments/{id}/submissions` — menunggu alur unggah berkas |
| Presensi guru dari aplikasi | Butuh aturan jam server dan pemeriksaan jadwal |
| Nilai dan umpan balik (tulis) | Guru masih menilai lewat panel web |
| Notifikasi | Tabel `notifications` sudah ada; endpoint polling belum |
| Halaman akreditasi & PPDB | Belum diprioritaskan untuk aplikasi |

Semua endpoint di atas bersifat **baca**. Tidak ada satu pun yang mengubah
data, kecuali penghitung `views` berita.

Kalau tim Flutter butuh endpoint lain, sebutkan **bentuk data yang dibutuhkan**
— bukan nama tabel. Contoh yang membantu:

> "Layar beranda butuh 5 berita terbaru: judul, ringkasan 120 karakter,
> gambar, tanggal. Tanpa isi lengkap."

Contoh yang tidak membantu:

> "Butuh API berita."

Yang pertama bisa langsung dikerjakan. Yang kedua butuh dua hari tanya-jawab.

---

## 8. Aturan yang dipegang server

Ini bukan janji, ini yang benar-benar terjadi di kode:

- Token asli **tidak pernah** disimpan. Database hanya menyimpan hash SHA-256.
  Kalau database bocor, isinya tidak bisa dipakai masuk.
- Setiap token terikat ke satu perangkat. Logout di satu perangkat tidak
  mengeluarkan pengguna dari perangkat lain.
- Refresh selalu berotasi. Token lama mati saat token baru terbit.
- Pesan galat login tidak membocorkan apakah akun terdaftar.
- Setiap percobaan masuk — berhasil maupun gagal — tercatat di `activity_logs`
  dengan waktu, IP, dan perangkat.
- Tidak ada cookie. Tidak ada sesi. API tidak bisa diserang CSRF.
- Galat server tidak pernah menampilkan stack trace ke klien. Detailnya masuk
  `error_log` server saja.

---

## 9. Menguji dari komputer sendiri

Sebelum melaporkan "API tidak jalan", jalankan ini dulu:

```bash
# 1. Server hidup?
curl -s http://localhost/mts-alhuda-website/api/v1/health

# 2. Bisa login?
curl -s -X POST http://localhost/mts-alhuda-website/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"admin","password":"KATA_SANDI"}'

# 3. Token berlaku?
curl -s http://localhost/mts-alhuda-website/api/v1/me \
  -H "Authorization: Bearer TOKEN_DARI_LANGKAH_2"

# 4. Data akademik (butuh token yang sama)
curl -s http://localhost/mts-alhuda-website/api/v1/schedules \
  -H "Authorization: Bearer TOKEN_DARI_LANGKAH_2"
```

Untuk memeriksa semuanya sekaligus — termasuk pembatasan peran guru dan
siswa — jalankan:

```bash
php tools/api-test.php http://localhost/mts-alhuda-website admin KATA_SANDI_ADMIN
```

Uji peran membutuhkan akun contoh. Kalau belum ada, jalankan
`php tools/migrate.php seed --demo` lebih dulu; kalau tidak, bagian itu
dilewati dengan keterangan, bukan dianggap lulus.

Di PowerShell, `curl` adalah alias untuk `Invoke-WebRequest` dan tidak menerima
opsi yang sama. Pakai `curl.exe` (dengan `.exe`), atau `Invoke-RestMethod`:

```powershell
Invoke-RestMethod -Uri "http://localhost/mts-alhuda-website/api/v1/health"
```

---

## 10. Kalau butuh akses database langsung

Untuk tim backend — bukan tim mobile — akses langsung bisa diberikan. Caranya
di `database/README.md` bagian "Akses untuk tim". Yang perlu diingat:

- Port **3306** (MySQL/MariaDB). Kalau tumpukan Anda memakai port lain — mis.
  XAMPP yang dipasang di 3307 — sesuaikan, dan jangan lupa mengisi
  `config/database.local.php`. Tanpa berkas itu, koneksi mencoba 3306 lebih dulu.
- Setiap orang punya akun sendiri. Tidak ada akun bersama.
- Peran `readonly` untuk yang hanya perlu membaca, `developer` untuk yang
  mengubah struktur.
- Akun `developer` bisa `DROP TABLE`. Jangan dipakai di komputer yang
  menyambung ke database berisi data nyata.
