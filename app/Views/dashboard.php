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
    <div class="card bg-base-100 shadow xl:col-span-2">
        <div class="card-body p-4">
            <div class="font-semibold text-sm mb-2">
                Rincian per <?= $perKecamatan ? 'Kecamatan' : 'Desa' ?>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
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

<!-- Hasil Coklit Pantarlih -->
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="font-semibold text-sm mb-1">Hasil Coklit Pantarlih</div>
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa">
                    <option value="">PILIH DESA</option>
                    <?php foreach ($arrDesa as $d) : ?>
                        <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-36">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <select class="select select-bordered select-sm" id="TPS">
                    <option value="">PILIH TPS</option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?= $i ?>">TPS <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">KATEGORI</span></span>
                <select class="select select-bordered select-sm" id="KET">
                    <option value="">PILIH KATEGORI</option>
                    <option value="0">PEMILIH SESUAI</option>
                    <option value="U">PEMILIH UBAH</option>
                    <option value="B">PEMILIH BARU</option>
                    <option value="TMS">PEMILIH MEMENUHI SYARAT</option>
                    <option value="8">PEMILIH TERSARING</option>
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
                <button type="button" class="btn btn-primary btn-sm" onclick="loadData()">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url() ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="MHC" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>NKK</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>TGL LAHIR</th>
                        <th>LAST UPDATE</th>
                        <th>KET</th>
                        <th>L/P</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna merah muda = keterangan (KET) berupa kode angka (tersaring/bermasalah).</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function loadData() {
    if ($.fn.DataTable.isDataTable('#MHC')) {
        $('#MHC').DataTable().destroy();
    }
    $('#MHC').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('dashboard/getPemilih') ?>",
            data: {
                'desa': $('#DESA').val(),
                'tms': $('#KET').val(),
                'tps': $('#TPS').val(),
                'lp': $('#LP').val()
            },
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false }
        ],
        createdRow: function (row, data) {
            if (data[8] > 0) {
                $(row).addClass('row-tersaring');
            }
        }
    });
}

$(document).ready(function () {
    loadData();
});
<?= $this->endSection() ?>
