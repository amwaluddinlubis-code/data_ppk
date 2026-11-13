<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-search text-primary"></i> Paket Uji Petik Bawaslu — <?= esc($periode['nama'] ?? '') ?></h2>
        <p class="text-sm">Agregat data hidup saat ini dan daftar perubahan pada periode terpilih. NIK selalu tersamar. Unduh CSV paket ini tercatat pada log ekspor.</p>
        <div class="mt-2 flex gap-2">
            <a class="btn btn-primary btn-sm" href="<?= base_url('bawaslu/uji-petik.csv?periode_id=' . (int) $periode['id']) ?>"><i class="fas fa-download"></i> Unduh CSV</a>
            <a class="btn btn-outline btn-sm" href="<?= base_url('bawaslu') ?>">Kembali</a>
        </div>
        <h3 class="font-bold text-sm mt-4 mb-2">Agregat per Desa/Kelurahan</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Kecamatan</th><th>Desa/Kelurahan</th><th class="text-right">MS</th><th class="text-right">TMS</th><th class="text-right">Laki-laki</th><th class="text-right">Perempuan</th></tr></thead>
                <tbody>
                <?php $t = ['ms' => 0, 'tms' => 0, 'laki' => 0, 'perempuan' => 0]; foreach ($agregat as $a) : foreach ($t as $k => $_) { $t[$k] += (int) $a[$k]; } ?>
                    <tr><td><?= esc($a['kecamatan']) ?></td><td><?= esc($a['kelurahan']) ?></td>
                        <td class="text-right"><?= number_format((int) $a['ms'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $a['tms'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $a['laki'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $a['perempuan'], 0, ',', '.') ?></td></tr>
                <?php endforeach ?>
                    <tr class="font-bold"><td colspan="2">TOTAL</td>
                        <td class="text-right"><?= number_format($t['ms'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($t['tms'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($t['laki'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($t['perempuan'], 0, ',', '.') ?></td></tr>
                </tbody>
            </table>
        </div>
        <h3 class="font-bold text-sm mt-4 mb-2">Daftar Perubahan Periode (maks. 2.000 terakhir, NIK tersamar)</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Waktu</th><th>Aksi</th><th>NIK</th><th>Dokumen Bukti</th><th>Keterangan</th><th>Petugas</th></tr></thead>
                <tbody>
                <?php foreach ($perubahan as $p) : ?>
                    <tr><td class="text-xs whitespace-nowrap"><?= esc(date('d-m-Y H:i', strtotime((string) $p['created_at']))) ?></td>
                        <td><?= esc($p['jenis']) ?></td>
                        <td class="font-mono text-xs"><?= esc($p['nik']) ?></td>
                        <td class="text-xs"><?= esc($p['dokumen_jenis'] ?? '') ?></td>
                        <td class="text-xs"><?= esc($p['keterangan'] ?? '') ?></td>
                        <td class="text-xs"><?= esc($p['username'] ?? '') ?></td></tr>
                <?php endforeach ?>
                <?php if ($perubahan === []) : ?><tr><td colspan="6" class="text-center">Tidak ada perubahan tercatat pada periode ini.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
