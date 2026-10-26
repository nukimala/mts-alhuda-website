<?php
/**
 * Panel admin — modul Pengguna & Role.
 *
 * Alur:
 *   GET  /admin/pengguna                        daftar + filter + paginasi
 *   GET  /admin/pengguna/create                 formulir akun baru
 *   POST /admin/pengguna                        simpan akun baru
 *   GET  /admin/pengguna/{id}/edit              formulir sunting
 *   POST /admin/pengguna/{id}                   simpan perubahan
 *   POST /admin/pengguna/{id}/delete            hapus (lunak)
 *   POST /admin/pengguna/{id}/aktifkan          aktifkan akun
 *   POST /admin/pengguna/{id}/nonaktifkan       nonaktifkan akun
 *   POST /admin/pengguna/{id}/reset             setel ulang sandi sementara
 *   POST /admin/pengguna/{id}/buka-kunci        buka kunci karena gagal login
 *
 * Aturan yang dijaga di sini, bukan di model:
 *   - pengguna tidak bisa menonaktifkan atau menghapus akunnya sendiri
 *     (kalau bisa, satu klik memutus aksesnya sendiri dan panel jadi
 *     tidak bisa dipakai untuk membatalkannya),
 *   - admin aktif terakhir tidak bisa dinonaktifkan, dihapus, atau
 *     diturunkan rolenya (kalau bisa, tidak ada lagi yang dapat mengelola
 *     pengguna).
 */

class AdminPenggunaController
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'role'     => (string) ($_GET['role'] ?? ''),
            'status'   => (string) ($_GET['status'] ?? ''),
            'page'     => (int) ($_GET['halaman'] ?? 1),
            'per_page' => self::PER_PAGE,
        ];

        $data = AdminGate::shell($user, 'Kelola Pengguna | Panel Admin', 'Kelola Pengguna');

        $data['result'] = User::paginate($filters);
        $data['filters'] = $filters;
        $data['counts'] = User::roleCounts();
        $data['canWrite'] = AdminGate::can('users.write');
        $data['currentId'] = (int) $user['id'];

        require_once __DIR__ . '/../Views/admin/pengguna/index.php';
    }

    public function create(): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        $data = AdminGate::shell($user, 'Tambah Pengguna | Panel Admin', 'Tambah Pengguna');

        $data['account'] = null;
        $data['old'] = self::emptyForm();
        $data['errors'] = [];
        $data['canWrite'] = true;
        $data['temporaryPassword'] = AdminGate::pullFlash('_flash_temp_password');

        require_once __DIR__ . '/../Views/admin/pengguna/form.php';
    }

    public function store(): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan kirim ulang.');
            header('Location: ' . base_url('admin/pengguna/create'));
            return;
        }

        $input = $this->collectInput();
        $errors = $this->validate($input, null);

        if ($errors !== []) {
            $this->renderFormWithErrors($user, null, $input, $errors, 422);
            return;
        }

        try {
            $created = User::create($input);
        } catch (PDOException $e) {
            $errors['email'] = 'Akun gagal disimpan. Kemungkinan email sudah dipakai akun lain.';
            $this->renderFormWithErrors($user, null, $input, $errors, 500);
            return;
        }

        Auth::log(
            'user.created',
            'users',
            $created['id'],
            'Membuat akun: ' . $input['name'] . ' (' . User::ROLES[$input['role']] . ')',
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        // Sandi sementara tidak dimasukkan ke pesan sukses: pesan itu tampil
        // di halaman daftar dan bisa terlihat orang lain yang lewat. Disimpan
        // di kunci sendiri, hanya muncul di formulir akun yang baru dibuat.
        AdminGate::flashValue('_flash_temp_password', $created['password']);
        AdminGate::flashSuccess('Akun "' . $input['name'] . '" dibuat. Sandi sementara ada di kotak di atas formulir — catat sekarang.');

        header('Location: ' . base_url('admin/pengguna/' . $created['id'] . '/edit'));
    }

    public function edit(array $segments): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        $account = User::find((int) ($segments[2] ?? 0));

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan. Mungkin sudah dihapus.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $data = AdminGate::shell($user, 'Sunting Pengguna | Panel Admin', 'Sunting Pengguna');

        $data['account'] = $account;
        $data['old'] = self::formFromRow($account);
        $data['errors'] = [];
        $data['canWrite'] = AdminGate::can('users.write');
        $data['isSelf'] = (int) $account['id'] === (int) $user['id'];
        $data['isLastAdmin'] = $this->isLastActiveAdmin($account);
        $data['temporaryPassword'] = AdminGate::pullFlash('_flash_temp_password');

        require_once __DIR__ . '/../Views/admin/pengguna/form.php';
    }

    public function update(array $segments): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $account = User::find($id);

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan. Mungkin sudah dihapus.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan kirim ulang.');
            header('Location: ' . base_url('admin/pengguna/' . $id . '/edit'));
            return;
        }

        $input = $this->collectInput();
        $errors = $this->validate($input, $id);

        // Admin aktif terakhir tidak boleh turun jadi role lain.
        $newRole = (string) $input['role'];
        if ($this->isLastActiveAdmin($account) && $newRole !== 'admin') {
            $errors['role'] = 'Ini satu-satunya admin aktif. Angkat admin lain dulu sebelum mengubah role akun ini.';
        }

        if ($errors !== []) {
            $this->renderFormWithErrors($user, $account, $input, $errors, 422);
            return;
        }

        try {
            User::update($id, $input);
        } catch (PDOException $e) {
            $errors['email'] = 'Perubahan gagal disimpan. Kemungkinan email sudah dipakai akun lain.';
            $this->renderFormWithErrors($user, $account, $input, $errors, 500);
            return;
        }

        Auth::log(
            'user.updated',
            'users',
            $id,
            'Mengubah akun: ' . $input['name'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        AdminGate::flashSuccess('Perubahan pada akun "' . $input['name'] . '" tersimpan.');
        header('Location: ' . base_url('admin/pengguna/' . $id . '/edit'));
    }

    public function destroy(array $segments): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $account = User::find($id);

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ($id === (int) $user['id']) {
            AdminGate::flashError('Anda tidak bisa menghapus akun Anda sendiri.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ($this->isLastActiveAdmin($account)) {
            AdminGate::flashError('Ini satu-satunya admin aktif dan tidak bisa dihapus.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        User::delete($id);

        Auth::log(
            'user.deleted',
            'users',
            $id,
            'Menghapus akun: ' . (string) $account['name'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        AdminGate::flashSuccess('Akun "' . $account['name'] . '" dihapus.');
        header('Location: ' . base_url('admin/pengguna'));
    }

    public function activate(array $segments): void
    {
        $this->setActive($segments, true);
    }

    public function deactivate(array $segments): void
    {
        $this->setActive($segments, false);
    }

    private function setActive(array $segments, bool $active): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $account = User::find($id);

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if (!$active) {
            if ($id === (int) $user['id']) {
                AdminGate::flashError('Anda tidak bisa menonaktifkan akun Anda sendiri.');
                header('Location: ' . base_url('admin/pengguna'));
                return;
            }

            if ($this->isLastActiveAdmin($account)) {
                AdminGate::flashError('Ini satu-satunya admin aktif dan tidak bisa dinonaktifkan.');
                header('Location: ' . base_url('admin/pengguna'));
                return;
            }
        }

        User::setActive($id, $active);

        Auth::log(
            $active ? 'user.activated' : 'user.deactivated',
            'users',
            $id,
            ($active ? 'Mengaktifkan' : 'Menonaktifkan') . ' akun: ' . (string) $account['name'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        AdminGate::flashSuccess(
            'Akun "' . $account['name'] . '" ' . ($active ? 'diaktifkan.' : 'dinonaktifkan. Sesi dan token aplikasinya dicabut.')
        );

        header('Location: ' . $this->backUrl());
    }

    public function resetPassword(array $segments): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $account = User::find($id);

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $password = User::resetPassword($id);

        Auth::log(
            'user.password_reset',
            'users',
            $id,
            'Menyetel ulang sandi akun: ' . (string) $account['name'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        AdminGate::flashValue('_flash_temp_password', $password);
        AdminGate::flashSuccess(
            'Sandi "' . $account['name'] . '" disetel ulang. Sandi sementara ada di kotak di atas formulir — catat sekarang.'
        );

        header('Location: ' . base_url('admin/pengguna/' . $id . '/edit'));
    }

    public function unlock(array $segments): void
    {
        $user = $this->requireWrite();
        if ($user === null) {
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $account = User::find($id);

        if ($account === null) {
            AdminGate::flashError('Akun tidak ditemukan.');
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        User::unlock($id);

        Auth::log(
            'user.unlocked',
            'users',
            $id,
            'Membuka kunci akun: ' . (string) $account['name'],
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        AdminGate::flashSuccess('Kunci akun "' . $account['name'] . '" dibuka. Sandi tidak berubah.');

        header('Location: ' . $this->backUrl());
    }

    // -----------------------------------------------------------------------
    // Validasi dan bentuk formulir
    // -----------------------------------------------------------------------

    /**
     * @return array<string,string>
     */
    private function collectInput(): array
    {
        return [
            'name'     => trim((string) ($_POST['name'] ?? '')),
            'email'    => trim((string) ($_POST['email'] ?? '')),
            'username' => trim((string) ($_POST['username'] ?? '')),
            'role'     => (string) ($_POST['role'] ?? 'guru'),
            'phone'    => trim((string) ($_POST['phone'] ?? '')),
            'nip'      => trim((string) ($_POST['nip'] ?? '')),
            'gender'   => (string) ($_POST['gender'] ?? 'L'),
        ];
    }

    /**
     * @param array<string,string> $input
     * @return array<string,string>
     */
    private function validate(array $input, ?int $excludeId): array
    {
        $errors = [];

        $nameLength = mb_strlen($input['name']);
        if ($nameLength === 0) {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        } elseif ($nameLength > 120) {
            $errors['name'] = 'Nama maksimal 120 karakter (saat ini ' . $nameLength . ').';
        }

        $email = $input['email'];
        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (mb_strlen($email) > 160) {
            $errors['email'] = 'Email maksimal 160 karakter.';
        } elseif (User::emailTaken($email, $excludeId)) {
            $errors['email'] = 'Email ini sudah dipakai akun lain.';
        }

        if (!isset(User::ROLES[$input['role']])) {
            $errors['role'] = 'Role tidak dikenal.';
        }

        $username = $input['username'];
        if ($username !== '' && mb_strlen($username) > 60) {
            $errors['username'] = 'Nama pengguna maksimal 60 karakter.';
        }

        $phone = $input['phone'];
        if ($phone !== '' && !preg_match('/^[0-9 +().-]{6,24}$/', $phone)) {
            $errors['phone'] = 'Nomor telepon hanya boleh angka dan tanda + ( ) - .';
        }

        $nip = $input['nip'];
        if ($nip !== '' && !preg_match('/^[0-9 ]{6,30}$/', $nip)) {
            $errors['nip'] = 'NIP hanya boleh angka dan spasi.';
        }

        if (!in_array($input['gender'], ['L', 'P'], true)) {
            $errors['gender'] = 'Jenis kelamin tidak dikenal.';
        }

        return $errors;
    }

    /**
     * @return array<string,string>
     */
    private static function emptyForm(): array
    {
        return [
            'name'     => '',
            'email'    => '',
            'username' => '',
            'role'     => 'guru',
            'phone'    => '',
            'nip'      => '',
            'gender'   => 'L',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,string>
     */
    private static function formFromRow(array $row): array
    {
        return [
            'name'     => (string) ($row['name'] ?? ''),
            'email'    => (string) ($row['email'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'role'     => (string) ($row['role'] ?? 'guru'),
            'phone'    => (string) ($row['phone'] ?? ''),
            'nip'      => (string) ($row['nip'] ?? ''),
            'gender'   => 'L',
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed>|null $account
     * @param array<string,string> $input
     * @param array<string,string> $errors
     */
    private function renderFormWithErrors(array $user, ?array $account, array $input, array $errors, int $status): void
    {
        http_response_code($status);

        $data = AdminGate::shell(
            $user,
            ($account === null ? 'Tambah Pengguna' : 'Sunting Pengguna') . ' | Panel Admin',
            $account === null ? 'Tambah Pengguna' : 'Sunting Pengguna'
        );

        $data['account'] = $account;
        $data['old'] = $input;
        $data['errors'] = $errors;
        $data['canWrite'] = true;
        $data['isSelf'] = $account !== null && (int) $account['id'] === (int) $user['id'];
        $data['isLastAdmin'] = $account !== null && $this->isLastActiveAdmin($account);
        $data['temporaryPassword'] = null;

        require_once __DIR__ . '/../Views/admin/pengguna/form.php';
    }

    /**
     * Wajib punya izin tulis. Mengembalikan data pengguna atau null kalau
     * sudah dialihkan.
     *
     * @return array<string,mixed>|null
     */
    private function requireWrite(): ?array
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return null;
        }

        if (!AdminGate::can('users.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin mengelola pengguna.');
            header('Location: ' . base_url('admin/pengguna'));
            return null;
        }

        return $user;
    }

    /**
     * @param array<string,mixed> $account
     */
    private function isLastActiveAdmin(array $account): bool
    {
        if ((string) $account['role'] !== 'admin' || (int) $account['is_active'] !== 1) {
            return false;
        }

        return User::activeAdminCount() <= 1;
    }

    /**
     * Kembali ke halaman daftar dengan filter yang sama, kalau dikirim.
     * Nilai `back` hanya diterima kalau menunjuk ke dalam /admin/pengguna —
     * tanpa pemeriksaan ini, parameter itu bisa dipakai untuk mengalihkan
     * pengguna ke situs lain setelah aksi.
     */
    private function backUrl(): string
    {
        $back = trim((string) ($_POST['back'] ?? ''));

        if ($back !== '' && preg_match('#^/?admin/pengguna(\?[\w=&%.\-+]*)?$#', $back) === 1) {
            return base_url(ltrim($back, '/'));
        }

        return base_url('admin/pengguna');
    }
}
