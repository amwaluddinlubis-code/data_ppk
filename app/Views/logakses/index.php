<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <p class="text-sm mb-2">Total: <strong><?= number_format($total, 0, ',', '.') ?></strong> catatan akses</p>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>WAKTU</th>
                        <th>PENGGUNA</th>
                        <th>AKSI</th>
                        <th>RINCIAN</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []) : ?>
                        <tr><td colspan="5" class="text-center opacity-60">Belum ada catatan akses.</td></tr>
                    <?php endif; ?>
                    <?php
                    $warna = [
                        'masuk' => 'badge-success', 'masuk_gagal' => 'badge-error',
                        'keluar' => 'badge-ghost', 'cetak_daftar_ms' => 'badge-info',
                        'unduh_pdf_ms' => 'badge-info', 'backup_basis_data' => 'badge-warning',
                        'ganti_kecamatan' => 'badge-primary',
                    ];
                    ?>
                    <?php foreach ($rows as $r) : ?>
                        <tr>
                            <td class="whitespace-nowrap"><?= esc($r['created_at']) ?></td>
                            <td><?= esc($r['pengguna_username'] ?? '—') ?></td>
                            <td><span class="badge <?= $warna[$r['aksi']] ?? 'badge-ghost' ?> badge-sm"><?= esc($r['aksi']) ?></span></td>
                            <td><?= esc($r['detail'] ?? '') ?></td>
                            <td class="font-mono text-xs"><?= esc($r['ip_address'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex items-center gap-2 mt-4">
            <?php if ($halaman > 1) : ?>
                <a href="<?= base_url('log-akses') . '?hal=' . ($halaman - 1) ?>" class="btn btn-sm">« Sebelumnya</a>
            <?php endif; ?>
            <span class="text-sm">Halaman <?= $halaman ?> dari <?= $totalHal ?></span>
            <?php if ($halaman < $totalHal) : ?>
                <a href="<?= base_url('log-akses') . '?hal=' . ($halaman + 1) ?>" class="btn btn-sm">Berikutnya »</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
