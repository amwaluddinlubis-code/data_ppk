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
        table.ttd { width: 60%; margin-top: 10px; border-collapse: collapse; }
        table.ttd td, table.ttd th { padding: 4px 6px; }
        .kecil { font-size: 9px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop-model">Model A-Rekap<br>Kabko-PDPB</div>
    <h1>REKAPITULASI</h1>
    <h2>PEMUTAKHIRAN DATA PEMILIH BERKELANJUTAN (PDPB)<br>Oleh KPU Kabupaten/Kota — <?= esc($periode['nama']) ?></h2>

    <table class="meta">
        <tr><td style="width:130px">Provinsi</td><td>: <?= esc($provinsi) ?></td></tr>
        <tr><td>Kabupaten/Kota</td><td>: <?= esc($kabupaten) ?></td></tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th>No</th><th>Nama Kecamatan</th><th>Jumlah Desa/Kelurahan</th>
                <th>Jumlah Pemilih L</th><th>Jumlah Pemilih P</th><th>Jumlah Pemilih L+P</th><th>Keterangan</th>
            </tr>
            <tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th></tr>
        </thead>
        <tbody>
            <?php $no = 0; foreach ($perKec as $r) : $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($r['kecamatan']) ?></td>
                    <td class="angka"><?= number_format((int) $r['desa'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($r['laki'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($r['perempuan'], 0, ',', '.') ?></td>
                    <td class="angka"><?= number_format($r['total'], 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td class="angka"><?= number_format($total['desa'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['laki'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['perempuan'], 0, ',', '.') ?></td>
                <td class="angka"><?= number_format($total['total'], 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <?php
    $tglSah = ! empty($periode['ba_tanggal']) ? date('d-m-Y', strtotime((string) $periode['ba_tanggal'])) : '…………';
    $tempatSah = ! empty($periode['ba_tempat']) ? $periode['ba_tempat'] : '…………';
    $anggota = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($periode['ba_anggota'] ?? '')))));
    ?>
    <p style="margin-top:18px">
        Disahkan dalam rapat pleno KPU Kabupaten/Kota di <?= esc($tempatSah) ?>, tanggal <?= esc($tglSah) ?>.<br>
        KPU KABUPATEN/KOTA <?= esc(strtoupper($kabupaten)) ?>
    </p>
    <table class="ttd">
        <tr><th style="text-align:left">No</th><th style="text-align:left">Jabatan</th><th style="text-align:left">Nama</th><th style="text-align:left">Tanda Tangan</th></tr>
        <tr><td>1</td><td>Ketua</td><td><?= esc($periode['ba_ketua'] ?? '……………………') ?></td><td>……………………</td></tr>
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <tr><td><?= $i + 2 ?></td><td>Anggota</td><td><?= esc($anggota[$i] ?? '……………………') ?></td><td>……………………</td></tr>
        <?php endfor; ?>
    </table>
    <p class="kecil">Catatan: *) Disesuaikan dengan jumlah kecamatan. Nama ketua/anggota terisi otomatis bila metadata Berita Acara periode ini sudah disimpan. Hal 1 dari 1.</p>
</body>
</html>
