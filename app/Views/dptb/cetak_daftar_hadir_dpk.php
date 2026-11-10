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
        .meta { width: 60%; margin-bottom: 8px; }
        .meta td { padding: 1px 4px; vertical-align: top; }
        table.daftar { width: 100%; border-collapse: collapse; }
        table.daftar th, table.daftar td { border: 1px solid #444; padding: 4px; height: 22px; }
        table.daftar th { background: #eee; font-size: 10px; }
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

    <h1>DAFTAR HADIR PEMILIH KHUSUS (DPK)</h1>
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
                <th style="width:30px">No</th><th>Nama Pemilih</th><th style="width:140px">NIK</th>
                <th style="width:50px">JK</th><th>Alamat</th><th>Keterangan</th><th style="width:130px">Tanda Tangan</th>
            </tr>
            <tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th></tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= 15; $i++) : ?>
                <tr><td><?= $i ?></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <p class="kecil">
        Dokumen bantu: daftar hadir pemilih khusus (pemegang KTP-el setempat yang tidak terdaftar dalam DPT/DPTb) diisi dan ditandatangani di hadapan KPPS pada hari pemungutan suara.
        Berkas ini bukan formulir Model A. Hal 1 dari 1.
    </p>

    <table class="ttd">
        <tr>
            <td style="width:55%"></td>
            <td style="text-align:center">
                <?= esc($kel['nama']) ?>, tanggal …… …………… 20……<br>
                Kelompok Penyelenggara Pemungutan Suara TPS <?= $tps ?><br>
                Ketua<br><br><br><br>
                ( …………………………………… )
            </td>
        </tr>
    </table>
</body>
</html>
