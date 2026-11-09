<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa">
                    <option value="">PILIH DESA</option>
                    <?php foreach ($arrDesa->getResult() as $d) : ?>
                        <option value="<?= esc($d->DESA, 'attr') ?>"><?= esc($d->DESA) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control w-full sm:w-36">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <select class="select select-bordered select-sm" id="TPS">
                    <option value="">PILIH TPS</option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?= $i ?>">TPS <?= $i ?></option>
                    <?php endfor; ?>
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
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="loadData()">
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

        <div class="overflow-x-auto">
            <table id="example1" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>NKK</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>TGL LAHIR</th>
                        <th>L/P</th>
                        <th>UMUR</th>
                        <th>KET</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Tombol CETAK DPS membuka daftar pemilih aktif (MS) sesuai desa &amp; TPS yang dipilih, di tab baru siap cetak.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function loadData() {
    if ($.fn.DataTable.isDataTable('#example1')) {
        $('#example1').DataTable().destroy();
    }
    $('#example1').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('pemilih/aktif') ?>",
            data: {
                'desa': $('#DESA').val(),
                'tps': $('#TPS').val(),
                'lp': $('#LP').val()
            },
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false },
            { className: 'text-center', targets: [0, 2, 3, 4, 6, 7, 8, 9] }
        ]
    });
}

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

$(document).ready(function () {
    loadData();
});
<?= $this->endSection() ?>
