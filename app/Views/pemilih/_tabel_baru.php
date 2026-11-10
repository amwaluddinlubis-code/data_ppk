<?php
/** Potongan tabel Pemilih Baru (HTMX). Variabel dari Pemilih::dataBaru(). */
helper('tabel');
$awal = $total === 0 ? 0 : (($halaman - 1) * $perHal) + 1;
$paginasi = ['urlTabel' => $urlTabel, 'target' => $target, 'saring' => $saring, 'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal];
?>
<?= view('layout/_paginasi_htmx', array_merge($paginasi, ['bagian' => 'atas'])) ?>
<div class="overflow-x-auto">
    <table class="table table-zebra table-sm w-full">
        <thead>
            <tr>
                <th>NO</th>
                <th><?= judulUrut('DESA', 'desa', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th><?= judulUrut('TPS', 'tps', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th>NIK</th>
                <th><?= judulUrut('NAMA', 'nama', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th><?= judulUrut('TGL LAHIR', 'tgllhr', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th>L/P</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []) : ?>
                <tr><td colspan="7" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r) : ?>
                <?php // Baris biru muda: benar-benar baru (tanpa nama lama),
                       // sama seperti pewarnaan DataTables sebelumnya. ?>
                <tr class="<?= empty($r['OLD_NAMA']) ? 'row-baru-benar' : '' ?>">
                    <td><?= $awal + $i ?></td>
                    <td><?= esc($r['DESA']) ?></td>
                    <td><?= esc($r['TPS']) ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                    <td><?= esc($r['NAMA']) ?></td>
                    <td><?= esc($r['TGLLHR']) ?></td>
                    <td><?= esc($r['JENIS_KELAMIN']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('layout/_paginasi_htmx', array_merge($paginasi, ['bagian' => 'bawah'])) ?>
