<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 11px; margin: 16px; }
        h1 { font-size: 14px; text-align: center; margin: 2px 0; }
        h2 { font-size: 12px; text-align: center; margin: 2px 0 10px; font-weight: normal; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { padding: 1px 4px; vertical-align: top; }
        table.daftar { width: 100%; border-collapse: collapse; }
        table.daftar th, table.daftar td { border: 1px solid #444; padding: 3px 4px; }
        table.daftar th { background: #eee; font-size: 10px; }
        .ttd { margin-top: 24px; width: 100%; }
        .ttd td { width: 50%; vertical-align: top; }
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <h1>MODEL A-DAFTAR PEMILIH-PDPB</h1>
    <h2>DAFTAR PEMILIH HASIL PEMUTAKHIRAN DATA PEMILIH BERKELANJUTAN<br>Periode: <?= esc($periode['nama'] ?? '') ?></h2>

    <table class="meta">
        <tr>
            <td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td>
            <td style="width:130px">Desa/Kelurahan</td><td>: <?= esc($kel['nama']) ?></td>
        </tr>
        <tr>
            <td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td>
            <td>Kecamatan</td><td>: <?= esc($kel['kecamatan']) ?></td>
        </tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th>NIK</th><th>Nomor KK</th><th>Nama Lengkap</th><th>Tempat Lahir</th>
                <th>Tgl Lahir</th><th>Status Kawin</th><th>JK</th><th>Alamat</th><th>RT</th><th>RW</th>
                <th>Disabilitas</th><th>TPS</th><th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 0; foreach ($baris as $b) : $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($b['NIK']) ?></td>
                    <td><?= esc($b['NKK']) ?></td>
                    <td><?= esc($b['NAMA']) ?></td>
                    <td><?= esc($b['TMPTLHR'] ?? '') ?></td>
                    <td><?= esc($b['TGLLHR'] ?? '') ?></td>
                    <td><?= esc($b['STATUS'] ?? '') ?></td>
                    <td><?= esc($b['JENIS_KELAMIN'] ?? '') ?></td>
                    <td><?= esc($b['ALAMAT'] ?? '') ?></td>
                    <td><?= esc($b['RT'] ?? '') ?></td>
                    <td><?= esc($b['RW'] ?? '') ?></td>
                    <td><?= esc((string) ($b['DISABILITAS'] ?? '0')) ?></td>
                    <td><?= esc((string) ($b['TPS'] ?? '')) ?></td>
                    <td><?= esc(\App\Libraries\Keterangan::nama((string) ($b['KET'] ?? '0'), 'pdpb')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="kecil">
        Keterangan — B: Pemilih baru; U: Ubah elemen data; 1: Meninggal; 2: Ganda; 3: Dibawah umur; 4: Pindah domisili; 5: WNA; 6: TNI; 7: Polri; 8: Hak politik dicabut.
        Status perkawinan — B: Belum kawin; S: Sudah kawin; P: Pernah kawin.
        Disabilitas — 1: Fisik; 2: Intelektual; 3: Mental; 4: Sensorik Wicara; 5: Sensorik Rungu; 6: Sensorik Netra.
        Jumlah pemilih pada daftar ini: <?= number_format(count($baris), 0, ',', '.') ?> orang.
    </p>

    <table class="ttd">
        <tr>
            <td></td>
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
