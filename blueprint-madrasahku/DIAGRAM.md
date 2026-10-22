# Kumpulan Diagram

Satu file untuk semua diagram proyek. Semua diagram memakai **Mermaid** agar mudah dirender, direvisi, dan disimpan di Git.

Status: **v0.4 — 2026-10-21**

Cara pakai:

- Buka file ini di VS Code, lalu aktifkan preview Mermaid.
- Atau tempel blok Mermaid ke https://mermaid.live.
- Ekspor PNG/SVG untuk lampiran SQA ke `diagrams/export/`.

Tidak ada lagi file `.mmd` atau `.drawio` terpisah. File ini adalah sumber tunggal diagram.

## Daftar diagram

| ID | Diagram | Dipakai di |
|---|---|---|
| D-01 | Arsitektur hybrid | Blueprint, SQA Report |
| D-02 | Alur autentikasi | Blueprint, Test Scenario |
| D-03 | Alur presensi guru | Blueprint, Test Scenario |
| D-04 | Alur akreditasi | Blueprint, SQA Report |
| D-05 | Alur info + dokumen PPDB | Blueprint, Test Scenario |
| D-06 | ERD | Blueprint, SQA Report |
| D-07 | Use case | SQA Report |
| D-08 | Topologi lingkungan uji | Test Plan |
| D-09 | Piramida pengujian | Test Plan |
| D-10 | Alur bug triage | Test Plan |
| D-11 | Alur SDLC dan QA | SQA Report |
| D-12 | Deployment dan go-live | SQA Report |

---

## D-01 Arsitektur Hybrid

Menunjukkan hubungan website PHP, REST API, MySQL, dan Flutter. MySQL adalah sumber kebenaran; autentikasi dan notifikasi ditangani backend sendiri tanpa penyedia identitas eksternal (email lewat SMTP).

```mermaid
flowchart LR
    PUB[Publik] --> WEB[Website PHP]
    ADM[Admin / Operator] --> WEB
    WEB --> DB[(MySQL)]
    WEB --> API[REST API PHP]
    APP[Flutter App] -->|Bearer API Token| API
    API -->|verifikasi hash token + status user| DB
    APP -->|poll notifikasi| API
    API --> MAIL[SMTP Mail] -.-> APP
```

---

## D-02 Alur Autentikasi

Akun dibuat admin/operator di MySQL. Aplikasi login memakai endpoint PHP yang menerbitkan API token; backend memverifikasi hash token dan mengecek status user di MySQL.

```mermaid
sequenceDiagram
    participant Admin
    participant Web
    participant DB as MySQL
    participant API
    participant App as Flutter

    Admin->>Web: Buat akun siswa/guru
    Web->>DB: Simpan user + hash + role + status
    App->>API: POST /auth/login (email, password, device)
    API->>DB: Cek user, is_active, password_verify
    API->>DB: Simpan hash access + refresh token
    API-->>App: access_token + refresh_token
    App->>API: Request + Bearer access_token
    API->>DB: Verifikasi hash token, masa berlaku, is_active
    API-->>App: Profil + menu sesuai role
    App->>API: GET /notifications
    API->>DB: Ambil notifikasi belum dibaca
    API-->>App: Daftar notifikasi + badge
```

---

## D-03 Alur Presensi Guru

Guru melakukan check-in kehadiran harian atau jam mengajar. API memvalidasi role, jadwal, duplikasi, dan waktu server sebelum menyimpan presensi. Tidak ada presensi siswa atau QR siswa.

```mermaid
sequenceDiagram
    participant Guru
    participant App as Flutter
    participant API
    participant DB as MySQL

    Guru->>App: Buka menu Presensi Guru
    App->>API: GET /attendance/today
    API->>DB: Ambil jadwal dan status hari ini
    API-->>App: Jadwal + status presensi
    Guru->>App: Check-in kehadiran
    App->>API: POST /attendance/check-in
    API->>DB: Validasi role, jadwal, duplikasi, jam server
    API->>DB: Simpan teacher_attendances
    API-->>App: hadir / terlambat
    Guru->>App: Lihat status terverifikasi
```

---

## D-04 Alur Akreditasi

Alur dari periode, seed IA2024, bank bukti, mapping, DKA, review, dashboard gap, sampai ekspor untuk Sispena.

```mermaid
flowchart TD
    A[Admin buat periode] --> B[Seed struktur IA2024]
    B --> C[Bank bukti]
    C --> D[Mapping bukti ke butir]
    D --> E[Isi DKA]
    E --> F[Review kepala/tim]
    F -->|revisi| D
    F -->|selesai| G[Dashboard kesiapan]
    G --> H[Export untuk Sispena]
    H --> I[Visitasi]
    I --> J[Tindak lanjut]
```

---

## D-05 Alur Info dan Dokumen PPDB

PPDB tanpa form. Publik membaca informasi dan mengunduh dokumen. Admin/operator mengelola info dan dokumen.

```mermaid
flowchart LR
    P[Publik] --> I[Halaman Info PPDB]
    I --> D[Daftar Dokumen Aktif]
    D --> C[Unduh via Controller]
    A[Admin / Operator] --> M[Kelola Info + Dokumen]
    M --> DB[(MySQL)]
    I --> DB
    D --> DB
    C --> S[Storage Privat]
```

---

## D-06 ERD

ERD akademik, presensi guru, akreditasi, dan identitas. Tabel CMS (berita, galeri, fasilitas, halaman, menu, pengaturan, PPDB, kontak, media) dibahas di `10-perombakan-web.md`.

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "profil siswa"
    USERS ||--o| TEACHERS : "profil guru"
    USERS ||--o{ ACTIVITY_LOGS : "aktivitas"
    USERS ||--o{ API_TOKENS : "token"
    USERS ||--o{ NOTIFICATIONS : "notifikasi"
    USERS ||--o{ PASSWORD_RESETS : "reset"

    ACADEMIC_YEARS ||--o{ CLASSES : "tahun ajaran"
    CLASSES ||--o{ STUDENTS : "anggota"
    CLASSES ||--o{ SCHEDULES : "jadwal"
    SUBJECTS ||--o{ SCHEDULES : "mapel"
    TEACHERS ||--o{ SCHEDULES : "guru"
    CLASSES ||--o{ CLASS_SUBJECTS : "kelas-mapel"
    SUBJECTS ||--o{ CLASS_SUBJECTS : "kelas-mapel"
    TEACHERS ||--o{ CLASS_SUBJECTS : "pengampu"

    CLASS_SUBJECTS ||--o{ MATERIALS : "materi"
    CLASS_SUBJECTS ||--o{ ASSIGNMENTS : "tugas"
    ASSIGNMENTS ||--o{ SUBMISSIONS : "pengumpulan"
    STUDENTS ||--o{ SUBMISSIONS : "mengumpulkan"
    TEACHERS ||--o{ SUBMISSIONS : "menilai"

    TEACHERS ||--o{ TEACHER_ATTENDANCES : "presensi"
    SCHEDULES |o--o{ TEACHER_ATTENDANCES : "jadwal mengajar"

    ACCREDITATION_PERIODS ||--o{ ACCREDITATION_COMPONENTS : "komponen"
    ACCREDITATION_COMPONENTS ||--o{ ACCREDITATION_BUTIRS : "butir"
    ACCREDITATION_BUTIRS ||--o{ ACCREDITATION_INDICATORS : "indikator"
    ACCREDITATION_BUTIRS ||--o{ DKA_ENTRIES : "DKA"
    EVIDENCE ||--o{ EVIDENCE_BUTIR : "mapping"
    ACCREDITATION_BUTIRS ||--o{ EVIDENCE_BUTIR : "bukti"
    ACCREDITATION_BUTIRS ||--o{ INDICATOR_CHECKS : "pemeriksaan"
    ACCREDITATION_INDICATORS ||--o{ INDICATOR_CHECKS : "pemeriksaan"
    ACCREDITATION_PERIODS ||--o{ EVIDENCE : "bukti"
    USERS ||--o{ EVIDENCE : "mengunggah"
    USERS ||--o{ DKA_ENTRIES : "mengisi"
    USERS ||--o{ MAIN_DOCUMENTS : "mengunggah"
    USERS ||--o{ TASKS : "ditugaskan"
    USERS ||--o{ ACCREDITATION_RESULTS : "mencatat"
    ACCREDITATION_PERIODS ||--o{ MAIN_DOCUMENTS : "dokumen utama"
    ACCREDITATION_PERIODS ||--o{ TASKS : "tugas"
    ACCREDITATION_PERIODS ||--o{ ACCREDITATION_RESULTS : "hasil"
    ACCREDITATION_RESULTS ||--o{ FOLLOW_UPS : "tindak lanjut"

    USERS {
        bigint id PK
        string name
        string email UK
        string username UK
        string password_hash
        string role
        boolean is_active
        boolean must_change_password
        datetime last_login_at
    }

    STUDENTS {
        bigint id PK
        bigint user_id FK
        string nis
        string nisn
        string gender
        date birth_date
        bigint class_id FK
        boolean is_active
    }

    TEACHERS {
        bigint id PK
        bigint user_id FK
        string nip
        string phone
        boolean is_active
    }

    ACADEMIC_YEARS {
        bigint id PK
        string name
        date start_date
        date end_date
        boolean is_active
    }

    CLASSES {
        bigint id PK
        string name
        string level
        bigint academic_year_id FK
        bigint homeroom_teacher_id FK
    }

    SUBJECTS {
        bigint id PK
        string code UK
        string name
    }

    CLASS_SUBJECTS {
        bigint id PK
        bigint class_id FK
        bigint subject_id FK
        bigint teacher_id FK
    }

    SCHEDULES {
        bigint id PK
        bigint class_subject_id FK
        tinyint day_of_week
        time start_time
        time end_time
        string room
    }

    MATERIALS {
        bigint id PK
        bigint class_subject_id FK
        string title
        text description
        string file_path
        datetime published_at
    }

    ASSIGNMENTS {
        bigint id PK
        bigint class_subject_id FK
        string title
        text description
        datetime due_at
        decimal max_score
    }

    SUBMISSIONS {
        bigint id PK
        bigint assignment_id FK
        bigint student_id FK
        string file_path
        text note
        decimal score
        text feedback
        datetime submitted_at
        datetime graded_at
        bigint graded_by FK
    }

    TEACHER_ATTENDANCES {
        bigint id PK
        bigint teacher_id FK
        bigint schedule_id FK
        date attendance_date
        string attendance_type
        datetime check_in_at
        datetime check_out_at
        string status
        string note
        bigint verified_by FK
    }

    EVIDENCE {
        bigint id PK
        bigint period_id FK
        string title
        string file_path
        string mime_type
        bigint file_size
        string checksum
        date valid_until
        string status
        bigint uploaded_by FK
        bigint verified_by FK
    }

    EVIDENCE_BUTIR {
        bigint id PK
        bigint evidence_id FK
        bigint butir_id FK
    }

    DKA_ENTRIES {
        bigint id PK
        bigint butir_id FK
        text content
        int version
        string status
        bigint updated_by FK
    }

    INDICATOR_CHECKS {
        bigint id PK
        bigint butir_id FK
        bigint indicator_id FK
        string status
        text note
    }

    ACCREDITATION_PERIODS {
        bigint id PK
        string name
        date start_date
        date end_date
        string status
        boolean is_active
    }

    ACCREDITATION_COMPONENTS {
        bigint id PK
        bigint period_id FK
        int number
        string name
    }

    ACCREDITATION_BUTIRS {
        bigint id PK
        bigint component_id FK
        int number
        text text
        string status
    }

    ACCREDITATION_INDICATORS {
        bigint id PK
        bigint butir_id FK
        int number
        text text
    }

    MAIN_DOCUMENTS {
        bigint id PK
        bigint period_id FK
        string type
        string title
        string file_path
        string status
    }

    TASKS {
        bigint id PK
        bigint period_id FK
        string title
        bigint assigned_to FK
        date due_date
        string status
    }

    ACCREDITATION_RESULTS {
        bigint id PK
        bigint period_id FK
        string grade
        decimal score
        text notes
        date decided_at
    }

    FOLLOW_UPS {
        bigint id PK
        bigint result_id FK
        text recommendation
        string status
        date due_date
    }

    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string entity
        bigint entity_id
        text metadata
        datetime created_at
    }

    API_TOKENS {
        bigint id PK
        bigint user_id FK
        string access_token_hash UK
        string refresh_token_hash UK
        string device_name
        string platform
        string ip_address
        datetime expires_at
        datetime refresh_expires_at
        datetime last_used_at
        datetime revoked_at
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string title
        text body
        string link
        string type
        boolean is_read
        datetime read_at
        datetime created_at
    }

    PASSWORD_RESETS {
        bigint id PK
        bigint user_id FK
        string token_hash
        datetime expires_at
        datetime used_at
    }
```

---

## D-07 Use Case

Aktor dan use case utama. Web dan aplikasi memakai data yang sama; hak akses dibatasi role.

```mermaid
flowchart LR
    P((Publik))
    S((Siswa))
    G((Guru))
    O((Operator))
    A((Admin))
    K((Kepala Madrasah))
    T((Tim Akreditasi))

    subgraph PUB[Publik]
        P1[Lihat berita/profil]
        P2[Lihat info + unduh dokumen PPDB]
        P3[Kirim kontak]
    end

    subgraph AKAD[Akademik]
        S1[Lihat jadwal]
        S2[Lihat tugas dan nilai]
        G1[Kelola materi/tugas]
        G2[Check-in presensi guru]
    end

    subgraph MAN[Manajemen]
        O1[Kelola akun]
        O2[Kelola data akademik]
        A1[Kelola pengguna dan role]
        K1[Review akreditasi]
        T1[Kelola bukti dan DKA]
    end

    P --> P1
    P --> P2
    P --> P3
    S --> S1
    S --> S2
    G --> G1
    G --> G2
    O --> O1
    O --> O2
    A --> A1
    K --> K1
    T --> T1
```

---

## D-08 Topologi Lingkungan Uji

Lingkungan lokal dan staging, termasuk web, Flutter, server SMTP, dan tools pengujian.

```mermaid
flowchart LR
    DEV[Developer] --> LOCAL[Local PHP + MySQL]
    DEV --> GIT[Git Repository]
    GIT --> STG[Staging Web + MySQL]
    GIT --> APPSTG[Staging Flutter Build]
    STG --> MAILSTG[Staging SMTP]
    STG --> TEST[Test Devices + Postman + Lighthouse]
    APPSTG --> TEST
    TEST --> REPORT[Evidence + Defect Report]
    STG --> PROD[Produksi]
```

---

## D-09 Piramida Pengujian

Komposisi target: unit/component 40%, integration 35%, E2E/UAT 25%.

```mermaid
flowchart TD
    E2E[E2E / UAT - sedikit, alur kritis] --> INT[Integration - API + DB + Token]
    INT --> UNIT[Unit / Component - banyak, cepat]
```

---

## D-10 Alur Bug Triage

Alur dari temuan sampai tiket ditutup, termasuk validasi dan retest.

```mermaid
flowchart LR
    A[Temuan] --> B[Reproduksi]
    B --> C{Valid?}
    C -->|tidak| D[Tutup sebagai invalid]
    C -->|ya| E[Tentukan severity]
    E --> F[Assign]
    F --> G[Fix]
    G --> H[Retest]
    H --> I{Lulus?}
    I -->|tidak| F
    I -->|ya| J[Tutup]
```

---

## D-11 Alur SDLC dan QA

Aktivitas QA mengikuti setiap fase SDLC, bukan hanya di akhir.

```mermaid
flowchart LR
    R[Requirement] --> D[Design]
    D --> I[Implementasi]
    I --> T[Testing]
    T --> DP[Deployment]
    DP --> M[Maintenance]
    R -.-> RQ[Review requirement]
    D -.-> DQ[Review desain]
    I -.-> CQ[Code review + unit test]
    T -.-> TQ[Test execution + defect]
    DP -.-> GQ[Smoke test + rollback plan]
```

---

## D-12 Deployment dan Go-live

Alur deploy dari lokal ke staging, smoke test, UAT, produksi, dan rollback.

```mermaid
flowchart LR
    L[Local build] --> S[Staging deploy]
    S --> SM[Smoke test]
    SM --> U[UAT]
    U --> G{Exit criteria?}
    G -->|belum| F[Perbaikan]
    F --> S
    G -->|ya| P[Produksi]
    P --> PM[Monitoring]
    PM --> RB[Rollback jika perlu]
```

---

## Catatan konsistensi

- Nama aktor: Publik, Siswa, Guru, Operator, Admin, Kepala Madrasah, Tim Akreditasi.
- Nama sistem: Website MTs Al-Huda, Aplikasi MADRASAHKU, REST API, MySQL, API Token, SMTP.
- Tidak ada penyedia identitas eksternal (Firebase Auth, Auth0, Supabase Auth, dan sejenisnya) untuk autentikasi, dan tidak ada Firestore/Firebase Hosting/FCM.
- Satu-satunya layanan eksternal adalah SMTP untuk email transaksional; SMTP tidak menyimpan akun, role, atau hash password.
- PPDB tanpa form; hanya informasi dan dokumen unduhan.
- Setiap diagram memakai istilah dan status yang sama dengan dokumen terkait.

## Ekspor untuk laporan SQA

1. Render diagram di VS Code atau mermaid.live.
2. Ekspor PNG 300 dpi atau SVG.
3. Simpan di `diagrams/export/` dengan nama `D-XX-nama.png`.
4. Masukkan ke dokumen SQA dan perbarui daftar gambar.
