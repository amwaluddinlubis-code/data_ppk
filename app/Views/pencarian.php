<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <form method="get" action="<?= base_url('cari') ?>" class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-96">
                <span class="label"><span class="label-text font-semibold">CARI NAMA / NIK / NKK</span></span>
                <input type="text" name="q" minlength="3" required class="input input-bordered input-sm"
                       placeholder="Ketik minimal 3 huruf/angka…" value="<?= esc($q, 'attr') ?>">
            </label>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i> CARI
            </button>
        </form>

        <?php if ($q !== '' && mb_strlen($q) < 3) : ?>
            <p class="text-sm opacity-70">Kata kunci terlalu pendek — ketik minimal 3 huruf/angka.</p>
        <?php elseif ($q !== '') : ?>
            <p class="text-sm mb-3">
                Ditemukan <strong><?= number_format($total, 0, ',', '.') ?></strong> baris untuk
                &ldquo;<?= esc($q) ?>&rdquo; dalam cakupan wilayah Anda.
                <?php if ($total > count($rows)) : ?>
                    Yang ditampilkan hanya <?= count($rows) ?> baris pertama — persempit kata kunci bila pemilih yang dicari belum tampak.
                <?php endif; ?>
            </p>
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NIK</th>
                            <th>NKK</th>
                            <th>NAMA</th>
                            <th>TGL LAHIR</th>
                            <th>L/P</th>
                            <th>KECAMATAN</th>
                            <th>DESA</th>
                            <th>TPS</th>
                            <th>GOLONGAN</th>
                            <th>KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []) : ?>
                            <tr><td colspan="11" class="text-center opacity-60">Tidak ada data yang cocok.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $i => $r) : ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="font-mono text-xs"><?= esc($r['NIK']) ?></td>
                                <td class="font-mono text-xs"><?= esc($r['NKK']) ?></td>
                                <td><?= esc($r['NAMA']) ?></td>
                                <td><?= esc($r['TGLLHR']) ?></td>
                                <td><?= esc($r['JENIS_KELAMIN']) ?></td>
                                <td><?= esc($r['KEC']) ?></td>
                                <td><?= esc($r['DESA']) ?></td>
                                <td><?= esc($r['TPS']) ?></td>
                                <td><span class="badge badge-sm <?= str_starts_with($r['GOLONGAN'], 'Tersaring') ? 'badge-error' : 'badge-success' ?>"><?= esc($r['GOLONGAN']) ?></span></td>
                                <td><?= esc($r['KET_LABEL']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p class="text-sm opacity-70">Pencarian ini menelusuri seluruh data coklit dalam cakupan wilayah Anda — nama, NIK, atau NKK — tanpa perlu membuka daftar satu per satu.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
