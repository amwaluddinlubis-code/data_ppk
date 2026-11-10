<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 12px; margin: 20px; line-height: 1.55; }
        h1 { font-size: 14px; text-align: center; margin: 2px 0; }
        h2 { font-size: 13px; text-align: center; margin: 2px 0; }
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
    $hariNama = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulanNama = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tgl = $periode['ba_tanggal'] ?? null;
    $hari = $tgl ? ($hariNama[date('l', strtotime((string) $tgl))] ?? '……') : '…………';
    $tglTulis = $tgl ? ((int) date('d', strtotime((string) $tgl))) . ' ' . $bulanNama[(int) date('n', strtotime((string) $tgl))] . ' ' . date('Y', strtotime((string) $tgl)) : '………………';
    $masukan = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($periode['ba_masukan'] ?? '')))));
    $anggota = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($periode['ba_anggota'] ?? '')))));
    ?>

    <p class="tengah" style="font-weight:bold">KOMISI PEMILIHAN UMUM<br>KABUPATEN <?= esc(strtoupper($kabupaten)) ?></p>
    <h1>BERITA ACARA</h1>
    <p class="tengah">Nomor: <?= esc($periode['ba_nomor'] ?? '……………………………………') ?></p>
    <p class="tengah">TENTANG<br><strong>REKAPITULASI DAFTAR PEMILIH BERKELANJUTAN</strong><br><?= esc(strtoupper($periode['nama'])) ?></p>

    <p>Pada hari ini <?= esc($hari) ?>, tanggal <?= esc($tglTulis) ?>, bertempat di <?= esc($periode['ba_tempat'] ?? '………………………………') ?><?= ! empty($periode['ba_pukul']) ? ', pukul ' . esc($periode['ba_pukul']) . ' WIB' : '' ?>, KPU Kabupaten/Kota <?= esc($kabupaten) ?> telah melaksanakan Rapat Pleno Terbuka Rekapitulasi Daftar Pemilih Berkelanjutan <?= esc($periode['nama']) ?> tingkat Kabupaten/Kota <?= esc($kabupaten) ?>.</p>

    <p>Dalam Rapat tersebut, KPU Kabupaten/Kota <?= esc($kabupaten) ?> menetapkan Rekapitulasi Daftar Pemilih Berkelanjutan Kabupaten/Kota <?= esc($kabupaten) ?> dengan rincian sebagai berikut:</p>

    <p>1. Rekapitulasi Daftar Pemilih Berkelanjutan</p>
    <table class="rekap">
        <tr>
            <th>JUMLAH KECAMATAN</th><th>JUMLAH DESA/KELURAHAN</th>
            <th>JUMLAH LAKI-LAKI</th><th>JUMLAH PEREMPUAN</th><th>TOTAL</th>
        </tr>
        <tr>
            <td><?= $fmt($jmlKec) ?></td><td><?= $fmt($jmlDesa) ?></td>
            <td><?= $fmt($angka['laki']) ?></td><td><?= $fmt($angka['perempuan']) ?></td><td><?= $fmt($angka['total']) ?></td>
        </tr>
    </table>

    <p>2. Menerima masukan dari:</p>
    <?php if ($masukan !== []) : ?>
        <?php foreach ($masukan as $i => $m) : ?>
            <p style="margin-left:18px"><?= chr(97 + $i) ?>. <?= esc($m) ?></p>
        <?php endforeach; ?>
    <?php else : ?>
        <p style="margin-left:18px">a. ………………………………………………………………………………………</p>
        <p style="margin-left:18px">b. ………………………………………………………………………………………</p>
        <p style="margin-left:18px">c. ………………………………………………………………………………………</p>
    <?php endif; ?>

    <p>Daftar Pemilih Berkelanjutan tersebut selanjutnya ditetapkan secara lebih rinci dalam dokumen Rekapitulasi tingkat kabupaten/kota sebagaimana terlampir yang merupakan bagian tidak terpisahkan dari Berita Acara ini.</p>
    <p>Berdasarkan Berita Acara ini rekapitulasi ditetapkan dengan Keputusan KPU Kabupaten/Kota.</p>

    <table style="width:100%; margin-top:20px">
        <tr>
            <td style="width:50%"></td>
            <td>
                Dibuat di&nbsp;&nbsp;&nbsp;&nbsp;: <?= esc($periode['ba_tempat'] ?? '……………………') ?><br>
                Pada Tanggal : <?= esc($tglTulis) ?>
            </td>
        </tr>
    </table>

    <p style="margin-top:16px">KPU Kabupaten/Kota <?= esc($kabupaten) ?></p>
    <table class="ttd">
        <tr><td style="width:30px">1.</td><td>Ketua</td><td>: <?= esc($periode['ba_ketua'] ?? '……………………') ?></td><td>( …………………… )</td></tr>
        <?php for ($i = 0; $i < 4; $i++) : ?>
            <tr><td><?= $i + 2 ?>.</td><td>Anggota</td><td>: <?= esc($anggota[$i] ?? '……………………') ?></td><td>( …………………… )</td></tr>
        <?php endfor; ?>
    </table>
</body>
</html>
