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
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 landscape; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop-model">Model A-Rekap <?= strtoupper($jenjang) ?><br>Perubahan Pemilih</div>
    <h1>REKAPITULASI PERUBAHAN PEMILIH</h1>
    <h2><?= esc($tahap['konteks_nama']) ?><br>Perubahan dari <?= strtoupper($sebelum['tahap']) ?> ke <?= strtoupper($tahap['tahap']) ?> — <?= esc($label) ?></h2>

    <table class="meta">
        <tr><td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td></tr>
        <tr><td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td></tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th><?= $jenjang === 'pps' ? 'Nomor TPS' : ($jenjang === 'ppk' ? 'Desa/Kelurahan' : 'Nama Kecamatan') ?></th>
                <th>Pemilih Aktif Awal</th><th>Pemilih Baru</th><th>Pemilih Tidak Memenuhi Syarat</th>
                <th>Perbaikan Data Pemilih</th><th>Pemilih Akhir</th><th>Keterangan</th>
            </tr>
            <tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th></tr>
        </thead>
        <tbody>
            <?php $no = 0; foreach ($baris as $b) : $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($b['satuan']) ?></td>
                    <td class="angka"><?= number_format($b['awal'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($b['baru'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($b['tms'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($b['perbaikan'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($b['akhir'], 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td class="angka"><?= number_format($total['awal'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['baru'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['tms'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['perbaikan'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['akhir'], 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <p class="kecil">
        Dihitung sebagai selisih potret penetapan <?= strtoupper($sebelum['tahap']) ?> dan <?= strtoupper($tahap['tahap']) ?>: Pemilih Akhir = Awal + Baru − Tidak Memenuhi Syarat.
        "Tidak Memenuhi Syarat" mencakup seluruh baris yang tidak lagi terdaftar pada potret akhir. Hal 1 dari 1.
    </p>

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
