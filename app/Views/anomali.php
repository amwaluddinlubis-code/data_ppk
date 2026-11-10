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

<div class="card bg-base-100 shadow mt-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <div class="text-sm font-bold">UMUR EKSTREM</div>
            <div class="badge badge-error badge-lg">Di atas 100 tahun: <?= number_format($jmlAtas100, 0, ',', '.') ?></div>
            <div class="badge badge-warning badge-lg">Di bawah 17 tahun: <?= number_format($jmlBawah17, 0, ',', '.') ?></div>
        </div>
        <p class="text-xs opacity-70 mb-3">Umur dihitung pada <strong>hari pemungutan suara (acuan: <?= esc($acuanTampil) ?>)</strong>. Hanya baris aktif (MS) yang dinilai. Kelompok di bawah 17 tahun hanya memuat yang berstatus <strong>belum kawin</strong> — pemilih di bawah 17 tahun yang sudah/pernah kawin tetap sah memilih, jadi tidak ditampilkan. Kelompok di atas 100 tahun adalah indikasi warga yang belum terdata meninggal.</p>
        <?php if ($adalahAdmin) : ?>
            <form method="post" action="<?= base_url('anomali/acuan') ?>" class="flex flex-wrap items-end gap-3 mb-3">
                <?= csrf_field() ?>
                <label class="form-control w-full sm:w-64">
                    <span class="label"><span class="label-text font-semibold">TANGGAL ACUAN PEMUNGUTAN (ADMIN)</span></span>
                    <input type="date" name="tanggal" value="<?= esc($acuan, 'attr') ?>" class="input input-bordered input-sm" required>
                </label>
                <button type="submit" class="btn btn-sm btn-primary">Simpan Acuan</button>
            </form>
        <?php endif ?>
        <div class="flex flex-wrap items-end gap-3 mb-3">
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">KATEGORI UMUR</span></span>
                <select class="select select-bordered select-sm" id="kategoriUmur">
                    <option value="">SEMUA KATEGORI</option>
                    <option value="atas">DI ATAS 100 TAHUN</option>
                    <option value="bawah">DI BAWAH 17 TAHUN</option>
                </select>
            </label>
        </div>
        <div class="overflow-x-auto">
            <table id="umur" class="table table-zebra table-sm w-full">
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
                        <th>UMUR</th>
                        <th>KATEGORI</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow mt-4">
    <div class="card-body p-4">
        <div class="text-sm font-bold mb-2">STATISTIK KESESUAIAN NIK</div>
        <div class="overflow-x-auto mb-3">
            <table class="table table-sm w-full">
                <tbody>
                    <tr>
                        <td>Total baris dalam cakupan Anda</td>
                        <td class="text-right font-bold"><?= number_format($statNik['total'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>NIK bukan 16 digit angka</td>
                        <td class="text-right font-bold"><?= number_format($statNik['non16'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Kode tanggal pada NIK rusak (hari/bulan di luar rentang sah)</td>
                        <td class="text-right font-bold"><?= number_format($statNik['kode_rusak'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Tanggal pada NIK berbeda dengan tanggal lahir tercatat</td>
                        <td class="text-right font-bold"><?= number_format($statNik['tgl_beda'], 0, ',', '.') ?> (<?= number_format($statNik['persen'], 1, ',', '.') ?>% dari <?= number_format($statNik['dinilai'], 0, ',', '.') ?> yang dinilai)</td>
                    </tr>
                    <tr>
                        <td class="pl-8 opacity-70">— komponen hari berbeda</td>
                        <td class="text-right opacity-70"><?= number_format($statNik['hari_beda'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="pl-8 opacity-70">— komponen bulan berbeda</td>
                        <td class="text-right opacity-70"><?= number_format($statNik['bulan_beda'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="pl-8 opacity-70">— komponen tahun berbeda</td>
                        <td class="text-right opacity-70"><?= number_format($statNik['tahun_beda'], 0, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="text-xs font-semibold mb-1">RINCIAN PER KECAMATAN</div>
        <div class="overflow-x-auto">
            <table class="table table-zebra table-sm w-full">
                <thead>
                    <tr>
                        <th>KECAMATAN</th>
                        <th class="text-right">BARIS DINILAI</th>
                        <th class="text-right">TANGGAL BERBEDA</th>
                        <th class="text-right">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statKecamatan as $s) : ?>
                        <tr>
                            <td><?= esc($s['nama']) ?></td>
                            <td class="text-right"><?= number_format($s['dinilai'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($s['beda'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($s['persen'], 1, ',', '.') ?>%</td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Ketidakcocokan tanggal pada NIK bersifat massal dan umumnya warisan data Dukcapil, sehingga disajikan sebagai <strong>statistik pemetaan</strong> (bukan daftar kerja per orang) — berguna untuk melihat kecamatan mana yang datanya paling perlu klarifikasi massal ke Dukcapil. Perbandingan kode wilayah pada NIK dengan kecamatan domisili belum termasuk di sini karena memerlukan padanan kode Kemendagri–Sidalih.</p>
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

function loadUmur() {
    $('#umur').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "<?= base_url('anomali/getUmur') ?>",
            type: 'post',
            data: function (d) {
                d.kategori = $('#kategoriUmur').val();
            }
        },
        columnDefs: [
            { targets: 0, orderable: false },
            { targets: 10, orderable: false },
            {
                targets: 11,
                orderable: false,
                render: function (data) {
                    return data === 'Di Atas 100 Tahun'
                        ? '<span class="badge badge-error badge-sm">Di Atas 100 Tahun</span>'
                        : '<span class="badge badge-warning badge-sm">Di Bawah 17 Tahun</span>';
                }
            }
        ]
    });
}

$(document).ready(function () {
    loadTerselubung();
    loadKk();
    loadUmur();
    $('#kategoriKk').on('change', function () {
        $('#kk').DataTable().ajax.reload();
    });
    $('#kategoriUmur').on('change', function () {
        $('#umur').DataTable().ajax.reload();
    });
});
<?= $this->endSection() ?>
