<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">NIK GANDA ANTAR WILAYAH</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlGanda, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">NIK tercatat di lebih dari satu desa atau kecamatan, dalam cakupan wilayah Anda.</div>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">ANTAR KECAMATAN</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlKecamatan, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Satu NIK muncul di dua kecamatan atau lebih.</div>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">ANTAR DESA (SATU KECAMATAN)</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlDesa, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Satu NIK muncul di dua desa/kelurahan atau lebih dalam kecamatan yang sama.</div>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-3">
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">KATEGORI KEGANDAAN</span></span>
                <select class="select select-bordered select-sm" id="kategori">
                    <option value="">SEMUA KATEGORI</option>
                    <option value="kecamatan">ANTAR KECAMATAN</option>
                    <option value="desa">ANTAR DESA</option>
                </select>
            </label>
        </div>
        <div class="overflow-x-auto">
            <table id="ganda" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NIK</th>
                        <th>NKK</th>
                        <th>NAMA</th>
                        <th>L/P</th>
                        <th>KECAMATAN</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KETERANGAN</th>
                        <th>SUMBER</th>
                        <th>KATEGORI</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Analisis ini hanya memperhatikan <strong>NIK</strong> pada seluruh data coklit: setiap NIK yang tercatat di lebih dari satu desa/kelurahan atau kecamatan dikelompokkan di sini, dan <strong>semua baris</strong> NIK itu ditampilkan (termasuk baris di wilayah seberang) agar mudah dikoordinasikan. Kegandaan di dalam desa yang sama tidak ditampilkan karena merupakan pola normal coklit (baris tersaring + baris baru orang yang sama). Kelompok yang tampil dibatasi pada yang menyentuh wilayah Anda.</p>
    </div>
</div>

<div class="card bg-base-100 shadow border-2 border-warning mt-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <div class="text-sm font-bold">PERINGATAN: JENIS KELAMIN TIDAK COCOK DENGAN KODE NIK</div>
            <div class="badge badge-warning badge-lg"><?= number_format($jmlPeringatan, 0, ',', '.') ?> pemilih</div>
        </div>
        <p class="text-xs opacity-70 mb-3">Jenis kelamin yang tercatat berbeda dengan kode pada NIK — pada NIK, perempuan dikodekan sebagai tanggal lahir ditambah 40. Ini <strong>hanya peringatan</strong>: perbedaan seperti ini sering berasal dari data Dukcapil dan belum tentu datanya salah. Gunakan sebagai bahan klarifikasi, dan jangan mengubah data hanya berdasarkan peringatan ini.</p>
        <div class="overflow-x-auto">
            <table id="peringatan" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NIK</th>
                        <th>NKK</th>
                        <th>NAMA</th>
                        <th>TERCATAT</th>
                        <th>MENURUT NIK</th>
                        <th>KECAMATAN</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KETERANGAN</th>
                        <th>TANDA</th>
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
    if ($.fn.DataTable.isDataTable('#ganda')) {
        $('#ganda').DataTable().destroy();
    }
    $('#ganda').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('residu/getGanda') ?>",
            type: 'post',
            data: function (d) {
                d.kategori = $('#kategori').val();
            }
        },
        columnDefs: [
            { targets: 0, orderable: false },
            {
                targets: 10,
                orderable: false,
                render: function (data) {
                    return data === 'Antar Kecamatan'
                        ? '<span class="badge badge-error badge-sm">Antar Kecamatan</span>'
                        : '<span class="badge badge-info badge-sm">Antar Desa</span>';
                }
            }
        ]
    });
}

function loadPeringatan() {
    $('#peringatan').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('residu/getPeringatan') ?>",
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false },
            { targets: 5, orderable: false },
            {
                targets: 10,
                orderable: false,
                render: function (data) {
                    return '<span class="badge badge-warning badge-sm">' + data + '</span>';
                }
            }
        ]
    });
}

$(document).ready(function () {
    loadData();
    loadPeringatan();
    $('#kategori').on('change', function () {
        $('#ganda').DataTable().ajax.reload();
    });
});
<?= $this->endSection() ?>
