<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 12px; margin: 20px; line-height: 1.55; }
        h1 { font-size: 14px; text-align: center; margin: 2px 0; }
        .tengah { text-align: center; }
        table.rekap { border-collapse: collapse; margin: 8px 0; }
        table.rekap th, table.rekap td { border: 1px solid #444; padding: 4px 10px; text-align: center; }
        table.ttd { width: 70%; margin-top: 8px; border-collapse: collapse; }
        table.ttd td { padding: 4px 6px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 18mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <?php
    $fmt = static fn ($n) => number_format((int) $n, 0, ',', '.');
    $penyelenggara = $jenjang === 'kabko' ? 'KOMISI PEMILIHAN UMUM<br>KABUPATEN ' . esc(strtoupper($kabupaten))
        : ($jenjang === 'ppk' ? 'PANITIA PEMILIHAN KECAMATAN' : 'PANITIA PEMUNGUTAN SUARA');
    $anggota = $jenjang === 'kabko'
        ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($tahap['ba_anggota'] ?? '')))))
        : [];
    $tglTulis = ! empty($tahap['ba_tanggal']) ? date('d-m-Y', strtotime((string) $tahap['ba_tanggal'])) : '………………';
    ?>

    <p class="tengah" style="font-weight:bold"><?= $penyelenggara ?><br><?= esc(strtoupper($label)) ?></p>
    <h1>BERITA ACARA</h1>
    <p class="tengah">Nomor: <?= $jenjang === 'kabko' ? esc($tahap['ba_nomor'] ?? '……………………………………') : '……………………………………' ?></p>
    <p class="tengah">TENTANG<br><strong>REKAPITULASI DAN PENETAPAN <?= esc($judulTahap) ?></strong><br><?= esc($tahap['konteks_nama']) ?></p>

    <p>Pada hari ini …………, tanggal <?= esc($tglTulis) ?>, bertempat di <?= $jenjang === 'kabko' ? esc($tahap['ba_tempat'] ?? '………………………………') : '………………………………' ?>, <?= $jenjang === 'kabko' ? 'KPU Kabupaten/Kota ' . esc($kabupaten) : ($jenjang === 'ppk' ? 'Panitia Pemilihan Kecamatan ' . esc($label) : 'Panitia Pemungutan Suara ' . esc($label)) ?> telah melaksanakan Rapat Pleno Terbuka Rekapitulasi dan Penetapan <?= esc($judulTahap) ?> tingkat <?= $jenjang === 'kabko' ? 'Kabupaten/Kota' : ($jenjang === 'ppk' ? 'Kecamatan' : 'Desa/Kelurahan') ?>.</p>

    <p>Dalam Rapat tersebut ditetapkan <?= esc($judulTahap) ?> dengan rincian sebagai berikut:</p>

    <table class="rekap">
        <tr>
            <?php foreach ($angka['satuan'] as $nama => $nilai) : ?>
                <th>JUMLAH <?= esc(strtoupper($nama)) ?></th>
            <?php endforeach; ?>
            <th>JUMLAH LAKI-LAKI</th><th>JUMLAH PEREMPUAN</th><th>TOTAL PEMILIH</th>
        </tr>
        <tr>
            <?php foreach ($angka['satuan'] as $nilai) : ?>
                <td><?= $fmt($nilai) ?></td>
            <?php endforeach; ?>
            <td><?= $fmt($angka['laki']) ?></td><td><?= $fmt($angka['perempuan']) ?></td><td><?= $fmt($angka['ms']) ?></td>
        </tr>
    </table>

    <p><?= esc($judulTahap) ?> tersebut selanjutnya ditetapkan secara lebih rinci dalam dokumen daftar pemilih dan rekapitulasi sebagaimana terlampir yang merupakan bagian tidak terpisahkan dari Berita Acara ini.</p>

    <table style="width:100%; margin-top:20px">
        <tr>
            <td style="width:50%"></td>
            <td>
                Dibuat di&nbsp;&nbsp;&nbsp;&nbsp;: <?= $jenjang === 'kabko' ? esc($tahap['ba_tempat'] ?? '……………………') : '……………………' ?><br>
                Pada Tanggal : <?= esc($tglTulis) ?>
            </td>
        </tr>
    </table>

    <p style="margin-top:16px"><?= $jenjang === 'kabko' ? 'KPU Kabupaten/Kota ' . esc($kabupaten) : ($jenjang === 'ppk' ? 'Panitia Pemilihan Kecamatan' : 'Panitia Pemungutan Suara') ?></p>
    <table class="ttd">
        <tr><td style="width:30px">1.</td><td>Ketua</td><td>: <?= $jenjang === 'kabko' ? esc($tahap['ba_ketua'] ?? '……………………') : '……………………' ?></td><td>( …………………… )</td></tr>
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <tr><td><?= $i + 2 ?>.</td><td>Anggota</td><td>: <?= esc($anggota[$i] ?? '……………………') ?></td><td>( …………………… )</td></tr>
        <?php endfor; ?>
    </table>
</body>
</html>
