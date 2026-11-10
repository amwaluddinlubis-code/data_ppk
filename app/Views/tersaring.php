<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow" data-filter-panel x-data="{ memuat: false }"
     @htmx:before-request="memuat = true" @htmx:after-request="memuat = false">
    <div class="card-body p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="badge badge-info badge-sm hidden" x-bind:class="{ 'hidden': !memuat }">
                <span class="loading loading-spinner loading-xs"></span> Memuat…
            </span>
        </div>
        <div class="flex flex-wrap items-end gap-3 mb-4" data-filter-controls>
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa"
                        hx-get="<?= base_url('filter/tps-opsi') ?>" hx-target="#TPS" hx-swap="innerHTML"
                        hx-vals='{"sumber":"tersaring"}'>
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
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">STATUS (KODE TMS)</span></span>
                <select class="select select-bordered select-sm" id="KET" name="ket">
                    <option value="">PILIH STATUS</option>
                    <option value="1">1 - MENINGGAL</option>
                    <option value="2">2 - GANDA</option>
                    <option value="3">3 - DI BAWAH UMUR</option>
                    <option value="4">4 - PINDAH DOMISILI</option>
                    <option value="5">5 - WNA</option>
                    <option value="6">6 - TNI</option>
                    <option value="7">7 - POLRI</option>
                    <option value="8">8 - SALAH PENEMPATAN TPS</option>
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
                       hx-get="<?= base_url('pemilih/tabelTersaring') ?>" hx-target="#wadah-tabel-tersaring" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms" hx-include="[name='desa'],[name='tps'],[name='lp'],[name='ket']">
            </label>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" data-filter-apply
                        hx-get="<?= base_url('pemilih/tabelTersaring') ?>" hx-target="#wadah-tabel-tersaring" hx-swap="innerHTML"
                        hx-include="[name='desa'],[name='tps'],[name='lp'],[name='ket'],[name='cari']">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <button type="button" class="btn btn-ghost btn-sm" data-filter-reset>
                    <i class="fas fa-undo"></i> RESET
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporData('csv')">
                    <i class="fas fa-file-csv"></i> EKSPOR CSV
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporData('xlsx')">
                    <i class="fas fa-file-excel"></i> EKSPOR EXCEL
                </button>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2" data-filter-chips aria-live="polite" aria-label="Filter aktif" hidden></div>
        <div id="wadah-tabel-tersaring">
            <?= view('pemilih/_tabel_tersaring', $tabelAwal) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function eksporData(format) {
    var params = new URLSearchParams({
        desa: $('#DESA').val() || '',
        tps: $('#TPS').val() || '',
        lp: $('#LP').val() || '',
        ket: $('#KET').val() || '',
        format: format
    });
    window.location = "<?= base_url('ekspor/tersaring') ?>" + '?' + params.toString();
}

// Tabel halaman ini memakai HTMX (partial server-side).
<?= $this->endSection() ?>
