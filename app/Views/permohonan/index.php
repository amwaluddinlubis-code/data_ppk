<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-user-edit text-primary"></i> Permohonan Perbaikan Data Pemilih</h2>
        <p class="text-sm">Register permohonan perbaikan data dari warga (hak subjek data — UU 27/2022). Setiap permohonan wajib ditindaklanjuti paling lambat <strong>3×24 jam</strong> sejak dicatat; baris yang melewati tenggat ditandai merah.</p>
        <?php if (session()->getFlashdata('sukses')) : ?><div class="alert alert-success py-2 text-sm mt-2"><?= esc(session()->getFlashdata('sukses')) ?></div><?php endif ?>
        <?php if (session()->getFlashdata('galat')) : ?><div class="alert alert-error py-2 text-sm mt-2"><?= esc(session()->getFlashdata('galat')) ?></div><?php endif ?>

        <form method="post" action="<?= base_url('permohonan/simpan') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-3">
            <?= csrf_field() ?>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">NIK PEMILIH</span></span>
                <input type="text" name="nik" maxlength="16" required class="input input-bordered input-sm" placeholder="16 digit"></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">NAMA PEMILIH</span></span>
                <input type="text" name="nama" required class="input input-bordered input-sm"></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">DESA/KELURAHAN</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm">
                    <option value="0">— Pilih —</option>
                    <?php foreach ($daftarKel as $k) : ?><option value="<?= (int) $k['id'] ?>"><?= esc($k['kecamatan']) ?> — <?= esc($k['nama']) ?></option><?php endforeach ?>
                </select></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">UNSUR YANG DIPERBAIKI</span></span>
                <select name="unsur" class="select select-bordered select-sm">
                    <?php foreach ($unsur as $u) : ?><option><?= esc($u) ?></option><?php endforeach ?>
                </select></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">NAMA PEMOHON</span></span>
                <input type="text" name="pemohon" required class="input input-bordered input-sm" placeholder="Yang bersangkutan / keluarga"></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">KONTAK PEMOHON</span></span>
                <input type="text" name="kontak" class="input input-bordered input-sm" placeholder="No. HP (bila ada)"></label>
            <label class="form-control md:col-span-3"><span class="label"><span class="label-text font-semibold">URAIAN PERBAIKAN</span></span>
                <textarea name="uraian" class="textarea textarea-bordered textarea-sm" rows="2" placeholder="Data sekarang tertulis …, seharusnya … (sertakan dokumen pendukung yang ditunjukkan pemohon)"></textarea></label>
            <div><button class="btn btn-primary btn-sm">Catat Permohonan</button></div>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Dicatat</th><th>Pemilih</th><th>Wilayah</th><th>Unsur</th><th>Pemohon</th><th>Tenggat 3×24 Jam</th><th>Status</th><th>Ubah Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r) : ?>
                    <tr class="<?= $r['lewat_sla'] ? 'bg-error/10' : '' ?>">
                        <td class="text-xs whitespace-nowrap"><?= esc(date('d-m-Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td><div class="font-semibold"><?= esc($r['nama']) ?></div><div class="font-mono text-xs"><?= esc(substr((string) $r['nik'], 0, 6)) ?>******<?= esc(substr((string) $r['nik'], -4)) ?></div>
                            <?php if (! empty($r['uraian'])) : ?><div class="text-xs opacity-70"><?= esc($r['uraian']) ?></div><?php endif ?></td>
                        <td class="text-xs"><?= esc($r['kecamatan'] ?? '') ?><br><?= esc($r['kelurahan'] ?? '') ?></td>
                        <td><?= esc($r['unsur']) ?></td>
                        <td class="text-xs"><?= esc($r['pemohon']) ?><?= ! empty($r['kontak']) ? '<br>' . esc($r['kontak']) : '' ?></td>
                        <td class="text-xs whitespace-nowrap"><?= $r['lewat_sla'] ? '<span class="badge badge-error badge-sm">LEWAT</span> ' : '' ?><?= esc($r['batas_sla']) ?></td>
                        <td><span class="badge badge-sm <?= match ($r['status']) { 'selesai' => 'badge-success', 'diproses' => 'badge-info', 'ditolak' => 'badge-error', default => 'badge-warning' } ?>"><?= esc($r['status']) ?></span>
                            <?php if (! empty($r['catatan'])) : ?><div class="text-xs opacity-70"><?= esc($r['catatan']) ?></div><?php endif ?></td>
                        <td>
                            <form method="post" action="<?= base_url('permohonan/status') ?>" class="flex items-center gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <select name="status" class="select select-bordered select-xs w-28">
                                    <?php foreach (['baru', 'diproses', 'selesai', 'ditolak'] as $s) : ?><option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach ?>
                                </select>
                                <input type="text" name="catatan" class="input input-bordered input-xs w-36" placeholder="Catatan tindak lanjut">
                                <button class="btn btn-primary btn-xs">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if ($rows === []) : ?><tr><td colspan="8" class="text-center">Belum ada permohonan tercatat.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
