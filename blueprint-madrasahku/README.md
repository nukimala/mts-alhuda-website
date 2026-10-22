# Blueprint MadrasahKu

Folder ini berisi rancangan proyek **Website MTs Al-Huda + Aplikasi MADRASAHKU** setelah konsep berubah dari sekadar landing page menjadi pusat dokumentasi mutu dan akreditasi.

Status dokumen: **v0.5 — 2026-10-22**

## Keputusan utama

| Hal | Keputusan |
|---|---|
| Fokus | Gabungan: publik, akademik, PPDB, akreditasi |
| Web | Custom PHP MVC + MySQL/PDO |
| Mobile | Flutter, dikerjakan tim terpisah |
| Firebase | Tidak dipakai sama sekali |
| Auth | Satu tabel `users`; web session PHP, mobile API token |
| Notifikasi | In-app center + email SMTP, tanpa push real-time |
| Sumber data | MySQL |
| Akun | Dibuat admin/operator, tanpa registrasi mandiri |
| Presensi | Hanya kehadiran guru; check-in via aplikasi/operator |
| PPDB | Tanpa form; hanya informasi + dokumen unduhan |
| Reset password | Admin reset dan tautan email |
| Nilai | Hanya nilai/feedback tugas |
| SQA | Revisi 3 dokumen, reset hasil lama, uji semua modul |
| Lingkungan uji | Lokal + staging |

## Struktur folder

```
blueprint-madrasahku/
├── README.md
├── 00-GAMBARAN-SISTEM-UNTUK-MITRA.md -> gambaran sistem bahasa awam untuk pimpinan/guru
├── 00-GAMBARAN-SISTEM-UNTUK-MITRA.docx -> versi siap kirim (non-teknis)
├── 00-DOKUMEN-PERSETUJUAN-MITRA.md -> pengantar + checklist persetujuan untuk mitra
├── 00-DOKUMEN-PERSETUJUAN-MITRA.docx -> versi siap kirim (dokumen formal)
├── 01-arsitektur.md
├── 02-alur-autentikasi.md
├── 03-alur-presensi-guru.md
├── 04-alur-akreditasi.md
├── 05-alur-ppdb.md
├── 06-erd.md
├── 07-lingkungan-dan-strategi-sqa.md
├── 08-rencana-implementasi.md
├── 09-keputusan.md
├── 10-perombakan-web.md
├── PRD.md               -> Product Requirement Document lengkap
├── DIAGRAM.md           -> semua diagram Mermaid
├── diagrams/
│   └── export/      -> hasil ekspor PNG/SVG
├── tools/
│   └── md2docx.py   -> konverter Markdown ke DOCX (butuh pip install python-docx)
└── sqa/
    ├── README.md
    ├── checklist-test-plan.md
    ├── checklist-test-scenario.md
    ├── checklist-laporan.md
    ├── test-case-baru.md
    └── kpi.md
```

## Daftar diagram

| ID | Diagram | Format | Dokumen |
|---|---|---|---|
| D-01 | Arsitektur hybrid | Mermaid | Blueprint, SQA Report |
| D-02 | Alur autentikasi | Mermaid | Blueprint, Test Scenario |
| D-03 | Alur presensi guru | Mermaid | Blueprint, Test Scenario |
| D-04 | Alur akreditasi | Mermaid | Blueprint, SQA Report |
| D-05 | Alur info + dokumen PPDB | Mermaid | Blueprint, Test Scenario |
| D-06 | ERD | Mermaid | Blueprint, SQA Report |
| D-07 | Use case | Mermaid | SQA Report |
| D-08 | Topologi lingkungan uji | Mermaid | Test Plan |
| D-09 | Piramida pengujian | Mermaid | Test Plan |
| D-10 | Alur bug triage | Mermaid | Test Plan |
| D-11 | Alur SDLC & QA | Mermaid | SQA Report |
| D-12 | Deployment/go-live | Mermaid | SQA Report |

Semua diagram ada di `DIAGRAM.md`.

## Dokumen PRD (Product Requirement Document)

Untuk spesifikasi produk lengkap, standar fungsional/non-fungsional, skema domain data, dan batasan cakupan sistem secara resmi, baca `PRD.md`.

## Dokumen gambaran awam (D-00A)

Untuk pimpinan madrasah, yayasan, dan guru yang bukan teknis, baca `00-GAMBARAN-SISTEM-UNTUK-MITRA.md` (dan versi `.docx` siap kirim). Isinya gambaran tampilan website dan aplikasi HP, cara pakai per role (guru absen, siswa lihat tugas/nilai, operator kelola berita), pusat kendali akreditasi IA2024, tabel perbandingan sistem lama vs baru, FAQ, dan lembar persetujuan singkat — semuanya tanpa jargon teknis.

## Dokumen persetujuan mitra (D-00)

Untuk dikirim ke mitra, baca `00-DOKUMEN-PERSETUJUAN-MITRA.md`. Isinya ringkasan seluruh rancangan (arsitektur, design system, model data, kontrak API, alur kunci, kriteria penerimaan), daftar kebutuhan dari mitra, 10 pertanyaan terbuka, checklist persetujuan 17 butir, dan blok tanda tangan. Dokumen ini menjadi baseline v1.0; setiap perubahan setelah disetujui dicatat di `09-keputusan.md`.

Versi siap kirim: `00-DOKUMEN-PERSETUJUAN-MITRA.docx` (A4, halaman sampul terpisah, heading bernomor, tabel header hijau, footer nomor halaman). Untuk membuat ulang atau mengonversi dokumen lain:

```
python tools/md2docx.py PRD.md PRD.docx
```

## Dokumen khusus web

Untuk brainstorming perombakan website, baca `10-perombakan-web.md`. Dokumen itu berisi audit kondisi saat ini, sitemap, struktur folder, tabel CMS, UX admin, keamanan, rencana migrasi, dan prompt siap pakai untuk platform lain.

## Dummy UI untuk review mitra

Tampilan contoh ada di folder `mockup/` di dalam folder ini. Isinya 12 layar panel admin web (login, dashboard, berita, form berita, pengguna, PPDB, jadwal, presensi guru, akreditasi, pengaturan) dan 11 layar aplikasi mobile MADRASAHKU dalam bentuk bingkai HP.

- Buka langsung berkas `mockup/index.html` di browser. Folder ini tidak disajikan lewat web server.
- Gambar diambil dari `../public/assets/images/`, jadi `blueprint-madrasahku/` harus tetap berada di dalam proyek.
- Token desain mengikuti `public/css/style.css` (hijau `#15803D`, Plus Jakarta Sans, radius 16px).
- Semua data di dalamnya fiktif; tombol hanya berpindah antar layar, belum tersambung ke database.

Dipakai untuk memastikan mitra melihat bentuk akhir sistem **sebelum** masuk tahap coding, sehingga revisi tampilan tidak terjadi di tengah implementasi.

## Rencana eksekusi database

Untuk skema database (39–40 tabel, 6 domain, 7 batch migrasi, keputusan teknis yang harus disetujui, dan estimasi waktu), baca `11-rencana-eksekusi-database.md`. Dokumen itu **belum dieksekusi** — isinya rencana yang menunggu persetujuan.

## Sistem desain

Untuk token warna, tipografi, jarak, radius, spesifikasi komponen, aturan aksesibilitas, dan serah terima ke tim mobile, baca `../DESIGN.md` di root proyek. Dokumen itu diturunkan dari `public/css/style.css` dan `mockup/assets/mockup.css`, jadi isinya sesuai kode yang benar-benar ada — bukan rencana.

## Cara memakai

- Buka `DIAGRAM.md` di VS Code dan aktifkan preview Mermaid.
- Atau tempel blok Mermaid ke https://mermaid.live.
- Ekspor diagram untuk laporan SQA ke `diagrams/export/` dalam format PNG 300 dpi atau SVG.
- Jangan menimpa PDF SQA lama; arsipkan sebagai `v0.1` dan buat dokumen revisi baru.

## Prinsip yang tidak boleh dilanggar

1. MySQL adalah sumber kebenaran data akademik dan akreditasi.
2. Tidak ada Firebase, penyedia identitas eksternal, atau layanan push pihak ketiga untuk autentikasi, notifikasi, atau penyimpanan data; email transaksional memakai SMTP tanpa menyimpan data identitas.
3. Web dan mobile memakai satu sumber identitas (tabel `users`); mobile memakai API token milik backend.
4. Tidak ada registrasi mandiri; akun dibuat admin/operator.
5. Presensi hanya mencatat kehadiran guru; tidak ada presensi siswa atau kartu QR siswa.
6. Data pribadi siswa tidak boleh terbuka untuk publik.
7. PPDB tanpa form; hanya informasi dan dokumen yang bisa diunduh.
8. Semua status hasil SQA lama direset sebelum pengujian build baru.
