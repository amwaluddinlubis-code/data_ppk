<?php $assetBase = rtrim((string) parse_url(base_url('assets/'), PHP_URL_PATH), '/') . '/'; ?>
<!DOCTYPE html>
<html lang="id" data-theme="terang">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2e4d">
    <title><?= esc($title) ?></title>
    <script>
        (function () {
            var tema = 'terang';
            try { tema = localStorage.getItem('tema-pemilih') || 'terang'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema === 'gelap' ? 'gelap' : 'terang');
        })();
    </script>
    <link rel="stylesheet" href="<?= esc($assetBase . 'fontawesome/css/all.min.css', 'attr') ?>">
    <link rel="stylesheet" href="<?= esc($assetBase . 'css/app.css', 'attr') ?>">
</head>
<body class="min-h-screen text-base-content antialiased">
    <main class="relative isolate flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#0f2e4d] px-4 py-10 sm:px-6"
          aria-label="Masuk ke aplikasi">
        <!-- Latar: gradasi laut dalam + pola titik + cincin cahaya -->
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-[#123c61] via-[#0f2e4d] to-[#071b30]"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-25"
             style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.32) 1px, transparent 0); background-size: 30px 30px;"></div>
        <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[30rem] w-[30rem] -translate-x-1/2 rounded-full bg-sky-400/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-56 -left-32 -z-10 h-[26rem] w-[26rem] rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -right-36 -top-32 -z-10 h-[24rem] w-[24rem] rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -right-20 -top-16 -z-10 h-[16rem] w-[16rem] rounded-full border border-amber-300/20"></div>

        <!-- Merek -->
        <a href="<?= base_url('login') ?>" class="mb-7 flex flex-col items-center gap-3 text-center text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
            <span class="flex h-14 w-14 items-center justify-center rounded-3xl bg-white/10 text-2xl shadow-inner ring-2 ring-amber-300/60">
                <i class="fas fa-vote-yea" aria-hidden="true"></i>
            </span>
            <span>
                <span class="block text-base font-bold tracking-[0.22em]">DATA PEMILIH</span>
                <span class="mt-1 block text-xs text-blue-100/70">
                    <?= ! empty($kabupaten) ? 'Kabupaten ' . esc(ucwords(strtolower($kabupaten))) . ' · ' : '' ?>Ruang kerja PPK / PPS
                </span>
            </span>
        </a>

        <!-- Kartu masuk -->
        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-base-100 shadow-2xl shadow-black/40 ring-1 ring-white/20">
            <div class="h-1.5 bg-gradient-to-r from-amber-500 via-amber-300 to-amber-500"></div>
            <div class="p-6 sm:p-8">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold tracking-tight">Selamat datang kembali</h1>
                    <p class="mt-1.5 text-sm opacity-60">Masuk untuk melanjutkan ke ruang kerja data pemilih.</p>
                </div>

                <?php if (session()->getFlashdata('galat')) : ?>
                    <div class="alert alert-error mb-5 text-sm" role="alert">
                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                        <span><?= esc(session()->getFlashdata('galat')) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('info')) : ?>
                    <div class="alert alert-info mb-5 text-sm" role="status">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span><?= esc(session()->getFlashdata('info')) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('login') ?>" class="flex flex-col gap-5" id="form-login">
                    <?= csrf_field() ?>
                    <label class="form-control w-full gap-2">
                        <span class="text-sm font-semibold">Nama pengguna</span>
                        <span class="input input-bordered flex w-full items-center gap-3 rounded-xl bg-base-100 transition focus-within:border-primary focus-within:outline focus-within:outline-2 focus-within:outline-primary/20">
                            <i class="fas fa-user text-sm opacity-40" aria-hidden="true"></i>
                            <input type="text" name="username" value="<?= esc(old('username') ?? '', 'attr') ?>"
                                   class="grow bg-transparent outline-none" placeholder="Masukkan nama pengguna"
                                   required autofocus autocomplete="username">
                        </span>
                    </label>
                    <label class="form-control w-full gap-2">
                        <span class="text-sm font-semibold">Kata sandi</span>
                        <span class="input input-bordered flex w-full items-center gap-3 rounded-xl bg-base-100 transition focus-within:border-primary focus-within:outline focus-within:outline-2 focus-within:outline-primary/20">
                            <i class="fas fa-lock text-sm opacity-40" aria-hidden="true"></i>
                            <input type="password" name="sandi" id="sandi-login"
                                   class="grow bg-transparent outline-none" placeholder="Masukkan kata sandi"
                                   required autocomplete="current-password">
                            <button type="button" id="toggle-sandi" class="btn btn-ghost btn-xs btn-square opacity-60 hover:opacity-100"
                                    aria-label="Tampilkan kata sandi" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </span>
                    </label>
                    <button type="submit" class="btn btn-primary mt-1 h-12 w-full rounded-xl text-sm font-bold shadow-lg shadow-primary/25" id="tombol-masuk">
                        <span>Masuk</span>
                        <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-base-300"></div>
                    <span class="text-[10px] font-semibold uppercase tracking-[.15em] opacity-40">Akses resmi</span>
                    <div class="h-px flex-1 bg-base-300"></div>
                </div>
                <p class="text-center text-xs leading-5 opacity-60">
                    Untuk PPK tingkat kecamatan dan PPS tingkat desa.<br>
                    Belum memiliki akun? Hubungi Admin Kabupaten.
                </p>
            </div>
        </div>

        <!-- Jaminan singkat -->
        <div class="mt-7 flex flex-wrap items-center justify-center gap-2 text-[11px] font-medium text-blue-100/75">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3 py-1.5">
                <i class="fas fa-map-marked-alt text-emerald-300" aria-hidden="true"></i> Sesuai cakupan wilayah
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3 py-1.5">
                <i class="fas fa-user-shield text-sky-300" aria-hidden="true"></i> Akses sesuai peran
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3 py-1.5">
                <i class="fas fa-lock text-amber-300" aria-hidden="true"></i> Aktivitas tercatat
            </span>
        </div>
        <p class="mt-6 text-center text-[11px] text-blue-100/45">
            <i class="fas fa-shield-alt mr-1" aria-hidden="true"></i>
            Data dan akses Anda dilindungi sesuai peran pengguna.
        </p>
    </main>
    <script>
        var sandi = document.getElementById('sandi-login');
        var tombolSandi = document.getElementById('toggle-sandi');
        tombolSandi.addEventListener('click', function () {
            var tampil = sandi.type === 'password';
            sandi.type = tampil ? 'text' : 'password';
            tombolSandi.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            tombolSandi.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            tombolSandi.innerHTML = '<i class="fas ' + (tampil ? 'fa-eye-slash' : 'fa-eye') + '" aria-hidden="true"></i>';
            sandi.focus();
        });
        document.getElementById('form-login').addEventListener('submit', function () {
            var tombol = document.getElementById('tombol-masuk');
            tombol.disabled = true;
            tombol.innerHTML = '<span class="loading loading-spinner loading-sm"></span><span>Memproses...</span>';
        });
    </script>
</body>
</html>
