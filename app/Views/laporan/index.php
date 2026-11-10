<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4 print:shadow-none">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-4 print:hidden">
            <form method="get" class="flex items-end gap-3">
                <label class="form-control w-full sm:w-64">
                    <span class="label"><span class="label-text font-semibold">RINCIAN PER TPS — PILIH DESA</span></span>
                    <select name="desa" class="select select-bordered select-sm" onchange="this.form.submit()">
                        <option value="">— SEMUA DESA (RINGKASAN) —</option>
                        <?php foreach ($daftarDesa as $d) : ?>
                            <option value="<?= esc($d['nilai'], 'attr') ?>" <?= $pilihanAktif === $d['nilai'] ? 'selected' : '' ?>><?= esc($d['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
            <button type="button" class="btn btn-secondary btn-sm ml-auto" onclick="window.print()">
                <i class="fas fa-print"></i> CETAK HALAMAN INI
            </button>
        </div>

        <h3 class="font-bold mb-2">Ringkasan Per Desa — <?= $kecamatan ? 'Kec. ' . esc(ucwords(strtolower($kecamatan))) : 'Seluruh Kabupaten ' . esc(ucwords(strtolower($kabupaten ?? ''))) ?></h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <?php if ($tampilKec) : ?><th>KECAMATAN</th><?php endif; ?>
                        <th>DESA</th>
                        <th class="text-right">TPS</th>
                        <th class="text-right">TOTAL COKLIT</th>
                        <th class="text-right">MS / AKTIF</th>
                        <th class="text-right">KK (MS)</th>
                        <th class="text-right">BARU</th>
                        <th class="text-right">TERSARING</th>
                        <th class="text-right">PINDAH TPS</th>
                        <th class="print:hidden">RINCIAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $jml = ['jumlah_tps' => 0, 'total' => 0, 'ms' => 0, 'kk' => 0, 'baru' => 0, 'tersaring' => 0, 'pindah' => 0];
                    foreach ($perDesa as $r) {
                        foreach (array_keys($jml) as $k) {
                            $jml[$k] += (int) $r[$k];
                        }
                    }
                    ?>
                    <?php foreach ($perDesa as $r) : ?>
                        <tr class="<?= $pilihanAktif === $r['KEC'] . '::' . $r['DESA'] ? 'font-bold bg-base-200' : '' ?>">
                            <?php if ($tampilKec) : ?><td><?= esc(ucwords(strtolower($r['KEC']))) ?></td><?php endif; ?>
                            <td><?= esc($r['DESA']) ?></td>
                            <td class="text-right"><?= number_format((int) $r['jumlah_tps'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['total'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['ms'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['kk'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['baru'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['tersaring'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $r['pindah'], 0, ',', '.') ?></td>
                            <td class="print:hidden">
                                <a href="<?= base_url('laporan') . '?desa=' . urlencode($r['KEC'] . '::' . $r['DESA']) ?>" class="btn btn-primary btn-xs">PER TPS</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="<?= $tampilKec ? 2 : 1 ?>">TOTAL</td>
                        <td class="text-right"><?= number_format($jml['jumlah_tps'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['total'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['ms'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['kk'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['baru'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['tersaring'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($jml['pindah'], 0, ',', '.') ?></td>
                        <td class="print:hidden"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Catatan: jumlah TPS pada baris TOTAL adalah penjumlahan TPS per desa. Angka KK dihitung dari daftar MS.</p>
    </div>
</div>

<?php if ($desaDipilih !== '') : ?>
    <div class="card bg-base-100 shadow print:shadow-none">
        <div class="card-body p-4">
            <h3 class="font-bold mb-2">Rincian Per TPS — Desa <?= esc($desaDipilih) ?><?= $kecDipilih ? ', Kec. ' . esc(ucwords(strtolower($kecDipilih))) : '' ?></h3>
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full max-w-3xl">
                    <thead>
                        <tr>
                            <th>TPS</th>
                            <th class="text-right">TOTAL COKLIT</th>
                            <th class="text-right">MS / AKTIF</th>
                            <th class="text-right">KK (MS)</th>
                            <th class="text-right">BARU</th>
                            <th class="text-right">TERSARING</th>
                            <th class="text-right">PINDAH TPS</th>
                            <th class="print:hidden">DAFTAR MS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perTps as $r) : ?>
                            <tr>
                                <td>TPS <?= esc($r['TPS']) ?></td>
                                <td class="text-right"><?= number_format((int) $r['total'], 0, ',', '.') ?></td>
                                <td class="text-right"><?= number_format((int) $r['ms'], 0, ',', '.') ?></td>
                                <td class="text-right"><?= number_format((int) $r['kk'], 0, ',', '.') ?></td>
                                <td class="text-right"><?= number_format((int) $r['baru'], 0, ',', '.') ?></td>
                                <td class="text-right"><?= number_format((int) $r['tersaring'], 0, ',', '.') ?></td>
                                <td class="text-right"><?= number_format((int) $r['pindah'], 0, ',', '.') ?></td>
                                <td class="print:hidden whitespace-nowrap">
                                    <a href="<?= base_url('pemilih/ms') . '?desa=' . urlencode($desaDipilih) . '&tps=' . urlencode($r['TPS']) ?>" target="_blank" class="btn btn-ghost btn-xs">HTML</a>
                                    <a href="<?= base_url('pemilih/ms-pdf') . '?desa=' . urlencode($desaDipilih) . '&tps=' . urlencode($r['TPS']) ?>" target="_blank" class="btn btn-secondary btn-xs">PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
