<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$lamaInput = session()->getFlashdata('_ci_old_input') ?? [];
$nilai = static function (string $k) use ($lamaInput, $baris) {
    return $lamaInput[$k] ?? ($baris[$k] ?? '');
};
$isEdit = $baris !== null;
$aksiForm = $isEdit ? base_url('coklit/update/' . $baris['ID']) : base_url('coklit/simpan');
$tglLahirInput = (string) ($lamaInput['TGL_LAHIR_INPUT']
    ?? (\App\Libraries\ImporCoklit::tanggalLahirIso($baris['TGLLHR'] ?? null) ?? ''));
?>
<?php if (session()->getFlashdata('galat')) : ?>
    <div class="alert alert-error shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif; ?>

<div class="card bg-base-100 shadow max-w-4xl">
    <div class="card-body p-4">
        <form method="post" action="<?= $aksiForm ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">NIK *</span></span>
                    <input type="text" name="NIK" value="<?= esc($nilai('NIK'), 'attr') ?>" maxlength="16" inputmode="numeric" class="input input-bordered input-sm font-mono" required>
                    <span class="label"><span class="label-text-alt opacity-60">Tepat 16 angka</span></span>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">NKK *</span></span>
                    <input type="text" name="NKK" value="<?= esc($nilai('NKK'), 'attr') ?>" maxlength="16" inputmode="numeric" class="input input-bordered input-sm font-mono" required>
                    <span class="label"><span class="label-text-alt opacity-60">Tepat 16 angka</span></span>
                </label>
                <label class="form-control md:col-span-2">
                    <span class="label"><span class="label-text font-semibold">NAMA *</span></span>
                    <input type="text" name="NAMA" value="<?= esc($nilai('NAMA'), 'attr') ?>" class="input input-bordered input-sm" required>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">TEMPAT LAHIR</span></span>
                    <input type="text" name="TMPTLHR" value="<?= esc($nilai('TMPTLHR'), 'attr') ?>" class="input input-bordered input-sm">
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">TANGGAL LAHIR</span></span>
                    <input type="date" name="TGL_LAHIR_INPUT" value="<?= esc($tglLahirInput, 'attr') ?>" class="input input-bordered input-sm">
                    <span class="label"><span class="label-text-alt opacity-60">Dipilih dari kalender; tersimpan tetap dalam format data Sidalih.</span></span>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">STATUS PERKAWINAN</span></span>
                    <select name="STATUS" class="select select-bordered select-sm">
                        <option value="">— Belum diisi —</option>
                        <?php foreach ($daftarStatus as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>" <?= (string) $nilai('STATUS') === (string) $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">JENIS KELAMIN *</span></span>
                    <select name="JENIS_KELAMIN" class="select select-bordered select-sm">
                        <option value="L" <?= $nilai('JENIS_KELAMIN') === 'L' ? 'selected' : '' ?>>L — LAKI-LAKI</option>
                        <option value="P" <?= $nilai('JENIS_KELAMIN') === 'P' ? 'selected' : '' ?>>P — PEREMPUAN</option>
                    </select>
                </label>
                <label class="form-control md:col-span-2">
                    <span class="label"><span class="label-text font-semibold">ALAMAT</span></span>
                    <input type="text" name="ALAMAT" value="<?= esc($nilai('ALAMAT'), 'attr') ?>" class="input input-bordered input-sm">
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">RT</span></span>
                    <input type="text" name="RT" value="<?= esc($nilai('RT'), 'attr') ?>" class="input input-bordered input-sm">
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">RW</span></span>
                    <input type="text" name="RW" value="<?= esc($nilai('RW'), 'attr') ?>" class="input input-bordered input-sm">
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">KECAMATAN *</span></span>
                    <?php if ($kecTarget !== null) : ?>
                        <input type="text" value="<?= esc(ucwords(strtolower($kecTarget)), 'attr') ?>" class="input input-bordered input-sm" disabled>
                    <?php else : ?>
                        <select name="KEC" class="select select-bordered select-sm">
                            <?php foreach ($daftarKecamatan as $namaKec) : ?>
                                <option value="<?= esc($namaKec, 'attr') ?>" <?= $nilai('KEC') === $namaKec ? 'selected' : '' ?>><?= esc(ucwords(strtolower($namaKec))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="label"><span class="label-text-alt opacity-60">Anda membuka tampilan seluruh kabupaten — pilih kecamatan tujuan baris ini. Pilihan DESA di bawah memuat semua desa; pastikan desa berada di kecamatan ini.</span></span>
                    <?php endif; ?>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">DESA *</span></span>
                    <?php if ($cakupan !== null) : ?>
                        <input type="text" value="<?= esc($cakupan, 'attr') ?>" class="input input-bordered input-sm" disabled>
                        <input type="hidden" name="DESA" value="<?= esc($cakupan, 'attr') ?>">
                    <?php else : ?>
                        <select name="DESA" class="select select-bordered select-sm">
                            <?php foreach ($daftarDesa as $d) : ?>
                                <option value="<?= esc($d, 'attr') ?>" <?= $nilai('DESA') === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">TPS *</span></span>
                    <input type="number" name="TPS" value="<?= esc($nilai('TPS'), 'attr') ?>" min="1" max="99" class="input input-bordered input-sm" required>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">KETERANGAN (KET) *</span></span>
                    <select name="KET" class="select select-bordered select-sm">
                        <?php foreach ($labelKet as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>" <?= (string) $nilai('KET') === (string) $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">SUMBER</span></span>
                    <input type="text" name="SUMBER" value="<?= esc($nilai('SUMBER'), 'attr') ?>" class="input input-bordered input-sm" list="daftar-sumber">
                    <datalist id="daftar-sumber">
                        <option value="COKLIT"></option>
                        <option value="DP4"></option>
                        <option value="MASYARAKAT"></option>
                        <option value="NKK_TERPISAH"></option>
                        <option value="PINDAH TPS"></option>
                        <option value="ANALISIS_GANDA"></option>
                    </datalist>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">DISABILITAS</span></span>
                    <select name="DISABILITAS" class="select select-bordered select-sm">
                        <option value="">— Belum diisi —</option>
                        <?php foreach ($daftarDisabilitas as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>" <?= (string) $nilai('DISABILITAS') === (string) $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">KEPEMILIKAN KTP-EL</span></span>
                    <select name="EKTP" class="select select-bordered select-sm">
                        <option value="">— Belum diisi —</option>
                        <?php foreach ($daftarEktp as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>" <?= (string) $nilai('EKTP') === (string) $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">TANGGAL COKLIT</span></span>
                    <input type="date" name="TGL_COKLIT" value="<?= esc($nilai('TGL_COKLIT'), 'attr') ?>" class="input input-bordered input-sm">
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">DOKUMEN BUKTI PERUBAHAN</span></span>
                    <select name="dokumen_jenis" class="select select-bordered select-sm">
                        <option value="">— Tanpa dokumen —</option>
                        <?php foreach (\App\Libraries\DokumenBukti::DAFTAR as $dok) : ?>
                            <option value="<?= esc($dok, 'attr') ?>"><?= esc($dok) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-control">
                    <span class="label"><span class="label-text font-semibold">KETERANGAN DOKUMEN</span></span>
                    <input type="text" name="dokumen_keterangan" maxlength="255" placeholder="Nomor / tanggal dokumen" class="input input-bordered input-sm">
                </label>
            </div>
            <p class="text-xs opacity-60 mt-2">Dokumen bukti mengikuti PKPU PDPB Pasal 17 ayat (4): perubahan data pemilih dibuktikan dokumen administrasi kependudukan atau dokumen pendukung lain yang sah. Tercatat di Riwayat Perubahan dan Rekap PDPB periode berjalan.</p>

            <div class="flex gap-2 mt-5">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> SIMPAN</button>
                <a href="<?= base_url('coklit') ?>" class="btn btn-ghost btn-sm">BATAL</a>
            </div>
            <p class="text-xs opacity-60 mt-3">Setiap penyimpanan tercatat di Riwayat Perubahan beserta nama penggunanya.</p>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
