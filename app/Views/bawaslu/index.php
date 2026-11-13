<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-eye text-primary"></i> Pengawasan Bawaslu — PDPB</h2>
        <p class="text-sm">Dukungan pengawasan pemutakhiran data pemilih berkelanjutan (Perbawaslu 1/2025). <strong>Paket uji petik</strong> berisi agregat per desa/kelurahan dan daftar perubahan satu periode dengan NIK tersamar — siap diserahkan saat Bawaslu melakukan uji petik. <strong>Saran perbaikan tertulis</strong> Bawaslu (Pasal 9) dicatat dan dipantau tindak lanjutnya di register bawah.</p>
        <?php if (session()->getFlashdata('sukses')) : ?><div class="alert alert-success py-2 text-sm mt-2"><?= esc(session()->getFlashdata('sukses')) ?></div><?php endif ?>
        <?php if (session()->getFlashdata('galat')) : ?><div class="alert alert-error py-2 text-sm mt-2"><?= esc(session()->getFlashdata('galat')) ?></div><?php endif ?>
        <form method="get" action="<?= base_url('bawaslu/uji-petik') ?>" class="flex items-end gap-2 mt-3">
            <label class="form-control"><span class="label"><span class="label-text font-semibold">PERIODE UJI PETIK</span></span>
                <select name="periode_id" class="select select-bordered select-sm w-64">
                    <?php foreach ($periodeList as $p) : ?><option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $periodeAktif['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?> (<?= esc($p['status']) ?>)</option><?php endforeach ?>
                </select></label>
            <button class="btn btn-primary btn-sm">Buka Paket Uji Petik</button>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Catat Saran Perbaikan Bawaslu</h3>
        <form method="post" action="<?= base_url('bawaslu/saran-simpan') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <?= csrf_field() ?>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">NOMOR SURAT</span></span>
                <input type="text" name="nomor_surat" required class="input input-bordered input-sm"></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">TANGGAL SURAT</span></span>
                <input type="date" name="tanggal" class="input input-bordered input-sm"></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">PERIODE PDPB</span></span>
                <select name="periode_id" class="select select-bordered select-sm">
                    <option value="0">— Tidak terkait periode —</option>
                    <?php foreach ($periodeList as $p) : ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option><?php endforeach ?>
                </select></label>
            <label class="form-control md:col-span-2"><span class="label"><span class="label-text font-semibold">URAIAN SARAN</span></span>
                <textarea name="uraian" required class="textarea textarea-bordered textarea-sm" rows="2"></textarea></label>
            <label class="form-control"><span class="label"><span class="label-text font-semibold">PENANGGUNG JAWAB</span></span>
                <input type="text" name="penanggung_jawab" class="input input-bordered input-sm"></label>
            <div><button class="btn btn-primary btn-sm">Simpan Saran</button></div>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Register Saran Perbaikan</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead><tr><th>Surat</th><th>Periode</th><th>Uraian</th><th>Penanggung Jawab</th><th>Status</th><th>Tindak Lanjut</th></tr></thead>
                <tbody>
                <?php foreach ($saran as $s) : ?>
                    <tr>
                        <td class="text-xs whitespace-nowrap"><strong><?= esc($s['nomor_surat']) ?></strong><br><?= ! empty($s['tanggal']) ? esc(date('d-m-Y', strtotime((string) $s['tanggal']))) : '' ?></td>
                        <td class="text-xs"><?= esc($s['periode_nama'] ?? '—') ?></td>
                        <td class="text-sm"><?= esc($s['uraian']) ?></td>
                        <td class="text-xs"><?= esc($s['penanggung_jawab'] ?? '') ?></td>
                        <td><span class="badge badge-sm <?= match ($s['status']) { 'selesai' => 'badge-success', 'ditindaklanjuti' => 'badge-info', default => 'badge-warning' } ?>"><?= esc($s['status']) ?></span></td>
                        <td>
                            <form method="post" action="<?= base_url('bawaslu/saran-status') ?>" class="flex items-center gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <select name="status" class="select select-bordered select-xs w-32">
                                    <?php foreach (['baru', 'ditindaklanjuti', 'selesai'] as $st) : ?><option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach ?>
                                </select>
                                <input type="text" name="tindak_lanjut" value="<?= esc($s['tindak_lanjut'] ?? '') ?>" class="input input-bordered input-xs w-44" placeholder="Isi tindak lanjut">
                                <button class="btn btn-primary btn-xs">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if ($saran === []) : ?><tr><td colspan="6" class="text-center">Belum ada saran perbaikan tercatat.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
