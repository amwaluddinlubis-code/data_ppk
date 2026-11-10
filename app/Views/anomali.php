<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">DUPLIKAT TERSELUBUNG</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlTerselubung, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Kelompok nama + tanggal lahir sama dengan NIK berbeda (<?= number_format($jmlBaris, 0, ',', '.') ?> baris), dalam cakupan wilayah Anda.</div>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">KK TERSEBAR</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlKk, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Kartu keluarga yang anggota aktifnya tercatat di lebih dari satu TPS.</div>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">KK ANTAR DESA</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlKkDesa, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Satu KK tersebar di dua desa/kelurahan atau lebih.</div>
        </div>
    </div>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="text-xs font-semibold opacity-60">KK BEDA TPS (SATU DESA)</div>
            <div class="text-2xl font-bold mt-1"><?= number_format($jmlKkTps, 0, ',', '.') ?></div>
            <div class="text-xs opacity-60 mt-1">Satu KK tersebar di beberapa TPS dalam desa yang sama.</div>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <div class="text-sm font-bold mb-2">DUPLIKAT TERSELUBUNG — NAMA & TANGGAL LAHIR SAMA, NIK BERBEDA</div>
        <div class="overflow-x-auto">
            <table id="terselubung" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NIK</th>
                        <th>NKK</th>
                        <th>NAMA</th>
                        <th>TGL LAHIR</th>
                        <th>L/P</th>
                        <th>KECAMATAN</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KETERANGAN</th>
                        <th>SUMBER</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Hanya baris <strong>aktif (MS)</strong> yang dinilai. Pola ini tidak tertangkap Analisis NIK Ganda karena NIK-nya memang berbeda — penyebab tersering adalah salah ketik NIK saat coklit, tetapi bisa juga dua orang berbeda (misalnya kembar dengan nama mirip), jadi perlakukan sebagai <strong>bahan pemeriksaan</strong>, bukan vonis ganda. Kelompok yang tampil dibatasi pada yang menyentuh wilayah Anda, dan semua baris kelompok itu ditampilkan agar mudah dikoordinasikan.</p>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <div class="text-sm font-bold mb-2">KK TERSEBAR — SATU KK, LEBIH DARI SATU TPS</div>
        <div class="flex flex-wrap items-end gap-3 mb-3">
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">KATEGORI</span></span>
                <select class="select select-bordered select-sm" id="kategoriKk">
                    <option value="">SEMUA KATEGORI</option>
                    <option value="desa">ANTAR DESA</option>
                    <option value="tps">BEDA TPS (SATU DESA)</option>
                </select>
            </label>
        </div>
        <div class="overflow-x-auto">
            <table id="kk" class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NKK</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>L/P</th>
                        <th>KECAMATAN</th>
                        <th>DESA</th>
                        <th>TPS</th>
                        <th>KETERANGAN</th>
                        <th>KATEGORI</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Hanya anggota <strong>aktif (MS)</strong> yang dihitung. KK yang tersebar menyulitkan pemilih pada hari pemungutan dan rawan keberatan, jadi daftar ini bahan penataan TPS dan koordinasi dengan PPS setempat. Kelompok yang tampil dibatasi pada yang menyentuh wilayah Anda; seluruh anggota KK itu ditampilkan.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function loadTerselubung() {
    $('#terselubung').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('anomali/getTerselubung') ?>",
            type: 'post'
        },
        columnDefs: [
            { targets: 0, orderable: false }
        ]
    });
}

function loadKk() {
    $('#kk').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('anomali/getKkTersebar') ?>",
            type: 'post',
            data: function (d) {
                d.kategori = $('#kategoriKk').val();
            }
        },
        columnDefs: [
            { targets: 0, orderable: false },
            {
                targets: 9,
                orderable: false,
                render: function (data) {
                    return data === 'Antar Desa'
                        ? '<span class="badge badge-error badge-sm">Antar Desa</span>'
                        : '<span class="badge badge-info badge-sm">Beda TPS</span>';
                }
            }
        ]
    });
}

$(document).ready(function () {
    loadTerselubung();
    loadKk();
    $('#kategoriKk').on('change', function () {
        $('#kk').DataTable().ajax.reload();
    });
});
<?= $this->endSection() ?>
