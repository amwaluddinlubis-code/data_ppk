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
                        hx-vals='{"sumber":"aktif"}'>
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
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">CARI</span></span>
                <input type="text" class="input input-bordered input-sm" name="cari" placeholder="Nama / NIK / NKK…"
                       hx-get="<?= base_url('pemilih/tabelAktif') ?>" hx-target="#wadah-tabel-aktif" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms" hx-include="[name='desa'],[name='tps'],[name='lp']">
            </label>
            <label class="form-control w-full sm:w-44">
                <span class="label"><span class="label-text font-semibold">L/P</span></span>
                <select class="select select-bordered select-sm" id="LP" name="lp">
                    <option value="">PILIH L/P</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm"
                        hx-get="<?= base_url('pemilih/tabelAktif') ?>" hx-target="#wadah-tabel-aktif" hx-swap="innerHTML"
                        hx-include="[name='desa'],[name='tps'],[name='lp'],[name='cari']">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url('pemilih/aktifPrint') ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
                <button type="button" class="btn btn-success btn-sm" onclick="printPdf()">
                    <i class="fas fa-print"></i> CETAK DPS
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="printPdfFile()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <div id="wadah-tabel-aktif">
            <?= view('pemilih/_tabel_aktif', $tabelAwal) ?>
        </div>
        <p class="text-xs opacity-60 mt-2">Tombol CETAK DPS membuka daftar pemilih aktif (MS) sesuai desa &amp; TPS yang dipilih, di tab baru siap cetak.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
// Tabel halaman ini memakai HTMX (partial server-side); yang tersisa
// di sini hanya fungsi tombol cetak.
function printPdf() {
    var desa = $('#DESA').val();
    var tps = $('#TPS').val();
    var url = "<?= base_url('pemilih/ms') ?>" + '?desa=' + encodeURIComponent(desa) + '&tps=' + encodeURIComponent(tps);
    window.open(url, '_blank');
}

function printPdfFile() {
    var desa = $('#DESA').val();
    var tps = $('#TPS').val();
    var url = "<?= base_url('pemilih/ms-pdf') ?>" + '?desa=' + encodeURIComponent(desa) + '&tps=' + encodeURIComponent(tps);
    window.open(url, '_blank');
}
<?= $this->endSection() ?>
