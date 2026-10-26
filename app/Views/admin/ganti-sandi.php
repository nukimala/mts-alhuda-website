<?php
/**
 * Ganti kata sandi. Wajib dilalui saat must_change_password = 1.
 */

$forced = !empty($data['forced']);

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <span class="current">Ganti Kata Sandi</span>
</div>

<div class="page-head">
    <div>
        <h1>Ganti Kata Sandi</h1>
        <p>
            <?= $forced
                ? 'Akun ini masih memakai kata sandi sementara. Ganti dulu sebelum melanjutkan.'
                : 'Perbarui kata sandi akun Anda.' ?>
        </p>
    </div>
</div>

<div style="max-width:560px">
    <?php if (!empty($data['error'])): ?>
        <div class="notice notice-danger">
            <span class="mi mi-sm">error</span>
            <span><?= e((string) $data['error']) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($forced): ?>
        <div class="notice notice-warning">
            <span class="mi mi-sm">warning</span>
            <span>Demi keamanan, akses ke menu lain dikunci sampai kata sandi diganti.</span>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="<?= base_url('admin/ganti-sandi') ?>" autocomplete="off">
            <?= Csrf::field() ?>

            <div class="field">
                <label for="current_password">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password"
                       autocomplete="current-password" required>
            </div>

            <div class="field">
                <label for="new_password">Kata sandi baru</label>
                <input id="new_password" name="new_password" type="password"
                       autocomplete="new-password" required minlength="12">
                <p class="field-hint">Minimal 12 karakter. Kombinasikan huruf besar, kecil, angka, dan simbol.</p>
            </div>

            <div class="field">
                <label for="confirm_password">Ulangi kata sandi baru</label>
                <input id="confirm_password" name="confirm_password" type="password"
                       autocomplete="new-password" required minlength="12">
            </div>

            <div class="notice notice-info">
                <span class="mi mi-sm">info</span>
                <span>Setelah kata sandi diganti, semua token aplikasi milik akun ini dicabut.</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="mi mi-sm">save</span> Simpan Kata Sandi
                </button>
                <?php if (!$forced): ?>
                    <a class="btn btn-ghost" href="<?= base_url('admin') ?>">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../layouts/admin.php';
