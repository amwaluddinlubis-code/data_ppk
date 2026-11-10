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
        .meta td { padding: 1px 4px; vertical-align: top; }
        table.daftar { width: 100%; border-collapse: collapse; clear: both; }
        table.daftar th, table.daftar td { border: 1px solid #444; padding: 3px 4px; }
        table.daftar th { background: #eee; font-size: 10px; }
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 landscape; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop-model">Model A-Daftar<br>Potensial Pemilih</div>
    <h1>DAFTAR POTENSIAL PEMILIH</h1>
    <h2><?= esc($konteksNama) ?></h2>

    <table class="meta">
        <tr><td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td></tr>
        <tr><td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td></tr>
        <tr><td>Kecamatan</td><td>: <?= esc($kel['kecamatan']) ?></td></tr>
        <tr><td>Desa/Kelurahan</td><td>: <?= esc($kel['nama']) ?></td></tr>
        <tr><td>TPS</td><td>: <?= $tps ?></td></tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th>No KK</th><th>NIK</th><th>Nama</th><th>Tempat Lahir</th><th>Tanggal Lahir</th>
                <th>Status Kawin</th><th>JK</th><th>Alamat</th><th>RT</th><th>RW</th><th>Disabilitas</th>
                <th>KTP-el</th><th>Hasil Coklit</th>
            </tr>
            <tr>
                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 0; foreach ($baris as $r) : $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($r['NKK'] ?? '') ?></td>
                    <td><?= esc($r['NIK'] ?? '') ?></td>
                    <td><?= esc($r['NAMA'] ?? '') ?></td>
                    <td><?= esc($r['TMPTLHR'] ?? '') ?></td>
                    <td><?= esc($r['TGLLHR'] ?? '') ?></td>
                    <td><?= esc($r['STATUS'] ?? '') ?></td>
                    <td><?= esc($r['JENIS_KELAMIN'] ?? '') ?></td>
                    <td><?= esc($r['ALAMAT'] ?? '') ?></td>
                    <td><?= esc($r['RT'] ?? '') ?></td>
                    <td><?= esc($r['RW'] ?? '') ?></td>
                    <td><?= esc((string) ($r['DISABILITAS'] ?? '0')) ?></td>
                    <td><?= esc($r['EKTP'] ?? '') ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="kecil">
        Kolom Hasil Coklit diisi Pantarlih saat pencocokan dan penelitian: beri tanda sesuai keadaan (tetap/ubah/tidak memenuhi syarat beserta alasannya).
        Jumlah potensial pemilih pada daftar ini: <?= number_format(count($baris), 0, ',', '.') ?>. Hal 1 dari 1.
    </p>
</body>
</html>
