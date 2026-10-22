# DESIGN.md — Sistem Desain MTs Al-Huda & MADRASAHKU

> **Status:** draf v1.0 — diturunkan dari kode yang benar-benar ada, bukan dari asumsi.
> **Sumber utama:** `public/css/style.css` (web publik), `blueprint-madrasahku/mockup/assets/mockup.css` (admin + mobile).
> **Cakupan:** website publik, panel admin web, aplikasi mobile MADRASAHKU.
>
> Dokumen ini **mengikat**. Kalau kode dan dokumen ini berbeda, kode yang salah — kecuali
> perbedaan itu dicatat di §2 dan §20.
>
> Terakhir diperbarui: 22 Oktober 2026

---

## 1. Tujuan dokumen

Ada tiga pihak yang memakai dokumen ini:

| Pihak | Yang diambil |
|---|---|
| Developer web | token, komponen, struktur layout, aturan state |
| Tim mobile (Flutter) | warna, tipografi, radius, jarak, pola interaksi |
| Mitra / pimpinan | memahami tampilan akan seperti apa, tanpa membaca kode |

Dokumen ini **bukan** pengganti `10-perombakan-web.md` §10. Bagian itu ringkas (12 baris tabel);
dokumen ini adalah spesifikasi lengkap yang bisa langsung dipakai menulis CSS dan widget Flutter.

---

## 2. Konflik yang harus diselesaikan lebih dulu

Tiga hal ini sudah berbeda antara blueprint, kode lama, dan mockup. Harus diputuskan sebelum
satu baris CSS admin ditulis, karena menyentuh **semua** komponen.

| No | Hal | Blueprint | Kode lama (`style.css`) | Mockup | Rekomendasi |
|---|---|---|---|---|---|
| **D-1** | Radius | 16px | 6px tombol, 9px kartu, 15px panel | 16px kartu, 9–11px tombol | **Pakai 16px kartu / 10px kontrol** — konsisten dengan mockup yang sudah dilihat mitra |
| **D-2** | Ukuran font dasar | tidak disebut | **29 dari 45 deklarasi `font-size` di bawah 12px** (terkecil 7px) | 14px body, 13px tombol, minimum 10.5px | **14px body, minimum 12px di mana pun.** Lihat audit §2.1 |
| **D-3** | Font weight | tidak disebut | 800 untuk heading | 800 heading, 700 tombol | **Pertahankan 800/700/600/400.** Jangan pakai 500 — tidak dimuat |

Keputusan D-1 dan D-2 akan mengubah `style.css` yang sudah ada. Perubahannya kecil tapi menyebar —
karena itu harus disetujui sekarang, bukan setelah 20 komponen jadi.

### 2.1 Audit ukuran teks `style.css`

Ini bukan dugaan — hasil hitung langsung dari berkas:

| Ukuran | Jumlah deklarasi | Contoh pemakaian |
|---|---|---|
| 7px | 3 | `.mini-btn`, `.program-cta .btn`, footer |
| 8px | 7 | footer tautan, `.mini-btn` |
| 9px | 6 | `.eyebrow`, `.section-label`, `.nav-menu a` (≤900px) |
| 10px | 7 | `.btn`, `.nav-cta`, `.card-date` |
| 11px | 6 | `.nav-menu a`, `.hero-description`, `.gallery-item figcaption` |
| ≥12px | 16 | `.article-body` 13px, heading 17–72px |

**29 dari 45 deklarasi (64%) di bawah 12px.** Pada layar 1366px dengan `4.3vw`, teks 8px setara
±0,6% lebar layar — tidak terbaca tanpa zoom. Ini masalah nyata, bukan selera.

Akibatnya: memperbaiki D-2 bukan sekadar mengganti angka. Perlu menaikkan ukuran **dan**
menyesuaikan padding/tinggi elemen agar tata letak tidak pecah. Estimasi: **3–4 jam** untuk
`style.css` penuh, di luar panel admin.

Keputusan yang saya sarankan: **naikkan seluruh 29 deklarasi ke minimum 12px**, dan terima bahwa
beberapa elemen akan terlihat lebih besar dari sekarang. Tampilan yang bisa dibaca lebih penting
daripada proporsi yang rapi di satu tangkapan layar.

### 2.2 Audit radius `style.css`

**12 nilai radius berbeda** dipakai di satu berkas:

```
4px · 5px · 6px · 7px · 9px (x2) · 10px · 12px · 15px · 16px · 20px · 99px · 999px
```

Tidak ada satu pun yang dominan. Akibatnya sudut kartu, tombol, dan panel terlihat tidak satu
keluarga. `--radius-lg: 16px` sudah dideklarasikan di `:root` tapi hanya dipakai **2 kali**.

Skala yang saya sarankan — hanya **5 nilai**:

| Token | Nilai | Untuk |
|---|---|---|
| `--r-sm` | 7px | pagination, kontrol mini |
| `--r-md` | 10px | tombol, input, badge persegi |
| `--r-lg` | 16px | kartu, panel, modal |
| `--r-xl` | 22px | kepala profil mobile |
| `--r-full` | 999px | pil, avatar, switch |

Delapan nilai di luar skala — `4px`, `5px`, `6px`, `9px`, `12px`, `15px`, `20px`, `99px` — dihapus
dan dipetakan ke skala di atas. `99px` dan `999px` adalah duplikat makna: pilih `999px`.

---

## 3. Identitas merek

### 3.1 Karakter

Madrasah, bukan startup. Kesan yang harus muncul: **tertib, tenang, terpercaya, islami tanpa
berlebihan**. Bukan: ramai, gradien mencolok, animasi berlebih, ilustrasi kartun.

Tiga kata pegangan: **hijau, bersih, jelas**.

### 3.2 Logo

| Aturan | Ketentuan |
|---|---|
| Berkas | `public/assets/images/LOGO_MTS 1.png` |
| Latar terang | logo asli, tanpa kotak |
| Latar gelap (sidebar) | logo di dalam kotak putih radius 10px, padding 3px |
| Ukuran minimum | 28px (nav), 38px (sidebar), 62px (halaman login), 68px (aplikasi) |
| Ruang kosong | minimal 25% tinggi logo di semua sisi |
| Larangan | jangan regangkan, jangan ganti warna, jangan beri bayangan |

### 3.3 Suara penulisan (copywriting)

| Konteks | Gaya | Contoh |
|---|---|---|
| Halaman publik | formal, hangat | "Selamat datang di MTs Al-Huda Gondang" |
| Tombol | kata kerja, 1–2 kata | "Simpan", "Terbitkan", "Koreksi" |
| Pesan galat | apa yang salah + apa yang harus dilakukan | "Kata sandi minimal 8 karakter." |
| Pesan sukses | apa yang berubah | "Berita tersimpan sebagai draft." |
| Konfirmasi hapus | sebut objeknya | "Hapus berita *Peringatan Hari Santri*?" |
| Istilah asing | hanya yang sudah lazim | "check-in" boleh, "dashboard" → "Beranda" |

Aturan tegas: **jangan menyebut istilah teknis ke pengguna**. "Token kedaluwarsa" → "Sesi Anda
berakhir, silakan masuk lagi".

---

## 4. Warna

### 4.1 Token inti

| Token | Hex | Fungsi | Kontras di atas putih |
|---|---|---|---|
| `--green` | `#15803D` | aksi utama, tautan, aksen | **5,02:1** ✓ AA |
| `--dark-green` | `#0B3D1E` | sidebar, footer, hero gelap | 12,36:1 ✓ AAA |
| `--light-green` | `#4ADE80` | penanda aktif, badge di latar gelap | 7,09:1 di `--dark-green` ✓ AAA |
| `--mint` | `#A7F3D0` | latar ikon, avatar | — (dekoratif) |
| `--pale-green` | `#BBF7D0` | latar lembut | — |
| `--text` | `#111827` | teks utama | **17,74:1** ✓ AAA |
| `--gray` | `#6B7280` | teks sekunder | **4,83:1** ✓ AA |
| `--slate` | `#94A3B8` | **hanya** placeholder, ikon non-kritis, garis | **2,56:1** ✗ gagal untuk teks |
| `--border` | `#E1ECE5` | garis pemisah, border kartu | dekoratif |
| `--soft` | `#F9FAFB` | latar halaman | — |
| `--white` | `#FFFFFF` | permukaan kartu | — |

### 4.2 Warna status

| Token | Hex | Latar lembut | Kontras teks | Pakai untuk |
|---|---|---|---|---|
| `--green` | `#15803D` | `#E9F9EF` | 5,02:1 ✓ | hadir, terbit, disetujui, berhasil |
| `--amber` | `#B45309` | `#FFFBEB` | 5,02:1 ✓ | terlambat, menunggu, menjelang kadaluarsa |
| `--red` | `#DC2626` | `#FEF2F2` | 4,83:1 ✓ | alpa, gagal, kadaluarsa, hapus |
| `--blue` | `#1D4ED8` | `#EFF6FF` | 6,70:1 ✓ | izin, informasi, netral-aktif |
| `--gray` | `#6B7280` | `#F1F3F5` | 4,83:1 ✓ | draft, nonaktif, belum ada |

### 4.3 Aturan pemakaian

1. **`--slate` tidak boleh jadi warna teks.** Kontras 2,56:1 gagal WCAG AA. Hanya untuk
   placeholder input, ikon non-esensial, dan garis.
2. **Hijau bukan untuk semua hal.** Kalau semua tombol hijau, tidak ada yang menonjol. Satu
   layar maksimal **satu** tombol `btn-primary`.
3. **Warna status selalu berpasangan** teks + latar lembut. Jangan teks berwarna di atas putih
   tanpa latar — sulit dibedakan pengguna buta warna.
4. **Jangan mengandalkan warna saja.** Status harus punya label teks: "Terlambat", bukan hanya
   titik kuning.
5. Gradien hanya di: hero publik, panel presensi mobile, kepala profil mobile, kartu statistik
   publik. Tidak di tabel, tidak di form.

---

## 5. Tipografi

### 5.1 Keluarga font

```
Plus Jakarta Sans — 400, 600, 700, 800
```

Dimuat dari Google Fonts dengan `display=swap`. **Tidak ada font kedua.** Angka memakai font yang
sama (bukan monospace) — kecuali kode di artikel.

Fallback: `system-ui, -apple-system, sans-serif`.

### 5.2 Skala

| Peran | Ukuran | Weight | Line-height | Letter-spacing | Contoh |
|---|---|---|---|---|---|
| Hero publik | `clamp(38px, 4.3vw, 54px)` | 800 | 0.99 | -2.4px | judul beranda |
| Judul halaman | 21px | 800 | 1.25 | -0.2px | "Kelola Berita" |
| Judul bagian | 25px | 800 | 1.15 | -0.8px | heading seksi publik |
| Judul kartu | 14px | 800 | 1.4 | 0 | nama kartu dashboard |
| Teks isi | 14px | 400 | 1.5 | 0 | paragraf |
| Teks isi kecil | 13px | 400 | 1.55 | 0 | keterangan tabel |
| Label form | 12px | 700 | 1.4 | 0 | "Judul Berita" |
| Teks bantuan | 11.5px | 400 | 1.5 | 0 | "Maks. 2 MB" |
| Label kolom tabel | 10.5px | 800 | 1.4 | 0.07em | huruf kapital semua |
| Angka besar | 24px | 800 | 1.2 | -0.6px | "21" di kartu statistik |
| Angka sangat besar | 34–40px | 800 | 1.0 | -1.5px | jam presensi mobile |

**Aturan:** tidak ada teks di bawah **12px** untuk isi. 10.5px hanya boleh untuk label kolom tabel
(huruf kapital, tebal, pendek). Nilai lama 9px dan 10px di `style.css` harus dinaikkan.

### 5.3 Panjang baris

Maksimum **72 karakter** untuk paragraf. Di panel admin, teks panjang (isi DKA, isi berita) diberi
`max-width: 68ch`.

---

## 6. Jarak

Skala 4px. Tidak ada nilai di luar skala ini.

| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--sp-1` | 4px | jarak ikon–teks dalam badge |
| `--sp-2` | 8px | jarak antar tombol, padding badge |
| `--sp-3` | 12px | padding sel tabel horizontal, jarak antar field |
| `--sp-4` | 16px | jarak antar kartu, padding kartu kecil |
| `--sp-5` | 20px | padding kartu |
| `--sp-6` | 24px | padding halaman |
| `--sp-8` | 32px | jarak antar seksi |
| `--sp-10` | 40px | jarak seksi publik |
| `--sp-12` | 48px | padding hero |

**Jarak antar elemen form:** 15px vertikal. **Jarak antar grup form:** 26px.
**Padding sel tabel:** 12px vertikal, 16px horizontal.

---

## 7. Radius, border, bayangan

| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--r-sm` | 7px | pagination, kontrol kecil |
| `--r-md` | 9–10px | tombol, input, badge persegi |
| `--r-lg` | 16px | kartu, panel, modal |
| `--r-xl` | 22px | kepala profil mobile |
| `--r-full` | 999px | badge status, avatar, pill, switch |

| Border | Nilai |
|---|---|
| Standar | `1px solid var(--border)` |
| Fokus | `2px solid var(--green)` + `box-shadow: 0 0 0 3px rgba(21,128,61,.12)` |
| Galat | `1px solid var(--red)` |
| Putus-putus | `1.5px dashed #C7E3D2` — hanya untuk area unggah dan slot kosong |

| Bayangan | Nilai | Dipakai untuk |
|---|---|---|
| `--shadow-sm` | `0 2px 8px rgba(15,61,30,.06)` | kartu diam |
| `--shadow` | `0 12px 30px rgba(15,61,30,.10)` | modal, panel mengambang |
| Tombol utama | `0 4px 12px rgba(21,128,61,.22)` | hanya `btn-primary` |

Bayangan tidak dipakai untuk tabel, baris, atau teks. Kalau butuh pemisah, pakai border.

---

## 8. Layout — website publik

```
Container      : min(1100px, 100% - 40px)
Header         : tinggi 62px, sticky, blur 10px, latar putih 96%
Header mobile  : tinggi 58px, menu jadi panel absolut (≤700px)
Hero           : gradien putih → #F0FDF4 → #DCFCE7, padding 60px 0 48px
Grid program   : 4 kolom → 2 kolom (≤900px) → 1 kolom (≤480px)
Footer         : 4 kolom → 1 kolom (≤480px)
```

Lebar baca artikel: `max-width: 68ch`.

---

## 9. Layout — panel admin

```
Sidebar   : 262px tetap, latar --dark-green, sticky, tinggi 100vh, bisa digulir
Topbar    : 64px, sticky, latar putih, border bawah
Konten    : padding 24px 26px 44px, max-width 1320px
```

### 9.1 Sidebar

Grup menu (urutan tetap):

```
Utama              Beranda · Laporan
Konten Website     Berita · Galeri · Fasilitas · Halaman Statis · Menu Navigasi
Layanan            Pengguna & Role · Info & Dokumen PPDB
Akademik           Jadwal Pelajaran · Presensi Guru
Akreditasi IA2024  Dashboard Kesiapan · Bank Bukti · Editor DKA
Sistem             Pengaturan Situs
```

| Elemen | Spesifikasi |
|---|---|
| Label grup | 10px, 700, kapital, `letter-spacing .12em`, putih 40% |
| Item | 13px, 600, padding 9px 10px, radius 9px, putih 76% |
| Item aktif | latar `rgba(74,222,128,.16)`, teks putih, garis kiri 3px `--light-green` |
| Item hover | latar `rgba(255,255,255,.07)` |
| Badge jumlah | pil hijau muda, teks `#06331a`, 10px/800 |
| Ikon | 17×17px, `stroke-width 1.8`, `stroke: currentColor` |

### 9.2 Topbar

Urutan kiri→kanan: pencarian (maks 340px) · pengisi · notifikasi (titik merah) · bantuan ·
pemisah · avatar + nama + role.

### 9.3 Kepala halaman

```
Breadcrumb (12px, --slate) → H1 (21px/800) → deskripsi (13px, --gray)
Aksi di kanan: maksimal 2 tombol
```

### 9.4 Grid konten

| Pola | Kelas | Dipakai untuk |
|---|---|---|
| 4 kartu statistik | `.grid-4` | dashboard |
| 2 kolom | `.grid-2` | kartu sejajar |
| 3 kolom | `.grid-3` | kontak, email |
| Utama + samping | `.split` (1.62fr / 1fr) | tabel + panel bantuan |
| Form + samping | `.form-grid` (1.6fr / 1fr) | form + publikasi |

Semua grid runtuh ke 1 kolom di bawah 1100px.

---

## 10. Layout — aplikasi mobile

```
Bingkai HP     : 372 × 764 px (mockup), rasio 19.5:9
Layar          : 390 × 844 px (target perangkat)
Status bar     : 10.5px, padding 8px 20px 2px
Kepala aplikasi: padding 12px 18px 14px, latar putih
Isi            : padding 14px 16px, padding bawah 90px (ruang bottom nav)
Bottom nav     : 4 item, tinggi ±62px, sticky bawah
```

### 10.1 Bottom navigation

| Aturan | Ketentuan |
|---|---|
| Jumlah item | **tepat 4** — tidak 5, tidak 3 |
| Label | 9.5px/700, ikon 19px di atas |
| Aktif | `--green` |
| Tidak aktif | `--slate` |
| Isi per role | Guru: Beranda · Jadwal · Tugas · Profil. Siswa: Beranda · Jadwal · Tugas · Profil |
| Item tersembunyi | yang tidak berhak **tidak ditampilkan**, bukan dinonaktifkan |

### 10.2 Target sentuh

Minimum **44×44 px** untuk semua elemen yang bisa ditekan. Tombol utama aplikasi tinggi 46px.

---

## 11. Komponen

Setiap komponen di bawah ini **sudah ada** di `mockup.css` atau `style.css`. Nama kelas adalah
kontrak — jangan diganti tanpa memperbarui dokumen ini.

### 11.1 Tombol

| Varian | Kelas | Latar | Teks | Border | Dipakai untuk |
|---|---|---|---|---|---|
| Utama | `.btn .btn-primary` | `--green` | putih | — | satu aksi utama per layar |
| Garis | `.btn .btn-outline` | putih | `--green` | `#C7E3D2` | aksi sekunder |
| Tenang | `.btn .btn-ghost` | transparan | `--gray` | — | tautan "lihat semua" |
| Bahaya | `.btn .btn-danger` | `#FEF2F2` | `--red` | `#FBD5D5` | hapus, nonaktifkan |
| Kecil | `.btn-sm` | — | — | — | di dalam tabel, toolbar |
| Penuh | `.btn-block` | — | — | — | di dalam panel samping |

Ukuran: padding 9px 15px, radius 10px, 13px/700, ikon 15px.
Kondisi nonaktif: `opacity .45`, `cursor: not-allowed`, tanpa bayangan.
**Maksimum satu `btn-primary` per layar.**

### 11.2 Kartu

```html
<div class="card">
  <div class="card-head"><h3>Judul</h3><div class="actions">…</div></div>
  <div class="card-pad">…isi…</div>
</div>
```

Radius 16px, border `--border`, latar putih, `--shadow-sm`. Kepala kartu padding 15px 20px dengan
garis bawah. Isi padding 18px 20px.

### 11.3 Kartu statistik

```
Ikon 40×40 (radius 11px) · label 11.5px/600 · angka 24px/800 · tren 11px/700
Varian ikon: hijau (default), .amber, .blue, .red
```

Selalu empat dalam satu baris di dashboard. Angka tanpa pemisah ribuan kecuali ≥10.000.

### 11.4 Tabel

| Elemen | Spesifikasi |
|---|---|
| Kepala | 10.5px/800, kapital, `letter-spacing .07em`, latar `#FBFDFC` |
| Baris | 12.8px, padding 12px 16px, garis bawah `#F1F6F3` |
| Hover baris | latar `#FBFDFC` |
| Judul dalam sel | 700, dengan sub-teks 11px `--gray` |
| Aksi baris | 3 ikon maksimal, 29×29px |
| Gulir | `overflow-x: auto` — jangan pernah memotong kolom |

**Aturan isi:** kolom aksi selalu paling kanan. Kolom pertama tidak pernah berisi aksi.
Tanggal format `21 Okt 2026` — bukan `2026-10-21` di tampilan.

### 11.5 Toolbar tabel

Urutan: pencarian (ikon + input) · filter status · filter kategori · pengisi · urutan.
Tinggi 13px padding vertikal, garis bawah.

### 11.6 Pagination

`Menampilkan 1–8 dari 24 guru` di kiri, tombol halaman di kanan. Halaman aktif hijau penuh.
Tombol 30×30px, radius 7px.

### 11.7 Badge status

| Kelas | Warna | Contoh teks |
|---|---|---|
| `.pill.green` | hijau | Terbit, Hadir, Disetujui |
| `.pill.amber` | kuning | Terlambat, Menunggu, Segera berakhir |
| `.pill.red` | merah | Alpa, Kadaluarsa, Belum diisi |
| `.pill.blue` | biru | Izin, Informasi |
| `.pill.gray` | abu | Draft, Nonaktif |

Bentuk: pil, 11px/700, padding 3px 9px, dengan titik 5px di depan (kecuali `.pill.plain`).
**Selalu sertakan teks.** Titik saja tidak cukup.

### 11.8 Input dan form

```html
<div class="field">
  <label>Judul Berita <span class="req">*</span></label>
  <input type="text">
  <div class="hint">Maksimal 120 karakter.</div>
  <div class="err">Judul wajib diisi.</div>
</div>
```

| Elemen | Spesifikasi |
|---|---|
| Label | 12px/700, jarak bawah 6px, tanda wajib `*` merah |
| Input | padding 10px 12px, radius 9px, 13px, border `--border` |
| Fokus | border hijau + cincin 3px `rgba(21,128,61,.12)` |
| Galat | border merah + latar `#FFFCFC` + teks galat 11.5px/600 merah |
| Bantuan | 11.5px `--gray`, **di atas** pesan galat |
| Area teks | min-height 96px, `resize: vertical` |
| Dua kolom | `.field-row` (1fr 1fr), runtuh di mobile |

Aturan: galat muncul **di bawah** field yang salah, bukan dikumpulkan di atas form.
Isian lama dipertahankan saat validasi gagal.

### 11.9 Switch

36×20px, lintasan `#D8E7DE` → hijau saat aktif, tombol putih 16px. Selalu disertai label teks di
sebelah kiri, bukan di dalam.

### 11.10 Area unggah

```html
<div class="dropzone">
  <svg>…</svg>
  <b>Tarik berkas ke sini</b>
  <span>atau klik · PDF/DOCX/JPG · maks. 10 MB</span>
</div>
```

Garis putus-putus `#C7E3D2`, latar `#FBFDFC`, radius 11px. Wajib menyebut tipe dan ukuran maksimum.

### 11.11 Notifikasi inline (alert)

| Kelas | Warna | Dipakai untuk |
|---|---|---|
| `.notice.info` | biru | penjelasan, aturan sistem |
| `.notice.warn` | kuning | peringatan, butir kosong |
| `.notice.ok` | hijau | berhasil, tidak ada bentrok |

Ikon 16px + teks 12.5px. **Tidak boleh dipakai untuk galat form** — itu tugas `.field .err`.

### 11.12 Modal

```
Kepala  : 15px 18px, judul 13.5px/800, tombol tutup kanan
Isi     : 16px 18px
Kaki    : 13px 18px, latar #FBFDFC, tombol rata kanan
Lebar   : maks 440px (form), 480px (pemilihan)
Latar   : rgba(11,61,30,.34) + blur 2px
```

Tutup lewat: tombol ×, tombol Batal, tombol Esc, klik latar. **Jangan** tutup lewat klik latar
kalau ada isian yang belum disimpan tanpa konfirmasi.

### 11.13 Tab

Dua bentuk:
- **Tab garis** (`.tab-bar`) — di dalam kartu, garis bawah 2px hijau saat aktif. Untuk pengaturan.
- **Tab pil** (`.tab-row .tab`) — deretan pil bisa digulir horizontal. Untuk aplikasi mobile.

### 11.14 Breadcrumb

`Beranda / Konten Website / **Berita**`. 12px, `--slate` untuk yang bukan halaman aktif, `--gray`
600 untuk aktif. Pemisah `/`.

### 11.15 Keadaan kosong

Wajib ada. Isi tiga bagian:

1. Ikon garis 22px, `--slate`
2. Kalimat sebab — "Belum ada berita di kategori ini."
3. Satu aksi — tombol "+ Tambah Berita" atau tautan "Hapus filter"

**Dilarang:** halaman kosong tanpa penjelasan, atau hanya tulisan "No data".

### 11.16 Ring progres

`conic-gradient` dengan lubang putih 8px. Ukuran 66–84px. Angka di tengah 16px/800 + satuan 10px.
Dipakai untuk: progres akreditasi, progres penilaian, persentase presensi.

### 11.17 Bar progres

Tinggi 7px, radius 20px, lintasan `#EDF3EF`. Varian `.amber` dan `.red` untuk kondisi buruk.

### 11.18 Kartu sesi (mobile)

```
Waktu (62px, pil hijau muda) · Mapel + kelas + ruang · Status (pil kecil)
```

### 11.19 Nav bawah (mobile)

Lihat §10.1.

---

## 12. Keadaan komponen

Setiap komponen interaktif wajib punya 6 keadaan. Kalau tidak ada, dianggap belum selesai.

| Keadaan | Aturan |
|---|---|
| Normal | sesuai spesifikasi |
| Hover | perubahan ≤ 8% kecerahan, transisi 200ms |
| Fokus | cincin hijau 3px, **selalu terlihat** — jangan `outline: none` tanpa pengganti |
| Aktif | turun 1px atau lebih gelap |
| Nonaktif | `opacity .45`, kursor `not-allowed`, tidak bisa difokus |
| Memuat | tombol: teks diganti "Menyimpan…" + spinner 14px, tombol dinonaktifkan |

### 12.1 Keadaan halaman

| Keadaan | Tampilan |
|---|---|
| Memuat | kerangka abu-abu (skeleton) berbentuk kartu — bukan spinner di tengah layar |
| Kosong | lihat §11.15 |
| Galat | `.notice.warn` dengan sebab + tombol "Coba lagi" |
| Tanpa akses | halaman 403 dengan tombol "Kembali ke Beranda" |
| Tidak ditemukan | halaman 404, bukan pengalihan diam-diam |

---

## 13. Aturan penyajian data

| Data | Format | Contoh |
|---|---|---|
| Tanggal panjang | `Kamis, 22 Oktober 2026` | kepala halaman |
| Tanggal pendek | `21 Okt 2026` | kolom tabel |
| Tanggal + waktu | `21 Okt 2026, 14.32` | riwayat |
| Jam | `07.00` (titik, bukan titik dua) | jadwal, presensi |
| Rentang jam | `07.00 – 08.20` | jadwal |
| Angka ribuan | titik: `1.248` | statistik |
| Persen | `87,5%` (koma) | statistik |
| Nilai | bilangan bulat 0–100, tanpa desimal | penilaian |
| Berkas | `1,8 MB` (koma) | dokumen PPDB |
| Nama orang | nama lengkap tanpa gelar di tabel; gelar di detail | — |
| Nama guru | `Ust. Ahmad Fauzi` di tampilan publik, `Ahmad Fauzi` di admin | — |

Zona waktu: **WIB**, dari jam server. Tidak pernah dari jam perangkat.

---

## 14. Aksesibilitas

Target: **WCAG 2.1 Level AA**.

| Butir | Ketentuan | Status sekarang |
|---|---|---|
| Kontras teks | ≥ 4,5:1 normal, ≥ 3:1 besar | ✓ semua token lulus kecuali `--slate` (2,56:1) |
| `--slate` sebagai teks | dilarang | ✗ dipakai di breadcrumb, `time`, chevron, nav bawah (mockup) — **perbaiki** |
| Ukuran teks minimum | 12px | ✗ **29 dari 45 deklarasi di `style.css` di bawah 12px** — **perbaiki** |
| Target sentuh | ≥ 44×44px | ✓ tombol aplikasi 46px, ✗ ikon tabel admin 29px — **perbaiki di mobile** |
| Fokus terlihat | wajib di semua elemen | perlu audit |
| Label form | setiap input punya `<label for>` | ✓ di mockup |
| Teks alternatif | semua `<img>` punya `alt` | ✓ di mockup, perlu audit di view lama |
| Bahasa halaman | `<html lang="id">` | ✓ |
| Status tanpa warna | label teks wajib | ✓ |
| Navigasi keyboard | urutan logis, tanpa jebakan | perlu uji |
| Zoom 200% | tidak ada teks terpotong | perlu uji |

**Aturan tegas:** jangan pernah menghapus `outline` tanpa memberi pengganti yang terlihat.

---

## 15. Responsif

| Titik henti | Perubahan |
|---|---|
| **1280px** | panel DKA 3 kolom → 1 kolom |
| **1100px** | `.split`, `.form-grid`, `.grid-3`, `.grid-4` → 1 kolom; sidebar tetap |
| **900px** | grid program publik 4 → 2 kolom |
| **700px** | header publik 62 → 58px, menu jadi panel absolut |
| **560px** | hero dalam 60 → 40px, `.info-grid` → 1 kolom |
| **480px** | container `100% - 28px`, `.nav-cta` disembunyikan, footer 1 kolom |

Panel admin **tidak** dirancang untuk ponsel. Di bawah 900px, sidebar menjadi laci yang dibuka
lewat tombol — bukan dikecilkan menjadi ikon saja.

---

## 16. Ikonografi

| Aturan | Ketentuan |
|---|---|
| Gaya | garis (outline), **bukan** isi penuh |
| Grid | 24×24, padding dalam 2px |
| Tebal garis | 1.8 (biasa), 2 (aktif), 2.6 (penanda centang) |
| Ujung garis | `round` |
| Warna | `stroke: currentColor` — ikut warna teks |
| Ukuran tampil | 14, 15, 17, 19, 20, 22px |
| Sumber | set ikon internal di `mockup.js` (30 ikon), disimpan sebagai SVG inline |

Tidak memakai font ikon, tidak memakai emoji di antarmuka. Emoji hanya boleh di dokumen.

Daftar ikon yang sudah tersedia: home, news, image, building, page, menu, users, download,
calendar, book, check, award, settings, search, bell, plus, edit, trash, eye, clock, upload,
chart, link, info, warn, logout, lock, grid, task.

---

## 17. Gerak

| Konteks | Durasi | Fungsi |
|---|---|---|
| Hover tombol/tautan | 200ms | `ease` |
| Buka/tutup modal | 180ms | `ease-out` |
| Ganti tab | 150ms | `ease` |
| Kartu naik saat hover | 150ms | `translateY(-1px)` |
| Notifikasi masuk | 250ms | `ease-out`, geser 8px |

**Dilarang:** animasi berulang tanpa henti, parallax, animasi pada tabel, transisi > 300ms.
Semua animasi harus dimatikan kalau `prefers-reduced-motion: reduce`.

---

## 18. Daftar layar

23 layar, semuanya sudah ada sebagai mockup di `blueprint-madrasahku/mockup/`.

### 18.1 Website publik (sudah ada di kode)

| Halaman | Berkas view |
|---|---|
| Beranda | `app/Views/home/index.php` |
| Profil | `app/Views/profil/index.php` |
| Akademik | `app/Views/akademik/index.php` |
| Fasilitas | `app/Views/fasilitas/index.php` |
| Galeri | `app/Views/galeri/index.php` |
| Berita (daftar) | `app/Views/berita/index.php` |
| Berita (detail) | `app/Views/berita/detail.php` |
| PPDB | `app/Views/ppdb/index.php` |
| Kontak | `app/Views/kontak/index.php` |
| 404 | `app/Views/errors/404.php` |

### 18.2 Panel admin (mockup)

| No | Layar | Berkas |
|---|---|---|
| A-1 | Login | `admin-login.html` |
| A-2 | Dashboard | `admin-dashboard.html` |
| A-3 | Kelola Berita | `admin-berita.html` |
| A-4 | Form Berita | `admin-berita-form.html` |
| A-5 | Pengguna & Role | `admin-pengguna.html` |
| A-6 | Info & Dokumen PPDB | `admin-ppdb.html` |
| A-7 | Jadwal Pelajaran | `admin-jadwal.html` |
| A-8 | Presensi Guru | `admin-presensi.html` |
| A-9 | Akreditasi — Dashboard | `admin-akreditasi-dashboard.html` |
| A-10 | Akreditasi — Bank Bukti | `admin-akreditasi-bukti.html` |
| A-11 | Akreditasi — Editor DKA | `admin-akreditasi-dka.html` |
| A-12 | Pengaturan Situs | `admin-pengaturan.html` |

### 18.3 Aplikasi mobile (mockup)

| No | Layar | Anchor |
|---|---|---|
| C-1 | Login | `mobile.html#login` |
| C-2 | Dashboard Guru | `mobile.html#dashboard-guru` |
| C-3 | Presensi / Check-in | `mobile.html#checkin` |
| C-4 | Riwayat Presensi | `mobile.html#riwayat` |
| C-5 | Jadwal Pelajaran | `mobile.html#jadwal` |
| C-6 | Materi | `mobile.html#materi` |
| C-7 | Tugas Siswa | `mobile.html#tugas` |
| C-8 | Penilaian (Guru) | `mobile.html#nilai-guru` |
| C-9 | Nilai & Feedback (Siswa) | `mobile.html#nilai` |
| C-10 | Notifikasi | `mobile.html#notifikasi` |
| C-11 | Profil & Menu | `mobile.html#profil` |

### 18.4 Layar yang belum punya mockup

Perlu dibuat sebelum coding modul terkait:

- Galeri (admin) — pola tabel sama dengan berita
- Fasilitas (admin) — pola tabel
- Halaman Statis (admin) — pola form
- Menu Navigasi (admin) — perlu pola urutan seret-lepas
- Laporan (admin) — belum dirancang sama sekali
- Presensi: koreksi massal (admin)
- Akreditasi: butir detail dengan indikator (admin)
- Akreditasi: hasil & tindak lanjut (admin)
- Halaman publik: detail galeri, detail fasilitas

---

## 19. Struktur berkas CSS

Sasaran akhir (belum dikerjakan):

```
public/css/
  tokens.css        # :root — satu-satunya tempat nilai mentah
  base.css          # reset, tipografi, elemen dasar
  components.css    # tombol, kartu, badge, tabel, form, modal, tab
  public.css        # navbar, hero, program, footer, halaman dalam
  admin.css         # sidebar, topbar, konten, pola tabel/form admin
```

Aturan:

1. **Tidak ada nilai warna/ukuran mentah** di luar `tokens.css`. Kalau butuh `#15803D`, pakai
   `var(--green)`.
2. Penamaan kelas: `.blok-elemen-keadaan` — `.card`, `.card-head`, `.btn-primary`, `.pill-amber`.
   Bukan `.greenButton` atau `.kotak1`.
3. Tidak ada `!important`. Kalau terpaksa, berarti strukturnya salah.
4. Tidak ada CSS inline kecuali untuk nilai dinamis (lebar progres, persentase ring).
5. Kedalaman selektor maksimal 2 tingkat.

Saat ini semua aturan publik masih di satu berkas `style.css` (757 baris). Pemecahan dilakukan
bersamaan dengan pembuatan panel admin, bukan sebelum — supaya tidak ada dua kali kerja.

---

## 20. Serah terima ke tim mobile

Tim Flutter memakai token yang sama. Yang harus diserahkan:

| Berkas | Isi |
|---|---|
| `tokens.json` | warna, tipografi, jarak, radius — **belum dibuat** |
| `DESIGN.md` (dokumen ini) | aturan & pola |
| `blueprint-madrasahku/mockup/mobile.html` | acuan visual 11 layar |

### 20.1 Padanan token Flutter

```dart
// lib/theme/tokens.dart
class AppColors {
  static const green      = Color(0xFF15803D);
  static const darkGreen  = Color(0xFF0B3D1E);
  static const lightGreen = Color(0xFF4ADE80);
  static const mint       = Color(0xFFA7F3D0);
  static const text       = Color(0xFF111827);
  static const gray       = Color(0xFF6B7280);
  static const slate      = Color(0xFF94A3B8);
  static const border     = Color(0xFFE1ECE5);
  static const soft       = Color(0xFFF9FAFB);
  static const amber      = Color(0xFFB45309);
  static const red        = Color(0xFFDC2626);
  static const blue       = Color(0xFF1D4ED8);
}

class AppRadius {
  static const sm   = 7.0;
  static const md   = 10.0;
  static const lg   = 16.0;
  static const xl   = 22.0;
  static const full = 999.0;
}

class AppSpace {
  static const s1 = 4.0;
  static const s2 = 8.0;
  static const s3 = 12.0;
  static const s4 = 16.0;
  static const s5 = 20.0;
  static const s6 = 24.0;
  static const s8 = 32.0;
}
```

Tipografi Flutter memakai Plus Jakarta Sans dari `pubspec.yaml`, bobot 400/600/700/800.

### 20.2 Aturan yang tidak boleh dilanggar tim mobile

1. Tidak ada warna di luar tabel §4.1 dan §4.2.
2. Radius mengikuti §7, bukan default Material.
3. Bottom navigation tepat 4 item.
4. Presensi memakai jam server, bukan `DateTime.now()` perangkat.
5. Tidak ada tombol "Daftar" — akun dibuat operator madrasah.
6. Tidak ada notifikasi push pihak ketiga.
7. Nama guru/siswa di aplikasi tidak boleh tampil di halaman publik.

---

## 21. Tata kelola

| Hal | Aturan |
|---|---|
| Mengubah token | harus lewat dokumen ini + persetujuan mitra |
| Menambah komponen | tulis spesifikasinya di §11 dulu, baru coding |
| Menyimpang dari dokumen | catat di §22 dengan alasan |
| Tinjauan | setiap akhir batch pengembangan |

### Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 22 Okt 2026 | Versi awal, diturunkan dari `style.css` + `mockup.css` |

---

## 22. Penyimpangan yang tercatat

| No | Penyimpangan | Alasan | Status |
|---|---|---|---|
| S-1 | `style.css` memakai radius 6/9/15px, dokumen menetapkan 10/16px | kode lama, belum diseragamkan | menunggu keputusan D-1 |
| S-2 | **29 dari 45 deklarasi `font-size` di `style.css` < 12px** (7–11px) | kode lama | menunggu keputusan D-2, est. 3–4 jam |
| S-3 | `--slate` (2,56:1) dipakai sebagai warna teks: `.breadcrumb`, `.notif-row time`, `.menu-item .chev`, `.bottom-nav a`, `.screen-card .no`, `.modal-mock .x` | kode lama + mockup, gagal kontras AA | perlu diperbaiki → ganti `--gray` |
| S-4 | Panel admin belum ada CSS produksi, masih di `mockup.css` | tahap dummy | dikerjakan bersama panel admin |
| S-5 | Belum ada `tokens.json` untuk tim mobile | menunggu D-1 & D-2 final | — |
| S-6 | Belum ada mockup untuk 9 layar (§18.4) | tahap dummy, prioritas berbeda | dibuat sebelum modul terkait |

---

## 23. Ringkasan satu halaman

```
Warna      hijau #15803D · gelap #0B3D1E · teks #111827 · abu #6B7280
Font       Plus Jakarta Sans 400/600/700/800 · isi 14px · minimum 12px
Radius     kartu 16px · kontrol 10px · pil penuh
Jarak      4/8/12/16/20/24/32/40/48
Container  publik 1100px · admin 1320px · sidebar 262px
Tombol     1 utama per layar · nonaktif opacity .45
Tabel      kepala kapital 10.5px · baris 12.8px · aksi paling kanan
Status     selalu teks + warna, tidak warna saja
Kosong     sebab + satu aksi, tidak pernah halaman kosong
Kontras    AA minimum · --slate tidak untuk teks
Mobile     bottom nav 4 item · target sentuh 44px · jam server
```
