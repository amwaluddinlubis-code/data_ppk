<!DOCTYPE html>
<html lang="id" data-theme="terang">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <script>
        (function () {
            var tema = 'terang';
            try { tema = localStorage.getItem('tema-pemilih') || 'terang'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema === 'gelap' ? 'gelap' : 'terang');
        })();
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl overflow-hidden">
        <div class="bg-primary text-primary-content p-5 text-center">
            <div class="text-3xl mb-1"><i class="fas fa-vote-yea"></i></div>
            <div class="font-bold text-xl leading-tight">Data Pemilih</div>
            <div class="text-sm opacity-80">
                <?= ! empty($kabupaten) ? 'Kabupaten ' . esc(ucwords(strtolower($kabupaten))) : 'Aplikasi Analisis Data Pemilih' ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (session()->getFlashdata('galat')) : ?>
                <div class="alert alert-error text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('info')) : ?>
                <div class="alert alert-info text-sm"><?= esc(session()->getFlashdata('info')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login') ?>" class="flex flex-col gap-3" id="form-login">
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
                <button type="submit" class="btn btn-primary w-full mt-1" id="tombol-masuk">Masuk</button>
            </form>

            <p class="text-xs opacity-60 text-center mt-2">
                Untuk PPK tingkat kecamatan dan PPS tingkat desa.
                Hubungi Admin Kabupaten bila belum punya akun.
            </p>
        </div>
    </div>
    <script>
        document.getElementById('form-login').addEventListener('submit', function () {
            var tombol = document.getElementById('tombol-masuk');
            tombol.disabled = true;
            tombol.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Memproses...';
        });
    </script>
</body>
</html>
