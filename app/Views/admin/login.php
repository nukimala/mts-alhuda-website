<?php require_once __DIR__ . '/../../helpers.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($data['title'] ?? 'Panel Admin | MTs Al-Huda Gondang') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body class="auth">
<div class="login-shell">
    <div class="login-card">
        <div class="card">
            <div class="card-pad">
                <img class="login-logo" src="<?= base_url('assets/images/LOGO_MTS 1.png') ?>" alt="Logo MTs Al-Huda">

                <h1>Masuk Panel Admin</h1>
                <p class="sub">Website MTs Al-Huda Gondang</p>

                <?php if (!empty($data['error'])): ?>
                    <div class="notice warn" style="margin-bottom:16px">
                        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                        <div><?= htmlspecialchars((string) $data['error'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('admin/login') ?>" autocomplete="off">
                    <?= Csrf::field() ?>

                    <div class="field<?= !empty($data['error']) ? ' invalid' : '' ?>">
                        <label for="identifier">Nama Pengguna atau Email <span class="req">*</span></label>
                        <input
                            id="identifier"
                            name="identifier"
                            type="text"
                            value="<?= htmlspecialchars((string) ($data['old'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="username"
                            autofocus
                            required>
                    </div>

                    <div class="field">
                        <label for="password">Kata Sandi <span class="req">*</span></label>
                        <div class="pw-wrap">
                            <input id="password" name="password" type="password" autocomplete="current-password" required>
                            <button type="button" class="toggle" data-toggle="password">Lihat</button>
                        </div>
                    </div>

                    <div class="remember">
                        <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                        <a href="<?= base_url('admin/lupa-sandi') ?>">Lupa kata sandi?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                </form>

                <p class="tiny muted" style="text-align:center;margin-top:16px">
                    Akun dibuat oleh Operator Madrasah. Tidak ada pendaftaran mandiri.
                </p>
            </div>
        </div>

        <p class="tiny muted" style="text-align:center;margin-top:16px">
            <a href="<?= base_url() ?>">&larr; Kembali ke website</a>
        </p>
    </div>
</div>

<div class="foot-strip">MTs Al-Huda Gondang &middot; Panel Admin</div>

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
