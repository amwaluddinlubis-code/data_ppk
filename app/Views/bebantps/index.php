<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-gauge-high text-primary"></i> Analisis Beban TPS — <?= esc($konteksNama) ?></h2>
        <p class="text-sm">Beban TPS = pemilih MS terdaftar + pemilih pindahan (DPTb) yang masuk ke TPS itu. Batas yang dipakai: <strong><?= number_format($batas, 0, ',', '.') ?> pemilih/TPS</strong> (<?= esc($acuan) ?>). TPS pada 90% batas ditandai mendekati.</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
            <div class="stat bg-base-200 rounded-box p-3">
                <div class="stat-title text-xs">JUMLAH TPS</div>
                <div class="stat-value text-2xl"><?= number_format($ringkas['tps'], 0, ',', '.') ?></div>
            </div>
            <div class="stat bg-base-200 rounded-box p-3">
                <div class="stat-title text-xs">MELEBIHI BATAS</div>
                <div class="stat-value text-2xl text-error"><?= number_format($ringkas['lewat'], 0, ',', '.') ?></div>
            </div>
            <div class="stat bg-base-200 rounded-box p-3">
                <div class="stat-title text-xs">MENDEKATI (≥90%)</div>
                <div class="stat-value text-2xl text-warning"><?= number_format($ringkas['mendekati'], 0, ',', '.') ?></div>
            </div>
            <div class="stat bg-base-200 rounded-box p-3">
                <div class="stat-title text-xs">BEBAN TERBESAR</div>
                <div class="stat-value text-2xl"><?= number_format($ringkas['maks'], 0, ',', '.') ?></div>
            </div>
        </div>
        <?php if (! $terkunci && $daftarKecamatan !== []) : ?>
            <form method="get" action="<?= base_url('beban-tps') ?>" class="flex items-end gap-2 mt-3">
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">KECAMATAN</span></span>
                    <select name="kecamatan_id" class="select select-bordered select-sm w-64" onchange="this.form.submit()">
                        <option value="0">— Semua kecamatan —</option>
                        <?php foreach ($daftarKecamatan as $k) : ?>
                            <option value="<?= (int) $k['id'] ?>" <?= $kecFilter === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Ringkasan per Kecamatan</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Kecamatan</th><th class="text-right">TPS</th><th class="text-right">Total Beban</th><th class="text-right">Beban Maks</th><th class="text-right">Mendekati</th><th class="text-right">Melebihi</th></tr></thead>
                <tbody>
                    <?php foreach ($perKec as $k) : ?>
                        <tr>
                            <td class="font-semibold"><?= esc($k['nama']) ?></td>
                            <td class="text-right"><?= number_format($k['tps'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($k['pemilih'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($k['maks'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= $k['mendekati'] > 0 ? '<span class="badge badge-warning badge-sm">' . $k['mendekati'] . '</span>' : '0' ?></td>
                            <td class="text-right"><?= $k['lewat'] > 0 ? '<span class="badge badge-error badge-sm">' . $k['lewat'] . '</span>' : '0' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">TPS Mendekati & Melebihi Batas</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Kecamatan</th><th>Desa/Kelurahan</th><th>TPS</th><th class="text-right">MS</th><th class="text-right">Pindahan Masuk</th><th class="text-right">Total Beban</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($bermasalah as $t) : ?>
                        <tr>
                            <td><?= esc($t['kecamatan']) ?></td>
                            <td><?= esc($t['desa']) ?></td>
                            <td><?= (int) $t['tps'] ?></td>
                            <td class="text-right"><?= number_format($t['ms'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($t['masuk'], 0, ',', '.') ?></td>
                            <td class="text-right font-bold"><?= number_format($t['total'], 0, ',', '.') ?></td>
                            <td><?= $t['status'] === 'lewat'
                                ? '<span class="badge badge-error badge-sm">Melebihi ' . number_format($t['kelebihan'], 0, ',', '.') . '</span>'
                                : '<span class="badge badge-warning badge-sm">Mendekati</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($bermasalah === []) : ?>
                        <tr><td colspan="7" class="text-center opacity-60">Tidak ada TPS yang mendekati atau melebihi batas pada cakupan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">TPS yang melebihi batas adalah bahan usulan pemecahan TPS kepada KPU Kabupaten/Kota pada tahap pemetaan TPS.</p>
    </div>
</div>
<?= $this->endSection() ?>
