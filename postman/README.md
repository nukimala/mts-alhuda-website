# Postman — MTs Al-Huda API Full Test

Skenario uji lengkap REST API v1 MTs Al-Huda, dipakai bersama web dan aplikasi
MADRASAHKU.

## Isi

| Folder | Cakupan | Butuh token |
|---|---|---|
| `01 - Autentikasi` | health, settings publik, login, `/me`, refresh + rotasi token | sebagian |
| `02 - Konten Publik` | berita, pengumuman, agenda, fasilitas, galeri, halaman statis | tidak |
| `03 - Akademik` | jadwal, tugas, detail tugas, materi | ya |
| `04 - Presensi` | presensi hari ini dan riwayat (role guru) | ya |
| `05 - Notifikasi` | daftar, tandai satu, tandai semua | ya |
| `06 - Negatif` | 401, 404, 405, 422 tanpa menulis data | tidak |
| `07 - Tulis Data (manual)` | check-in/out, kumpulkan tugas — **menulis data asli** | ya |
| `08 - Cleanup` | logout, pastikan token dicabut | ya |

Total 34 request.

## Environment

Tersedia dua environment di `postman/environments/`:

- **MTs Al-Huda Online** → `https://mts-alhuda.shulkhimaulana.my.id/api/v1`
- **MTs Al-Huda Local** → `http://localhost/mts-alhuda-website/api/v1`

Variabel yang perlu diisi:

| Variabel | Keterangan |
|---|---|
| `baseUrl` | sudah terisi per environment |
| `username` | akun uji (jangan admin asli di produksi) |
| `password` | password akun uji (disimpan sebagai secret) |
| `allowWrites` | `'false'` default; ubah `'true'` hanya untuk folder 07 |

Token **tidak** disimpan di environment. Request Login dan Refresh mengisi
collection variable `accessToken`, `refreshToken`, `oldRefreshToken`,
`userRole`, `postSlug`, `assignmentPublicId`, dan `notificationId` secara
otomatis.

## Menjalankan

### Lewat Postman GUI

1. Buka folder ini sebagai workspace Postman, atau import
   `postman/collections/MTs Al-Huda API - Full Test`.
2. Pilih environment **MTs Al-Huda Online**.
3. Isi `username` dan `password`, lalu simpan.
4. Jalankan folder `01 - Autentikasi` sampai `06 - Negatif`.
5. Jalankan `08 - Cleanup` di akhir.
6. Folder `07` hanya jika `allowWrites = true` dan memang boleh membuat data
   uji di server tersebut.

### Lewat Postman CLI

```bash
# baca saja: konten publik + kasus negatif
postman collection run "postman/collections/MTs Al-Huda API - Full Test" \
  -e "postman/environments/MTs Al-Huda Online.environment.yaml" \
  -i "02 - Konten Publik" -i "06 - Negatif"

# seluruh alur (butuh username/password terisi)
postman collection run "postman/collections/MTs Al-Huda API - Full Test" \
  -e "postman/environments/MTs Al-Huda Online.environment.yaml"
```

## Catatan penting

- **`GET /posts` dan `GET /posts/{slug}` gagal 500 di server online** karena
  tabel `berita` online masih memakai kolom lama (`judul`, `ringkasan`, `isi`,
  `gambar`, `penulis`, `tanggal`), sedangkan API membaca `title`, `excerpt`,
  `content`, `cover_path`, `author_id`, `published_at`. Perbaiki migrasi tabel
  atau mapping controller sebelum endpoint ini dianggap lulus.
- `GET /pages/profil` boleh 404 jika baris halaman dengan slug tersebut belum
  ada; request ini menerima 200 atau 404.
- Folder `07` sengaja dilewati (`pm.execution.skipRequest()`) saat
  `allowWrites !== 'true'`.
- Jangan memakai akun demo lokal (`guru` / `Contoh#AlHuda2026`) di server
  online.
