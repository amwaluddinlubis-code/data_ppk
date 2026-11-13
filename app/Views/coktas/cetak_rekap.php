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
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 landscape; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    <h1>REKAPITULASI HASIL COKTAS (PENCOCOKAN DAN PENELITIAN TERBATAS)</h1>
    <h2>Pemutakhiran Data Pemilih Berkelanjutan — <?= esc($periode['nama'] ?? '') ?><br>Kabupaten <?= esc($meta['kabupaten'] ?? '') ?></h2>
    <table class="daftar">
        <thead>
            <tr><th>No</th><th>Kecamatan</th><th>Desa/Kelurahan</th><th>Pemilih MS</th><th>Sudah Dicoktas</th><th>Sesuai</th><th>Tidak Sesuai</th><th>Tidak Ditemukan</th><th>Progres</th></tr>
        </thead>
        <tbody>
        <?php $no = 0; $t = ['ms' => 0, 'tercoktas' => 0, 'sesuai' => 0, 'tidakSesuai' => 0, 'tidakDitemukan' => 0]; ?>
        <?php foreach ($progres as $p) : $no++; foreach ($t as $k => $_) { $t[$k] += (int) $p[$k]; } $ms = (int) $p['ms']; ?>
            <tr>
                <td><?= $no ?></td><td><?= esc($p['kecamatan']) ?></td><td><?= esc($p['nama']) ?></td>
                <td class="angka"><?= number_format($ms, 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $p['tercoktas'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $p['sesuai'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $p['tidakSesuai'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format((int) $p['tidakDitemukan'], 0, ',', '.') ?></td>
                <td class="angka"><?= $ms > 0 ? round(100 * (int) $p['tercoktas'] / $ms, 1) : 0 ?>%</td>
            </tr>
        <?php endforeach ?>
            <tr class="total">
                <td colspan="3">TOTAL</td>
                <td class="angka"><?= number_format($t['ms'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($t['tercoktas'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($t['sesuai'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($t['tidakSesuai'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($t['tidakDitemukan'], 0, ',', '.') ?></td>
                <td class="angka"><?= $t['ms'] > 0 ? round(100 * $t['tercoktas'] / $t['ms'], 1) : 0 ?>%</td>
            </tr>
        </tbody>
    </table>
    <p class="kecil">Dokumen bantu internal PPK/PPS dari aplikasi pendamping Sidalih; bukan formulir resmi KPU. Dicetak <?= date('d-m-Y H:i') ?>.</p>
</body>
</html>
