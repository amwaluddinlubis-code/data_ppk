<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">DESA</span></span>
                <select class="select select-bordered select-sm" id="DESA" name="desa">
                    <option value="">PILIH DESA</option>
                    <?php foreach ($arrDesa as $d) : ?>
                        <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
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
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">STATUS (KODE TMS)</span></span>
                <select class="select select-bordered select-sm" id="KET">
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
                <select class="select select-bordered select-sm" id="LP">
                    <option value="">PILIH L/P</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="loadData()">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url('pemilih/tersaring') ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="example1" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>TGL LAHIR</th>
                        <th>L/P</th>
                        <th>KET</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
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
            url: "<?= base_url('pemilih/getPemilihTersaring') ?>",
            data: {
                'desa': $('#DESA').val(),
                'ket': $('#KET').val(),
                'lp': $('#LP').val(),
                'tps': $('#TPS').val()
            },
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false }
        ]
    });
}

$(document).ready(function () {
    loadData();
});
<?= $this->endSection() ?>
