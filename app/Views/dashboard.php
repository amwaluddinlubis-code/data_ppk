<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$fmt = static fn ($n) => number_format((int) $n, 0, ',', '.');
$totalMs = max(1, (int) $stats['ms']);
$pctL = $stats['ms'] > 0 ? round($stats['laki'] / $totalMs * 100, 1) : 0;
$pctP = $stats['ms'] > 0 ? round($stats['perempuan'] / $totalMs * 100, 1) : 0;
$kartu = [
    ['label' => 'Total Hasil Coklit', 'nilai' => $stats['total'], 'ikon' => 'fa-clipboard-list', 'warna' => 'bg-primary text-primary-content'],
    ['label' => 'Pemilih MS (Aktif)', 'nilai' => $stats['ms'], 'ikon' => 'fa-user-check', 'warna' => 'bg-success text-success-content'],
    ['label' => 'Pemilih Baru', 'nilai' => $stats['baru'], 'ikon' => 'fa-user-plus', 'warna' => 'bg-info text-info-content'],
    ['label' => 'Tersaring (TMS)', 'nilai' => $stats['tersaring'], 'ikon' => 'fa-filter', 'warna' => 'bg-error text-error-content'],
    ['label' => 'Pindah TPS', 'nilai' => $stats['pindah'], 'ikon' => 'fa-exchange-alt', 'warna' => 'bg-warning text-warning-content'],
    ['label' => 'Jumlah KK (MS)', 'nilai' => $stats['kk'], 'ikon' => 'fa-home', 'warna' => 'bg-secondary text-secondary-content'],
];
?>
<?php
$konteksJenis = \App\Libraries\Konteks::jenis();
$konteksNama = \App\Libraries\Konteks::nama();
$batasTps = \App\Libraries\Konteks::batasTps();
$tglPungut = \App\Libraries\Konteks::tanggalPemungutan();
?>
<!-- Konteks penyelenggaraan aktif (Fase A) -->
<div class="flex flex-wrap items-center gap-2 mb-3">
    <span class="badge badge-primary gap-1"><i class="fas fa-flag"></i> <?= esc($konteksNama) ?></span>
    <span class="badge badge-outline"><?= esc(\App\Libraries\Konteks::labelJenis()) ?></span>
    <?php if ($batasTps !== null) : ?>
        <span class="badge badge-outline">Batas <?= $batasTps ?> pemilih/TPS</span>
    <?php endif; ?>
    <?php if ($tglPungut !== null) : ?>
        <span class="badge badge-outline">Pemungutan: <?= esc(date('d-m-Y', strtotime($tglPungut))) ?></span>
    <?php endif; ?>
    <?php if (session()->get('pengguna_peran') === 'admin') : ?>
        <a href="<?= base_url('konteks') ?>" class="link link-primary text-xs">Ubah konteks</a>
    <?php endif; ?>
</div>

<!-- Kartu statistik -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
    <?php foreach ($kartu as $k) : ?>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-wide opacity-60 truncate"><?= esc($k['label']) ?></div>
                        <div class="text-2xl md:text-3xl font-bold leading-tight"><?= $fmt($k['nilai']) ?></div>
                    </div>
                    <div class="rounded-xl <?= $k['warna'] ?> w-10 h-10 shrink-0 flex items-center justify-center">
                        <i class="fas <?= $k['ikon'] ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Perlu Ditangani: pintu tugas harian (Paket Menengah) -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <div class="flex items-center gap-2 mb-3">
            <i class="fas fa-clipboard-check text-warning"></i>
            <span class="font-bold text-sm">PERLU DITANGANI</span>
            <span class="text-xs opacity-60">Temuan data yang menunggu pemeriksaan Anda — klik untuk membuka daftarnya.</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            <?php
            $kartuPerlu = [
                ['label' => 'NIK Ganda Antar Wilayah', 'nilai' => $perlu['ganda'], 'satuan' => 'NIK', 'tautan' => base_url('residu'), 'ikon' => 'fa-clone'],
                ['label' => 'Peringatan JK vs NIK', 'nilai' => $perlu['peringatan'], 'satuan' => 'pemilih', 'tautan' => base_url('residu'), 'ikon' => 'fa-venus-mars'],
                ['label' => 'Duplikat Terselubung', 'nilai' => $perlu['terselubung'], 'satuan' => 'kelompok', 'tautan' => base_url('anomali'), 'ikon' => 'fa-user-secret'],
                ['label' => 'KK Tersebar', 'nilai' => $perlu['kk'], 'satuan' => 'KK', 'tautan' => base_url('anomali'), 'ikon' => 'fa-house-circle-exclamation'],
                ['label' => 'Umur Ekstrem', 'nilai' => $perlu['umur'], 'satuan' => 'pemilih', 'tautan' => base_url('anomali'), 'ikon' => 'fa-cake-candles'],
            ];
            ?>
            <?php foreach ($kartuPerlu as $k) : ?>
                <a href="<?= $k['tautan'] ?>" class="card <?= $k['nilai'] > 0 ? 'bg-warning/10 border border-warning/40' : 'bg-base-200/60' ?> hover:shadow transition">
                    <div class="card-body p-3">
                        <div class="flex items-center gap-2 text-xs font-semibold opacity-70">
                            <i class="fas <?= $k['ikon'] ?>"></i> <?= esc($k['label']) ?>
                        </div>
                        <div class="text-2xl font-bold mt-1">
                            <?= number_format($k['nilai'], 0, ',', '.') ?> <span class="text-xs font-normal opacity-60"><?= esc($k['satuan']) ?></span>
                        </div>
                        <div class="text-xs <?= $k['nilai'] > 0 ? 'text-warning font-semibold' : 'opacity-50' ?>">
                            <?= $k['nilai'] > 0 ? 'Perlu diperiksa →' : 'Bersih' ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- PDPB periode berjalan (Fase B) -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <i class="fas fa-sync-alt text-primary"></i>
            <span class="font-bold text-sm">PDPB — PERIODE BERJALAN</span>
            <span class="badge badge-outline badge-sm"><?= esc($pdpb['nama']) ?></span>
            <a href="<?= base_url('pdpb') ?>" class="link link-primary text-xs ml-auto">Buka Rekap PDPB →</a>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-base-200/60 rounded-box p-3">
                <div class="text-xs font-semibold opacity-70">PEMILIH BARU</div>
                <div class="text-2xl font-bold mt-1"><?= $fmt($pdpb['baru']) ?></div>
            </div>
            <div class="bg-base-200/60 rounded-box p-3">
                <div class="text-xs font-semibold opacity-70">MASUK TMS</div>
                <div class="text-2xl font-bold mt-1"><?= $fmt($pdpb['tms']) ?></div>
            </div>
            <div class="bg-base-200/60 rounded-box p-3">
                <div class="text-xs font-semibold opacity-70">UBAH ELEMEN DATA</div>
                <div class="text-2xl font-bold mt-1"><?= $fmt($pdpb['ubah']) ?></div>
            </div>
        </div>
        <p class="text-xs opacity-60 mt-2">Perubahan tercatat pada wilayah Anda selama periode PDPB berjalan — direkap paling sedikit setiap 3 bulan (PKPU 1/2025 Pasal 9 ayat (2)).</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-3 mb-4">
    <!-- Rincian L/P pada daftar MS -->
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="font-semibold text-sm mb-1">Pemilih MS menurut Jenis Kelamin</div>
            <div class="text-xs opacity-60 mb-3">Dari <?= $fmt($stats['ms']) ?> pemilih memenuhi syarat pada wilayah aktif.</div>
            <div class="flex justify-between text-sm mb-1">
                <span><i class="fas fa-male text-primary"></i> Laki-laki</span>
                <span class="font-semibold"><?= $fmt($stats['laki']) ?> (<?= $pctL ?>%)</span>
            </div>
            <progress class="progress progress-primary w-full" value="<?= $pctL ?>" max="100"></progress>
            <div class="flex justify-between text-sm mb-1 mt-3">
                <span><i class="fas fa-female text-secondary"></i> Perempuan</span>
                <span class="font-semibold"><?= $fmt($stats['perempuan']) ?> (<?= $pctP ?>%)</span>
            </div>
            <progress class="progress progress-secondary w-full" value="<?= $pctP ?>" max="100"></progress>
            <div class="grid grid-cols-2 gap-2 mt-4 text-center">
                <div class="bg-base-200 rounded-lg p-2">
                    <div class="text-lg font-bold"><?= $fmt($stats['desa']) ?></div>
                    <div class="text-[11px] uppercase opacity-60">Desa/Kelurahan</div>
                </div>
                <div class="bg-base-200 rounded-lg p-2">
                    <div class="text-lg font-bold"><?= $fmt($stats['tps']) ?></div>
                    <div class="text-[11px] uppercase opacity-60">TPS</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rincian per wilayah -->
    <div class="card bg-base-100 shadow xl:col-span-2" x-data="{ terbuka: true }">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold text-sm">
                    Rincian per <?= $perKecamatan ? 'Kecamatan' : 'Desa' ?>
                </div>
                <button type="button" class="btn btn-ghost btn-xs" @click="terbuka = !terbuka">
                    <i class="fas" :class="terbuka ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    <span x-text="terbuka ? 'Sembunyikan' : 'Tampilkan'"></span>
                </button>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto hidden" x-bind:class="{ 'hidden': !terbuka }">
                <table class="table table-sm table-zebra w-full">
                    <thead>
                        <tr>
                            <th><?= $perKecamatan ? 'Kecamatan' : 'Desa' ?></th>
                            <th class="text-right">Total</th>
                            <th class="w-40 hidden md:table-cell">MS</th>
                            <th class="text-right">MS</th>
                            <th class="text-right hidden sm:table-cell">Baru</th>
                            <th class="text-right hidden sm:table-cell">Tersaring</th>
                            <th class="text-right hidden lg:table-cell">TPS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perWilayah as $w) : ?>
                            <?php $pctMs = (int) $w['total'] > 0 ? round($w['ms'] / $w['total'] * 100) : 0; ?>
                            <tr>
                                <td class="font-medium"><?= esc(ucwords(strtolower($w['wilayah']))) ?></td>
                                <td class="text-right"><?= $fmt($w['total']) ?></td>
                                <td class="hidden md:table-cell">
                                    <progress class="progress progress-success w-full" value="<?= $pctMs ?>" max="100"></progress>
                                </td>
                                <td class="text-right"><?= $fmt($w['ms']) ?></td>
                                <td class="text-right hidden sm:table-cell"><?= $fmt($w['baru']) ?></td>
                                <td class="text-right hidden sm:table-cell"><?= $fmt($w['tersaring']) ?></td>
                                <td class="text-right hidden lg:table-cell"><?= $fmt($w['jumlah_tps']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($perWilayah === []) : ?>
                            <tr><td colspan="7" class="text-center opacity-60">Belum ada data coklit pada wilayah ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hasil Coklit Pantarlih (pilot HTMX + Alpine) -->
<div class="card bg-base-100 shadow" x-data="{ memuat: false }"
     @htmx:before-request="memuat = true" @htmx:after-request="memuat = false">
    <div class="card-body p-4">
        <div class="flex items-center gap-2 mb-1">
            <div class="font-semibold text-sm">Hasil Coklit Pantarlih</div>
            <span class="badge badge-info badge-sm hidden" x-bind:class="{ 'hidden': !memuat }">
                <span class="loading loading-spinner loading-xs"></span> Memuat…
            </span>
        </div>
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa"
                        hx-get="<?= base_url('filter/tps-opsi') ?>" hx-target="#TPS" hx-swap="innerHTML"
                        hx-vals='{"sumber":"semua"}'>
                    <option value="">PILIH DESA</option>
                    <?php foreach ($arrDesa as $d) : ?>
                        <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-36">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <select class="select select-bordered select-sm" id="TPS" name="tps">
                    <option value="">PILIH TPS</option>
                    <?php foreach ($tpsAwal as $nomor) : ?>
                        <option value="<?= esc($nomor, 'attr') ?>">TPS <?= esc($nomor) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">KATEGORI</span></span>
                <select class="select select-bordered select-sm" id="KET" name="tms">
                    <option value="">PILIH KATEGORI</option>
                    <option value="0">0 — PEMILIH SESUAI</option>
                    <option value="U">U — PEMILIH UBAH</option>
                    <option value="B">B — PEMILIH BARU</option>
                    <option value="MS">SEMUA PEMILIH MS (KODE 0, U, B)</option>
                    <option value="TMS">SEMUA PEMILIH TERSARING / TMS (KODE 1–8)</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-44">
                <span class="label"><span class="label-text font-semibold">L/P</span></span>
                <select class="select select-bordered select-sm" id="LP" name="lp">
                    <option value="">PILIH L/P</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm"
                        hx-get="<?= base_url('dashboard/tabelPemilih') ?>"
                        hx-target="#wadah-hasil-coklit" hx-swap="innerHTML"
                        hx-include="[name='desa'],[name='tps'],[name='tms'],[name='lp']">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url() ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div id="wadah-hasil-coklit">
            <?= view('dashboard/_tabel_pemilih', $tabelAwal) ?>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna merah muda = keterangan (KET) berupa kode angka (tersaring/bermasalah). Bagian ini memakai HTMX: saringan &amp; halaman ditukar tanpa memuat ulang halaman.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
// Pilot HTMX: tabel Hasil Coklit Dasbor tidak lagi memakai
// DataTables/jQuery — saringan, opsi TPS, dan pagination berjalan
// lewat atribut hx-* (HTMX) dan status muat lewat Alpine.js.
<?= $this->endSection() ?>
