# Decision Log

| ID | Keputusan | Alasan | Dampak |
|---|---|---|---|
| K-01 | Scope gabungan publik + akademik + akreditasi | Kebutuhan utama madrasah | Proyek lebih besar, perlu scope guard |
| K-02 | Web custom PHP MVC + MySQL/PDO | Sesuai codebase yang ada | Tidak ada rewrite framework |
| K-03 | Mobile Flutter terpisah | Tim dan teknologi berbeda | Perlu kontrak API |
| K-04 | Tanpa Firebase/penyedia identitas eksternal | Satu sistem identitas sendiri, tanpa service account dan tanpa lock-in vendor | Autentikasi dan notifikasi diimplementasikan backend PHP (session web + API token + tabel `notifications` + SMTP untuk email saja) |
| K-05 | MySQL sumber kebenaran | Konsistensi data | API wajib cek MySQL |
| K-06 | Akun dibuat admin/operator | Kontrol dan keamanan | Tidak ada registrasi mandiri |
| K-07 | Presensi hanya untuk guru | Fokus disiplin guru dan bukti IA2024; menyederhanakan scope <1 bulan | Tidak ada presensi siswa, kartu QR siswa, atau sesi kelas; data masuk ke `teacher_attendances` |
| K-08 | Reset password admin + email | Fleksibilitas operasional | Perlu SMTP dan token reset |
| K-09 | Nilai hanya tugas | Batasi kompleksitas | Tidak ada rapor lengkap v1 |
| K-10 | IA2024 sebagai instrumen | Acuan resmi | Seed 4 komponen/14 butir/59 indikator |
| K-11 | Aplikasi tidak menggantikan Sispena | Sispena tetap resmi | Fokus ekspor dan kesiapan |
| K-12 | Revisi 3 dokumen SQA, reset hasil | Hasil lama tidak relevan | Uji ulang semua modul |
| K-13 | Lokal + staging | Bukti uji lebih valid | Perlu biaya/akses staging |
| K-14 | Semua diagram Mermaid dalam satu file `DIAGRAM.md` | Mudah dirender dan direvisi | Tidak ada file diagram terpisah |
| K-15 | Folder baru `blueprint-madrasahku` | Memisahkan rancangan dari kode | Tidak mengganggu aplikasi lama |
| K-16 | PPDB tanpa form, hanya info + dokumen unduhan | Pendaftaran tetap manual/offline | Tidak ada tabel pendaftaran, verifikasi, atau cek status |
| K-17 | Notifikasi in-app + email, tanpa push real-time | Tidak ada ketergantungan pada layanan push eksternal (FCM/OneSignal); email cukup melalui SMTP | Guru/siswa melihat notifikasi saat aplikasi aktif; push bisa ditambahkan nanti tanpa mengubah skema |
| K-18 | Baseline persetujuan mitra dicatat di `00-DOKUMEN-PERSETUJUAN-MITRA.md` v1.0 | Mitteruskan seluruh rancangan dalam satu dokumen pengantar yang dapat ditandatangani | Perubahan setelah disetujui wajib dicatat di §17 dokumen tersebut dan di log ini |

## Pertanyaan terbuka

- Sumber Word/Google Docs asli untuk 3 dokumen SQA?
- Server SMTP mana yang dipakai untuk reset password dan pengumuman email (SMTP hosting/Gmail Workspace atau mail server sendiri)?
- Apakah server staging sudah tersedia?
- Siapa pelaksana UAT dan kapan jadwalnya?
- Apakah tim mobile memakai repo yang sama atau repo terpisah?
- Apakah data guru/siswa akan diimpor dari Excel atau diinput manual?
- Apakah check-in guru cukup lewat tombol aplikasi, atau perlu validasi lokasi/QR statis?
- Berapa batas toleransi keterlambatan guru dan jam masuk resmi madrasah?
- Dokumen PPDB apa saja yang wajib tersedia dan siapa penanggung jawab pembaruannya?
- Apakah perlu statistik jumlah unduhan dokumen PPDB?
