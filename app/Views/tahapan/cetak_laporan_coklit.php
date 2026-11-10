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

    <div class="kop-model">Model A-Laporan<br>Hasil Coklit</div>
    <h1>LAPORAN HASIL COKLIT</h1>
    <h2><?= esc($konteksNama) ?></h2>

    <table class="meta">
        <tr><td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td></tr>
        <tr><td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td></tr>
        <tr><td>Kecamatan</td><td>: <?= esc($kel['kecamatan']) ?></td></tr>
        <tr><td>Desa/Kelurahan</td><td>: <?= esc($kel['nama']) ?></td></tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th>TPS</th><th>Jumlah KK</th><th>Pemilih Terdaftar</th>
                <th>Sudah Dicoklit</th><th>Memenuhi Syarat</th><th>Tidak Memenuhi Syarat</th><th>Keterangan</th>
            </tr>
            <tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th></tr>
        </thead>
        <tbody>
            <?php
            $no = 0; $tot = ['kk' => 0, 'terdaftar' => 0, 'tercoklit' => 0, 'ms' => 0, 'tms' => 0];
            foreach ($rows as $r) : $no++;
                foreach ($tot as $k => $_) { $tot[$k] += (int) $r[$k]; }
            ?>
                <tr>
                    <td><?= $no ?></td>
                    <td>TPS <?= (int) $r['tps'] ?></td>
                    <td class="angka"><?= number_format((int) $r['kk'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format((int) $r['terdaftar'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format((int) $r['tercoklit'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format((int) $r['ms'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format((int) $r['tms'], 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2">JUMLAH</td>
                <td class="angka"><?= number_format($tot['kk'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tot['terdaftar'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tot['tercoklit'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tot['ms'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($tot['tms'], 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <p class="kecil">"Sudah Dicoklit" dihitung dari baris bertanggal coklit pada data; angka memenuhi/tidak memenuhi syarat adalah keadaan daftar saat ini. Hal 1 dari 1.</p>

    <table class="ttd">
        <tr>
            <td style="width:55%"></td>
            <td style="text-align:center">
                <?= esc($kel['nama']) ?>, tanggal …… …………… 20……<br>
                Panitia Pemungutan Suara<br><br><br><br>
                ( …………………………………… )<br>
                Ketua
            </td>
        </tr>
    </table>
</body>
</html>
