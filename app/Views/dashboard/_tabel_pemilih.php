<?php
/**
 * Potongan tabel Hasil Coklit Pantarlih (pilot HTMX).
 *
 * Dirender server-side pada muat pertama Dasbor dan ditukar lewat
 * HTMX untuk saringan/pagination berikutnya — tidak ada DataTables
 * dan tidak ada muat ulang halaman pada bagian ini.
 */
$awal  = $total === 0 ? 0 : (($halaman - 1) * $perHal) + 1;
$akhir = min($total, $halaman * $perHal);
$tautan = static function (int $h) use ($saring, $perHal): string {
    $params = array_merge($saring, ['perhal' => $perHal, 'hal' => $h]);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    return base_url('dashboard/tabelPemilih') . '?' . http_build_query($params);
};
$jendela = range(max(1, $halaman - 2), min($totalHal, $halaman + 2));
?>
<div class="flex flex-wrap items-center gap-3 mb-2">
    <span class="text-sm opacity-70">Menampilkan <?= number_format($awal, 0, ',', '.') ?>–<?= number_format($akhir, 0, ',', '.') ?> dari <?= number_format($total, 0, ',', '.') ?> baris</span>
    <label class="text-sm ml-auto flex items-center gap-2">Per halaman
        <select name="perhal" class="select select-bordered select-sm"
                hx-get="<?= base_url('dashboard/tabelPemilih') ?>"
                hx-target="#wadah-hasil-coklit" hx-swap="innerHTML"
                hx-vals='<?= esc(json_encode(array_merge($saring, ['hal' => 1])), 'attr') ?>'>
            <?php foreach ([10, 25, 50] as $pilihan) : ?>
                <option value="<?= $pilihan ?>" <?= $perHal === $pilihan ? 'selected' : '' ?>><?= $pilihan ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</div>

<div class="overflow-x-auto">
    <table class="table table-zebra table-sm w-full">
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
        <tbody>
            <?php if ($rows === []) : ?>
                <tr><td colspan="10" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r) : ?>
                <tr class="<?= \App\Libraries\Keterangan::isTms((string) $r['KET']) ? 'bg-error/10' : '' ?>">
                    <td><?= $awal + $i ?></td>
                    <td><?= esc($r['DESA']) ?></td>
                    <td><?= esc($r['TPS']) ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NKK']) ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                    <td><?= esc($r['NAMA']) ?></td>
                    <td><?= esc($r['TGLLHR']) ?></td>
                    <td><?= esc($r['LU']) ?></td>
                    <td><?= esc($r['KET']) ?></td>
                    <td><?= esc($r['JENIS_KELAMIN']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="join mt-3">
    <?php if ($halaman > 1) : ?>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan(1) ?>" hx-target="#wadah-hasil-coklit" hx-swap="innerHTML">« Awal</button>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($halaman - 1) ?>" hx-target="#wadah-hasil-coklit" hx-swap="innerHTML">‹</button>
    <?php else : ?>
        <button type="button" class="btn btn-sm join-item" disabled>« Awal</button>
        <button type="button" class="btn btn-sm join-item" disabled>‹</button>
    <?php endif; ?>
    <?php foreach ($jendela as $h) : ?>
        <button type="button" class="btn btn-sm join-item <?= $h === $halaman ? 'btn-primary' : '' ?>" hx-get="<?= $tautan($h) ?>" hx-target="#wadah-hasil-coklit" hx-swap="innerHTML"><?= $h ?></button>
    <?php endforeach; ?>
    <?php if ($halaman < $totalHal) : ?>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($halaman + 1) ?>" hx-target="#wadah-hasil-coklit" hx-swap="innerHTML">›</button>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($totalHal) ?>" hx-target="#wadah-hasil-coklit" hx-swap="innerHTML">Akhir »</button>
    <?php else : ?>
        <button type="button" class="btn btn-sm join-item" disabled>›</button>
        <button type="button" class="btn btn-sm join-item" disabled>Akhir »</button>
    <?php endif; ?>
</div>
