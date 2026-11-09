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
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">KATEGORI</span></span>
                <select class="select select-bordered select-sm" id="KET">
                    <option value="">PILIH KATEGORI</option>
                    <option value="0">PEMILIH SESUAI</option>
                    <option value="U">PEMILIH UBAH</option>
                    <option value="B">PEMILIH BARU</option>
                    <option value="TMS">PEMILIH MEMENUHI SYARAT</option>
                    <option value="8">PEMILIH TERSARING</option>
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
                <a href="<?= base_url() ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="MHC" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>NKK</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>TGL LAHIR</th>
                        <th>LAST UPDATE</th>
                        <th>KET</th>
                        <th>L/P</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna merah muda = keterangan (KET) berupa kode angka (tersaring/bermasalah).</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function loadData() {
    if ($.fn.DataTable.isDataTable('#MHC')) {
        $('#MHC').DataTable().destroy();
    }
    $('#MHC').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('dashboard/getPemilih') ?>",
            data: {
                'desa': $('#DESA').val(),
                'tms': $('#KET').val(),
                'tps': $('#TPS').val(),
                'lp': $('#LP').val()
            },
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false }
        ],
        createdRow: function (row, data) {
            if (data[8] > 0) {
                $(row).addClass('row-tersaring');
            }
        }
    });
}

$(document).ready(function () {
    loadData();
});
<?= $this->endSection() ?>
