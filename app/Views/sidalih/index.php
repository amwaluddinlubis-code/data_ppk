<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h2 class="card-title text-base"><i class="fas fa-file-export text-primary"></i> Ekspor Format Sidalih</h2>
            <p class="text-sm">Unduh daftar pemilih (cakupan wilayah Anda) sebagai CSV dengan judul kolom persis ekspor Sidalih — DPID, KETERANGAN, TEMPAT LAHIR, dan seterusnya — agar dapat disandingkan dengan berkas Sidalih atau diolah di spreadsheet.</p>
            <a class="btn btn-primary btn-sm w-fit" href="<?= base_url('sidalih/ekspor') ?>"><i class="fas fa-download"></i> UNDUH EKSPOR SIDALIH (CSV)</a>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h2 class="card-title text-base"><i class="fas fa-balance-scale text-primary"></i> Banding dengan Ekspor Sidalih</h2>
            <p class="text-sm">Unggah berkas ekspor Sidalih (.xlsx atau .csv) untuk dibandingkan per NIK dengan basis data aplikasi. Hasil banding tersimpan di basis data lokal dan dapat dikosongkan kapan pun.</p>
            <form method="post" action="<?= base_url('sidalih/unggah') ?>" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                <?= csrf_field() ?>
                <input type="file" name="berkas" accept=".xlsx,.csv" class="file-input file-input-bordered file-input-sm w-full max-w-xs" required>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> UNGGAH & BANDINGKAN</button>
            </form>
            <?php if ($jumlahTemp > 0) : ?>
                <form method="post" action="<?= base_url('sidalih/bersihkan') ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost btn-xs" data-konfirmasi="Kosongkan hasil banding Sidalih?">Kosongkan hasil banding</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($ringkasan !== null) : ?>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="font-bold text-sm mb-1"><i class="fas fa-chart-pie text-primary"></i> Hasil Banding<?= isset($meta['banding_berkas']) ? ' — ' . esc($meta['banding_berkas']) : '' ?></h3>
            <p class="text-xs opacity-70 mb-3"><?= isset($meta['banding_waktu']) ? 'Diunggah ' . esc($meta['banding_waktu']) . ' · ' : '' ?><?= number_format($jumlahTemp, 0, ',', '.') ?> baris pada berkas Sidalih. Perbandingan per NIK terhadap seluruh basis data aplikasi (ringkasan kabupaten; selisih rinci dapat diunduh).</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="stat bg-base-200 rounded-box p-3">
                    <div class="stat-title text-xs">SAMA</div>
                    <div class="stat-value text-2xl text-success"><?= number_format($ringkasan['sama'], 0, ',', '.') ?></div>
                    <div class="stat-desc">NIK sama, elemen kunci sama</div>
                </div>
                <div class="stat bg-base-200 rounded-box p-3">
                    <div class="stat-title text-xs">BEDA ELEMEN</div>
                    <div class="stat-value text-2xl text-warning"><?= number_format($ringkasan['beda'], 0, ',', '.') ?></div>
                    <div class="stat-desc">Nama/keterangan/TPS/desa berbeda</div>
                </div>
                <div class="stat bg-base-200 rounded-box p-3">
                    <div class="stat-title text-xs">HANYA DI SIDALIH</div>
                    <div class="stat-value text-2xl text-info"><?= number_format($ringkasan['hanya_sidalih'], 0, ',', '.') ?></div>
                    <div class="stat-desc">Belum ada di aplikasi</div>
                </div>
                <div class="stat bg-base-200 rounded-box p-3">
                    <div class="stat-title text-xs">HANYA DI APLIKASI</div>
                    <div class="stat-value text-2xl text-error"><?= number_format($ringkasan['hanya_aplikasi'], 0, ',', '.') ?></div>
                    <div class="stat-desc">Tidak ada pada berkas Sidalih</div>
                </div>
            </div>
            <a class="btn btn-outline btn-sm w-fit mt-3" href="<?= base_url('sidalih/unduh-selisih') ?>"><i class="fas fa-download"></i> UNDUH CSV SELISIH (NIK & KEDUA SISI)</a>
            <p class="text-xs opacity-60 mt-2">Catatan privasi: berkas selisih memuat NIK — perlakukan seperti data pemilih pada umumnya, jangan dibagikan ke pihak yang tidak berwenang.</p>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
