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
        <form method="get" class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">JENIS AKSI</span></span>
                <select name="aksi" class="select select-bordered select-sm" onchange="this.form.submit()">
                    <option value="">SEMUA</option>
                    <?php foreach (['tambah', 'ubah', 'hapus', 'pulih', 'impor'] as $a) : ?>
                        <option value="<?= $a ?>" <?= $aksi === $a ? 'selected' : '' ?>><?= strtoupper($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <p class="text-sm mb-2">Total: <strong><?= number_format($total, 0, ',', '.') ?></strong> catatan</p>

        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>WAKTU</th>
                        <th>AKSI</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>DESA</th>
                        <th>Oleh</th>
                        <th class="text-right">PULIHKAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []) : ?>
                        <tr><td colspan="7" class="text-center opacity-60">Belum ada catatan perubahan.</td></tr>
                    <?php endif; ?>
                    <?php
                    $warna = ['tambah' => 'badge-success', 'ubah' => 'badge-info', 'hapus' => 'badge-error', 'pulih' => 'badge-warning', 'impor' => 'badge-primary'];
                    ?>
                    <?php foreach ($rows as $r) : ?>
                        <tr>
                            <td class="whitespace-nowrap"><?= esc($r['created_at']) ?></td>
                            <td><span class="badge <?= $warna[$r['aksi']] ?? 'badge-ghost' ?> badge-sm"><?= strtoupper(esc($r['aksi'])) ?></span></td>
                            <td class="font-mono text-xs"><?= esc($r['nik']) ?></td>
                            <td><?= esc($r['nama_tampil']) ?></td>
                            <td><?= esc($r['desa_tampil']) ?></td>
                            <td><?= esc($r['pengguna_username']) ?></td>
                            <td class="text-right">
                                <?php if ($r['aksi'] === 'hapus' && $isAdmin) : ?>
                                    <form method="post" action="<?= base_url('riwayat/pulih/' . $r['id']) ?>" onsubmit="return mintaKonfirmasi(this, 'Pulihkan Baris', 'Pulihkan baris <?= esc($r['nama_tampil'], 'js') ?> ke data coklit?', 'Ya, Pulihkan', 'btn-warning');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-warning btn-xs"><i class="fas fa-undo"></i> PULIHKAN</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= view('layout/pagination', [
            'basisUrl' => base_url('riwayat'),
            'paramPagination' => ['aksi' => $aksi],
            'halaman' => $halaman,
            'totalHal' => $totalHal,
            'total' => $total,
            'perHal' => $perHal,
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>
