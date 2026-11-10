<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; font-size: 12px; margin: 20px; line-height: 1.6; }
        h1 { font-size: 14px; text-align: center; margin: 2px 0; }
        .kop-model { float: right; border: 1.5px solid #222; padding: 6px 10px; font-weight: bold; font-size: 11px; text-align: center; }
        table.isi { margin: 10px 0; }
        table.isi td { padding: 2px 6px; vertical-align: top; }
        table.ttd { width: 100%; margin-top: 26px; }
        @media print { .noprint { display: none; } body { margin: 0; } @page { size: A4 portrait; margin: 16mm; } }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop-model">Model A-Surat<br>Pindah Memilih</div>
    <h1>SURAT PINDAH MEMILIH</h1>
    <p style="text-align:center; margin-top:0"><?= esc($row['konteks_nama']) ?></p>

    <p>Yang bertanda tangan di bawah ini menerangkan bahwa pemilih berikut mengajukan pindah memilih:</p>

    <table class="isi">
        <tr><td style="width:190px">Nama</td><td>: <strong><?= esc($row['nama']) ?></strong></td></tr>
        <tr><td>NIK</td><td>: <?= esc($row['nik']) ?></td></tr>
        <tr><td>Jenis Kelamin</td><td>: <?= esc($pemilih['JENIS_KELAMIN'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
        <tr><td>Alamat</td><td>: <?= esc($pemilih['ALAMAT'] ?? '') ?> RT <?= esc($pemilih['RT'] ?? '') ?>/RW <?= esc($pemilih['RW'] ?? '') ?></td></tr>
        <tr><td>Terdaftar pada</td><td>: TPS <?= (int) $row['asal_tps'] ?>, Desa/Kelurahan <?= esc($asal['nama']) ?>, Kecamatan <?= esc($asal['kecamatan']) ?>, Kabupaten <?= esc($kabupaten) ?></td></tr>
        <tr><td>Pindah memilih ke</td><td>: <?php if ($tujuan !== null) : ?>
            TPS <?= (int) $row['tujuan_tps'] ?>, Desa/Kelurahan <?= esc($tujuan['nama']) ?>, Kecamatan <?= esc($tujuan['kecamatan']) ?>, Kabupaten <?= esc($kabupaten) ?>
        <?php else : ?>
            <?= esc($row['tujuan_teks'] ?? '') ?> (di luar Kabupaten <?= esc($kabupaten) ?>)
        <?php endif; ?></td></tr>
        <tr><td>Alasan pindah memilih</td><td>: <?= esc($row['alasan']) ?></td></tr>
        <tr><td>Tanggal pengajuan</td><td>: <?= esc(date('d-m-Y', strtotime((string) $row['created_at']))) ?></td></tr>
    </table>

    <p>Surat ini diterbitkan sebagai dasar pencatatan pemilih yang bersangkutan dalam Daftar Pemilih Tambahan (DPTb) pada TPS tujuan, dan yang bersangkutan tidak lagi menggunakan hak pilihnya pada TPS asal.</p>

    <table class="ttd">
        <tr>
            <td style="width:50%; text-align:center">
                Mengetahui,<br>
                Panitia Pemilihan Kecamatan <?= esc($asal['kecamatan']) ?><br>
                Ketua<br><br><br><br>
                ( …………………………………… )
            </td>
            <td style="text-align:center">
                <?= esc($asal['nama']) ?>, tanggal …… …………… 20……<br>
                Panitia Pemungutan Suara <?= esc($asal['nama']) ?><br>
                Ketua<br><br><br><br>
                ( …………………………………… )
            </td>
        </tr>
    </table>
</body>
</html>
