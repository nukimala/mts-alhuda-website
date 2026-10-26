<?php
/**
 * Formulir pengguna — dipakai untuk membuat akun baru dan menyunting.
 *
 * Sandi tidak pernah ditampilkan di formulir ini: akun baru mendapat sandi
 * sementara yang dibuat sistem, dan sandi lupa disetel ulang lewat tombol di
 * halaman daftar. Alasannya sederhana — formulir yang punya kolom sandi
 * mengundang orang mengetik sandi lemah yang dipakai bersama.
 */

$account = $data['account'] ?? null;
$old = $data['old'] ?? [];
$errors = $data['errors'] ?? [];
$canWrite = (bool) ($data['canWrite'] ?? true);
$isSelf = (bool) ($data['isSelf'] ?? false);
$isLastAdmin = (bool) ($data['isLastAdmin'] ?? false);
$temporaryPassword = $data['temporaryPassword'] ?? null;

$isEdit = $account !== null;
$id = $isEdit ? (int) $account['id'] : 0;

$action = $isEdit
    ? base_url('admin/pengguna/' . $id)
    : base_url('admin/pengguna');

$isActive = $isEdit ? (int) ($account['is_active'] ?? 1) === 1 : true;
$isGuru = ($old['role'] ?? 'guru') === 'guru';

$field = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? ' has-error' : '';
};

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <a href="<?= base_url('admin/pengguna') ?>">Pengguna &amp; Role</a>
    <span class="sep">/</span>
    <span class="current"><?= $isEdit ? 'Sunting' : 'Tambah Baru' ?></span>
</div>

<form method="post" action="<?= $action ?>" id="user-form">
    <?= Csrf::field() ?>

    <div class="page-head">
        <div>
            <h1><?= $isEdit ? 'Sunting Pengguna' : 'Buat Pengguna Baru' ?></h1>
            <p>
                <?php if ($isEdit): ?>
                    Dibuat <?= e(Format::date((string) ($account['created_at'] ?? ''))) ?>
                    <?php if (!empty($account['last_login_at'])): ?>
                        &middot; terakhir masuk <?= e(Format::ago((string) $account['last_login_at'])) ?>
                    <?php else: ?>
                        &middot; belum pernah masuk
                    <?php endif; ?>
                <?php else: ?>
                    Akun baru wajib mengganti sandi saat pertama kali masuk
                <?php endif; ?>
            </p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= base_url('admin/pengguna') ?>">Batal</a>
            <?php if ($canWrite): ?>
                <button class="btn btn-primary" type="submit">
                    <span class="mi mi-sm">save</span>
                    <?= $isEdit ? 'Simpan Perubahan' : 'Buat Akun' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($temporaryPassword !== null): ?>
        <div class="temp-password" data-temp-password>
            <div class="temp-password-head">
                <span class="mi">key</span>
                <strong>Sandi sementara</strong>
                <span class="temp-password-note">tampil sekali — catat sekarang</span>
            </div>
            <div class="temp-password-value">
                <code data-temp-value><?= e((string) $temporaryPassword) ?></code>
                <button class="btn btn-secondary btn-sm" type="button" data-copy-temp>Salin</button>
            </div>
            <p class="temp-password-hint">
                Berikan sandi ini kepada pengguna lewat jalur yang aman. Ia wajib menggantinya
                saat pertama kali masuk. Halaman ini tidak menampilkannya lagi setelah dimuat ulang.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="notice notice-danger">
            <span class="mi mi-sm">error</span>
            <span>
                <strong>Ada <?= count($errors) ?> bagian yang perlu diperbaiki.</strong>
                Lihat tanda merah di bawah. Isian Anda tidak hilang.
            </span>
        </div>
    <?php endif; ?>

    <?php if ($isSelf && $isLastAdmin): ?>
        <?php // Kasus paling rawan: satu-satunya admin menyunting dirinya sendiri. ?>
        <div class="notice notice-warning">
            <span class="mi mi-sm">warning</span>
            <span>
                Ini akun Anda sendiri dan satu-satunya admin aktif. Role tidak bisa diturunkan,
                dan akun ini tidak bisa dinonaktifkan atau dihapus dari sini. Angkat admin lain
                dulu bila perlu.
            </span>
        </div>
    <?php elseif ($isSelf): ?>
        <div class="notice notice-info">
            <span class="mi mi-sm">person</span>
            <span>Ini akun Anda sendiri. Role dan status tidak bisa diubah dari sini — minta admin lain bila perlu.</span>
        </div>
    <?php elseif ($isLastAdmin): ?>
        <div class="notice notice-warning">
            <span class="mi mi-sm">warning</span>
            <span>
                Ini satu-satunya admin aktif. Role tidak bisa diturunkan dan akun tidak bisa
                dinonaktifkan atau dihapus sampai ada admin aktif lain.
            </span>
        </div>
    <?php endif; ?>

    <?php if ($isEdit && !$isActive): ?>
        <div class="notice notice-warning">
            <span class="mi mi-sm">block</span>
            <span>Akun ini sedang <strong>nonaktif</strong> dan tidak bisa masuk. Aktifkan dari halaman daftar.</span>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-grid-main">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Identitas Pengguna</h2>
                        <p class="card-sub">Data yang dipakai untuk masuk dan dikenali di panel</p>
                    </div>
                    <span class="mi" style="color:var(--green)">badge</span>
                </div>

                <div class="field<?= $field('name') ?>">
                    <div class="field-label-row">
                        <label for="name">Nama Lengkap <span class="req">*</span></label>
                        <span class="field-counter" data-counter-for="name" data-max="120">0 / 120</span>
                    </div>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e((string) ($old['name'] ?? '')) ?>"
                        maxlength="120"
                        placeholder="Contoh: Ahmad Fauzi, S.Pd"
                        <?= $canWrite ? '' : 'readonly' ?>
                        required>
                    <?php if (isset($errors['name'])): ?>
                        <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['name']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">Tulis lengkap dengan gelar, seperti yang ingin tampil di panel.</p>
                    <?php endif; ?>
                </div>

                <div class="field-row">
                    <div class="field<?= $field('email') ?>">
                        <label for="email">Email Madrasah <span class="req">*</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e((string) ($old['email'] ?? '')) ?>"
                            maxlength="160"
                            placeholder="nama@mts-alhuda.sch.id"
                            <?= $canWrite ? '' : 'readonly' ?>
                            required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['email']) ?></p>
                        <?php else: ?>
                            <p class="field-hint">Dipakai untuk masuk. Harus unik.</p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $field('username') ?>">
                        <label for="username">Nama Pengguna</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= e((string) ($old['username'] ?? '')) ?>"
                            maxlength="60"
                            placeholder="dibuat otomatis dari email"
                            <?= $canWrite ? '' : 'readonly' ?>>
                        <?php if (isset($errors['username'])): ?>
                            <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['username']) ?></p>
                        <?php else: ?>
                            <p class="field-hint">Boleh dikosongkan. Dipakai sebagai alternatif email saat masuk.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field<?= $field('phone') ?>">
                        <label for="phone">Nomor Telepon</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= e((string) ($old['phone'] ?? '')) ?>"
                            maxlength="24"
                            placeholder="08xx xxxx xxxx"
                            <?= $canWrite ? '' : 'readonly' ?>>
                        <?php if (isset($errors['phone'])): ?>
                            <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['phone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="role">Role <span class="req">*</span></label>
                        <select id="role" name="role" <?= ($canWrite && !$isSelf && !$isLastAdmin) ? '' : 'disabled' ?>>
                            <?php foreach (User::ASSIGNABLE_ROLES as $key): ?>
                                <option value="<?= e($key) ?>" <?= ($old['role'] ?? 'guru') === $key ? 'selected' : '' ?>>
                                    <?= e(User::ROLES[$key]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isSelf || $isLastAdmin): ?>
                            <input type="hidden" name="role" value="<?= e((string) ($old['role'] ?? 'guru')) ?>">
                        <?php endif; ?>
                        <?php if (isset($errors['role'])): ?>
                            <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['role']) ?></p>
                        <?php else: ?>
                            <p class="field-hint">Menentukan menu dan tombol yang muncul di panel.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-row" data-guru-only <?= $isGuru ? '' : 'hidden' ?>>
                    <div class="field<?= $field('nip') ?>">
                        <label for="nip">NIP / No. Identitas</label>
                        <input
                            type="text"
                            id="nip"
                            name="nip"
                            value="<?= e((string) ($old['nip'] ?? '')) ?>"
                            maxlength="30"
                            placeholder="19850314 201101 1 009"
                            <?= $canWrite ? '' : 'readonly' ?>>
                        <?php if (isset($errors['nip'])): ?>
                            <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['nip']) ?></p>
                        <?php else: ?>
                            <p class="field-hint">Hanya untuk role Guru. Boleh dikosongkan.</p>
                        <?php endif; ?>
                    </div>

                    <div class="field<?= $field('gender') ?>">
                        <label for="gender">Jenis Kelamin</label>
                        <select id="gender" name="gender" <?= $canWrite ? '' : 'disabled' ?>>
                            <option value="L" <?= ($old['gender'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= ($old['gender'] ?? 'L') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid-side">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Akses</h2>
                    <span class="mi" style="color:var(--green)">lock</span>
                </div>

                <?php if (!$isEdit): ?>
                    <div class="field" style="margin-bottom:0">
                        <div class="check-row" style="align-items:flex-start">
                            <input type="checkbox" checked disabled style="width:16px;height:16px;margin-top:2px">
                            <label>
                                Buat kata sandi sementara otomatis
                                <span class="field-hint" style="display:block;margin-top:2px">
                                    Sandi dibuat sistem dan wajib diganti saat login pertama.
                                </span>
                            </label>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="kv-list">
                        <div class="kv-row">
                            <span class="kv-key">Status</span>
                            <span class="kv-value">
                                <?php if (!$isActive): ?>
                                    <span class="pill pill-red pill-dot">Nonaktif</span>
                                <?php elseif (!empty($account['locked_until']) && strtotime((string) $account['locked_until']) > time()): ?>
                                    <span class="pill pill-amber pill-dot">Terkunci</span>
                                <?php else: ?>
                                    <span class="pill pill-green pill-dot">Aktif</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-key">Wajib ganti sandi</span>
                            <span class="kv-value">
                                <?= (int) ($account['must_change_password'] ?? 0) === 1 ? 'Ya' : 'Tidak' ?>
                            </span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-key">Percobaan gagal</span>
                            <span class="kv-value"><?= Format::number((int) ($account['failed_login_attempts'] ?? 0)) ?> kali</span>
                        </div>
                        <div class="kv-row">
                            <span class="kv-key">Terakhir masuk</span>
                            <span class="kv-value">
                                <?= !empty($account['last_login_at']) ? e(Format::dateTime((string) $account['last_login_at'])) : 'Belum pernah' ?>
                            </span>
                        </div>
                        <?php if (!empty($account['last_login_ip'])): ?>
                            <div class="kv-row">
                                <span class="kv-key">Dari IP</span>
                                <span class="kv-value mono"><?= e((string) $account['last_login_ip']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($canWrite): ?>
                        <div class="form-actions" style="margin-top:var(--sp-4)">
                            <button class="btn btn-secondary btn-block btn-sm" type="button"
                                    data-confirm-reset="<?= e((string) ($account['name'] ?? '')) ?>"
                                    data-post-form="reset-form">
                                <span class="mi mi-sm">key</span> Setel Ulang Sandi
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($canWrite): ?>
                <div class="card card-flat">
                    <div class="form-actions" style="padding-top:0;margin-top:0;border-top:0">
                        <button class="btn btn-primary btn-block" type="submit">
                            <span class="mi mi-sm">save</span>
                            <?= $isEdit ? 'Simpan Perubahan' : 'Buat Akun' ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($isEdit && $canWrite): ?>
    <form id="reset-form" method="post" action="<?= base_url('admin/pengguna/' . $id . '/reset') ?>" hidden>
        <?= Csrf::field() ?>
    </form>
<?php endif; ?>

<script>
    (function () {
        var form = document.getElementById('user-form');
        if (!form) {
            return;
        }

        // --- penghitung karakter nama --------------------------------------
        var nameInput = document.getElementById('name');
        var nameCounter = document.querySelector('[data-counter-for="name"]');

        if (nameInput && nameCounter) {
            var renderName = function () {
                nameCounter.textContent = nameInput.value.length + ' / 120';
                nameCounter.classList.toggle('is-over', nameInput.value.length > 120);
            };
            nameInput.addEventListener('input', renderName);
            renderName();
        }

        // --- blok NIP hanya untuk role guru --------------------------------
        var roleSelect = document.getElementById('role');
        var guruOnly = document.querySelector('[data-guru-only]');

        if (roleSelect && guruOnly) {
            var syncGuru = function () {
                guruOnly.hidden = roleSelect.value !== 'guru';
            };
            roleSelect.addEventListener('change', syncGuru);
            syncGuru();
        }

        // --- salin sandi sementara -----------------------------------------
        var copyButton = document.querySelector('[data-copy-temp]');
        var tempValue = document.querySelector('[data-temp-value]');

        if (copyButton && tempValue) {
            copyButton.addEventListener('click', function () {
                var text = tempValue.textContent.trim();
                var done = function () {
                    copyButton.textContent = 'Tersalin';
                    setTimeout(function () { copyButton.textContent = 'Salin'; }, 2000);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done, done);
                    return;
                }

                var range = document.createRange();
                range.selectNodeContents(tempValue);
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                try { document.execCommand('copy'); } catch (e) {}
                done();
            });
        }

        // --- setel ulang sandi dari formulir -------------------------------
        document.querySelectorAll('[data-confirm-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                var name = button.getAttribute('data-confirm-reset') || 'akun ini';
                if (!window.confirm('Setel ulang sandi "' + name + '"?\n\nSandi lama langsung tidak berlaku dan sesi pengguna diputus.')) {
                    return;
                }
                var target = document.getElementById(button.getAttribute('data-post-form'));
                if (target) {
                    target.removeAttribute('hidden');
                    target.submit();
                }
            });
        });

        // --- cegah kirim ganda ---------------------------------------------
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                setTimeout(function () {
                    button.disabled = true;
                    button.classList.add('is-disabled');
                }, 10);
            }
        });
    })();
</script>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../../layouts/admin.php';
