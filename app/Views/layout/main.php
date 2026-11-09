<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/datatables.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-base-200 min-h-screen">
<?php
// Menu utama + penanda menu aktif berdasarkan segmen URL
$segmen = service('request')->getUri()->getSegments();
$kunciAktif = implode('/', $segmen);
$namaKecamatan = ucwords(strtolower($kecamatan ?? ''));
$penggunaLogin = [
    'nama'  => session()->get('pengguna_nama') ?? '',
    'peran' => session()->get('pengguna_peran') ?? '',
];
$labelPeran = \App\Models\UserModel::labelPeran($penggunaLogin['peran']);
$inisial = strtoupper(substr($penggunaLogin['nama'], 0, 1) . substr(strstr($penggunaLogin['nama'], ' ') ?: '', 1, 1));
$menus = [
    ''                  => ['label' => 'HASIL COKLIT', 'ikon' => 'fa-border-all'],
    'residu'            => ['label' => 'NIK GANDA', 'ikon' => 'fa-check-double'],
    'pemilih'           => ['label' => 'PINDAH TPS', 'ikon' => 'fa-exchange-alt'],
    'pemilih/baru'      => ['label' => 'PEMILIH BARU', 'ikon' => 'fa-atom'],
    'pemilih/aktifPrint' => ['label' => 'PEMILIH AKTIF', 'ikon' => 'fa-file-alt'],
    'pemilih/tersaring' => ['label' => 'TERSARING', 'ikon' => 'fa-filter'],
    'laporan'           => ['label' => 'LAPORAN RINGKASAN', 'ikon' => 'fa-chart-bar'],
    'ganti-sandi'       => ['label' => 'GANTI SANDI', 'ikon' => 'fa-key'],
];
// Menu Kelola Pengguna hanya terlihat oleh Admin PPK (Fase 1);
// peran lain tetap tertolak walau memanggil URL-nya langsung.
if ($penggunaLogin['peran'] === 'admin') {
    $menus['pengguna']  = ['label' => 'KELOLA PENGGUNA', 'ikon' => 'fa-users'];
    $menus['log-akses'] = ['label' => 'LOG AKSES', 'ikon' => 'fa-shield-alt'];
    $menus['backup']    = ['label' => 'CADANGAN DATA', 'ikon' => 'fa-database'];
    $menus['kecamatan'] = ['label' => 'KECAMATAN', 'ikon' => 'fa-map-marked-alt'];
}
// Menu tulis (Fase 2) untuk Admin & Operator; Pemeriksa tidak melihatnya.
if (in_array($penggunaLogin['peran'], ['admin', 'operator'], true)) {
    $menus['coklit']  = ['label' => 'KELOLA DATA COKLIT', 'ikon' => 'fa-pen'];
    $menus['riwayat'] = ['label' => 'RIWAYAT PERUBAHAN', 'ikon' => 'fa-history'];
    $menus['impor']   = ['label' => 'IMPOR DATA', 'ikon' => 'fa-file-import'];
}
?>
<div class="drawer lg:drawer-open">
    <input id="drawer-utama" type="checkbox" class="drawer-toggle">

    <div class="drawer-content flex flex-col">
        <!-- Navbar atas -->
        <div class="navbar bg-base-100 shadow-sm sticky top-0 z-30">
            <div class="flex-none lg:hidden">
                <label for="drawer-utama" class="btn btn-ghost btn-square" aria-label="Buka menu">
                    <i class="fas fa-bars text-lg"></i>
                </label>
            </div>
            <div class="flex-1 min-w-0 flex flex-col justify-center">
                <div class="font-bold text-base md:text-lg leading-tight truncate"><?= esc($title_page) ?></div>
                <div class="text-xs opacity-70 leading-tight truncate">PPK Kecamatan <?= esc($namaKecamatan) ?></div>
            </div>
            <div class="flex-none hidden md:block">
                <span class="badge badge-primary badge-outline">Data Coklit Pantarlih</span>
            </div>
            <div class="flex-none ml-2">
                <a href="<?= base_url('logout') ?>" class="btn btn-ghost btn-sm" title="Keluar">
                    <i class="fas fa-sign-out-alt"></i><span class="hidden sm:inline"> Keluar</span>
                </a>
            </div>
        </div>

        <!-- Isi halaman -->
        <main class="p-3 md:p-5 w-full max-w-[1600px] mx-auto">
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

        <footer class="footer footer-center p-4 text-xs opacity-60 mt-auto">
            <p>Aplikasi Analisis Data Pemilih &mdash; PPK Kecamatan <?= esc($namaKecamatan) ?></p>
        </footer>
    </div>

    <!-- Sidebar -->
    <div class="drawer-side z-40">
        <label for="drawer-utama" class="drawer-overlay" aria-label="Tutup menu"></label>
        <aside class="bg-base-100 w-72 min-h-full flex flex-col border-r border-base-300">
            <div class="p-4 bg-primary text-primary-content">
                <div class="font-bold text-lg leading-tight">PPK <?= esc($namaKecamatan) ?></div>
                <div class="text-xs opacity-80">Aplikasi Analisis Data Pemilih</div>
            </div>
            <div class="flex items-center gap-3 p-4 border-b border-base-200">
                <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content rounded-full w-11">
                        <span class="text-sm font-bold"><?= esc($inisial) ?></span>
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="font-semibold text-sm truncate"><?= esc($penggunaLogin['nama']) ?></div>
                    <div class="text-xs opacity-60"><?= esc($labelPeran) ?></div>
                </div>
            </div>
            <ul class="menu p-3 gap-1 text-sm flex-1">
                <?php foreach ($menus as $kunci => $m) : ?>
                    <li>
                        <a href="<?= base_url($kunci) ?>"
                           class="<?= $kunciAktif === $kunci ? 'active' : '' ?>">
                            <i class="fas <?= $m['ikon'] ?> w-5 text-center"></i> <?= $m['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="p-3 text-[11px] opacity-50 border-t border-base-200">
                CodeIgniter 4 &middot; Tailwind + DaisyUI
            </div>
        </aside>
    </div>
</div>

<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
<script>
// Fase 5 (CSRF): sisipkan token pada semua permintaan AJAX POST (DataTables)
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' }
});
</script>
<script>
<?= $this->renderSection('javascript') ?>
</script>
</body>
</html>
