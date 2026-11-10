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
    <style>
        [x-cloak] { display: none !important; }
        /* Menu sidebar parent-child: anak grup tersembunyi sampai
           induknya dibuka (akordeon). */
        .menu-grup .menu-anak { display: none; }
        .menu-grup.terbuka .menu-anak { display: block; }
        .menu-grup .panah-grup { transition: transform 200ms ease; }
        .menu-grup.terbuka .panah-grup { transform: rotate(180deg); }
        .menu-grup.punya-aktif > .menu-induk { font-weight: 700; color: hsl(var(--p)); }
    </style>
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
        'impor'   => ['label' => 'Impor Data', 'ikon' => 'fa-file-import'],
        'riwayat' => ['label' => 'Riwayat Perubahan', 'ikon' => 'fa-history'],
    ]];
}
$grupMenu[] = ['grup' => 'Laporan', 'ikonGrup' => 'fa-chart-bar', 'menu' => [
    'laporan' => ['label' => 'Laporan Ringkasan', 'ikon' => 'fa-chart-line'],
]];
$menuAdmin = ['kecamatan' => ['label' => 'Kecamatan', 'ikon' => 'fa-map-marked-alt']];
if ($penggunaLogin['peran'] === 'admin') {
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
            <?php if ($bebasWilayah) : ?>
                <div class="px-4 py-3 border-b border-base-200">
                    <form method="post" action="<?= base_url('kecamatan/aktifkan') ?>">
                        <?= csrf_field() ?>
                        <label class="form-control">
                            <span class="label py-0 pb-1">
                                <span class="label-text text-[11px] font-bold uppercase tracking-wider opacity-60">
                                    <i class="fas fa-filter"></i> Filter Kecamatan
                                </span>
                            </span>
                            <select name="kecamatan" class="select select-bordered select-sm w-full"
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
            <nav class="flex-1 overflow-y-auto p-3" id="nav-samping">
                <?php foreach ($grupMenu as $grup) : ?>
                    <?php if ($grup['grup'] === null) : ?>
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
                    <?php else : ?>
                        <?php $punyaAktif = $grupAktifNama === $grup['grup']; ?>
                        <div class="menu-grup <?= $punyaAktif ? 'terbuka punya-aktif' : '' ?>" data-grup="<?= esc($grup['grup'], 'attr') ?>">
                            <button type="button"
                                    class="menu-induk w-full flex items-center gap-2 px-3 py-2 mt-2 rounded-lg text-[12px] font-bold uppercase tracking-wider opacity-80 hover:bg-base-200 hover:opacity-100"
                                    aria-expanded="<?= $punyaAktif ? 'true' : 'false' ?>">
                                <i class="fas <?= $grup['ikonGrup'] ?? 'fa-folder' ?> w-5 text-center"></i>
                                <span class="flex-1 text-left"><?= esc($grup['grup']) ?></span>
                                <i class="fas fa-chevron-down panah-grup text-[10px]"></i>
                            </button>
                            <ul class="menu menu-anak p-0 pl-3 gap-1 text-sm w-full">
                                <?php foreach ($grup['menu'] as $kunci => $m) : ?>
                                    <li>
                                        <a href="<?= base_url($kunci) ?>"
                                           class="<?= $kunciAktif === $kunci ? 'active' : '' ?>">
                                            <i class="fas <?= $m['ikon'] ?> w-5 text-center"></i> <?= $m['label'] ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <div class="p-3 text-[11px] opacity-50 border-t border-base-200">
                CodeIgniter 4 &middot; Tailwind + DaisyUI
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
<script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
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
// Menu sidebar parent-child (akordeon): satu grup terbuka dalam satu
// waktu. Grup berisi halaman aktif selalu terbuka; selain itu pilihan
// terakhir pengguna diingat per peramban.
(function () {
    var grups = Array.prototype.slice.call(document.querySelectorAll('#nav-samping .menu-grup'));
    if (!grups.length) return;
    function atur(grup, buka) {
        grup.classList.toggle('terbuka', buka);
        var induk = grup.querySelector('.menu-induk');
        if (induk) induk.setAttribute('aria-expanded', buka ? 'true' : 'false');
    }
    var adaAktif = grups.some(function (g) { return g.classList.contains('punya-aktif'); });
    if (!adaAktif) {
        var simpan = null;
        try { simpan = localStorage.getItem('grup-menu-pemilih'); } catch (e) {}
        grups.forEach(function (g) { atur(g, g.getAttribute('data-grup') === simpan); });
    }
    grups.forEach(function (g) {
        var induk = g.querySelector('.menu-induk');
        if (!induk) return;
        induk.addEventListener('click', function () {
            var akanBuka = !g.classList.contains('terbuka');
            grups.forEach(function (lain) { atur(lain, lain === g ? akanBuka : false); });
            try {
                if (akanBuka) localStorage.setItem('grup-menu-pemilih', g.getAttribute('data-grup'));
                else localStorage.removeItem('grup-menu-pemilih');
            } catch (e) {}
        });
    });
})();
</script>
<script>
<?= $this->renderSection('javascript') ?>
</script>
</body>
</html>
