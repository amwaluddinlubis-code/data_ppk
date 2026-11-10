<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-file-signature text-primary"></i> Berita Acara Rekapitulasi PDPB — Lampiran VII PKPU 1/2025</h2>
        <form method="get" action="<?= base_url('pdpb/ba') ?>" class="flex items-end gap-2 mb-3">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">PERIODE</span></span>
                <select name="periode_id" class="select select-bordered select-sm w-72" onchange="this.form.submit()">
                    <?php foreach ($daftarPeriode as $dp) : ?>
                        <option value="<?= (int) $dp['id'] ?>" <?= (int) $dp['id'] === (int) $periode['id'] ? 'selected' : '' ?>><?= esc($dp['nama']) ?><?= $dp['status'] === 'ditutup' ? ' (arsip)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <a class="btn btn-primary btn-sm" target="_blank" href="<?= base_url('pdpb/ba/cetak?periode_id=' . (int) $periode['id']) ?>"><i class="fas fa-print"></i> CETAK BERITA ACARA</a>
        </form>

        <form method="post" action="<?= base_url('pdpb/ba/simpan') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="periode_id" value="<?= (int) $periode['id'] ?>">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">NOMOR BERITA ACARA</span></span>
                <input type="text" name="ba_nomor" value="<?= esc($periode['ba_nomor'] ?? '', 'attr') ?>" maxlength="80" class="input input-bordered input-sm" placeholder="cth. 12/PL.02.1-BA/1213/KPU-Kab/XI/2026">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TANGGAL PLENO</span></span>
                <input type="date" name="ba_tanggal" value="<?= esc($periode['ba_tanggal'] ?? '', 'attr') ?>" class="input input-bordered input-sm">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TEMPAT PLENO</span></span>
                <input type="text" name="ba_tempat" value="<?= esc($periode['ba_tempat'] ?? '', 'attr') ?>" maxlength="120" class="input input-bordered input-sm" placeholder="cth. Kantor KPU Kabupaten Mandailing Natal">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">PUKUL (WIB)</span></span>
                <input type="text" name="ba_pukul" value="<?= esc($periode['ba_pukul'] ?? '', 'attr') ?>" maxlength="5" class="input input-bordered input-sm" placeholder="09.00">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">KETUA KPU KABUPATEN/KOTA</span></span>
                <input type="text" name="ba_ketua" value="<?= esc($periode['ba_ketua'] ?? '', 'attr') ?>" maxlength="120" class="input input-bordered input-sm">
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">ANGGOTA (SATU NAMA PER BARIS)</span></span>
                <textarea name="ba_anggota" rows="4" class="textarea textarea-bordered textarea-sm"><?= esc($periode['ba_anggota'] ?? '') ?></textarea>
            </label>
            <label class="form-control md:col-span-2">
                <span class="label"><span class="label-text font-semibold">MASUKAN YANG DITERIMA (SATU PER BARIS — DARI BAWASLU, DISDUKCAPIL, MASYARAKAT, DLL.)</span></span>
                <textarea name="ba_masukan" rows="3" class="textarea textarea-bordered textarea-sm"><?= esc($periode['ba_masukan'] ?? '') ?></textarea>
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> SIMPAN METADATA</button>
                <span class="text-xs opacity-60 ml-2">Metadata tersimpan pada periode — Berita Acara dan pengesahan Model A-Rekap Kabko-PDPB yang dicetak ulang akan selalu sama.</span>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
