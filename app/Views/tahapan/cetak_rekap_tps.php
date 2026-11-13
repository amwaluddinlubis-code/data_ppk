<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 11px; margin: 16px; }
        h1 { font-size: 13px; text-align: center; margin: 2px 0; }
        h2 { font-size: 12px; text-align: center; margin: 2px 0 10px; font-weight: normal; }
        table.daftar { width: 100%; border-collapse: collapse; }
        table.daftar th, table.daftar td { border: 1px solid #444; padding: 3px 4px; }
        table.daftar th { background: #eee; font-size: 10px; }
        td.angka { text-align: right; }
        tr.total td { font-weight: bold; background: #f4f4f4; }
        tr.subtotal td { font-weight: bold; background: #fafafa; }
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    <h1>REKAPITULASI PLENO PER TPS — <?= esc($judulTahap) ?></h1>
    <h2><?= esc($tahap['konteks_nama']) ?> — Kabupaten <?= esc($kabupaten) ?>, Provinsi <?= esc($provinsi) ?></h2>
    <table class="daftar">
        <thead>
            <tr><th>No</th><th>Kecamatan</th><th>Desa/Kelurahan</th><th>TPS</th><th>Laki-laki</th><th>Perempuan</th><th>Jumlah Pemilih</th></tr>
        </thead>
        <tbody>
        <?php $no = 0; $tj = 0; $tl = 0; $tp = 0; $kelLalu = null; $sub = ['j' => 0, 'l' => 0, 'p' => 0]; ?>
        <?php foreach ($rows as $r) : ?>
            <?php if ($kelLalu !== null && $kelLalu !== $r['kelurahan']) : ?>
                <tr class="subtotal"><td colspan="4">Subtotal <?= esc($kelLalu) ?></td>
                    <td class="angka"><?= number_format($sub['l'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($sub['p'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($sub['j'], 0, ',', '.') ?></td></tr>
                <?php $sub = ['j' => 0, 'l' => 0, 'p' => 0]; ?>
            <?php endif ?>
            <?php $kelLalu = $r['kelurahan']; $no++;
                  $sub['j'] += (int) $r['jumlah']; $sub['l'] += (int) $r['laki']; $sub['p'] += (int) $r['perempuan'];
                  $tj += (int) $r['jumlah']; $tl += (int) $r['laki']; $tp += (int) $r['perempuan']; ?>
            <tr>
                <td><?= $no ?></td><td><?= esc($r['kecamatan']) ?></td><td><?= esc($r['kelurahan']) ?></td>
                <td><?= (int) $r['tps'] ?></td>
                <td class="angka"><?= number_format((int) $r['laki'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $r['perempuan'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $r['jumlah'], 0, ',', '.') ?></td>
            </tr>
        <?php endforeach ?>
        <?php if ($kelLalu !== null) : ?>
            <tr class="subtotal"><td colspan="4">Subtotal <?= esc($kelLalu) ?></td>
                <td class="angka"><?= number_format($sub['l'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($sub['p'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($sub['j'], 0, ',', '.') ?></td></tr>
        <?php endif ?>
            <tr class="total"><td colspan="4">TOTAL</td>
                <td class="angka"><?= number_format($tl, 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tp, 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tj, 0, ',', '.') ?></td></tr>
        </tbody>
    </table>
    <p class="kecil">Dari potret penetapan tahap <?= esc($tahap['tahap']) ?> (<?= esc($tahap['konteks_nama']) ?>). Dokumen bantu aplikasi pendamping Sidalih. Dicetak <?= date('d-m-Y H:i') ?>.</p>
</body>
</html>
