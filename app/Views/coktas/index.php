<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-clipboard-check text-primary"></i> Coktas — Pencocokan &amp; Penelitian Terbatas</h2>
        <p class="text-sm">Periode berjalan: <strong><?= esc($periode['nama'] ?? '') ?></strong>. Coktas adalah pencocokan terbatas data pemilih ke lapangan/dokumen dalam PDPB (Surat Dinas KPU 228/PP.05-SD/13/2026). Pilih pemilih dari daftar kerja, lalu catat hasilnya: sesuai, tidak sesuai, atau tidak ditemukan.</p>
        <?php if (session()->getFlashdata('sukses')) : ?><div class="alert alert-success py-2 text-sm mt-2"><?= esc(session()->getFlashdata('sukses')) ?></div><?php endif ?>
        <?php if (session()->getFlashdata('galat')) : ?><div class="alert alert-error py-2 text-sm mt-2"><?= esc(session()->getFlashdata('galat')) ?></div><?php endif ?>

        <form method="get" action="<?= base_url('coktas') ?>" class="flex flex-wrap items-end gap-2 mt-3">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">DESA/KELURAHAN</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm w-56">
                    <option value="0">— Semua pada cakupan —</option>
                    <?php foreach ($daftarKel as $k) : ?>
                        <option value="<?= (int) $k['id'] ?>" <?= (int) $f['kelurahan_id'] === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['kecamatan']) ?> — <?= esc($k['nama']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <input type="text" name="tps" value="<?= esc($f['tps']) ?>" class="input input-bordered input-sm w-20" placeholder="cth. 1">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">STATUS COKTAS</span></span>
                <select name="status" class="select select-bordered select-sm w-36">
                    <option value="semua" <?= $f['status'] === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="belum" <?= $f['status'] === 'belum' ? 'selected' : '' ?>>Belum dicoktas</option>
                    <option value="sudah" <?= $f['status'] === 'sudah' ? 'selected' : '' ?>>Sudah dicoktas</option>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">CARI NAMA</span></span>
                <input type="text" name="q" value="<?= esc($f['q']) ?>" class="input input-bordered input-sm w-48" placeholder="Nama pemilih">
            </label>
            <button class="btn btn-primary btn-sm">Tampilkan</button>
            <a class="btn btn-outline btn-sm" href="<?= base_url('coktas/cetak-rekap') ?>" target="_blank"><i class="fas fa-print"></i> Rekap</a>
        </form>

        <?php if ($filterSaya !== []) : ?>
            <div class="flex flex-wrap items-center gap-2 mt-3 text-sm">
                <span class="font-semibold">Filter tersimpan:</span>
                <?php foreach ($filterSaya as $fs) : $p = json_decode((string) $fs['parameter'], true) ?: []; ?>
                    <a class="badge badge-primary badge-outline" href="<?= base_url('coktas?' . http_build_query($p)) ?>"><?= esc($fs['nama']) ?></a>
                    <a class="text-error" title="Hapus filter" href="<?= base_url('coktas/filter-hapus/' . (int) $fs['id']) ?>" onclick="return confirm('Hapus filter ini?')">✕</a>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <form method="post" action="<?= base_url('coktas/filter-simpan') ?>" class="flex items-end gap-2 mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="kelurahan_id" value="<?= (int) $f['kelurahan_id'] ?>">
            <input type="hidden" name="tps" value="<?= esc($f['tps']) ?>">
            <input type="hidden" name="status" value="<?= esc($f['status']) ?>">
            <input type="hidden" name="q" value="<?= esc($f['q']) ?>">
            <input type="text" name="nama_filter" class="input input-bordered input-sm w-56" placeholder="Nama filter saat ini">
            <button class="btn btn-ghost btn-sm"><i class="fas fa-bookmark"></i> Simpan filter</button>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Daftar Kerja — <?= number_format($total, 0, ',', '.') ?> pemilih MS</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Nama</th><th>NIK</th><th>TPS</th><th>Hasil Coktas</th><th>Catat Hasil</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td><?= esc($r['nama']) ?></td>
                        <td class="font-mono text-xs"><?= esc($r['nik']) ?></td>
                        <td><?= (int) $r['tps'] ?></td>
                        <td>
                            <?php if ($r['hasil'] === 'sesuai') : ?><span class="badge badge-success badge-sm">Sesuai</span>
                            <?php elseif ($r['hasil'] === 'tidak_sesuai') : ?><span class="badge badge-warning badge-sm">Tidak sesuai</span>
                            <?php elseif ($r['hasil'] === 'tidak_ditemukan') : ?><span class="badge badge-error badge-sm">Tidak ditemukan</span>
                            <?php else : ?><span class="badge badge-ghost badge-sm">Belum</span><?php endif ?>
                            <?php if (! empty($r['keterangan'])) : ?><div class="text-xs opacity-70"><?= esc($r['keterangan']) ?></div><?php endif ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('coktas/simpan') ?>" class="flex items-center gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="mhc_id" value="<?= (int) $r['id'] ?>">
                                <select name="hasil" class="select select-bordered select-xs w-36">
                                    <option value="sesuai" <?= $r['hasil'] === 'sesuai' ? 'selected' : '' ?>>Sesuai</option>
                                    <option value="tidak_sesuai" <?= $r['hasil'] === 'tidak_sesuai' ? 'selected' : '' ?>>Tidak sesuai</option>
                                    <option value="tidak_ditemukan" <?= $r['hasil'] === 'tidak_ditemukan' ? 'selected' : '' ?>>Tidak ditemukan</option>
                                </select>
                                <input type="text" name="keterangan" value="<?= esc($r['keterangan'] ?? '') ?>" class="input input-bordered input-xs w-44" placeholder="Keterangan">
                                <button class="btn btn-primary btn-xs">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if ($rows === []) : ?><tr><td colspan="5" class="text-center">Tidak ada data pada saringan ini.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
        <?php $totalHal = max(1, (int) ceil($total / $perHal)); ?>
        <?php if ($totalHal > 1) : ?>
            <div class="join mt-3">
                <?php for ($i = 1; $i <= min($totalHal, 15); $i++) : ?>
                    <a class="join-item btn btn-sm <?= $i === $hal ? 'btn-active' : '' ?>" href="<?= base_url('coktas?' . http_build_query(array_merge($f, ['hal' => $i]))) ?>"><?= $i ?></a>
                <?php endfor ?>
                <?php if ($totalHal > 15) : ?><span class="join-item btn btn-sm btn-disabled">… <?= $totalHal ?></span><?php endif ?>
            </div>
        <?php endif ?>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-chart-line text-primary"></i> Pantau Progres per Desa/Kelurahan</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Kecamatan</th><th>Desa/Kelurahan</th><th class="text-right">Pemilih MS</th><th class="text-right">Tercoklit</th><th class="text-right">Progres Coklit</th><th class="text-right">Tercoktas</th><th class="text-right">Sesuai</th><th class="text-right">Tidak Sesuai</th><th class="text-right">Tidak Ditemukan</th><th class="text-right">Progres Coktas</th></tr></thead>
                <tbody>
                <?php foreach ($progres as $p) : $ms = (int) $p['ms']; $pc = $ms > 0 ? round(100 * (int) $p['tercoklit'] / $ms, 1) : 0; $pt = $ms > 0 ? round(100 * (int) $p['tercoktas'] / $ms, 1) : 0; ?>
                    <tr>
                        <td><?= esc($p['kecamatan']) ?></td><td><?= esc($p['nama']) ?></td>
                        <td class="text-right"><?= number_format($ms, 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $p['tercoklit'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= $pc ?>%</td>
                        <td class="text-right"><?= number_format((int) $p['tercoktas'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $p['sesuai'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $p['tidakSesuai'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format((int) $p['tidakDitemukan'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= $pt ?>%</td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-70 mt-2">Progres coklit dihitung dari tanggal coklit (TGL_COKLIT) pada data MS; progres coktas dari hasil yang tercatat pada periode berjalan.</p>
    </div>
</div>
<?= $this->endSection() ?>
