<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-user-shield text-primary"></i> Pelindungan Data Pribadi — Catatan Pemrosesan</h2>
        <p class="text-sm">Layar pertanggungjawaban pengelolaan data pemilih menurut UU No. 27 Tahun 2022 jo. PP No. 33 Tahun 2026 (berlaku 16 Januari 2027). Data pemilih (NIK, nomor KK, ragam disabilitas) diproses atas dasar kewajiban hukum penyelenggaraan pemilu; halaman ini merangkum siapa mengakses dan mengubah apa, ekspor apa saja yang keluar, dan permohonan warga yang belum selesai.</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-xs">AKSES 30 HARI</div><div class="stat-value text-2xl"><?= number_format($totalAkses, 0, ',', '.') ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-xs">TOTAL EKSPOR TERCATAT</div><div class="stat-value text-2xl"><?= number_format($totalEkspor, 0, ',', '.') ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-xs">PERMOHONAN LEWAT TENGGAT</div><div class="stat-value text-2xl <?= $lewatSla > 0 ? 'text-error' : '' ?>"><?= number_format($lewatSla, 0, ',', '.') ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-xs">STATUS PERMOHONAN</div>
                <div class="text-sm mt-1"><?php foreach ($permohonan as $p) : ?><?= esc($p['status']) ?>: <strong><?= (int) $p['jumlah'] ?></strong><br><?php endforeach ?><?php if ($permohonan === []) : ?>belum ada<?php endif ?></div></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="font-bold text-sm mb-2">Akses Terbanyak (30 hari)</h3>
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Aksi</th><th class="text-right">Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($aksesPerAksi as $a) : ?><tr><td><?= esc($a['aksi']) ?></td><td class="text-right"><?= number_format((int) $a['jumlah'], 0, ',', '.') ?></td></tr><?php endforeach ?>
                <?php if ($aksesPerAksi === []) : ?><tr><td colspan="2" class="text-center">Belum ada catatan.</td></tr><?php endif ?>
                </tbody>
            </table>
            <h3 class="font-bold text-sm mt-4 mb-2">Perubahan Data (30 hari)</h3>
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Aksi Perubahan</th><th class="text-right">Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($ubahPerJenis as $a) : ?><tr><td><?= esc($a['jenis']) ?></td><td class="text-right"><?= number_format((int) $a['jumlah'], 0, ',', '.') ?></td></tr><?php endforeach ?>
                <?php if ($ubahPerJenis === []) : ?><tr><td colspan="2" class="text-center">Belum ada catatan.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="font-bold text-sm mb-2">Ekspor / Unduhan Data Terakhir</h3>
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Jenis</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($ekspor as $e) : ?>
                    <tr><td class="text-xs whitespace-nowrap"><?= esc(date('d-m-Y H:i', strtotime((string) $e['created_at']))) ?></td><td><?= esc($e['username']) ?></td><td><?= esc($e['jenis']) ?></td><td class="text-xs"><?= esc($e['keterangan'] ?? '') ?><?= $e['jumlah'] !== null ? ' (' . number_format((int) $e['jumlah'], 0, ',', '.') . ' baris)' : '' ?></td></tr>
                <?php endforeach ?>
                <?php if ($ekspor === []) : ?><tr><td colspan="4" class="text-center">Belum ada ekspor tercatat.</td></tr><?php endif ?>
                </tbody>
            </table>
            <p class="text-xs opacity-70 mt-3">Pengingat kewajiban: perbaikan data atas permohonan subjek data paling lambat 3×24 jam; pemberitahuan kegagalan pelindungan data kepada subjek data dan lembaga paling lambat 3×24 jam sejak diketahui; penilaian dampak (DPIA) didokumentasikan terpisah sebagai pendamping manual aplikasi.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
