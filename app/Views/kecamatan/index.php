<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php $fmt = static fn ($n) => number_format((int) $n, 0, ',', '.'); ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <p class="text-sm opacity-80">
            Seluruh kecamatan berada dalam <strong>satu basis data kabupaten</strong>
            <?= $kabupaten ? '(Kabupaten ' . esc(ucwords(strtolower($kabupaten))) . ')' : '' ?>.
            <?php if ($terkunci) : ?>
                Wilayah tampil Anda <strong>terkunci mengikuti cakupan akun</strong> — hanya Admin Kabupaten yang dapat mengganti saringan kecamatan.
            <?php else : ?>
                Memilih kecamatan di bawah mengganti <strong>saringan wilayah sesi Anda</strong> — semua halaman, laporan, dan olahan mengikuti kecamatan terpilih. Pengguna PPK/PPS tetap terkunci ke wilayah akunnya masing-masing.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php if (! $terkunci) : ?>
        <div class="card <?= $semuaAktif ? 'bg-primary text-primary-content' : 'bg-base-100' ?> shadow">
            <div class="card-body p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-bold text-lg leading-tight">Seluruh Kabupaten</div>
                        <div class="text-xs opacity-70">Semua kecamatan jadi satu tampilan</div>
                    </div>
                    <i class="fas fa-globe text-xl opacity-70"></i>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center mt-3">
                    <div><div class="font-bold"><?= $fmt($totalKab) ?></div><div class="text-[11px] opacity-70">Pemilih</div></div>
                    <div><div class="font-bold"><?= count($daftar) ?></div><div class="text-[11px] opacity-70">Kecamatan</div></div>
                    <div><div class="font-bold"><?= $fmt(array_sum(array_column($daftar, 'desa'))) ?></div><div class="text-[11px] opacity-70">Desa</div></div>
                </div>
                <?php if ($semuaAktif) : ?>
                    <span class="badge badge-success mt-3">SEDANG AKTIF</span>
                <?php else : ?>
                    <form method="post" action="<?= base_url('kecamatan/aktifkan') ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kecamatan" value="__semua__">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Tampilkan Semua</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($daftar as $d) : ?>
        <div class="card <?= $d['aktif'] ? 'bg-primary text-primary-content' : 'bg-base-100' ?> shadow">
            <div class="card-body p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="font-bold text-lg leading-tight"><?= esc(ucwords(strtolower($d['nama']))) ?></div>
                        <div class="text-xs opacity-70">Kecamatan</div>
                    </div>
                    <i class="fas fa-map-marked-alt text-xl opacity-70"></i>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center mt-3">
                    <div><div class="font-bold"><?= $fmt($d['pemilih']) ?></div><div class="text-[11px] opacity-70">Pemilih</div></div>
                    <div><div class="font-bold"><?= $fmt($d['desa']) ?></div><div class="text-[11px] opacity-70">Desa</div></div>
                    <div><div class="font-bold"><?= $fmt($d['tps']) ?></div><div class="text-[11px] opacity-70">TPS</div></div>
                </div>
                <?php if ($d['aktif']) : ?>
                    <span class="badge badge-success mt-3">SEDANG AKTIF</span>
                <?php elseif (! $terkunci) : ?>
                    <form method="post" action="<?= base_url('kecamatan/aktifkan') ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kecamatan" value="<?= esc($d['nama'], 'attr') ?>">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Aktifkan</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($daftar === []) : ?>
    <div class="alert alert-warning text-sm mt-3">Belum ada data coklit di basis data — daftar kecamatan akan muncul sesudah data diimpor.</div>
<?php endif; ?>
<?= $this->endSection() ?>
