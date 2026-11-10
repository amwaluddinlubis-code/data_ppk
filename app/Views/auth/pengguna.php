<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="grid gap-4 lg:grid-cols-5">
    <div class="card bg-base-100 shadow-sm lg:col-span-2">
        <div class="card-body">
            <h2 class="card-title text-base">Tambah Pengguna</h2>
            <form method="post" action="<?= base_url('pengguna/tambah') ?>" class="flex flex-col gap-3">
                <?= csrf_field() ?>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Nama lengkap</span>
                    <input type="text" name="nama" value="<?= esc(old('nama') ?? '') ?>" class="input input-bordered w-full" required>
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Nama pengguna</span>
                    <input type="text" name="username" value="<?= esc(old('username') ?? '') ?>" class="input input-bordered w-full" required>
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Kata sandi awal (minimal 8 karakter)</span>
                    <input type="text" name="sandi" class="input input-bordered w-full" required>
                    <span class="label-text-alt">Pengguna wajib menggantinya saat pertama masuk.</span>
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Peran</span>
                    <select name="peran" class="select select-bordered w-full">
                        <option value="operator">Operator — olah data wilayahnya (PPK/PPS)</option>
                        <option value="viewer">Pemeriksa — baca + cetak saja</option>
                        <option value="admin">Admin Kabupaten — semua wilayah</option>
                    </select>
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Kecamatan cakupan (PPK/PPS)</span>
                    <select name="kecamatan_id" id="pilih-kecamatan" class="select select-bordered w-full">
                        <option value="">— Pilih kecamatan —</option>
                        <?php foreach ($daftarKecamatan as $kec) : ?>
                            <option value="<?= (int) $kec['id'] ?>" <?= (string) old('kecamatan_id') === (string) $kec['id'] ? 'selected' : '' ?>><?= esc(ucwords(strtolower($kec['nama']))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="label-text-alt">Wajib untuk PPK/PPS. Admin Kabupaten mengabaikan isian ini.</span>
                </label>
                <label class="form-control w-full">
                    <span class="label-text font-semibold">Desa (khusus PPS)</span>
                    <select name="kelurahan_id" id="pilih-desa" class="select select-bordered w-full">
                        <option value="">— Seluruh kecamatan (PPK) —</option>
                        <?php foreach ($daftarDesaSemua as $desa) : ?>
                            <option value="<?= (int) $desa['id'] ?>" data-kec="<?= (int) $desa['kecamatan_id'] ?>" <?= (string) old('kelurahan_id') === (string) $desa['id'] ? 'selected' : '' ?>><?= esc($desa['nama']) ?> — <?= esc(ucwords(strtolower($desa['kecamatan_nama']))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="label-text-alt">Pilih desa bila akun ini untuk PPS desa tersebut; daftar menyaring mengikuti kecamatan di atas.</span>
                </label>
                <button type="submit" class="btn btn-primary mt-1">Tambah Pengguna</button>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm lg:col-span-3">
        <div class="card-body">
            <h2 class="card-title text-base">Daftar Pengguna</h2>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr><th>Nama</th><th>Pengguna</th><th>Peran</th><th>Wilayah</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $p) : ?>
                        <tr>
                            <td><?= esc($p['nama']) ?></td>
                            <td><?= esc($p['username']) ?></td>
                            <td><?= esc(\App\Models\UserModel::labelPeran($p['peran'])) ?></td>
                            <td><?= esc(\App\Models\UserModel::labelTingkat($p)) ?></td>
                            <td>
                                <?php if ((int) $p['aktif'] === 1) : ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else : ?>
                                    <span class="badge badge-error">Nonaktif</span>
                                <?php endif; ?>
                                <?php if ((int) $p['harus_ganti_sandi'] === 1) : ?>
                                    <span class="badge badge-warning">Wajib ganti sandi</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap">
                                <form method="post" action="<?= base_url('pengguna/' . $p['id'] . '/aktif') ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-xs btn-ghost">
                                        <?= (int) $p['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= base_url('pengguna/' . $p['id'] . '/reset-sandi') ?>" class="inline">
                                    <?= csrf_field() ?>
                                      onsubmit="return confirm('Reset kata sandi <?= esc($p['username'], 'js') ?> menjadi ppk12345?')">
                                    <button type="submit" class="btn btn-xs btn-ghost">Reset Sandi</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs opacity-60 mt-2">Kata sandi hasil reset: <code>ppk12345</code> — pengguna dipaksa menggantinya saat masuk berikutnya.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
<script>
// Saring pilihan desa mengikuti kecamatan cakupan yang dipilih.
(function () {
    var kec = document.getElementById('pilih-kecamatan');
    var desa = document.getElementById('pilih-desa');
    if (!kec || !desa) return;
    function saring() {
        var k = kec.value;
        Array.prototype.forEach.call(desa.options, function (o) {
            if (!o.dataset.kec) return;
            o.hidden = k !== '' && o.dataset.kec !== k;
        });
        if (desa.selectedOptions.length && desa.selectedOptions[0].hidden) {
            desa.value = '';
        }
    }
    kec.addEventListener('change', saring);
    saring();
})();
</script>
<?= $this->endSection() ?>
