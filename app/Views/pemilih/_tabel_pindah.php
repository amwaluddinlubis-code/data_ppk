<?php
/** Potongan tabel Pemilih Pindah TPS (HTMX). Variabel dari Pemilih::dataPindah(). */
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
                <th>NIK</th>
                <th><?= judulUrut('NAMA', 'nama', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th><?= judulUrut('DESA', 'desa', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th><?= judulUrut('TPS', 'tps', $urut, $arah, $urlTabel, $target, $saring) ?></th>
                <th>DESA (BARU)</th>
                <th>TPS (BARU)</th>
                <th>NAMA (BARU)</th>
                <th>L/P</th>
                <th>KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []) : ?>
                <tr><td colspan="10" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r) : ?>
                <?php // Merah muda: belum ada padanan pemilih baru (belum
                       // punya TPS tujuan) — sesuai catatan di halaman. ?>
                <tr class="<?= empty($r['MSDESA']) ? 'row-tersaring' : '' ?>">
                    <td><?= $awal + $i ?></td>
                    <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                    <td><?= esc($r['NAMA']) ?></td>
                    <td><?= esc($r['DESA']) ?></td>
                    <td><?= esc($r['TPS']) ?></td>
                    <td><?= esc($r['MSDESA'] ?? '-') ?></td>
                    <td><?= esc($r['MSTPS'] ?? '-') ?></td>
                    <td><?= esc($r['MSNAMA'] ?? '-') ?></td>
                    <td><?= esc($r['MSLP'] ?? '') ?></td>
                    <td><?= esc(\App\Libraries\KodeBiodata::STATUS[(string) $r['STATUS']] ?? (string) ($r['STATUS'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= view('layout/_paginasi_htmx', array_merge($paginasi, ['bagian' => 'bawah'])) ?>
