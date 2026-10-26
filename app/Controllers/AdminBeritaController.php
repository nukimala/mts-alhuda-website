<?php
/**
 * Panel admin — modul Berita.
 *
 * Alur satu halaman:
 *   GET  /admin/berita                daftar + filter + paginasi
 *   GET  /admin/berita/create         formulir berita baru
 *   POST /admin/berita                simpan baru
 *   GET  /admin/berita/{id}/edit      formulir sunting
 *   POST /admin/berita/{id}           simpan perubahan
 *   POST /admin/berita/{id}/delete    hapus (lunak)
 *   POST /admin/berita/{id}/status    ubah status cepat dari tabel
 *
 * Validasi gagal tidak di-redirect. Formulir dirender ulang dengan isian
 * yang sudah diketik pengguna tetap utuh — orang tidak perlu mengetik ulang
 * seluruh berita hanya karena satu kolom salah.
 */

class AdminBeritaController
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
            'status'   => (string) ($_GET['status'] ?? ''),
            'category' => (string) ($_GET['kategori'] ?? ''),
            'sort'     => (string) ($_GET['urutkan'] ?? 'terbaru'),
            'page'     => (int) ($_GET['halaman'] ?? 1),
            'per_page' => self::PER_PAGE,
        ];

        $result = Post::paginate($filters);

        $data = AdminGate::shell(
            $user,
            'Kelola Berita | Panel Admin',
            'Kelola Berita'
        );

        $data['result'] = $result;
        $data['filters'] = $filters;
        $data['counts'] = Post::statusCounts();
        $data['canWrite'] = AdminGate::can('content.write');

        require_once __DIR__ . '/../Views/admin/berita/index.php';
    }

    public function create(): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        if (!AdminGate::can('content.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin menulis berita.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $data = AdminGate::shell(
            $user,
            'Tulis Berita Baru | Panel Admin',
            'Tulis Berita'
        );

        $data['post'] = null;
        $data['old'] = self::emptyForm();
        $data['errors'] = [];

        require_once __DIR__ . '/../Views/admin/berita/form.php';
    }

    public function store(): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        if (!AdminGate::can('content.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin menulis berita.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan kirim ulang.');
            header('Location: ' . base_url('admin/berita/create'));
            return;
        }

        $input = $this->collectInput();
        $errors = $this->validate($input);

        $uploaded = null;

        try {
            $uploaded = Media::store($_FILES['cover'] ?? [], [
                'kind'   => 'image',
                'folder' => 'berita',
                'alt'    => $input['title'],
            ]);
        } catch (RuntimeException $e) {
            $errors['cover'] = $e->getMessage();
        }

        if ($uploaded !== null) {
            $input['cover_path'] = $uploaded['path'];
        }

        if ($errors !== []) {
            $this->renderFormWithErrors($user, null, $input, $errors, 422);
            return;
        }

        try {
            $id = Post::create($input);
        } catch (PDOException $e) {
            if ($uploaded !== null) {
                Media::delete($uploaded['path']);
            }

            $errors['title'] = 'Berita gagal disimpan. Kemungkinan judul atau slug bentrok — coba ubah judulnya.';
            $this->renderFormWithErrors($user, null, $input, $errors, 500);
            return;
        }

        Auth::log('post.created', 'posts', $id, 'Membuat berita: ' . $input['title'], $_SERVER['REMOTE_ADDR'] ?? null);
        AdminGate::flashSuccess('Berita "' . $input['title'] . '" tersimpan.');

        header('Location: ' . base_url('admin/berita/' . $id . '/edit'));
    }

    public function edit(array $segments): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        $post = Post::find((int) ($segments[2] ?? 0));

        if ($post === null) {
            AdminGate::flashError('Berita tidak ditemukan. Mungkin sudah dihapus.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $data = AdminGate::shell(
            $user,
            'Sunting Berita | Panel Admin',
            'Sunting Berita'
        );

        $data['post'] = $post;
        $data['old'] = self::formFromRow($post);
        $data['errors'] = [];
        $data['canWrite'] = AdminGate::can('content.write');

        require_once __DIR__ . '/../Views/admin/berita/form.php';
    }

    public function update(array $segments): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        if (!AdminGate::can('content.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin mengubah berita.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $post = Post::find($id);

        if ($post === null) {
            AdminGate::flashError('Berita tidak ditemukan. Mungkin sudah dihapus.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan kirim ulang.');
            header('Location: ' . base_url('admin/berita/' . $id . '/edit'));
            return;
        }

        $input = $this->collectInput();
        $errors = $this->validate($input);

        // Cover lama tetap dipakai kecuali ada berkas baru atau diminta hapus.
        $input['cover_path'] = (string) ($post['cover_path'] ?? '');
        $removeCover = !empty($_POST['remove_cover']);
        $newUpload = null;

        try {
            $newUpload = Media::store($_FILES['cover'] ?? [], [
                'kind'   => 'image',
                'folder' => 'berita',
                'alt'    => $input['title'],
            ]);
        } catch (RuntimeException $e) {
            $errors['cover'] = $e->getMessage();
        }

        if ($newUpload !== null) {
            $input['cover_path'] = $newUpload['path'];
        } elseif ($removeCover) {
            $input['cover_path'] = '';
        }

        if ($errors !== []) {
            // Berkas baru sudah terlanjur tersimpan; hapus supaya tidak jadi sampah.
            if ($newUpload !== null) {
                Media::delete($newUpload['path']);
                $input['cover_path'] = (string) ($post['cover_path'] ?? '');
            }

            $this->renderFormWithErrors($user, $post, $input, $errors, 422);
            return;
        }

        try {
            Post::update($id, $input);
        } catch (PDOException $e) {
            if ($newUpload !== null) {
                Media::delete($newUpload['path']);
            }

            $errors['title'] = 'Perubahan gagal disimpan. Kemungkinan slug bentrok dengan berita lain.';
            $this->renderFormWithErrors($user, $post, $input, $errors, 500);
            return;
        }

        // Cover lama dibuang hanya setelah update berhasil.
        $oldCover = (string) ($post['cover_path'] ?? '');
        if ($oldCover !== '' && $oldCover !== $input['cover_path']) {
            Media::delete($oldCover);
        }

        Auth::log('post.updated', 'posts', $id, 'Mengubah berita: ' . $input['title'], $_SERVER['REMOTE_ADDR'] ?? null);
        AdminGate::flashSuccess('Perubahan pada "' . $input['title'] . '" tersimpan.');

        header('Location: ' . base_url('admin/berita/' . $id . '/edit'));
    }

    public function destroy(array $segments): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        if (!AdminGate::can('content.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin menghapus berita.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $post = Post::find($id);

        if ($post === null) {
            AdminGate::flashError('Berita tidak ditemukan.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        Post::delete($id);

        Auth::log('post.deleted', 'posts', $id, 'Menghapus berita: ' . (string) $post['title'], $_SERVER['REMOTE_ADDR'] ?? null);
        AdminGate::flashSuccess('Berita "' . $post['title'] . '" dihapus.');

        header('Location: ' . base_url('admin/berita'));
    }

    public function setStatus(array $segments): void
    {
        $user = AdminGate::requireLogin();
        if ($user === null) {
            return;
        }

        if (!AdminGate::can('content.write')) {
            AdminGate::flashError('Akun Anda tidak punya izin mengubah status berita.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            AdminGate::flashError('Sesi formulir kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $id = (int) ($segments[2] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if (!isset(Post::STATUSES[$status])) {
            AdminGate::flashError('Status tidak dikenal.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        $post = Post::find($id);

        if ($post === null) {
            AdminGate::flashError('Berita tidak ditemukan.');
            header('Location: ' . base_url('admin/berita'));
            return;
        }

        Post::setStatus($id, $status);

        Auth::log(
            'post.status',
            'posts',
            $id,
            'Status berita "' . (string) $post['title'] . '" menjadi ' . Post::STATUSES[$status],
            $_SERVER['REMOTE_ADDR'] ?? null
        );
        AdminGate::flashSuccess('Status "' . $post['title'] . '" sekarang ' . Post::STATUSES[$status] . '.');

        $back = (string) ($_POST['back'] ?? '');
        header('Location: ' . ($back !== '' ? $back : base_url('admin/berita')));
    }

    // -----------------------------------------------------------------------
    // Validasi dan bentuk formulir
    // -----------------------------------------------------------------------

    /**
     * @return array<string,string>
     */
    private function collectInput(): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));

        // Slug kosong diisi otomatis dari judul — perilaku yang dijanjikan
        // keterangan di formulir.
        if ($slug === '') {
            $slug = Format::slug($title);
        }

        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));

        // <input type="datetime-local"> mengirim "2026-10-18T08:30".
        if ($publishedAt !== '' && strpos($publishedAt, 'T') !== false) {
            $publishedAt = str_replace('T', ' ', $publishedAt);
        }

        return [
            'title'            => $title,
            'slug'             => $slug,
            'excerpt'          => trim((string) ($_POST['excerpt'] ?? '')),
            'content'          => (string) ($_POST['content'] ?? ''),
            'status'           => (string) ($_POST['status'] ?? 'draft'),
            'category'         => (string) ($_POST['category'] ?? 'umum'),
            'published_at'     => $publishedAt,
            'is_featured'      => !empty($_POST['is_featured']) ? '1' : '0',
            'meta_title'       => trim((string) ($_POST['meta_title'] ?? '')),
            'meta_description' => trim((string) ($_POST['meta_description'] ?? '')),
            'cover_path'       => '',
        ];
    }

    /**
     * @param array<string,string> $input
     * @return array<string,string>
     */
    private function validate(array $input): array
    {
        $errors = [];

        $titleLength = mb_strlen($input['title']);
        if ($titleLength === 0) {
            $errors['title'] = 'Judul berita wajib diisi.';
        } elseif ($titleLength > 120) {
            $errors['title'] = 'Judul maksimal 120 karakter (saat ini ' . $titleLength . ' karakter).';
        }

        $slug = (string) $input['slug'];
        if ($slug === '') {
            $errors['slug'] = 'Slug tidak bisa dibuat dari judul ini. Isi slug secara manual.';
        } elseif (mb_strlen($slug) > 180) {
            $errors['slug'] = 'Slug maksimal 180 karakter.';
        }

        if (mb_strlen((string) $input['excerpt']) > 400) {
            $errors['excerpt'] = 'Ringkasan maksimal 400 karakter.';
        }

        if (trim(strip_tags((string) $input['content'])) === '') {
            $errors['content'] = 'Isi berita tidak boleh kosong.';
        }

        if (!isset(Post::STATUSES[(string) $input['status']])) {
            $errors['status'] = 'Status penerbitan tidak dikenal.';
        }

        $publishedAt = (string) $input['published_at'];
        if ($publishedAt !== '' && strtotime($publishedAt) === false) {
            $errors['published_at'] = 'Format tanggal terbit tidak dikenali.';
        }

        return $errors;
    }

    /**
     * @return array<string,string>
     */
    private static function emptyForm(): array
    {
        return [
            'title'            => '',
            'slug'             => '',
            'excerpt'          => '',
            'content'          => '',
            'status'           => 'draft',
            'category'         => 'umum',
            'published_at'     => '',
            'is_featured'      => '0',
            'meta_title'       => '',
            'meta_description' => '',
            'cover_path'       => '',
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,string>
     */
    private static function formFromRow(array $post): array
    {
        $publishedAt = (string) ($post['published_at'] ?? '');

        return [
            'title'            => (string) ($post['title'] ?? ''),
            'slug'             => (string) ($post['slug'] ?? ''),
            'excerpt'          => (string) ($post['excerpt'] ?? ''),
            'content'          => (string) ($post['content'] ?? ''),
            'status'           => (string) ($post['status'] ?? 'draft'),
            'category'         => (string) ($post['category'] ?? 'umum'),
            'published_at'     => $publishedAt !== '' ? date('Y-m-d\TH:i', strtotime($publishedAt)) : '',
            'is_featured'      => (string) (int) ($post['is_featured'] ?? 0),
            'meta_title'       => (string) ($post['meta_title'] ?? ''),
            'meta_description' => (string) ($post['meta_description'] ?? ''),
            'cover_path'       => (string) ($post['cover_path'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed>|null $post
     * @param array<string,string> $input
     * @param array<string,string> $errors
     */
    private function renderFormWithErrors(array $user, ?array $post, array $input, array $errors, int $status): void
    {
        http_response_code($status);

        $data = AdminGate::shell(
            $user,
            ($post === null ? 'Tulis Berita Baru' : 'Sunting Berita') . ' | Panel Admin',
            $post === null ? 'Tulis Berita' : 'Sunting Berita'
        );

        $data['post'] = $post;
        $data['old'] = $input;
        $data['errors'] = $errors;
        $data['canWrite'] = true;

        require_once __DIR__ . '/../Views/admin/berita/form.php';
    }
}
