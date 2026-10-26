<?php require_once __DIR__ . '/../../helpers.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($data['title'] ?? 'Masuk Panel Admin | MTs Al-Huda Gondang') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    <link rel="stylesheet" href="<?= base_url('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body class="login-body">
<main class="login-card">
    <div class="login-brand">
        <img src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTs Al-Huda">
        <h1>Masuk Panel Admin</h1>
        <p>Website MTs Al-Huda Gondang</p>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="notice notice-danger">
            <span class="mi mi-sm">error</span>
            <span><?= e((string) $data['error']) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('admin/login') ?>" autocomplete="off">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="identifier">Nama pengguna atau email</label>
            <input id="identifier" name="identifier" type="text"
                   value="<?= e((string) ($data['old'] ?? '')) ?>"
                   autocomplete="username" autofocus required>
        </div>

        <div class="field">
            <label for="password">Kata sandi</label>
            <div style="display:flex;gap:var(--sp-2)">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button type="button" class="btn btn-secondary btn-sm" data-toggle="password" style="flex:0 0 auto">
                    Lihat
                </button>
            </div>
        </div>

        <div class="check-row" style="justify-content:space-between;margin-bottom:var(--sp-5)">
            <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk</button>
    </form>

    <div class="login-foot">
        Akun dibuat oleh Operator Madrasah. Tidak ada pendaftaran mandiri.<br>
        <a href="<?= base_url('') ?>">&larr; Kembali ke website</a>
    </div>
</main>

<script>
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.toggle);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Sembunyi' : 'Lihat';
        });
    });
</script>
</body>
</html>
