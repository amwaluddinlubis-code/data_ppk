<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow" x-data="{ memuat: false }"
     @htmx:before-request="memuat = true" @htmx:after-request="memuat = false">
    <div class="card-body p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="badge badge-info badge-sm hidden" x-bind:class="{ 'hidden': !memuat }">
                <span class="loading loading-spinner loading-xs"></span> Memuat…
            </span>
        </div>
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa"
                        hx-get="<?= base_url('filter/tps-opsi') ?>" hx-target="#TPS" hx-swap="innerHTML"
                        hx-vals='{"sumber":"pindah"}'>
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
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">STATUS</span></span>
                <select class="select select-bordered select-sm" id="STAT" name="stat">
                    <option value="">PILIH STATUS</option>
                    <option value="1">JADI PEMILIH BARU</option>
                    <option value="0">BELUM PUNYA TPS</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-44">
                <span class="label"><span class="label-text font-semibold">L/P</span></span>
                <select class="select select-bordered select-sm" id="LP" name="lp">
                    <option value="">PILIH JENIS KELAMIN</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">CARI</span></span>
                <input type="text" class="input input-bordered input-sm" name="cari" placeholder="Nama / NIK / NKK…"
                       hx-get="<?= base_url('pemilih/tabelPindah') ?>" hx-target="#wadah-tabel-pindah" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms" hx-include="[name='desa'],[name='tps'],[name='lp'],[name='stat']">
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm"
                        hx-get="<?= base_url('pemilih/tabelPindah') ?>" hx-target="#wadah-tabel-pindah" hx-swap="innerHTML"
                        hx-include="[name='desa'],[name='tps'],[name='lp'],[name='stat'],[name='cari']">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url('pemilih') ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporData('csv')">
                    <i class="fas fa-file-csv"></i> EKSPOR CSV
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporData('xlsx')">
                    <i class="fas fa-file-excel"></i> EKSPOR EXCEL
                </button>
            </div>
        </div>

        <div id="wadah-tabel-pindah">
            <?= view('pemilih/_tabel_pindah', $tabelAwal) ?>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna merah muda = belum ada padanan pemilih baru (belum punya TPS tujuan).</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function eksporData(format) {
    var params = new URLSearchParams({
        desa: $('#DESA').val() || '',
        tps: $('#TPS').val() || '',
        lp: $('#LP').val() || '',
        stat: $('#STAT').val() || '',
        format: format
    });
    window.location = "<?= base_url('ekspor/pindah') ?>" + '?' + params.toString();
}

// Tabel halaman ini memakai HTMX (partial server-side).
<?= $this->endSection() ?>
