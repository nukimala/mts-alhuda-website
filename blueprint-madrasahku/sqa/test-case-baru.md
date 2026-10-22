# Usulan Test Case Baru

Total: **41 test case**. Semua status awal: `Not Executed`.

| ID | Modul | Skenario | Expected | Prioritas |
|---|---|---|---|---|
| TC-AUTH-01 | Auth | Admin membuat akun siswa | User tersimpan di MySQL dengan hash password, `must_change_password=1`, tanpa sinkronisasi ke layanan luar | High |
| TC-AUTH-02 | Auth | Admin membuat akun guru | Role guru tersimpan; akun yang sama bisa dipakai login web dan mobile | High |
| TC-AUTH-03 | Auth | Email/username duplikat | Ditolak dengan pesan jelas | High |
| TC-AUTH-04 | Auth | Login pertama | Diarahkan wajib ganti password sebelum akses menu | High |
| TC-AUTH-05 | Auth | Akun dinonaktifkan | API menolak request dan seluruh token user dicabut | Critical |
| TC-AUTH-06 | Auth | Reset password admin/email | Password berubah, token reset hangus setelah dipakai, token API dicabut | High |
| TC-ATT-01 | Presensi Guru | Check-in harian tepat waktu | Record `hadir` tersimpan dengan `check_in_at` dari jam server | Critical |
| TC-ATT-02 | Presensi Guru | Check-in di luar toleransi | Record `terlambat` tersimpan dan tampil di rekap | High |
| TC-ATT-03 | Presensi Guru | Check-in ganda tanggal/jadwal sama | Ditolak, tidak membuat duplikat | High |
| TC-ATT-04 | Presensi Guru | Check-in di luar jadwal | Ditolak dengan pesan batas waktu | High |
| TC-ATT-05 | Presensi Guru | Check-in jadwal milik guru lain | Ditolak | High |
| TC-ATT-06 | Presensi Guru | Role siswa akses endpoint presensi | Ditolak HTTP 403 | Critical |
| TC-ATT-07 | Presensi Guru | Koreksi manual oleh operator/kepala | Status berubah, tercatat di `activity_logs`, rekap benar | Medium |
| TC-AKAD-01 | Akademik | CRUD kelas/mapel/jadwal | Data tersimpan dan tampil benar | High |
| TC-AKAD-02 | Akademik | Jadwal bentrok | Ditolak dengan pesan konflik | High |
| TC-AKAD-03 | Akademik | Upload materi | File valid tersimpan privat; file besar/tipe salah ditolak | High |
| TC-AKAD-04 | Akademik | Nilai tugas | Guru menilai; siswa hanya melihat nilai sendiri | Critical |
| TC-AKR-01 | Akreditasi | Seed IA2024 | 4 komponen, 14 butir, 59 indikator tersalin | High |
| TC-AKR-02 | Akreditasi | Upload bukti | Bukti valid tersimpan; tipe/ukuran salah ditolak | High |
| TC-AKR-03 | Akreditasi | Mapping bukti ke butir | Satu bukti dapat dipetakan ke beberapa butir | High |
| TC-AKR-04 | Akreditasi | Isi DKA | Maksimal 1.000 karakter; lebih dari itu ditolak/divalidasi | High |
| TC-AKR-05 | Akreditasi | Review butir | Status `review`/`revisi`/`selesai` berubah sesuai aksi | Medium |
| TC-AKR-06 | Akreditasi | Dashboard gap | Butir tanpa bukti/DKA tampil sebagai gap | High |
| TC-AKR-07 | Akreditasi | Ekspor | Ringkasan/DKA diekspor tanpa mengubah data; role dicek | High |
| TC-PPDB-01 | PPDB | Halaman info | Jadwal, syarat, alur, dan kontak tampil lengkap | High |
| TC-PPDB-02 | PPDB | Tanpa form | Tidak ada form pendaftaran; hanya unduhan dan kontak | High |
| TC-PPDB-03 | PPDB | Dokumen | Dokumen aktif tampil dan bisa diunduh; dokumen nonaktif 404 | High |
| TC-PPDB-04 | PPDB | Kelola dokumen | Admin upload/ganti dokumen; tipe/ukuran salah ditolak | High |
| TC-PUB-01 | Publik | CRUD berita | Berita tampil di halaman publik | Medium |
| TC-PUB-02 | Publik | Upload galeri | Gambar tampil, file invalid ditolak | Medium |
| TC-PUB-03 | Publik | Form kontak | Validasi dan sanitasi berjalan | Medium |
| TC-API-01 | API | Login token valid | `/auth/me` mengembalikan profil dan menu sesuai role | Critical |
| TC-API-02 | API | Token invalid/kedaluwarsa | Respons `401` | Critical |
| TC-API-03 | API | Pagination/filter | Data jadwal/tugas terpaginasi benar | Medium |
| TC-API-04 | API | Refresh token | Token lama dicabut dan token baru diterbitkan (rotasi) | High |
| TC-API-05 | API | Logout | Token dicabut, request berikutnya `401` | High |
| TC-NTF-01 | Notifikasi | Pengumuman admin | Notifikasi tersimpan di `notifications`, tampil di aplikasi, bisa ditandai terbaca | Medium |
| TC-SEC-01 | Keamanan | CSRF | Request tanpa token ditolak | High |
| TC-SEC-02 | Keamanan | IDOR/lintas role | Siswa/guru tidak bisa mengakses data role lain | Critical |
| TC-NFR-01 | Non-fungsional | Lighthouse | Performance ≥80, accessibility ≥90 | Medium |
| TC-NFR-02 | Non-fungsional | Performa API/check-in | API p95 ≤1 detik; check-in success ≥98% | Medium |

## Catatan eksekusi

- Isi kolom status, evidence, dan defect ID pada salinan kerja.
- Test case Critical/High harus lulus sebelum UAT.
- Test case Medium boleh ditunda dengan persetujuan.
- Setiap defect harus punya langkah reproduksi.
- PPDB diuji sebagai halaman informasi + unduhan, bukan alur pendaftaran.
- Presensi diuji untuk role guru saja; endpoint presensi harus menolak role lain.
- Autentikasi diuji tanpa penyedia identitas eksternal: satu tabel `users`, web memakai session, mobile memakai API token.
