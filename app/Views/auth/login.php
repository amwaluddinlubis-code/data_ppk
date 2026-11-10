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
<body class="min-h-screen bg-base-200 text-base-content">
    <main class="grid min-h-screen lg:grid-cols-[1.08fr_0.92fr]">
        <section class="relative isolate flex min-h-[240px] flex-col overflow-hidden bg-[#0f2e4d] px-6 py-7 text-white sm:min-h-[380px] sm:px-10 lg:min-h-screen lg:px-14 lg:py-10"
                 aria-label="Informasi aplikasi">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-[#123c61] via-[#0f2e4d] to-[#071b30]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 opacity-25"
                 style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.32) 1px, transparent 0); background-size: 30px 30px;"></div>
            <div class="pointer-events-none absolute -right-36 -top-40 -z-10 h-[28rem] w-[28rem] rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-20 -top-24 -z-10 h-[22rem] w-[22rem] rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -bottom-48 -left-36 -z-10 h-[30rem] w-[30rem] rounded-full border border-white/10"></div>

            <a href="<?= base_url('login') ?>" class="flex w-fit items-center gap-3 rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-xl shadow-inner ring-1 ring-white/15">
                    <i class="fas fa-vote-yea" aria-hidden="true"></i>
                </span>
                <span>
                    <span class="block text-sm font-bold tracking-wide">DATA PEMILIH</span>
                    <span class="mt-0.5 block text-[11px] text-blue-100/70">Pusat kerja data wilayah</span>
                </span>
            </a>

            <p class="relative mt-7 max-w-xs text-lg font-semibold leading-snug sm:hidden">
                Data pemilih dikelola lebih <span class="text-emerald-300">tertib, akurat, dan terpadu.</span>
            </p>

            <div class="relative mx-auto my-6 hidden w-full max-w-xl flex-1 items-center justify-center sm:flex lg:my-8">
                <svg class="absolute inset-0 h-full w-full opacity-90" viewBox="0 0 640 440" fill="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="peta-gradien" x1="140" y1="68" x2="490" y2="368" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#38BDF8" stop-opacity=".85"/>
                            <stop offset="1" stop-color="#34D399" stop-opacity=".5"/>
                        </linearGradient>
                        <filter id="cahaya" x="85" y="34" width="470" height="390" filterUnits="userSpaceOnUse">
                            <feGaussianBlur stdDeviation="18"/>
                        </filter>
                    </defs>
                    <path d="M183 108 231 75l46 14 31-24 47 21 38-7 32 35 48 10-9 42 30 29-20 38 14 39-41 19-17 46-49-9-33 25-43-20-42 9-27-33-46-5-15-39-34-22 15-39-25-37 23-38-11-40Z"
                          fill="url(#peta-gradien)" opacity=".16" filter="url(#cahaya)"/>
                    <path d="M183 108 231 75l46 14 31-24 47 21 38-7 32 35 48 10-9 42 30 29-20 38 14 39-41 19-17 46-49-9-33 25-43-20-42 9-27-33-46-5-15-39-34-22 15-39-25-37 23-38-11-40Z"
                          fill="url(#peta-gradien)" fill-opacity=".09" stroke="url(#peta-gradien)" stroke-width="1.5"/>
                    <path d="m229 119 56 33 58-19 42 42 62-16M210 206l67-16 35 39 61-8 51 39M249 292l49-42 45 39 58-21"
                          stroke="white" stroke-opacity=".18" stroke-width="1.2" stroke-dasharray="4 7"/>
                    <path d="M229 119 285 152l58-19 42 42 62-16M210 206l67-16 35 39 61-8 51 39M249 292l49-42 45 39 58-21"
                          stroke="url(#peta-gradien)" stroke-opacity=".7" stroke-width="1.4"/>
                    <g fill="#A7F3D0" stroke="#0f2e4d" stroke-width="5">
                        <circle cx="229" cy="119" r="7"/><circle cx="285" cy="152" r="7"/><circle cx="343" cy="133" r="7"/>
                        <circle cx="385" cy="175" r="7"/><circle cx="447" cy="159" r="7"/><circle cx="210" cy="206" r="7"/>
                        <circle cx="277" cy="190" r="7"/><circle cx="312" cy="229" r="7"/><circle cx="373" cy="221" r="7"/>
                        <circle cx="424" cy="260" r="7"/><circle cx="249" cy="292" r="7"/><circle cx="298" cy="250" r="7"/>
                        <circle cx="343" cy="289" r="7"/><circle cx="401" cy="268" r="7"/>
                    </g>
                    <circle cx="343" cy="211" r="105" stroke="white" stroke-opacity=".08"/>
                    <circle cx="343" cy="211" r="145" stroke="white" stroke-opacity=".06"/>
                </svg>

                <div class="relative w-full max-w-sm">
                    <div class="mb-4 flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-4 py-3 shadow-2xl backdrop-blur-md">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-300/15 text-emerald-200">
                                <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                            </span>
                            <span>
                                <span class="block text-[10px] font-semibold uppercase tracking-[.16em] text-blue-100/70">Wilayah kerja</span>
                                <span class="mt-1 block text-sm font-semibold"><?= ! empty($kabupaten) ? 'Kab. ' . esc(ucwords(strtolower($kabupaten))) : 'PPK · PPS' ?></span>
                            </span>
                        </div>
                        <i class="fas fa-chevron-right text-xs text-white/50" aria-hidden="true"></i>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 shadow-2xl backdrop-blur-md sm:p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-[.16em] text-blue-100/70">Alur pengelolaan</div>
                                <div class="mt-1 text-sm font-bold">Data terorganisasi</div>
                            </div>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-300/15 text-sky-200">
                                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-[#071b30]/40 p-3">
                                <i class="fas fa-users text-xs text-sky-200" aria-hidden="true"></i>
                                <span class="mt-2 block text-[10px] font-medium text-blue-50/80">Pemilih</span>
                            </div>
                            <div class="rounded-xl bg-[#071b30]/40 p-3">
                                <i class="fas fa-clipboard-check text-xs text-emerald-200" aria-hidden="true"></i>
                                <span class="mt-2 block text-[10px] font-medium text-blue-50/80">Verifikasi</span>
                            </div>
                            <div class="rounded-xl bg-[#071b30]/40 p-3">
                                <i class="fas fa-file-alt text-xs text-amber-200" aria-hidden="true"></i>
                                <span class="mt-2 block text-[10px] font-medium text-blue-50/80">Laporan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative hidden max-w-xl lg:block">
                <p class="text-2xl font-semibold leading-snug tracking-tight xl:text-3xl">
                    Kelola data pemilih dengan lebih
                    <span class="text-emerald-300">tertib, akurat, dan terpadu.</span>
                </p>
                <p class="mt-3 max-w-lg text-sm leading-6 text-blue-100/70">
                    Satu ruang kerja untuk memantau hasil coklit, meninjau perubahan,
                    dan menyusun laporan berdasarkan wilayah.
                </p>
                <div class="mt-6 flex items-center gap-2 text-xs text-blue-100/60">
                    <i class="fas fa-lock text-emerald-300" aria-hidden="true"></i>
                    Akses sesuai peran dan cakupan wilayah
                </div>
            </div>
            <div class="relative mt-auto flex items-center justify-between pt-5 text-[10px] text-blue-100/45 lg:mt-8">
                <span><?= ! empty($kabupaten) ? 'Kabupaten ' . esc(ucwords(strtolower($kabupaten))) : 'Aplikasi Analisis Data Pemilih' ?></span>
                <span>Ruang kerja PPK / PPS</span>
            </div>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="text-xl font-bold tracking-tight">Selamat datang</div>
                    <p class="mt-1 text-sm opacity-65">Masuk untuk melanjutkan ke ruang kerja Anda.</p>
                </div>
                <div class="mb-8 hidden lg:block">
                    <div class="mb-3 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <i class="fas fa-sign-in-alt text-lg" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm font-semibold uppercase tracking-[.16em] text-primary">Akses pengguna</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight">Selamat datang</h1>
                    <p class="mt-2 text-sm leading-6 opacity-65">Masuk untuk melanjutkan ke ruang kerja data pemilih.</p>
                </div>

                <div class="rounded-3xl border border-base-300/70 bg-base-100 p-5 shadow-xl shadow-slate-900/5 sm:p-8">
                    <div class="mb-6">
                        <h1 class="text-xl font-bold tracking-tight lg:text-lg">Masuk ke akun</h1>
                        <p class="mt-1 text-sm opacity-60">Gunakan nama pengguna dan kata sandi Anda.</p>
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
                        <button type="submit" class="btn btn-primary mt-1 h-12 w-full rounded-xl text-sm font-bold shadow-lg shadow-primary/20" id="tombol-masuk">
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
                <p class="mt-6 text-center text-[11px] opacity-45">
                    <i class="fas fa-shield-alt mr-1" aria-hidden="true"></i>
                    Data dan akses Anda dilindungi sesuai peran pengguna.
                </p>
            </div>
        </section>
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
