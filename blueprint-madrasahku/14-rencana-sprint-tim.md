# 14 — Rencana Sprint Tim: Minggu 1 (UI + Integrasi API Awal)

**Tujuan:** Dalam 7 hari, 7 orang (2 Admin, 3 Guru, 2 Siswa) membuat tampilan dasar per role, lalu menghubungkan **minimal 1 layar per orang** ke API nyata, dan siap di-merge ke branch `main`.

---

## 1. Susunan Tim & Tanggung Jawab

| Role | Anggota | Area Fokus (Screen) |
|---|---|---|
| **Admin** | Admin A | Dashboard, Pengaturan Sekolah, Riwayat Aktivitas |
| | Admin B | Data Guru, Data Siswa, Data Kelas, Manajemen Akun |
| **Guru** | Guru A | Presensi Harian, Riwayat Presensi |
| | Guru B | Daftar Tugas, Kirim Notifikasi |
| | Guru C | Materi & Jadwal, Profil Diri |
| **Siswa** | Siswa A | Daftar Tugas, Jadwal Pelajaran |
| | Siswa B | Materi & Buku, Profil Diri & Setting |

> **Catatan:** Setiap orang **hanya** mengerjakan screen di kolom "Area Fokus". Jangan ambil tugas orang lain tanpa koordinasi.

---

## 2. Alur Kerja Harian (7 Hari)

### HARI 1 — Onboarding & Fondasi API (Wajib Semua)
| Waktu | Aktiviti | Output |
|---|---|---|
| 09:00–10:00 | Clone repo backend & mobile | Semua punya kode lokal |
| 10:00–11:00 | Jalankan `tools/setup.php` (backend) | Database `mts_alhuda` + 14 migrasi + data contoh |
| 11:00–11:30 | Tambah dependensi ke `pubspec.yaml` | `http`, `shared_preferences`, `flutter_secure_storage` |
| 11:30–12:30 | Buat `auth_service.dart` (login, refresh, logout) | Bisa login & dapat token |
| 13:30–14:30 | Uji `GET /api/v1/health` di browser & HP (IP 192.168.100.22) | Semua melihat `{"status":"ok","migrasi":14}` |
| 14:30–15:00 | **Commit & push branch masing-masing** | Branch `feat/ui-<role>-<nama>` di GitHub |

**Perintah cepat Hari 1:**
```bash
# Backend (terminal Laragon/XAMPP)
cd C:\laragon\www\mts-alhuda-website
php tools\setup.php

# Mobile (terminal Flutter)
cd madrasahku-app
flutter pub add http shared_preferences flutter_secure_storage
flutter pub get
# buat lib/services/auth_service.dart (lihat template di bawah)
```

### HARI 2 — UI Dasar dengan Data DUMMY
- Setiap orang membuat **screen-nya sendiri** pakai data statis (hardcoded list, bukan dari API).
- Gunakan komponen yang sudah ada: `AppColors`, `AppTextStyle`, `CustomAppBar`, `CustomButton`.
- **Jangan** panggil `http` hari ini. Fokus: tata letak, warna, navigasi, loading state, empty state.

### HARI 3 — Review Desain & Konsistensi (15 menit per orang)
| Waktu | Aktiviti |
|---|---|
| 09:00–10:30 | Setiap orang demo 5 menit: "Screen ini pakai widget apa, warna apa" |
| 10:30–11:00 | Koordinator catat: warna/ font/ nama widget yang **harus disamakan** |
| 11:00–12:00 | Perbaiki ketidakkonsistenan (contoh: radius 12 vs 14, primary color beda) |
| 13:00–15:00 | Lanjut bikin screen lain / perbaikan kecil |

### HARI 4 — Swap 1 Screen ke API Nyata (Mulai Integrasi)
- Setiap orang pilih **satu screen favorit** (misal: Admin A → Dashboard, Guru A → Presensi Harian, Siswa A → Daftar Tugas).
- Ganti data dummy → panggil `Service.getX()` ke backend.
- Test di **device fisik (HP)** — **bukan emulator** — yang terhubung ke Wi-Fi yang sama dengan PC backend (IP 192.168.100.22).
- Kalau error jaringan: cek `config/database.php` port 3306, pastikan Laravel/MySQL jalan.

### HARI 5 — Integrasi Lengkap Minimal 1 Screen
- Semua screen favorit sudah pakai API nyata & bisa dibuka dari login.
- Pastikan navigasi antar layar lancar (push/pop, bottom nav, drawer).
- Verifikasi login role guru & siswa bisa beralih ke layar masing-masing.

### HARI 6 — Persiapan Merge & Resolusi Conflict
| Checklist Merge | Siapa Cek |
|---|---|
| `git status` bersih (tidak ada file untracked yang tidak perlu) | Masing-masing |
| `pubspec.yaml` semua orang **sama** (versi library identik) | Koordinator |
| `auth_service.dart` hanya **satu versi** (tidak ada duplikat beda) | Koordinator |
| Semua branch push ke GitHub (`feat/ui-...`) | Masing-masing |
| `README.md` mobile update: cara jalan, akun demo, IP LAN | Koordinator |

> **Jika ada conflict:** Diskusi cepat di grup chat, tentukan versi mana yang dipakai, lalu `git add` dan commit.

### HARI 7 — Merge ke Main & UAT
```bash
# Koordinator (atau siapa yang ditunjuk)
git checkout main
git pull origin main
git merge feat/ui-admin-a feat/ui-admin-b feat/ui-guru-a feat/ui-guru-b feat/ui-guru-c feat/ui-siswa-a feat/ui-siswa-b
# kalau conflict → fix manual → git add . → git commit
git push origin main
```
- Test manual di device fisik: login 3 role, buka 1 fitur masing-masing.
- Demo ke mitra: tampilkan mockup `blueprint-madrasahku/mockup/` sebagai acuan.
- **Sprint selesai.** Minggu depan lanjut fitur lanjutan (notifikasi, offline, dsb).

---

## 3. Template Kode Wajib (Copy-Pakai)

### `pubspec.yaml` (tambahkan di `dependencies:`)
```yaml
dependencies:
  flutter:
    sdk: flutter
  cupertino_icons: ^1.0.8
  google_fonts: ^8.2.1
  http: ^1.2.0
  shared_preferences: ^2.2.0
  flutter_secure_storage: ^9.0.0
  provider: ^6.0.0   # kalau belum punya state management
```

### `lib/services/auth_service.dart` (versi minimal)
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  static const String _baseUrl = 'http://192.168.100.22/mts-alhuda-website/api/v1';
  static const _storage = FlutterSecureStorage();
  static const _prefs = SharedPreferencesAsync(); // atau sync version

  static Future<Map<String, dynamic>?> login(String username, String password) async {
    final res = await http.post(
      Uri.parse('$_baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'username': username, 'password': password}),
    );
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body)['data'];
      await _storage.write(key: 'access_token', value: data['access_token']);
      await _storage.write(key: 'refresh_token', value: data['refresh_token']);
      await _prefs.setString('user_role', data['user']['role']);
      return data['user'];
    }
    return null;
  }

  static Future<void> logout() async {
    await _storage.delete(key: 'access_token');
    await _storage.delete(key: 'refresh_token');
    await _prefs.remove('user_role');
  }

  static Future<String?> getAccessToken() async {
    return await _storage.read(key: 'access_token');
  }

  static Future<void> refreshToken() async {
    final refresh = await _storage.read(key: 'refresh_token');
    if (refresh == null) return;
    final res = await http.post(
      Uri.parse('$_baseUrl/auth/refresh'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'refresh_token': refresh}),
    );
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body)['data'];
      await _storage.write(key: 'access_token', value: data['access_token']);
      await _storage.write(key: 'refresh_token', value: data['refresh_token']);
    } else {
      await logout();
    }
  }
}
```

### `lib/services/api_client.dart` (wrapper HTTP + auto refresh)
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'auth_service.dart';

class ApiClient {
  static const String baseUrl = 'http://192.168.100.22/mts-alhuda-website/api/v1';

  static Future<http.Response> get(String path, {Map<String, String>? query}) async {
    final token = await AuthService.getAccessToken();
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    var res = await http.get(uri, headers: _authHeaders(token));
    if (res.statusCode == 401) {
      await AuthService.refreshToken();
      final newToken = await AuthService.getAccessToken();
      res = await http.get(uri, headers: _authHeaders(newToken));
    }
    return res;
  }

  static Future<http.Response> post(String path, Map<String, dynamic> body) async {
    final token = await AuthService.getAccessToken();
    var res = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: _authHeaders(token),
      body: jsonEncode(body),
    );
    if (res.statusCode == 401) {
      await AuthService.refreshToken();
      final newToken = await AuthService.getAccessToken();
      res = await http.post(
        Uri.parse('$baseUrl$path'),
        headers: _authHeaders(newToken),
        body: jsonEncode(body),
      );
    }
    return res;
  }

  static Map<String, String> _authHeaders(String? token) => {
    'Content-Type': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };
}
```

### Contoh Model (buat di `lib/models/` per entitas)
```dart
// lib/models/guru_model.dart
class Guru {
  final int id;
  final String nama;
  final String mapel;
  final bool aktif;

  Guru({required this.id, required this.nama, required this.mapel, required this.aktif});

  factory Guru.fromJson(Map<String, dynamic> json) => Guru(
    id: json['id'] as int,
    nama: json['nama'] as String,
    mapel: json['mapel'] as String,
    aktif: json['aktif'] as bool,
  );
}
```

### Contoh Service (buat di `lib/services/` per entitas)
```dart
// lib/services/guru_service.dart
import 'api_client.dart';
import 'guru_model.dart';

class GuruService {
  static Future<List<Guru>> getAll() async {
    final res = await ApiClient.get('/guru');
    if (res.statusCode == 200) {
      final List data = jsonDecode(res.body)['data'];
      return data.map((e) => Guru.fromJson(e)).toList();
    }
    throw Exception('Gagal memuat guru: ${res.statusCode}');
  }
}
```

---

## 4. Batasan Prompt AI (Aturan Pakai AI Assistant)

> **Tujuan:** AI dipakai untuk **mempercepat**, bukan **menggantikan pemahaman**. Setiap orang tetap wajib bisa jelaskan kode yang dihasilkan AI.

| ✅ Boleh Dipakai AI | ❌ Dilarang Dipakai AI |
|---|---|
| Menerjemahkan error message ke bahasa Indonesia | Menulis **seluruh screen** tanpa review |
| Membuat boilerplate (model, service, widget skeleton) | Mengganti logic bisnis (validasi, state, navigasi) |
| Memperbaiki syntax error / warning Flutter analyze | Memutuskan arsitektur (provider vs bloc, folder structure) |
| Menghasilkan data dummy JSON untuk testing | Mengubah kontrak API (field nama, tipe data, endpoint) |
| Mengecek warna/ukuran font sesuai `DESIGN.md` | Meng-commit kode yang **tidak dibaca/dipahami** |

### Prosedur Pakai AI (Wajib Diikuti)
1. **Tulis prompt spesifik** — contoh: *"Buat model Dart untuk JSON: {id, nama, mapel, aktif}"* — bukan *"Buat semua model guru"*.
2. **Copy hasil ke file**, lalu **baca & pahami** setiap baris.
3. **Jalankan `flutter analyze`** — kalau ada error, perbaiki manual atau tanya AI lagi dengan error log.
4. **Commit dengan pesan jelas** — contoh: `feat: tambah GuruModel dari API response`.
5. **Kalau AI menyarankan library baru** — tanya ke koordinator dulu, jangan langsung `flutter pub add`.

> **Sanksi:** Jika ditemukan kode di commit yang **tidak bisa dijelaskan** oleh pemilik branch, branch akan **di-revert** dan orang tersebut harus nulis ulang manual.

---

## 5. Komunikasi & Koordinasi

| Saluran | Gunakan Untuk |
|---|---|
| **Grup WhatsApp/Telegram** | Tanya jawab cepat, info "saya push branch", conflict merge |
| **GitHub Issues** | Bug yang butuh tracking, fitur yang butuh diskusi panjang |
| **Stand-up harian (15 menit, pagi hari 1–7)** | Setiap orang jawab: "Kemarin apa? Hari ini apa? Blocker apa?" |
| **Review Mingguan (Hari 7 sore)** | Demo ke mitra, catat feedback, rencanakan minggu depan |

---

## 6. Definition of Done (Kapan Screen Dikatakan Selesai)

Screen dianggap **selesai** jika:
- [ ] Tampil benar di lebar 360 px (HP) dan 1280 px (web/desktop)
- [ ] Warna & font sesuai `DESIGN.md` (primary `#219653`, Poppins/Inter, radius 12–14)
- [ ] Data dummy **sudah diganti** minimal 1 screen per orang dengan panggilan API nyata
- [ ] Loading state (shimmer/spinner) & error state (snackbar/alert) ada
- [ ] Navigasi ke layar lain lancar (tidak stuck, tidak blank)
- [ ] `flutter analyze` **0 error, 0 warning**
- [ ] Commit message jelas, branch push ke GitHub

---

## 7. Checklist Akhir Sprint (Hari 7 Sore)

| Item | Status |
|---|---|
| Semua 7 branch sudah di-merge ke `main` | ☐ |
| `flutter analyze` di `main` → 0 error | ☐ |
| 3 role bisa login & buka minimal 1 fitur di device fisik | ☐ |
| `php tools/api-test.php` di backend masih 80/80 | ☐ |
| `README.md` mobile update: cara jalan, akun demo, IP LAN | ☐ |
| Mockup `blueprint-madrasahku/mockup/` sudah dicek visualnya | ☐ |

---

## 8. Escalation Path (Kalau Ada Masalah)

| Masalah | Siapa Dilaporin | Batas Waktu |
|---|---|---|
| Backend error / API tidak jalan | Koordinator → Maintainer backend | < 2 jam |
| Merge conflict yang tidak bisa diselesaikan | Koordinator | < 1 jam |
| HP tidak konek ke API (IP LAN) | Semua cek Wi-Fi sama, cek firewall Windows | < 30 menit |
| Anggota tidak bisa hadir / progress stuck | Koordinator reassign tugas ke anggota lain | Harian |

---

## 9. Referensi Cepat (Bookmark Ini)

| Dokumen | Link Lokal |
|---|---|
| Kontrak API v1.2 | `blueprint-madrasahku/12-dokumentasi-api.md` |
| Desain sistem (warna, font, radius) | `DESIGN.md` |
| Mockup 23 layar | `blueprint-madrasahku/mockup/index.html` |
| Panduan eksekusi backend | `blueprint-madrasahku/13-panduan-eksekusi-tim.md` |
| Database siap impor | `database/mts_alhuda.sql` |
| Backend README | `README.md` |

---

**Dibuat:** 24 Oktober 2026  
**Versi:** 1.0  
**Pemilik:** Koordinator Sprint (bisa diganti nama)  
**Review mingguan:** Setiap Jumat sore