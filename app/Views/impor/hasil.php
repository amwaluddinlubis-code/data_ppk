<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="card-title text-base"><i class="fas fa-check-circle text-success"></i> Impor Selesai — <?= esc($ringkasan['jenis']) ?></h3>
        <div class="stats stats-vertical sm:stats-horizontal shadow-sm bg-base-200 w-full">
            <div class="stat">
                <div class="stat-title">BERKAS</div>
                <div class="stat-value text-base font-mono break-all"><?= esc($ringkasan['berkas']) ?></div>
                <div class="stat-desc">Mode: <?= $ringkasan['mode'] === 'ganti' ? 'GANTI SEMUA' : 'TAMBAH' ?></div>
            </div>
            <div class="stat">
                <div class="stat-title">BARIS TERBACA</div>
                <div class="stat-value"><?= number_format((int) $ringkasan['terbaca'], 0, ',', '.') ?></div>
            </div>
            <div class="stat">
                <div class="stat-title">MASUK</div>
                <div class="stat-value text-success"><?= number_format((int) $ringkasan['masuk'], 0, ',', '.') ?></div>
            </div>
            <div class="stat">
                <div class="stat-title">GAGAL / DILEWATI</div>
                <div class="stat-value <?= $ringkasan['gagal'] !== [] ? 'text-error' : '' ?>"><?= number_format(count($ringkasan['gagal']), 0, ',', '.') ?></div>
            </div>
        </div>

        <?php if ($ringkasan['cadangan'] !== null) : ?>
            <p class="text-sm mt-3">
                Cadangan data lama tersimpan di server:
                <span class="font-mono text-xs">writable/backup/<?= esc($ringkasan['cadangan']) ?></span>
            </p>
        <?php endif; ?>

        <?php if ($ringkasan['gagal'] !== []) : ?>
            <div class="collapse collapse-arrow bg-base-200 mt-3">
                <input type="checkbox">
                <div class="collapse-title text-sm font-semibold">Rincian baris gagal / dilewati (<?= count($ringkasan['gagal']) ?>)</div>
                <div class="collapse-content">
                    <ul class="text-xs font-mono list-disc ml-5 max-h-60 overflow-y-auto">
                        <?php foreach (array_slice($ringkasan['gagal'], 0, 200) as $g) : ?>
                            <li><?= esc($g) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($ringkasan['gagal']) > 200) : ?>
                            <li>… dan <?= count($ringkasan['gagal']) - 200 ?> lainnya.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="card-title text-base">Rekonsiliasi Basis Data Saat Ini</h3>
        <p class="text-sm opacity-70">Hitungan langsung dari data coklit &amp; daftar turunannya — gunakan untuk mencocokkan dengan berkas sumber / laporan manual.</p>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full max-w-xl">
                <tbody>
                    <?php foreach ($rekonsiliasi as $label => $nilai) : ?>
                        <tr>
                            <td><?= esc($label) ?></td>
                            <td class="text-right font-bold"><?= number_format((int) $nilai, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="<?= base_url('impor') ?>" class="btn btn-ghost btn-sm">« Kembali ke Impor</a>
            <a href="<?= base_url('/') ?>" class="btn btn-primary btn-sm">Lihat Hasil Coklit</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
