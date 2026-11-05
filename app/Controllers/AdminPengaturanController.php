<?php

class AdminPengaturanController
{
    private function guard(): array
    {
        Auth::start();
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }

        $user = Auth::user();
        if ($user !== null && (int) ($user['must_change_password'] ?? 0) === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            exit;
        }

        return $user;
    }

    public function index(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        $rows = $pdo->query("SELECT setting_key, setting_value, group_name FROM pengaturan")->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        // Sinkronisasi alias untuk view
        $settings['school_name'] = $settings['school_name'] ?? $settings['site_name'] ?? 'MTs Al-Huda Gondang';
        $settings['npsn'] = $settings['npsn'] ?? $settings['site_npsn'] ?? '20512345';
        $settings['foundation_name'] = $settings['foundation_name'] ?? $settings['site_foundation'] ?? 'Yayasan Pendidikan Al-Huda Gondang';
        $settings['phone'] = $settings['phone'] ?? $settings['contact_phone'] ?? '(0358) 123456';
        $settings['email'] = $settings['email'] ?? $settings['contact_email'] ?? 'info@mts-alhuda.sch.id';
        $settings['whatsapp'] = $settings['whatsapp'] ?? $settings['contact_whatsapp'] ?? '0812-3456-7890';
        $settings['school_address'] = $settings['school_address'] ?? $settings['contact_address'] ?? 'Jl. Raya Gondang No. 12, Gondang, Kab. Nganjuk, Jawa Timur';

        // Default Presensi & Mobile App
        $settings['attendance_check_in_time'] = $settings['attendance_check_in_time'] ?? '06:30';
        $settings['attendance_late_tolerance'] = $settings['attendance_late_tolerance'] ?? '15';
        $settings['attendance_check_out_time'] = $settings['attendance_check_out_time'] ?? '14:00';
        $settings['attendance_lat'] = $settings['attendance_lat'] ?? $settings['contact_map_lat'] ?? '-7.589123';
        $settings['attendance_lng'] = $settings['attendance_lng'] ?? $settings['contact_map_lng'] ?? '112.012345';
        $settings['attendance_radius_meters'] = $settings['attendance_radius_meters'] ?? '100';
        $settings['attendance_mobile_enabled'] = $settings['attendance_mobile_enabled'] ?? '1';

        $tab = trim((string) ($_GET['tab'] ?? 'identitas'));

        $data = [
            'title' => 'Pengaturan Situs & Aplikasi | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'pengaturan',
            'tab' => $tab,
            'settings' => $settings,
            'pengaturan' => $settings,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/pengaturan/index.php';
    }

    public function update(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengaturan'));
            return;
        }

        $tab = trim((string) ($_POST['current_tab'] ?? 'identitas'));
        $pdo = Database::connection();

        $keys = [
            // Identitas
            'school_name' => ['identitas', 1],
            'site_name' => ['identitas', 1],
            'npsn' => ['identitas', 1],
            'site_npsn' => ['identitas', 1],
            'foundation_name' => ['identitas', 1],
            'site_foundation' => ['identitas', 1],
            'accreditation_grade' => ['identitas', 1],
            'school_address' => ['identitas', 1],
            'contact_address' => ['identitas', 1],
            'school_vision' => ['identitas', 1],
            'school_motto' => ['identitas', 1],

            // Kontak
            'phone' => ['kontak', 1],
            'contact_phone' => ['kontak', 1],
            'email' => ['kontak', 1],
            'contact_email' => ['kontak', 1],
            'whatsapp' => ['kontak', 1],
            'contact_whatsapp' => ['kontak', 1],
            'contact_hours' => ['kontak', 1],
            'maps_embed' => ['kontak', 1],

            // Media Sosial
            'facebook_url' => ['sosial', 1],
            'social_facebook' => ['sosial', 1],
            'instagram_url' => ['sosial', 1],
            'social_instagram' => ['sosial', 1],
            'youtube_url' => ['sosial', 1],
            'social_youtube' => ['sosial', 1],

            // SEO
            'meta_title' => ['seo', 1],
            'meta_description' => ['seo', 1],
            'meta_keywords' => ['seo', 1],

            // Email
            'smtp_host' => ['email', 0],
            'smtp_port' => ['email', 0],
            'smtp_user' => ['email', 0],
            'smtp_from_name' => ['email', 0],

            // Keamanan
            'session_timeout' => ['keamanan', 0],
            'max_login_attempts' => ['keamanan', 0],

            // Presensi & Aplikasi Mobile (MADRASAHKU)
            'attendance_check_in_time' => ['presensi', 1],
            'attendance_late_tolerance' => ['presensi', 1],
            'attendance_check_out_time' => ['presensi', 1],
            'attendance_lat' => ['presensi', 1],
            'attendance_lng' => ['presensi', 1],
            'contact_map_lat' => ['presensi', 1],
            'contact_map_lng' => ['presensi', 1],
            'attendance_radius_meters' => ['presensi', 1],
            'attendance_mobile_enabled' => ['presensi', 1],
        ];

        $stmt = $pdo->prepare("INSERT INTO pengaturan (setting_key, setting_value, group_name, is_public, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, NOW(), NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_public = VALUES(is_public), updated_at = NOW()");

        foreach ($keys as $k => $conf) {
            [$group, $isPub] = $conf;
            if (isset($_POST[$k])) {
                $val = trim((string) $_POST[$k]);
                $stmt->execute([$k, $val, $group, $isPub]);

                // Duplikasi alias agar kompatibel
                if ($k === 'school_name') {
                    $stmt->execute(['site_name', $val, $group, $isPub]);
                } elseif ($k === 'npsn') {
                    $stmt->execute(['site_npsn', $val, $group, $isPub]);
                } elseif ($k === 'foundation_name') {
                    $stmt->execute(['site_foundation', $val, $group, $isPub]);
                } elseif ($k === 'phone') {
                    $stmt->execute(['contact_phone', $val, $group, $isPub]);
                } elseif ($k === 'email') {
                    $stmt->execute(['contact_email', $val, $group, $isPub]);
                } elseif ($k === 'whatsapp') {
                    $stmt->execute(['contact_whatsapp', $val, $group, $isPub]);
                } elseif ($k === 'school_address') {
                    $stmt->execute(['contact_address', $val, $group, $isPub]);
                } elseif ($k === 'attendance_lat') {
                    $stmt->execute(['contact_map_lat', $val, $group, $isPub]);
                } elseif ($k === 'attendance_lng') {
                    $stmt->execute(['contact_map_lng', $val, $group, $isPub]);
                }
            }
        }

        AuditLog::write('pengaturan.update', 'pengaturan', null, "Memperbarui pengaturan situs tab {$tab}");

        $_SESSION['_flash_success'] = 'Seluruh pengaturan berhasil disimpan.';
        header('Location: ' . base_url('admin/pengaturan?tab=' . $tab));
    }
}
