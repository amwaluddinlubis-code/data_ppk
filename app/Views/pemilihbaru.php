<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow" x-data="{ memuat: false }"
     @htmx:before-request="memuat = true" @htmx:after-request="memuat = false">
    <div class="card-body p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="badge badge-info badge-sm" x-show="memuat" x-cloak>
                <span class="loading loading-spinner loading-xs"></span> Memuat…
            </span>
        </div>
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa"
                        hx-get="<?= base_url('filter/tps-opsi') ?>" hx-target="#TPS" hx-swap="innerHTML"
                        hx-vals='{"sumber":"baru"}'>
                    <option value="">PILIH DESA</option>
                    <?php foreach ($arrDesa as $d) : ?>
                        <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-36">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <select class="select select-bordered select-sm" id="TPS" name="tps">
                    <option value="">PILIH TPS</option>
                    <?php foreach ($tpsAwal as $nomor) : ?>
                        <option value="<?= esc($nomor, 'attr') ?>">TPS <?= esc($nomor) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-52">
                <span class="label"><span class="label-text font-semibold">STATUS DP4</span></span>
                <select class="select select-bordered select-sm" id="DP4" name="dp4">
                    <option value="">PILIH STATUS</option>
                    <option value="1">ADA DI DP4</option>
                    <option value="0">TIDAK ADA DI DP4</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-44">
                <span class="label"><span class="label-text font-semibold">L/P</span></span>
                <select class="select select-bordered select-sm" id="LP" name="lp">
                    <option value="">PILIH L/P</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">CARI</span></span>
                <input type="text" class="input input-bordered input-sm" name="cari" placeholder="Nama / NIK…"
                       hx-get="<?= base_url('pemilih/tabelBaru') ?>" hx-target="#wadah-tabel-baru" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms" hx-include="[name='desa'],[name='tps'],[name='lp'],[name='dp4']">
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm"
                        hx-get="<?= base_url('pemilih/tabelBaru') ?>" hx-target="#wadah-tabel-baru" hx-swap="innerHTML"
                        hx-include="[name='desa'],[name='tps'],[name='lp'],[name='dp4'],[name='cari']">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url('pemilih/baru') ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div id="wadah-tabel-baru">
            <?= view('pemilih/_tabel_baru', $tabelAwal) ?>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna biru muda = pemilih benar-benar baru (tidak ditemukan pada daftar tersaring).</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
// Tabel halaman ini memakai HTMX (partial server-side).
<?= $this->endSection() ?>
