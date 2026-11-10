<!DOCTYPE html>
<html lang="id" data-theme="terang">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <script>
        // Tema (terang/gelap) diterapkan sebelum halaman digambar,
        // mengikuti pilihan tersimpan pengguna.
        (function () {
            var tema = 'terang';
            try { tema = localStorage.getItem('tema-pemilih') || 'terang'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', tema === 'gelap' ? 'gelap' : 'terang');
        })();
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/datatables.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-base-200 min-h-screen">
<?php
// Menu utama berkelompok (alur ala Sidalih) + penanda menu aktif
$segmen = service('request')->getUri()->getSegments();
$kunciAktif = implode('/', $segmen);
$namaKabupaten = ucwords(strtolower($kabupaten ?? ''));
$kecAktif = $kecamatan ?? null;
$penggunaLogin = [
    'nama'      => session()->get('pengguna_nama') ?? '',
    'peran'     => session()->get('pengguna_peran') ?? '',
    'kecamatan' => session()->get('pengguna_kecamatan') ?? '',
    'desa'      => session()->get('pengguna_desa') ?? '',
];
$labelTingkat = \App\Models\UserModel::labelTingkat($penggunaLogin);
$labelPeran = \App\Models\UserModel::labelPeran($penggunaLogin['peran']);
$inisial = strtoupper(substr($penggunaLogin['nama'], 0, 1) . substr(strstr($penggunaLogin['nama'], ' ') ?: '', 1, 1));
$scopeLabel = $penggunaLogin['desa'] !== ''
    ? 'Desa ' . ucwords(strtolower($penggunaLogin['desa']))
    : ($kecAktif !== null && $kecAktif !== ''
        ? 'Kecamatan ' . ucwords(strtolower($kecAktif))
        : 'Seluruh Kabupaten');

$grupMenu = [];
$grupMenu[] = ['grup' => null, 'menu' => [
    '' => ['label' => 'Dasbor', 'ikon' => 'fa-th-large'],
]];
$grupMenu[] = ['grup' => 'Data Pemilih', 'menu' => [
    'pemilih/aktifPrint' => ['label' => 'Pemilih Aktif (MS)', 'ikon' => 'fa-file-alt'],
    'pemilih/baru'       => ['label' => 'Pemilih Baru', 'ikon' => 'fa-user-plus'],
    'pemilih'            => ['label' => 'Pindah TPS', 'ikon' => 'fa-exchange-alt'],
    'pemilih/tersaring'  => ['label' => 'Tersaring (TMS)', 'ikon' => 'fa-filter'],
    'residu'             => ['label' => 'NIK Ganda', 'ikon' => 'fa-check-double'],
    'anomali'            => ['label' => 'Deteksi Anomali', 'ikon' => 'fa-search-plus'],
]];
if (in_array($penggunaLogin['peran'], ['admin', 'operator'], true)) {
    $grupMenu[] = ['grup' => 'Pengolahan', 'menu' => [
        'coklit'  => ['label' => 'Kelola Data Coklit', 'ikon' => 'fa-pen'],
        'impor'   => ['label' => 'Impor Data', 'ikon' => 'fa-file-import'],
        'riwayat' => ['label' => 'Riwayat Perubahan', 'ikon' => 'fa-history'],
    ]];
}
$grupMenu[] = ['grup' => 'Laporan', 'menu' => [
    'laporan' => ['label' => 'Laporan Ringkasan', 'ikon' => 'fa-chart-bar'],
]];
$menuAdmin = ['kecamatan' => ['label' => 'Kecamatan', 'ikon' => 'fa-map-marked-alt']];
if ($penggunaLogin['peran'] === 'admin') {
    $menuAdmin['pengguna']  = ['label' => 'Kelola Pengguna', 'ikon' => 'fa-users'];
    $menuAdmin['log-akses'] = ['label' => 'Log Akses', 'ikon' => 'fa-shield-alt'];
    $menuAdmin['backup']    = ['label' => 'Cadangan Data', 'ikon' => 'fa-database'];
}
$grupMenu[] = ['grup' => 'Administrasi', 'menu' => $menuAdmin];
$grupMenu[] = ['grup' => 'Akun', 'menu' => [
    'ganti-sandi' => ['label' => 'Ganti Sandi', 'ikon' => 'fa-key'],
]];
?>
<div class="drawer lg:drawer-open">
    <input id="drawer-utama" type="checkbox" class="drawer-toggle">

    <div class="drawer-content flex flex-col">
        <!-- Navbar atas -->
        <div class="navbar bg-base-100 shadow-sm sticky top-0 z-30 px-2 md:px-4">
            <div class="flex-none lg:hidden">
                <label for="drawer-utama" class="btn btn-ghost btn-square" aria-label="Buka menu">
                    <i class="fas fa-bars text-lg"></i>
                </label>
            </div>
            <div class="flex-1 min-w-0 flex flex-col justify-center">
                <div class="font-bold text-base md:text-lg leading-tight truncate"><?= esc($title_page) ?></div>
                <div class="text-xs opacity-70 leading-tight truncate">
                    <?= $namaKabupaten !== '' ? 'Kabupaten ' . esc($namaKabupaten) . ' &middot; ' : '' ?><?= esc($scopeLabel) ?>
                </div>
            </div>
            <div class="flex-none hidden md:flex">
                <span class="badge badge-primary badge-outline gap-1">
                    <i class="fas fa-map-marker-alt"></i> <?= esc($scopeLabel) ?>
                </span>
            </div>
            <div class="flex-none ml-1">
                <button type="button" id="tombol-tema" class="btn btn-ghost btn-square" title="Ganti tema terang/gelap" aria-label="Ganti tema terang/gelap">
                    <i class="fas fa-moon text-lg" id="ikon-tema"></i>
                </button>
            </div>
            <div class="flex-none ml-1">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-sm gap-2">
                        <span class="avatar placeholder">
                            <span class="bg-primary text-primary-content rounded-full w-8 flex items-center justify-center">
                                <span class="text-xs font-bold"><?= esc($inisial) ?></span>
                            </span>
                        </span>
                        <span class="hidden sm:block text-left leading-tight">
                            <span class="block text-sm font-semibold truncate max-w-[10rem]"><?= esc($penggunaLogin['nama']) ?></span>
                            <span class="block text-[11px] opacity-60"><?= esc($labelTingkat) ?></span>
                        </span>
                        <i class="fas fa-chevron-down text-xs opacity-60"></i>
                    </label>
                    <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-box w-56 z-50 border border-base-300">
                        <li class="menu-title"><?= esc($labelPeran) ?></li>
                        <li><a href="<?= base_url('ganti-sandi') ?>"><i class="fas fa-key w-4"></i> Ganti Sandi</a></li>
                        <li><a href="<?= base_url('logout') ?>"><i class="fas fa-sign-out-alt w-4"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Isi halaman -->
        <main class="p-3 md:p-5 pb-24 lg:pb-5 w-full max-w-[1600px] mx-auto">
            <?php if (session()->getFlashdata('galat')) : ?>
                <div class="alert alert-error text-sm mb-3"><?= esc(session()->getFlashdata('galat')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('sukses')) : ?>
                <div class="alert alert-success text-sm mb-3"><?= esc(session()->getFlashdata('sukses')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('info')) : ?>
                <div class="alert alert-info text-sm mb-3"><?= esc(session()->getFlashdata('info')) ?></div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>

        <footer class="footer footer-center p-4 pb-24 lg:pb-4 text-xs opacity-60 mt-auto">
            <p>Aplikasi Data Pemilih<?= $namaKabupaten !== '' ? ' &mdash; Kabupaten ' . esc($namaKabupaten) : '' ?> &middot; <?= esc($scopeLabel) ?></p>
        </footer>
    </div>

    <!-- Sidebar -->
    <div class="drawer-side z-40">
        <label for="drawer-utama" class="drawer-overlay" aria-label="Tutup menu"></label>
        <aside class="bg-base-100 w-72 min-h-full flex flex-col border-r border-base-300">
            <div class="p-4 bg-primary text-primary-content">
                <div class="font-bold text-lg leading-tight flex items-center gap-2">
                    <i class="fas fa-vote-yea"></i> Data Pemilih
                </div>
                <div class="text-xs opacity-80">
                    <?= $namaKabupaten !== '' ? 'Kabupaten ' . esc($namaKabupaten) : 'Aplikasi Analisis Data Pemilih' ?>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 border-b border-base-200">
                <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content rounded-full w-11">
                        <span class="text-sm font-bold"><?= esc($inisial) ?></span>
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="font-semibold text-sm truncate"><?= esc($penggunaLogin['nama']) ?></div>
                    <div class="text-xs opacity-60"><?= esc($labelTingkat) ?></div>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto p-3">
                <?php foreach ($grupMenu as $grup) : ?>
                    <?php if ($grup['grup'] !== null) : ?>
                        <div class="px-2 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider opacity-50"><?= esc($grup['grup']) ?></div>
                    <?php endif; ?>
                    <ul class="menu p-0 gap-1 text-sm w-full">
                        <?php foreach ($grup['menu'] as $kunci => $m) : ?>
                            <li>
                                <a href="<?= base_url($kunci) ?>"
                                   class="<?= $kunciAktif === $kunci ? 'active' : '' ?>">
                                    <i class="fas <?= $m['ikon'] ?> w-5 text-center"></i> <?= $m['label'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </nav>
            <div class="p-3 text-[11px] opacity-50 border-t border-base-200">
                CodeIgniter 4 &middot; Tailwind + DaisyUI
            </div>
        </aside>
    </div>
</div>

<!-- Navigasi bawah (khusus HP) -->
<nav class="btm-nav btm-nav-sm lg:hidden bg-base-100 border-t border-base-300 z-30">
    <a href="<?= base_url('') ?>" class="<?= $kunciAktif === '' ? 'active text-primary' : '' ?>">
        <i class="fas fa-th-large text-base"></i>
        <span class="btm-nav-label">Dasbor</span>
    </a>
    <a href="<?= base_url('pemilih/aktifPrint') ?>" class="<?= $kunciAktif === 'pemilih/aktifPrint' ? 'active text-primary' : '' ?>">
        <i class="fas fa-file-alt text-base"></i>
        <span class="btm-nav-label">Pemilih MS</span>
    </a>
    <?php if (in_array($penggunaLogin['peran'], ['admin', 'operator'], true)) : ?>
        <a href="<?= base_url('coklit') ?>" class="<?= $kunciAktif === 'coklit' ? 'active text-primary' : '' ?>">
            <i class="fas fa-pen text-base"></i>
            <span class="btm-nav-label">Coklit</span>
        </a>
    <?php else : ?>
        <a href="<?= base_url('laporan') ?>" class="<?= $kunciAktif === 'laporan' ? 'active text-primary' : '' ?>">
            <i class="fas fa-chart-bar text-base"></i>
            <span class="btm-nav-label">Laporan</span>
        </a>
    <?php endif; ?>
    <a href="<?= base_url('kecamatan') ?>" class="<?= $kunciAktif === 'kecamatan' ? 'active text-primary' : '' ?>">
        <i class="fas fa-map-marked-alt text-base"></i>
        <span class="btm-nav-label">Wilayah</span>
    </a>
    <label for="drawer-utama" class="cursor-pointer">
        <i class="fas fa-bars text-base"></i>
        <span class="btm-nav-label">Menu</span>
    </label>
</nav>

<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
<script>
// Fase 5 (CSRF): sisipkan token pada semua permintaan AJAX POST (DataTables)
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' }
});
</script>
<script>
// Tema terang/gelap: tombol di navbar atas, pilihan tersimpan per peramban.
(function () {
    var ikon = document.getElementById('ikon-tema');
    function segarkanIkon() {
        var gelap = document.documentElement.getAttribute('data-theme') === 'gelap';
        if (ikon) {
            ikon.className = gelap ? 'fas fa-sun text-lg' : 'fas fa-moon text-lg';
        }
    }
    segarkanIkon();
    var tombol = document.getElementById('tombol-tema');
    if (tombol) {
        tombol.addEventListener('click', function () {
            var sekarang = document.documentElement.getAttribute('data-theme') === 'gelap' ? 'terang' : 'gelap';
            document.documentElement.setAttribute('data-theme', sekarang);
            try { localStorage.setItem('tema-pemilih', sekarang); } catch (e) {}
            segarkanIkon();
        });
    }
})();
</script>
<script>
<?= $this->renderSection('javascript') ?>
</script>
</body>
</html>
