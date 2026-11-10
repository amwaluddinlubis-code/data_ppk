<?php
/** Potongan tabel Duplikat Terselubung (HTMX). Variabel dari Anomali::dataTerselubung(). */
$awal = $total === 0 ? 0 : (($halaman - 1) * $perHal) + 1;
$paginasi = ['urlTabel' => $urlTabel, 'target' => $target, 'saring' => $saring, 'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal];
?>
<?= view('layout/_paginasi_htmx', array_merge($paginasi, ['bagian' => 'atas'])) ?>
<div class="overflow-x-auto">
    <table class="table table-zebra table-sm w-full">
        <thead>
            <tr>
                <th>NO</th>
                <th>NIK</th>
                <th>NKK</th>
                <th>NAMA</th>
                <th>TGL LAHIR</th>
                <th>L/P</th>
                <th>KECAMATAN</th>
                <th>DESA</th>
                <th>TPS</th>
                <th>KETERANGAN</th>
                <th>SUMBER</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []) : ?>
                <tr><td colspan="11" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r) : ?>
                <tr>
                    <td><?= $awal + $i ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NKK']) ?></td>
                    <td><?= esc($r['NAMA']) ?></td>
                    <td><?= esc($r['TGL_TAMPIL']) ?></td>
                    <td><?= esc($r['JENIS_KELAMIN']) ?></td>
                    <td><?= esc($r['KEC']) ?></td>
                    <td><?= esc($r['DESA']) ?></td>
                    <td><?= esc($r['TPS']) ?></td>
                    <td><?= esc($r['KET_LABEL']) ?></td>
                    <td><?= esc($r['SUMBER']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('layout/_paginasi_htmx', array_merge($paginasi, ['bagian' => 'bawah'])) ?>
