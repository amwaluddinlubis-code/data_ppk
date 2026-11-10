<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('sukses')) : ?>
    <div class="alert alert-success shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('sukses')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('galat')) : ?>
    <div class="alert alert-error shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="card-title text-base"><i class="fas fa-file-import"></i> Impor Data Coklit</h3>
            <p class="text-sm opacity-80">
                Berkas CSV/Excel dengan baris judul = nama kolom data coklit
                (<span class="font-mono text-xs">NIK, NKK, NAMA, TMPTLHR, TGLLHR, STATUS, JENIS_KELAMIN, ALAMAT, RT, RW, DISABILITAS, EKTP, KET, SUMBER, TPS, DESA, …</span>).
                Kolom wajib: <strong>NIK, NAMA, DESA, TPS, KET</strong>. Baris dengan NIK tidak sah dilewati dan dilaporkan.
                Bila berkas memuat kolom <strong>KEC</strong>, baris yang kecamatannya berbeda dari kecamatan tujuan ikut dilewati.
            </p>
            <form method="post" action="<?= base_url('impor/mhc') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php if ($kecTerkunci !== null) : ?>
                    <div class="alert alert-info text-sm mb-3">
                        Kecamatan tujuan terkunci mengikuti akun Anda: <strong><?= esc(ucwords(strtolower((string) $kecTerkunciNama))) ?></strong>
                    </div>
                <?php else : ?>
                    <label class="form-control mb-3">
                        <span class="label"><span class="label-text font-semibold">KECAMATAN TUJUAN</span></span>
                        <select name="kecamatan_target" class="select select-bordered select-sm w-full">
                            <option value="">— Pilih kecamatan —</option>
                            <?php foreach ($daftarKecamatan as $kec) : ?>
                                <option value="<?= (int) $kec['id'] ?>"><?= esc(ucwords(strtolower($kec['nama']))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-control mb-3">
                        <span class="label"><span class="label-text font-semibold">ATAU KECAMATAN BARU</span></span>
                        <input type="text" name="kecamatan_baru" class="input input-bordered input-sm w-full" placeholder="Nama kecamatan (bila datanya pertama kali masuk)">
                        <span class="label-text-alt">Isian ini menang atas pilihan di atas.</span>
                    </label>
                <?php endif; ?>
                <label class="form-control mb-3">
                    <span class="label"><span class="label-text font-semibold">BERKAS</span></span>
                    <input type="file" name="berkas" accept=".csv,.xlsx,.xls" class="file-input file-input-bordered file-input-sm w-full" required>
                </label>
                <div class="flex flex-col gap-2 mb-4">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="radio" name="mode" value="tambah" class="radio radio-primary radio-sm" checked>
                        <span class="label-text"><strong>TAMBAH</strong> — sisipkan baris baru; UID yang sudah ada dilewati.</span>
                    </label>
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="radio" name="mode" value="ganti" class="radio radio-error radio-sm">
                        <span class="label-text"><strong>GANTI</strong> — kosongkan data coklit <strong>kecamatan tujuan saja</strong> lalu isi dari berkas. Data lama kecamatan itu otomatis dicadangkan ke berkas CSV di server (<span class="font-mono text-xs">writable/backup</span>) sebelum ditimpa; kecamatan lain tidak tersentuh.</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Jalankan impor data coklit?');">
                    <i class="fas fa-upload"></i> IMPOR DATA COKLIT
                </button>
            </form>
            <p class="text-xs opacity-60 mt-3">
                Sesudah impor, daftar MS / Tersaring / Baru / Pindah TPS langsung mengikuti data baru
                (semuanya dihitung otomatis dari data coklit) dan halaman hasil menampilkan rekonsiliasinya.
            </p>
        </div>
    </div>

    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="card-title text-base"><i class="fas fa-tags"></i> Impor Keterangan Status TMS</h3>
            <p class="text-sm opacity-80">
                Berkas CSV/Excel dengan kolom <strong>NIK</strong> dan <strong>STATUS</strong> (atau KETERANGAN).
                Keterangan ini yang tampil sebagai STATUS pada halaman Tersaring &amp; Pindah TPS.
                NIK yang sudah ada diperbarui keterangannya; yang belum ada ditambahkan.
            </p>
            <form method="post" action="<?= base_url('impor/tms8') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label class="form-control mb-4">
                    <span class="label"><span class="label-text font-semibold">BERKAS</span></span>
                    <input type="file" name="berkas_tms8" accept=".csv,.xlsx,.xls" class="file-input file-input-bordered file-input-sm w-full" required>
                </label>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fas fa-upload"></i> IMPOR KETERANGAN
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="card-title text-base">Catatan Impor Terakhir</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>WAKTU</th>
                        <th>JENIS</th>
                        <th>MODE</th>
                        <th>BERKAS</th>
                        <th class="text-right">MASUK</th>
                        <th class="text-right">GAGAL</th>
                        <th>CADANGAN</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (($catatan ?? []) === []) : ?>
                        <tr><td colspan="8" class="text-center opacity-60">Belum ada impor yang tercatat.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($catatan ?? []) as $c) : ?>
                        <tr>
                            <td class="whitespace-nowrap"><?= esc($c['created_at']) ?></td>
                            <td><?= esc(strtoupper($c['jenis'])) ?></td>
                            <td><?= $c['mode'] === 'ganti' ? '<span class="badge badge-error badge-sm">GANTI</span>' : '<span class="badge badge-ghost badge-sm">TAMBAH</span>' ?></td>
                            <td class="font-mono text-xs"><?= esc($c['nama_berkas']) ?></td>
                            <td class="text-right"><?= number_format((int) $c['jumlah_baris'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format((int) $c['jumlah_gagal'], 0, ',', '.') ?></td>
                            <td class="font-mono text-xs"><?= esc($c['berkas_cadangan'] ?? '—') ?></td>
                            <td><?= esc($c['pengguna_username']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
