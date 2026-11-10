<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('sukses')) : ?>
    <div class="alert alert-success shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('sukses')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('galat')) : ?>
    <div class="alert alert-error shadow-sm mb-4 text-sm"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif; ?>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <form method="get" class="flex flex-wrap items-end gap-3">
                <label class="form-control w-full sm:w-72">
                    <span class="label"><span class="label-text font-semibold">CARI (NIK / NAMA / NKK)</span></span>
                    <input type="text" name="q" value="<?= esc($q, 'attr') ?>" class="input input-bordered input-sm" placeholder="Ketik lalu tekan Enter">
                </label>
                <?php if ($cakupan === null) : ?>
                    <label class="form-control w-full sm:w-56">
                        <span class="label"><span class="label-text font-semibold">DESA</span></span>
                        <select name="desa" class="select select-bordered select-sm" onchange="this.form.submit()">
                            <option value="">SEMUA DESA</option>
                            <?php foreach ($daftarDesa as $d) : ?>
                                <option value="<?= esc($d, 'attr') ?>" <?= $desa === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php else : ?>
                    <span class="badge badge-primary badge-outline">Desa cakupan: <?= esc($cakupan) ?></span>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> CARI</button>
            </form>
            <div class="ml-auto">
                <a href="<?= base_url('coklit/tambah') ?>" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> TAMBAH PEMILIH</a>
            </div>
        </div>

        <p class="text-sm mb-2">Total: <strong><?= number_format($total, 0, ',', '.') ?></strong> baris</p>

        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>NKK</th>
                        <th>NAMA</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KET</th>
                        <th>L/P</th>
                        <th class="text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []) : ?>
                        <tr><td colspan="8" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r) : ?>
                        <tr>
                            <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                            <td class="font-mono text-xs"><?= esc($r['NKK']) ?></td>
                            <td><?= esc($r['NAMA']) ?></td>
                            <td><?= esc($r['DESA']) ?></td>
                            <td><?= esc($r['TPS']) ?></td>
                            <td><span class="badge badge-ghost badge-sm"><?= esc($r['KET']) ?></span></td>
                            <td><?= esc($r['JENIS_KELAMIN']) ?></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="<?= base_url('coklit/edit/' . $r['ID']) ?>" class="btn btn-primary btn-xs"><i class="fas fa-edit"></i> UBAH</a>
                                <form method="post" action="<?= base_url('coklit/hapus/' . $r['ID']) ?>" class="inline" onsubmit="return mintaKonfirmasi(this, 'Hapus Pemilih', 'Hapus baris <?= esc($r['NAMA'], 'js') ?>? Salinannya tetap tersimpan di Riwayat Perubahan dan bisa dipulihkan Admin.', 'Ya, Hapus', 'btn-error');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-error btn-xs"><i class="fas fa-trash"></i> HAPUS</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= view('layout/pagination', [
            'basisUrl' => base_url('coklit'),
            'paramPagination' => ['q' => $q, 'desa' => $desa],
            'halaman' => $halaman,
            'totalHal' => $totalHal,
            'total' => $total,
            'perHal' => $perHal,
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>
