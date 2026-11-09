<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-2">
                <div class="font-bold text-xl leading-tight">PPK <?= esc(ucwords(strtolower($kecamatan ?? ''))) ?></div>
                <div class="text-sm opacity-70">Aplikasi Analisis Data Pemilih</div>
            </div>

            <?php if (session()->getFlashdata('galat')) : ?>
                <div class="alert alert-error text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('info')) : ?>
                <div class="alert alert-info text-sm"><?= esc(session()->getFlashdata('info')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login') ?>" class="flex flex-col gap-3">
                <?= csrf_field() ?>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Nama pengguna</span>
                    <input type="text" name="username" value="<?= esc(old('username') ?? '') ?>"
                           class="input input-bordered w-full" required autofocus autocomplete="username">
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Kata sandi</span>
                    <input type="password" name="sandi" class="input input-bordered w-full"
                           required autocomplete="current-password">
                </label>
                <button type="submit" class="btn btn-primary w-full mt-1">Masuk</button>
            </form>

            <p class="text-xs opacity-60 text-center mt-2">
                Hanya pengguna terdaftar yang bisa masuk. Hubungi Admin PPK bila belum punya akun.
            </p>
        </div>
    </div>
</body>
</html>
