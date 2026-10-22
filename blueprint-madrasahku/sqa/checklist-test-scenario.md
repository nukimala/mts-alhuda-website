# Checklist Revisi Test Scenario dan Test Case

Target versi: **v2**

## Pembersihan scope lama

- [ ] Hapus/arsipkan skenario registrasi mandiri.
- [ ] Hapus asumsi Firebase/Firestore untuk data akademik dan autentikasi.
- [ ] Hapus asumsi web statis tanpa database.
- [ ] Tandai test case lama yang tidak relevan sebagai `Out of Scope`.
- [ ] Reset semua status eksekusi menjadi `Not Executed`.

## Aktor dan role

- [ ] Publik.
- [ ] Siswa.
- [ ] Guru.
- [ ] Operator.
- [ ] Admin.
- [ ] Kepala Madrasah.
- [ ] Tim Akreditasi.

## Modul dan skenario

- [ ] Publik/CMS.
- [ ] Autentikasi dan pembuatan akun.
- [ ] Akademik.
- [ ] Presensi guru.
- [ ] Akreditasi.
- [ ] PPDB info + dokumen unduhan.
- [ ] API mobile.
- [ ] Keamanan/role.
- [ ] Non-fungsional ringan.

## Aturan khusus

- [ ] Akun hanya dibuat admin/operator; tidak ada registrasi mandiri.
- [ ] Login pertama wajib ganti password.
- [ ] Akun nonaktif langsung ditolak API dan tokennya dicabut.
- [ ] Token API: access token kedaluwarsa ditolak, refresh token merotasi token lama, logout mencabut token.
- [ ] Password sama untuk web dan mobile (satu tabel `users`).
- [ ] Hanya guru yang punya presensi; tidak ada presensi siswa atau kartu QR siswa.
- [ ] Check-in ganda pada tanggal/jadwal yang sama ditolak.
- [ ] Check-in di luar jadwal atau toleransi ditolak.
- [ ] Role selain guru ditolak (403).
- [ ] Nilai hanya untuk tugas.
- [ ] Bukti akreditasi disimpan privat.
- [ ] DKA maksimal 1.000 karakter.
- [ ] Ekspor akreditasi tidak mengubah data.
- [ ] Notifikasi in-app tersimpan di tabel `notifications` dan bisa ditandai terbaca; email reset password hanya berlaku sekali.
- [ ] PPDB tanpa form; hanya info dan dokumen yang bisa diunduh.

## Traceability

- [ ] Setiap requirement punya minimal satu test case.
- [ ] RTM diperbarui dengan ID baru.
- [ ] Test case baru ditambahkan dari `test-case-baru.md`.
- [ ] Kolom status, evidence, defect ID ditambahkan.
- [ ] Tidak ada test case tanpa expected result.

## Struktur test case

- [ ] ID unik.
- [ ] Judul.
- [ ] Precondition.
- [ ] Langkah.
- [ ] Expected result.
- [ ] Priority.
- [ ] Role.
- [ ] Status.
- [ ] Evidence.
- [ ] Defect ID.
