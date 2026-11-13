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
$grupMenu[] = ['grup' => 'Data Pemilih', 'ikonGrup' => 'fa-users', 'menu' => [
    'pemilih/aktifPrint' => ['label' => 'Pemilih Aktif (MS)', 'ikon' => 'fa-file-alt'],
    'pemilih/baru'       => ['label' => 'Pemilih Baru', 'ikon' => 'fa-user-plus'],
    'pemilih'            => ['label' => 'Pindah TPS', 'ikon' => 'fa-exchange-alt'],
    'pemilih/tersaring'  => ['label' => 'Tersaring (TMS)', 'ikon' => 'fa-filter'],
    'residu'             => ['label' => 'NIK Ganda', 'ikon' => 'fa-check-double'],
    'anomali'            => ['label' => 'Deteksi Anomali', 'ikon' => 'fa-search-plus'],
]];
if (in_array($penggunaLogin['peran'], ['admin', 'operator'], true)) {
    $grupMenu[] = ['grup' => 'Pengolahan', 'ikonGrup' => 'fa-cogs', 'menu' => [
        'coklit'  => ['label' => 'Kelola Data Coklit', 'ikon' => 'fa-pen'],
        'coktas'  => ['label' => 'Coktas PDPB', 'ikon' => 'fa-clipboard-check'],
        'impor'   => ['label' => 'Impor Data', 'ikon' => 'fa-file-import'],
        'riwayat' => ['label' => 'Riwayat Perubahan', 'ikon' => 'fa-history'],
    ]];
}
$grupMenu[] = ['grup' => 'Laporan', 'ikonGrup' => 'fa-chart-bar', 'menu' => [
    'laporan' => ['label' => 'Laporan Ringkasan', 'ikon' => 'fa-chart-line'],
    'pdpb'    => ['label' => 'PDPB Berkelanjutan', 'ikon' => 'fa-sync-alt'],
    'tahapan' => ['label' => 'Tahapan DPS–DPT', 'ikon' => 'fa-layer-group'],
    'dptb'    => ['label' => 'DPTb & Lokasi Khusus', 'ikon' => 'fa-exchange-alt'],
    'sidalih' => ['label' => 'Banding Sidalih', 'ikon' => 'fa-balance-scale'],
    'beban-tps' => ['label' => 'Beban TPS', 'ikon' => 'fa-gauge-high'],
    'permohonan' => ['label' => 'Permohonan Perbaikan Data', 'ikon' => 'fa-user-edit'],
    'bawaslu' => ['label' => 'Pengawasan Bawaslu', 'ikon' => 'fa-eye'],
]];
$menuAdmin = ['kecamatan' => ['label' => 'Kecamatan', 'ikon' => 'fa-map-marked-alt']];
if ($penggunaLogin['peran'] === 'admin') {
    $menuAdmin['konteks']   = ['label' => 'Konteks Penyelenggaraan', 'ikon' => 'fa-flag'];
    $menuAdmin['pelindungan-data'] = ['label' => 'Pelindungan Data Pribadi', 'ikon' => 'fa-user-shield'];
    $menuAdmin['pengguna']  = ['label' => 'Kelola Pengguna', 'ikon' => 'fa-users'];
    $menuAdmin['log-akses'] = ['label' => 'Log Akses', 'ikon' => 'fa-shield-alt'];
    $menuAdmin['backup']    = ['label' => 'Cadangan Data', 'ikon' => 'fa-database'];
}
$grupMenu[] = ['grup' => 'Administrasi', 'ikonGrup' => 'fa-cog', 'menu' => $menuAdmin];
$grupMenu[] = ['grup' => 'Akun', 'ikonGrup' => 'fa-user-circle', 'menu' => [
    'ganti-sandi' => ['label' => 'Ganti Sandi', 'ikon' => 'fa-key'],
]];

// Grup yang memuat halaman aktif (induknya dibuka otomatis).
$grupAktifNama = null;
foreach ($grupMenu as $g) {
    if ($g['grup'] !== null && array_key_exists($kunciAktif, $g['menu'])) {
        $grupAktifNama = $g['grup'];
        break;
    }
}

// Filter kecamatan untuk pengguna tingkat kabupaten (Admin): cakupan
// akunnya tidak terkunci, jadi ia bebas menyaring per sesi. Mekanisme
// sesi-nya sudah ada di Wilayah + Kecamatan::aktifkan — di sini hanya
// pemilih cepatnya di sidebar.
$bebasWilayah = \App\Libraries\Wilayah::kecPengguna() === null;
$filterKecAktif = \App\Libraries\Wilayah::kecEfektif();
$daftarKecFilter = [];
if ($bebasWilayah) {
    $daftarKecFilter = db_connect()->table('kecamatan')
        ->select('nama')->orderBy('nama', 'ASC')->get()->getResultArray();
}
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
            <div class="flex-1 min-w-0 flex flex-col justify-center overflow-hidden">
                <div class="w-full overflow-hidden font-bold text-sm sm:text-base md:text-lg leading-tight truncate"><?= esc($title_page) ?></div>
                <div class="hidden sm:block w-full overflow-hidden text-xs opacity-70 leading-tight truncate">
                    <?= $namaKabupaten !== '' ? 'Kabupaten ' . esc($namaKabupaten) . ' &middot; ' : '' ?><?= esc($scopeLabel) ?>
                </div>
            </div>
            <div class="flex-none hidden md:flex">
                <span class="badge badge-primary badge-outline gap-1">
                    <i class="fas fa-map-marker-alt"></i> <?= esc($scopeLabel) ?>
                </span>
            </div>
            <div class="flex-none hidden sm:block mx-1">
                <form method="get" action="<?= base_url('cari') ?>" class="join" role="search">
                    <input type="text" name="q" minlength="3" required
                           class="input input-bordered input-sm join-item w-40 lg:w-56"
                           placeholder="Cari nama / NIK / NKK…"
                           value="<?= esc((string) ($_GET['q'] ?? ''), 'attr') ?>"
                           title="Pencarian global: nama, NIK, atau NKK di seluruh data">
                    <button type="submit" class="btn btn-sm btn-primary join-item" title="Pencarian global" aria-label="Pencarian global">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
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
                        <i class="fas fa-chevron-down hidden sm:inline text-xs opacity-60"></i>
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
        <main class="p-3 md:p-5 pb-32 lg:pb-16 w-full max-w-[1600px] mx-auto">
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

        <footer class="footer footer-center app-footer">
            <div class="space-y-1">
                <p>Dikembangkan oleh: Amwaluddin Lubis @iamlubis &middot; PPK Ranto Baek &middot; Pemilu 2024</p>
            </div>
        </footer>
    </div>

    <!-- Sidebar -->
    <div class="drawer-side z-40">
        <label for="drawer-utama" class="drawer-overlay" aria-label="Tutup menu"></label>
        <aside class="bg-base-100 w-72 min-h-full flex flex-col border-r border-base-300 shadow-xl">
            <div class="relative overflow-hidden bg-gradient-to-br from-primary to-secondary p-5 text-primary-content">
                <div class="pointer-events-none absolute -right-7 -top-10 h-32 w-32 rounded-full border-[18px] border-white/10"></div>
                <div class="relative flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl shadow-inner">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-lg leading-tight">Data Pemilih</div>
                        <div class="mt-1 truncate text-xs opacity-80">
                            <?= $namaKabupaten !== '' ? 'Kabupaten ' . esc($namaKabupaten) : 'Aplikasi Analisis Data Pemilih' ?>
                        </div>
                    </div>
                </div>
                <div class="relative mt-4 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.16em] opacity-80">
                    <span class="h-1.5 w-1.5 rounded-full bg-success-content"></span>
                    Sistem operasional
                </div>
            </div>
            <div class="border-b border-base-200 p-3">
                <div class="flex items-center gap-3 rounded-2xl bg-base-200/70 p-3">
                    <div class="avatar placeholder">
                        <div class="w-11 rounded-xl bg-primary text-primary-content shadow-sm">
                            <span class="text-sm font-bold"><?= esc($inisial) ?></span>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-bold"><?= esc($penggunaLogin['nama']) ?></div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <span class="badge badge-primary badge-xs"><?= esc($labelPeran) ?></span>
                            <span class="truncate text-[11px] opacity-60"><?= esc($labelTingkat) ?></span>
                        </div>
                    </div>
                    <i class="fas fa-circle text-[7px] text-success" title="Sesi aktif" aria-label="Sesi aktif"></i>
                </div>
            </div>
            <?php if ($bebasWilayah) : ?>
                <div class="border-b border-base-200 px-3 py-3">
                    <form method="post" action="<?= base_url('kecamatan/aktifkan') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-1.5 flex items-center gap-2 px-1 text-[10px] font-bold uppercase tracking-[0.16em] opacity-55">
                            <i class="fas fa-map-marker-alt text-primary"></i> Cakupan wilayah
                        </div>
                        <label class="form-control">
                            <span class="sr-only">Pilih cakupan kecamatan</span>
                            <select name="kecamatan" class="select select-bordered select-sm w-full rounded-xl bg-base-100"
                                    onchange="this.form.submit()"
                                    title="Saring seluruh tampilan ke satu kecamatan (khusus admin kabupaten)">
                                <option value="__semua__" <?= $filterKecAktif === null ? 'selected' : '' ?>>Seluruh Kabupaten</option>
                                <?php foreach ($daftarKecFilter as $k) : ?>
                                    <option value="<?= esc($k['nama'], 'attr') ?>" <?= $filterKecAktif === $k['nama'] ? 'selected' : '' ?>>
                                        Kec. <?= esc(ucwords(strtolower($k['nama']))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>
                </div>
            <?php endif; ?>
            <nav class="flex-1 overflow-y-auto p-3" id="nav-samping" aria-label="Navigasi utama">
                <label class="input input-bordered input-sm mb-3 flex w-full items-center gap-2 rounded-xl bg-base-200/50 transition focus-within:border-primary focus-within:bg-base-100">
                    <i class="fas fa-search text-xs opacity-50"></i>
                    <input type="search" id="cari-menu-samping" class="grow" placeholder="Cari menu…"
                           autocomplete="off" aria-label="Cari menu navigasi">
                    <kbd class="kbd kbd-xs opacity-50">/</kbd>
                </label>
                <?php foreach ($grupMenu as $grup) : ?>
                    <?php if ($grup['grup'] === null) : ?>
                        <ul class="menu p-0 gap-1 text-sm w-full">
                            <?php foreach ($grup['menu'] as $kunci => $m) : ?>
                                <li>
                                    <a href="<?= base_url($kunci) ?>"
                                       class="rounded-xl py-2.5 font-medium transition duration-200 hover:translate-x-0.5 hover:bg-base-200 <?= $kunciAktif === $kunci ? 'active bg-primary text-primary-content shadow-md shadow-primary/20 hover:bg-primary' : '' ?>">
                                        <i class="fas <?= $m['ikon'] ?> w-5 text-center opacity-75"></i> <?= $m['label'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <?php $punyaAktif = $grupAktifNama === $grup['grup']; ?>
                        <div class="collapse collapse-arrow mb-1 rounded-2xl border border-transparent bg-base-200/40 transition-colors hover:border-base-300 hover:bg-base-200/70 <?= $punyaAktif ? 'punya-aktif border-primary/20 bg-primary/5' : '' ?>" data-grup="<?= esc($grup['grup'], 'attr') ?>">
                            <input type="checkbox" class="grup-cek min-h-0" <?= $punyaAktif ? 'checked' : '' ?> aria-label="Buka/tutup grup <?= esc($grup['grup'], 'attr') ?>">
                            <div class="collapse-title flex min-h-0 items-center gap-2 py-2.5 text-xs font-bold tracking-wide <?= $punyaAktif ? 'text-primary' : 'opacity-75' ?>">
                                <i class="fas <?= $grup['ikonGrup'] ?? 'fa-folder' ?> flex h-7 w-7 items-center justify-center rounded-lg bg-base-100 text-primary shadow-sm"></i>
                                <span><?= esc($grup['grup']) ?></span>
                            </div>
                            <div class="collapse-content px-2">
                                <ul class="menu w-full gap-1 p-0 text-sm">
                                    <?php foreach ($grup['menu'] as $kunci => $m) : ?>
                                        <li>
                                            <a href="<?= base_url($kunci) ?>"
                                               class="rounded-xl py-2 font-medium transition duration-200 hover:translate-x-0.5 hover:bg-base-200 <?= $kunciAktif === $kunci ? 'active bg-primary text-primary-content shadow-md shadow-primary/20 hover:bg-primary' : '' ?>">
                                                <i class="fas <?= $m['ikon'] ?> w-5 text-center opacity-75"></i> <?= $m['label'] ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <div class="border-t border-base-200 px-4 py-3">
                <div class="flex items-center justify-between text-[10px] opacity-50">
                    <span>Data Pemilih PPK</span>
                    <span class="font-semibold">CI4</span>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Modal konfirmasi bersama untuk tindakan penting -->
<dialog id="modal-konfirmasi" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg" id="judul-konfirmasi">Konfirmasi</h3>
        <p class="py-4 text-sm" id="pesan-konfirmasi"></p>
        <div class="modal-action">
            <button type="button" class="btn" id="batal-konfirmasi">Batal</button>
            <button type="button" class="btn btn-error" id="tombol-konfirmasi">Ya, Lanjutkan</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
</dialog>

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
<script src="<?= base_url('assets/js/htmx.min.js') ?>"></script>
<script defer src="<?= base_url('assets/js/alpine.min.js') ?>"></script>
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
// Modal konfirmasi DaisyUI pengganti confirm() bawaan peramban.
// Pemanggil memakai onsubmit="return mintaKonfirmasi(...)".
var formKonfirmasi = null;
function mintaKonfirmasi(form, judul, pesan, label, kelas) {
    formKonfirmasi = form;
    document.getElementById('judul-konfirmasi').textContent = judul;
    document.getElementById('pesan-konfirmasi').textContent = pesan;
    var tombol = document.getElementById('tombol-konfirmasi');
    tombol.textContent = label || 'Ya, Lanjutkan';
    tombol.className = 'btn ' + (kelas || 'btn-error');
    document.getElementById('modal-konfirmasi').showModal();
    return false;
}
document.getElementById('batal-konfirmasi').addEventListener('click', function () {
    document.getElementById('modal-konfirmasi').close();
    formKonfirmasi = null;
});
document.getElementById('tombol-konfirmasi').addEventListener('click', function () {
    var form = formKonfirmasi;
    formKonfirmasi = null;
    document.getElementById('modal-konfirmasi').close();
    if (form) form.submit();
});

// Pemberitahuan sukses biasa hilang sendiri; galat/info tetap tampil.
setTimeout(function () {
    document.querySelectorAll('.alert-success').forEach(function (el) {
        el.style.transition = 'opacity 400ms ease';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 450);
    });
}, 6000);
</script>
<script>
// Applying a select filter updates its HTMX table without requiring the
// explicit FILTER button. Keep dependent selects synchronized as well.
(function () {
    function initializeFilter(panel) {
        var controls = panel.querySelector('[data-filter-controls]');
        var apply = panel.querySelector('[data-filter-apply]');
        var reset = panel.querySelector('[data-filter-reset]');
        var chips = panel.querySelector('[data-filter-chips]');
        if (!controls || !apply) return;

        var fields = Array.prototype.slice.call(controls.querySelectorAll('select[name], input[type="text"][name]'));
        var suppressAutoApply = false;
        var toggle = document.createElement('button');
        var toggleLabel = document.createElement('span');
        var toggleCount = document.createElement('span');
        var toggleIcon = document.createElement('i');
        var controlsId = 'filter-controls-' + Math.random().toString(36).slice(2);
        var mobileFilters = window.matchMedia('(max-width: 639px)');
        var collapsed = mobileFilters.matches;

        toggle.type = 'button';
        toggle.className = 'btn btn-outline btn-sm filter-toggle';
        toggle.setAttribute('aria-controls', controlsId);
        toggleLabel.textContent = 'Filter & opsi';
        toggleCount.className = 'filter-toggle-count';
        toggleIcon.className = 'fas fa-chevron-down filter-toggle-icon';
        toggle.append(toggleLabel, toggleCount, toggleIcon);
        controls.id = controlsId;
        controls.classList.add('filter-controls-panel');
        controls.parentNode.insertBefore(toggle, controls);

        var table = panel.querySelector('[id^="wadah-tabel-"], #wadah-hasil-coklit');
        if (table) table.classList.add('filter-table-panel');

        function setCollapsed(value) {
            collapsed = value;
            controls.classList.toggle('filter-controls-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggleIcon.classList.toggle('fa-chevron-down', collapsed);
            toggleIcon.classList.toggle('fa-chevron-up', !collapsed);
        }

        toggle.addEventListener('click', function () {
            setCollapsed(!collapsed);
        });
        mobileFilters.addEventListener('change', function (event) {
            setCollapsed(event.matches);
        });
        setCollapsed(collapsed);

        function refreshChips() {
            var activeCount = fields.filter(function (field) {
                return field.value.trim() !== '';
            }).length;
            toggleCount.textContent = activeCount ? activeCount + ' aktif' : '';
            if (!chips) return;
            chips.replaceChildren();

            fields.forEach(function (field) {
                var value = field.value.trim();
                if (!value) return;

                var label = field.closest('label');
                var labelText = label && label.querySelector('.label-text');
                var name = labelText ? labelText.textContent.trim() : field.name;
                var displayValue = field.tagName === 'SELECT'
                    ? field.options[field.selectedIndex].textContent.trim()
                    : value;
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'badge badge-outline h-auto min-h-7 gap-1.5 px-2.5 py-1 font-medium hover:badge-primary';
                chip.textContent = name + ': ' + displayValue + '  x';
                chip.setAttribute('aria-label', 'Hapus filter ' + name);
                chip.addEventListener('click', function () {
                    field.value = '';
                    if (field.tagName === 'SELECT') {
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
                chips.appendChild(chip);
            });

            var kosong = chips.childElementCount === 0;
            chips.hidden = kosong;
            chips.classList.toggle('hidden', kosong);
        }

        fields.forEach(function (field) {
            field.addEventListener('input', refreshChips);
            field.addEventListener('change', function () {
                if (field.dataset.clearSelect) {
                    var dependent = panel.querySelector(field.dataset.clearSelect);
                    if (dependent && dependent.value) {
                        suppressAutoApply = true;
                        dependent.value = '';
                        dependent.dispatchEvent(new Event('change', { bubbles: true }));
                        suppressAutoApply = false;
                    }
                }

                refreshChips();
                if (!suppressAutoApply) apply.click();
            });
        });

        if (reset) {
            reset.addEventListener('click', function () {
                suppressAutoApply = true;
                var changedSelects = [];
                fields.forEach(function (field) {
                    if (!field.value) return;
                    field.value = '';
                    if (field.tagName === 'SELECT') changedSelects.push(field);
                });
                changedSelects.forEach(function (field) {
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                });
                suppressAutoApply = false;
                refreshChips();
                apply.click();
            });
        }

        refreshChips();
    }

    document.querySelectorAll('[data-filter-panel]').forEach(initializeFilter);
})();
</script>
<script>
// Filter TPS dinamis: isi pilihan #TPS dari nomor yang benar-benar
// ada pada data halaman/desa aktif, bukan daftar tetap 1–5. Nomor
// urut permintaan mencegah jawaban lama menimpa daftar desa terbaru
// bila pengguna mengganti desa saat server sedang sibuk.
var urutanMuatTps = 0;
function muatDaftarTps(desa) {
    var $tps = $('#TPS');
    if (!$tps.length) return;
    var sumber = $tps.data('sumber') || 'semua';
    var urutan = ++urutanMuatTps;
    $tps.prop('disabled', true);
    $.getJSON("<?= base_url('filter/tps') ?>", { desa: desa || '', sumber: sumber })
        .done(function (res) {
            if (urutan !== urutanMuatTps) return;
            $tps.empty().append('<option value="">PILIH TPS</option>');
            (res.tps || []).forEach(function (nomor) {
                $tps.append($('<option>').val(nomor).text('TPS ' + nomor));
            });
        })
        .fail(function () {
            if (urutan !== urutanMuatTps) return;
            $tps.empty().append('<option value="">PILIH TPS</option>');
        })
        .always(function () {
            if (urutan === urutanMuatTps) $tps.prop('disabled', false);
        });
}
</script>
<script>
// Menu sidebar parent-child memakai komponen collapse DaisyUI.
// Skrip ini hanya mengatur perilaku akordeon (satu grup terbuka) dan
// mengingat grup terakhir per peramban; grup berisi halaman aktif
// selalu terbuka (dicentang dari server).
(function () {
    var grups = Array.prototype.slice.call(document.querySelectorAll('#nav-samping [data-grup]'));
    var pencarian = document.getElementById('cari-menu-samping');
    if (!grups.length) return;
    var adaAktif = grups.some(function (g) { return g.classList.contains('punya-aktif'); });
    if (!adaAktif) {
        var simpan = null;
        try { simpan = localStorage.getItem('grup-menu-pemilih'); } catch (e) {}
        grups.forEach(function (g) {
            var cek = g.querySelector('.grup-cek');
            if (cek) cek.checked = g.getAttribute('data-grup') === simpan;
        });
    }
    grups.forEach(function (g) {
        var cek = g.querySelector('.grup-cek');
        if (!cek) return;
        cek.addEventListener('change', function () {
            if (cek.checked) {
                grups.forEach(function (lain) {
                    if (lain !== g) {
                        var c = lain.querySelector('.grup-cek');
                        if (c) c.checked = false;
                    }
                });
                try { localStorage.setItem('grup-menu-pemilih', g.getAttribute('data-grup')); } catch (e) {}
            } else {
                try { localStorage.removeItem('grup-menu-pemilih'); } catch (e) {}
            }
        });
    });

    function pulihkanGrup() {
        var simpan = null;
        try { simpan = localStorage.getItem('grup-menu-pemilih'); } catch (e) {}
        grups.forEach(function (g) {
            g.hidden = false;
            var cek = g.querySelector('.grup-cek');
            if (cek) cek.checked = g.classList.contains('punya-aktif') || (!adaAktif && g.getAttribute('data-grup') === simpan);
        });
        document.querySelectorAll('#nav-samping > ul.menu').forEach(function (ul) {
            ul.hidden = false;
            ul.querySelectorAll(':scope > li').forEach(function (li) { li.hidden = false; });
        });
    }

    function saringMenu() {
        var kata = (pencarian.value || '').trim().toLocaleLowerCase('id');
        document.querySelectorAll('#nav-samping ul.menu').forEach(function (ul) {
            var cocok = 0;
            ul.querySelectorAll(':scope > li').forEach(function (li) {
                var tampil = kata === '' || li.textContent.toLocaleLowerCase('id').includes(kata);
                li.hidden = !tampil;
                if (tampil) cocok++;
            });
            var grup = ul.closest('[data-grup]');
            if (grup) {
                grup.hidden = kata !== '' && cocok === 0;
                if (kata !== '' && cocok > 0) {
                    var cek = grup.querySelector('.grup-cek');
                    if (cek) cek.checked = true;
                }
            } else {
                ul.hidden = kata !== '' && cocok === 0;
            }
        });
        if (kata === '') pulihkanGrup();
    }

    if (pencarian) {
        pencarian.addEventListener('input', saringMenu);
        document.addEventListener('keydown', function (ev) {
            var target = ev.target;
            var sedangMengetik = target && (target.isContentEditable || /INPUT|TEXTAREA|SELECT/.test(target.tagName));
            if (ev.key === '/' && !sedangMengetik && !ev.ctrlKey && !ev.metaKey && !ev.altKey) {
                ev.preventDefault();
                pencarian.focus();
            }
            if (ev.key === 'Escape' && document.activeElement === pencarian) {
                pencarian.value = '';
                saringMenu();
            }
        });
    }
})();
</script>
<script>
// Select yang bisa dicari/diketik (combobox): setiap <select> DaisyUI
// diberi kotak ketik + daftar pilihan yang menyaring saat mengetik.
// Select aslinya disembunyikan tetapi tetap di DOM — nilai, atribut
// hx-*, dan handler change lama (HTMX/jQuery/inline) tetap bekerja
// karena pilihan diterapkan ke select lalu peristiwa change dikirim.
(function () {
    function labelTerpilih(sel) {
        var op = sel.options[sel.selectedIndex];
        return op ? op.textContent.trim() : '';
    }

    function tingkatkan(sel) {
        if (sel.dataset.bisaDicari !== undefined || sel.multiple) return;
        sel.dataset.bisaDicari = '1';

        // Select yang ditukar HTMX (outerHTML, mis. daftar TPS) meninggalkan
        // wadah lama sebagai yatim tepat sesudahnya — buang dulu.
        var yatim = sel.nextElementSibling;
        if (yatim && yatim.classList.contains('wadah-cari')) yatim.remove();

        var wadah = document.createElement('div');
        wadah.className = 'dropdown wadah-cari' + (sel.classList.contains('w-full') ? ' w-full' : '');

        var masukan = document.createElement('input');
        masukan.type = 'text';
        masukan.autocomplete = 'off';
        masukan.setAttribute('aria-label', 'Ketik untuk mencari pilihan');
        var ukuran = sel.classList.contains('select-xs') ? 'input-xs'
            : sel.classList.contains('select-sm') ? 'input-sm'
            : sel.classList.contains('select-lg') ? 'input-lg' : '';
        var kelasLebar = Array.prototype.filter.call(sel.classList, function (k) { return /^w-/.test(k); });
        masukan.className = 'input input-bordered w-full ' + ukuran + ' ' + kelasLebar.join(' ');
        masukan.placeholder = 'Ketik untuk mencari…';

        var daftar = document.createElement('ul');
        daftar.className = 'dropdown-content menu bg-base-100 rounded-box z-50 w-full p-2 shadow-lg border border-base-300 max-h-60 flex-nowrap overflow-y-auto';

        sel.parentNode.insertBefore(wadah, sel.nextSibling);
        wadah.appendChild(masukan);
        wadah.appendChild(daftar);
        sel.classList.add('hidden');

        function saring(teks) {
            var t = (teks || '').toLowerCase();
            Array.prototype.forEach.call(daftar.children, function (li) {
                li.style.display = li.textContent.toLowerCase().includes(t) ? '' : 'none';
            });
        }

        function bangunDaftar() {
            daftar.innerHTML = '';
            Array.prototype.forEach.call(sel.options, function (op) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.textContent = op.textContent.trim();
                if (op.value === sel.value) a.classList.add('active');
                a.addEventListener('mousedown', function (ev) {
                    ev.preventDefault();
                    sel.value = op.value;
                    masukan.value = labelTerpilih(sel);
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    masukan.blur();
                });
                li.appendChild(a);
                daftar.appendChild(li);
            });
        }

        function segarkanMasukan() {
            masukan.value = labelTerpilih(sel);
            bangunDaftar();
        }

        masukan.addEventListener('focus', function () {
            bangunDaftar();
            saring(masukan.value === labelTerpilih(sel) ? '' : masukan.value);
            masukan.select();
        });
        masukan.addEventListener('input', function () { saring(masukan.value); });
        masukan.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') {
                var pertama = Array.prototype.find.call(daftar.children, function (li) { return li.style.display !== 'none'; });
                if (pertama) {
                    ev.preventDefault();
                    pertama.querySelector('a').dispatchEvent(new MouseEvent('mousedown'));
                }
            } else if (ev.key === 'Escape') {
                masukan.value = labelTerpilih(sel);
                masukan.blur();
            }
        });
        masukan.addEventListener('blur', function () {
            setTimeout(function () { masukan.value = labelTerpilih(sel); }, 150);
        });
        sel.addEventListener('change', segarkanMasukan);
        new MutationObserver(function () { segarkanMasukan(); }).observe(sel, { childList: true });

        segarkanMasukan();
    }

    function tingkatkanSemua(akar) {
        if (!akar || !akar.querySelectorAll) return;
        if (akar.matches && akar.matches('select.select')) tingkatkan(akar);
        akar.querySelectorAll('select.select').forEach(tingkatkan);
    }

    document.addEventListener('DOMContentLoaded', function () { tingkatkanSemua(document); });
    // Select baru yang muncul lewat tukar HTMX (termasuk select yang
    // menggantikan select lama) ditingkatkan otomatis.
    new MutationObserver(function (mutasi) {
        mutasi.forEach(function (m) {
            m.addedNodes.forEach(function (n) {
                if (n.nodeType === 1) tingkatkanSemua(n);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
<script>
<?= $this->renderSection('javascript') ?>
</script>
</body>
</html>
