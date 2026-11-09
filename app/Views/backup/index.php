<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('galat')) : ?>
    <div class="alert alert-error shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif; ?>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="card-title text-base"><i class="fas fa-database"></i> Buat Cadangan Baru</h3>
        <p class="text-sm opacity-80">
            Basis data yang dicadangkan: <span class="font-mono font-bold"><?= esc($basisData) ?></span>
            (kecamatan aktif saat ini: <?= esc($kecamatan ?? '—') ?>).
            Hasilnya berupa berkas <span class="font-mono">.sql</span> berisi seluruh struktur &amp; data
            (termasuk tabel pengguna bila berada di basis data ini). Berkas juga tersimpan di server pada
            <span class="font-mono text-xs">writable/backup/</span>.
        </p>
        <div class="alert alert-warning text-sm mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            Berkas cadangan berisi NIK &amp; data pribadi pemilih. Simpan di tempat aman dan jangan dibagikan sembarangan.
        </div>
        <form method="post" action="<?= base_url('backup/buat') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Buat cadangan basis data sekarang?');">
                <i class="fas fa-download"></i> BUAT &amp; UNDUH CADANGAN
            </button>
        </form>
        <p class="text-xs opacity-60 mt-3">
            Pemulihan: impor berkas .sql ini lewat phpMyAdmin / klien MySQL ke basis data tujuan,
            atau serahkan kepada pengelola teknis.
        </p>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="card-title text-base">Cadangan Tersimpan di Server</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NAMA BERKAS</th>
                        <th>WAKTU</th>
                        <th class="text-right">UKURAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($daftar === []) : ?>
                        <tr><td colspan="3" class="text-center opacity-60">Belum ada berkas cadangan .sql di server.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($daftar as $b) : ?>
                        <tr>
                            <td class="font-mono text-xs"><?= esc($b['nama']) ?></td>
                            <td><?= esc($b['waktu']) ?></td>
                            <td class="text-right"><?= number_format($b['ukuran'] / 1024, 1, ',', '.') ?> KB</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
