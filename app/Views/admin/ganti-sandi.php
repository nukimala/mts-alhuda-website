<?php
/**
 * Ganti kata sandi. Wajib dilalui saat must_change_password = 1.
 */

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Ganti Kata Sandi
</div>

<div class="page-head">
    <div>
        <h1>Ganti Kata Sandi</h1>
        <p class="desc">
            <?= !empty($data['forced'])
                ? 'Akun ini memakai kata sandi sementara. Ganti dulu sebelum melanjutkan.'
                : 'Perbarui kata sandi akun Anda.' ?>
        </p>
    </div>
</div>

<div style="max-width:520px">
    <?php if (!empty($data['error'])): ?>
        <div class="notice warn" style="margin-bottom:16px">
            <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <div><?= htmlspecialchars((string) $data['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">
            <h3>Formulir Kata Sandi</h3>
        </div>
        <div class="card-pad">
            <form method="post" action="<?= base_url('admin/ganti-sandi') ?>" autocomplete="off">
                <?= Csrf::field() ?>

                <div class="field">
                    <label for="current_password">Kata Sandi Saat Ini <span class="req">*</span></label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                </div>

                <div class="field">
                    <label for="new_password">Kata Sandi Baru <span class="req">*</span></label>
                    <input id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="12">
                    <div class="hint">Minimal 12 karakter. Gunakan kombinasi huruf besar, kecil, angka, dan simbol.</div>
                </div>

                <div class="field">
                    <label for="confirm_password">Ulangi Kata Sandi Baru <span class="req">*</span></label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required minlength="12">
                </div>

                <div class="notice info" style="margin-bottom:16px">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    <div>Setelah kata sandi diganti, semua token aplikasi milik akun ini dicabut.</div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
                <?php if (empty($data['forced'])): ?>
                    <a class="btn btn-ghost" href="<?= base_url('admin') ?>">Batal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../layouts/admin.php';
