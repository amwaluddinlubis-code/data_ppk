<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 11px; margin: 12px; }
        .bukti { border: 1.5px solid #222; padding: 10px 12px; margin-bottom: 12px; page-break-inside: avoid; }
        .bukti h3 { margin: 0 0 2px; font-size: 12px; text-align: center; }
        .bukti .sub { text-align: center; font-size: 10px; margin-bottom: 8px; }
        table.isi td { padding: 1px 5px; vertical-align: top; }
        .ttd { float: right; text-align: center; font-size: 10px; margin-top: 6px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <?php for ($i = 0; $i < 2; $i++) : ?>
        <div class="bukti">
            <h3>TANDA BUKTI TERDAFTAR</h3>
            <div class="sub"><?= esc($konteksNama) ?> · Model A-Tanda Bukti Terdaftar</div>
            <table class="isi">
                <tr><td style="width:130px">Nama</td><td>: <strong><?= esc($pemilih['NAMA']) ?></strong></td></tr>
                <tr><td>NIK</td><td>: <?= esc($pemilih['NIK']) ?></td></tr>
                <tr><td>Alamat</td><td>: <?= esc($pemilih['ALAMAT'] ?? '') ?> RT <?= esc($pemilih['RT'] ?? '') ?>/RW <?= esc($pemilih['RW'] ?? '') ?></td></tr>
                <tr><td>Desa/Kelurahan</td><td>: <?= esc($kel['nama']) ?>, Kecamatan <?= esc($kel['kecamatan']) ?>, Kabupaten <?= esc($kabupaten) ?></td></tr>
                <tr><td>Terdaftar pada TPS</td><td>: <strong><?= (int) $pemilih['TPS'] ?></strong></td></tr>
            </table>
            <div class="ttd">
                <?= esc($kel['nama']) ?>, tanggal …… …………… 20……<br>
                Pantarlih/PPS<br><br><br>
                ( ………………………… )
            </div>
            <div style="clear:both"></div>
        </div>
    <?php endfor; ?>
    <p style="font-size:9px">Dua rangkap: satu untuk pemilih, satu untuk arsip PPS. Potong pada garis batas antar rangkap.</p>
</body>
</html>
