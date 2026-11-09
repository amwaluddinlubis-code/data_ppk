<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="overflow-x-auto">
            <table id="ganda" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KET</th>
                        <th>NIK</th>
                        <th>NKK</th>
                        <th>NAMA</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Daftar ini berasal dari view <code>analisis_nik_ganda</code>: NIK yang muncul lebih dari sekali pada daftar MS. Bila kosong, berarti tidak ada NIK ganda pada data MS saat ini.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function loadData() {
    if ($.fn.DataTable.isDataTable('#ganda')) {
        $('#ganda').DataTable().destroy();
    }
    $('#ganda').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('residu/getGanda') ?>",
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
