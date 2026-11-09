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
        <p class="text-sm opacity-80 mb-4">
            Satu pemasangan aplikasi dapat melayani banyak kecamatan — setiap kecamatan memakai
            basis datanya sendiri. Mengganti kecamatan aktif hanya mengubah tampilan &amp; olahan
            data pada <strong>sesi Anda</strong>; akun pengguna tetap satu (dari basis data utama).
            Daftar kecamatan diatur di <span class="font-mono text-xs">app/Config/Kecamatan.php</span>.
        </p>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>KECAMATAN</th>
                        <th>BASIS DATA</th>
                        <th class="text-right">BARIS COKLIT</th>
                        <th>STATUS</th>
                        <th class="text-right">TINDAKAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $d) : ?>
                        <tr class="<?= $d['aktif'] ? 'font-bold bg-base-200' : '' ?>">
                            <td><?= esc($d['nama']) ?></td>
                            <td class="font-mono text-xs"><?= esc($d['database']) ?></td>
                            <td class="text-right">
                                <?= $d['jumlah'] === null ? '<span class="badge badge-error badge-sm">BELUM SIAP</span>' : number_format($d['jumlah'], 0, ',', '.') ?>
                            </td>
                            <td>
                                <?php if ($d['aktif']) : ?>
                                    <span class="badge badge-success badge-sm">SEDANG AKTIF</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if (! $d['aktif'] && $d['jumlah'] !== null) : ?>
                                    <form method="post" action="<?= base_url('kecamatan/aktifkan/' . $d['grup']) ?>" onsubmit="return confirm('Ganti kecamatan aktif ke <?= esc($d['nama'], 'js') ?>?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-primary btn-xs">AKTIFKAN</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-3">
            Kecamatan berstatus BELUM SIAP berarti basis datanya belum dibuat / belum dimigrasikan.
            Lihat petunjuk di <span class="font-mono">app/Config/Kecamatan.php</span> dan README.
        </p>
    </div>
</div>
<?= $this->endSection() ?>
