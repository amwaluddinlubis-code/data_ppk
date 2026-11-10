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
        .kop-model { float: right; border: 1.5px solid #222; padding: 6px 10px; font-weight: bold; font-size: 11px; text-align: center; }
        .meta { width: 60%; margin-bottom: 8px; }
        .meta td { padding: 1px 4px; }
        table.daftar { width: 100%; border-collapse: collapse; clear: both; }
        table.daftar th, table.daftar td { border: 1px solid #444; padding: 3px 4px; }
        table.daftar th { background: #eee; font-size: 10px; }
        td.angka { text-align: right; }
        tr.total td { font-weight: bold; background: #f4f4f4; }
        .ttd { margin-top: 24px; width: 100%; }
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop-model">Model A-Rekap<br>Pemilih Pindahan</div>
    <h1>REKAPITULASI PEMILIH PINDAHAN (DPTb)</h1>
    <h2><?= esc($konteksNama) ?><br><?= esc($label) ?></h2>

    <table class="meta">
        <tr><td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td></tr>
        <tr><td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td></tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th><?= $jenjang === 'kabko' ? 'Nama Kecamatan' : 'Desa/Kelurahan' ?></th>
                <th>Pemilih Pindahan Masuk</th><th>Pemilih Pindahan Keluar</th><th>Keterangan</th>
            </tr>
            <tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr>
        </thead>
        <tbody>
            <?php $no = 0; foreach ($baris as $b) : $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($b['satuan']) ?></td>
                    <td class="angka"><?= number_format($b['masuk'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($b['keluar'], 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td class="angka"><?= number_format($total['masuk'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['keluar'], 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <p class="kecil">Pindahan keluar mencakup juga pengajuan dengan tujuan di luar kabupaten. Hal 1 dari 1.</p>

    <table class="ttd">
        <tr>
            <td style="width:55%"></td>
            <td style="text-align:center">
                Ditetapkan di <?= esc($label) ?>,<br>
                Tanggal, …… …………… 20……<br>
                <?= $jenjang === 'pps' ? 'Panitia Pemungutan Suara' : ($jenjang === 'ppk' ? 'Panitia Pemilihan Kecamatan' : 'KPU Kabupaten/Kota ' . esc($kabupaten)) ?><br><br><br><br>
                ( …………………………………… )<br>
                Ketua
            </td>
        </tr>
    </table>
</body>
</html>
