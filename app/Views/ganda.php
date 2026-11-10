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

<div class="card bg-base-100 shadow" x-data="{ memuat: false }"
     @htmx:before-request="memuat = true" @htmx:after-request="memuat = false">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-end gap-3 mb-3">
            <label class="form-control w-full sm:w-64">
                <span class="label"><span class="label-text font-semibold">KATEGORI KEGANDAAN</span></span>
                <select class="select select-bordered select-sm" id="kategori" name="kategori"
                        hx-get="<?= base_url('residu/tabelGanda') ?>" hx-target="#wadah-ganda" hx-swap="innerHTML"
                        hx-trigger="change" hx-include="[name='cari_ganda']">
                    <option value="">SEMUA KATEGORI</option>
                    <option value="kecamatan">ANTAR KECAMATAN</option>
                    <option value="desa">ANTAR DESA</option>
                </select>
            </label>
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">CARI</span></span>
                <input type="text" class="input input-bordered input-sm" name="cari_ganda" placeholder="Nama / NIK / NKK…"
                       hx-get="<?= base_url('residu/tabelGanda') ?>" hx-target="#wadah-ganda" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms" hx-include="[name='kategori']">
            </label>
            <span class="badge badge-info badge-sm hidden" x-bind:class="{ 'hidden': !memuat }">
                <span class="loading loading-spinner loading-xs"></span> Memuat…
            </span>
            <div class="flex gap-2 ml-auto">
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporGanda('csv')">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporGanda('xlsx')">
                    <i class="fas fa-file-excel"></i> EXCEL
                </button>
            </div>
        </div>
        <div id="wadah-ganda">
            <?= view('residu/_tabel_ganda', $tabelGandaAwal) ?>
        </div>
        <p class="text-xs opacity-60 mt-2">Analisis ini hanya memperhatikan <strong>NIK</strong> pada seluruh data coklit: setiap NIK yang tercatat di lebih dari satu desa/kelurahan atau kecamatan dikelompokkan di sini, dan <strong>semua baris</strong> NIK itu ditampilkan (termasuk baris di wilayah seberang) agar mudah dikoordinasikan. Kegandaan di dalam desa yang sama tidak ditampilkan karena merupakan pola normal coklit (baris tersaring + baris baru orang yang sama). Kelompok yang tampil dibatasi pada yang menyentuh wilayah Anda.</p>
    </div>
</div>

<div class="card bg-base-100 shadow border-2 border-warning mt-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <div class="text-sm font-bold">PERINGATAN: JENIS KELAMIN TIDAK COCOK DENGAN KODE NIK</div>
            <div class="badge badge-warning badge-lg"><?= number_format($jmlPeringatan, 0, ',', '.') ?> pemilih</div>
            <div class="flex gap-2 ml-auto">
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporPeringatan('csv')">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="eksporPeringatan('xlsx')">
                    <i class="fas fa-file-excel"></i> EXCEL
                </button>
            </div>
        </div>
        <p class="text-xs opacity-70 mb-3">Jenis kelamin yang tercatat berbeda dengan kode pada NIK — pada NIK, perempuan dikodekan sebagai tanggal lahir ditambah 40. Ini <strong>hanya peringatan</strong>: perbedaan seperti ini sering berasal dari data Dukcapil dan belum tentu datanya salah. Gunakan sebagai bahan klarifikasi, dan jangan mengubah data hanya berdasarkan peringatan ini.</p>
        <div class="flex flex-wrap items-end gap-3 mb-3">
            <label class="form-control w-full sm:w-56">
                <span class="label"><span class="label-text font-semibold">CARI</span></span>
                <input type="text" class="input input-bordered input-sm" name="cari_peringatan" placeholder="Nama / NIK / NKK…"
                       hx-get="<?= base_url('residu/tabelPeringatan') ?>" hx-target="#wadah-peringatan" hx-swap="innerHTML"
                       hx-trigger="input changed delay:500ms">
            </label>
        </div>
        <div id="wadah-peringatan">
            <?= view('residu/_tabel_peringatan', $tabelPeringatanAwal) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
function eksporGanda(format) {
    var kat = document.getElementById('kategori').value || '';
    window.location = "<?= base_url('ekspor/ganda') ?>" + '?kategori=' + encodeURIComponent(kat) + '&format=' + format;
}
function eksporPeringatan(format) {
    window.location = "<?= base_url('ekspor/peringatan') ?>" + '?format=' + format;
}
// Kedua tabel halaman ini memakai HTMX (partial server-side).
<?= $this->endSection() ?>
