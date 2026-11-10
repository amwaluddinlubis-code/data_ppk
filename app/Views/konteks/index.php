<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-flag text-primary"></i> Konteks Penyelenggaraan</h2>
        <p class="text-sm opacity-70">Satu basis data kabupaten dipakai bergantian untuk Pemilu, Pilkada, dan masa PDPB. Rezim aktif menentukan <strong>label kode keterangan 5 dan 8</strong> pada seluruh daftar, ekspor, dan cetakan, serta <strong>batas maksimal pemilih per TPS</strong>. Kode dan data pemilih tidak berubah.</p>

        <form method="post" action="<?= base_url('konteks/simpan') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
            <?= csrf_field() ?>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">JENIS PENYELENGGARAAN</span></span>
                <select name="jenis" class="select select-bordered select-sm" required>
                    <?php foreach ($daftarJenis as $kode => $label) : ?>
                        <option value="<?= esc($kode, 'attr') ?>" <?= $jenis === $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">NAMA PENYELENGGARAAN</span></span>
                <input type="text" name="nama" value="<?= esc($nama, 'attr') ?>" maxlength="120" class="input input-bordered input-sm" placeholder="cth. PDPB 2026 / Pemilu 2029 / Pilkada 2029" required>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TANGGAL PEMUNGUTAN (ACUAN)</span></span>
                <input type="date" name="tanggal" value="<?= esc($tanggal, 'attr') ?>" class="input input-bordered input-sm">
            </label>
            <div class="md:col-span-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> SIMPAN KONTEKS</button>
            </div>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Perbedaan antarrezim (dari formulir resmi)</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm w-full">
                <thead>
                    <tr><th>Rezim</th><th>Aturan</th><th>Kode 5</th><th>Kode 8</th><th>Batas per TPS</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold">Pemilu</td><td>PKPU 7/2022 jo. 7/2023</td>
                        <td><?= esc($legenda['pemilu']['5']) ?></td><td><?= esc($legenda['pemilu']['8']) ?></td>
                        <td>300 pemilih</td>
                        <td><?= $jenis === 'pemilu' ? '<span class="badge badge-primary badge-sm">AKTIF</span>' : '' ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Pilkada</td><td>PKPU 7/2024</td>
                        <td><?= esc($legenda['pilkada']['5']) ?></td><td><?= esc($legenda['pilkada']['8']) ?></td>
                        <td>600 pemilih</td>
                        <td><?= $jenis === 'pilkada' ? '<span class="badge badge-primary badge-sm">AKTIF</span>' : '' ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">PDPB</td><td>PKPU 1/2025 jo. 1/2026</td>
                        <td><?= esc($legenda['pdpb']['5']) ?></td><td><?= esc($legenda['pdpb']['8']) ?></td>
                        <td>— (tidak membentuk TPS)</td>
                        <td><?= $jenis === 'pdpb' ? '<span class="badge badge-primary badge-sm">AKTIF</span>' : '' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Kode 1–4, 6, 7 serta kode B (Baru) dan U (Ubah) sama di semua rezim. Masa berjalan di antara penyelenggaraan adalah masa PDPB — rekap pemutakhiran dilakukan paling sedikit setiap 3 bulan (PKPU 1/2025 Pasal 9 ayat (2)).</p>
    </div>
</div>
<?= $this->endSection() ?>
