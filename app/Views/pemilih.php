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
                <select class="select select-bordered select-sm" id="TPS" data-sumber="pindah">
                    <option value="">PILIH TPS</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-60">
                <span class="label"><span class="label-text font-semibold">STATUS</span></span>
                <select class="select select-bordered select-sm" id="STAT">
                    <option value="">PILIH STATUS</option>
                    <option value="1">JADI PEMILIH BARU</option>
                    <option value="0">BELUM PUNYA TPS</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-44">
                <span class="label"><span class="label-text font-semibold">L/P</span></span>
                <select class="select select-bordered select-sm" id="LP">
                    <option value="">PILIH JENIS KELAMIN</option>
                    <option value="L">LAKI-LAKI</option>
                    <option value="P">PEREMPUAN</option>
                </select>
            </label>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="loadData()">
                    <i class="fas fa-filter"></i> FILTER
                </button>
                <a href="<?= base_url('pemilih') ?>" class="btn btn-error btn-sm">
                    <i class="fas fa-undo"></i> RESET FILTER
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="example1" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>UID</th>
                        <th>NKK</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KET</th>
                        <th>DESA (BARU)</th>
                        <th>TPS (BARU)</th>
                        <th>NAMA (BARU)</th>
                        <th>L/P</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Baris berwarna merah muda = belum ada padanan pemilih baru (belum punya TPS tujuan).</p>
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
            url: "<?= base_url('pemilih/pindahTPS') ?>",
            data: {
                'desa': $('#DESA').val(),
                'stat': $('#STAT').val(),
                'lp': $('#LP').val(),
                'tps': $('#TPS').val()
            },
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false },
            { targets: 1, visible: false },
            { targets: 2, visible: false },
            { targets: 7, visible: false }
        ],
        createdRow: function (row, data) {
            if (data[8] == null) {
                $(row).addClass('row-tersaring');
            }
        }
    });
}

$(document).ready(function () {
    muatDaftarTps($('#DESA').val());
    $('#DESA').on('change', function () {
        muatDaftarTps(this.value);
    });
    loadData();
});
<?= $this->endSection() ?>
