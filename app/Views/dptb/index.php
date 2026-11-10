<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h2 class="card-title text-base"><i class="fas fa-exchange-alt text-primary"></i> Register Pindah Memilih (DPTb) — <?= esc($konteksNama) ?></h2>
        <p class="text-sm">Register ini adalah jalur <strong>formal</strong> pengajuan pindah memilih dan dasar cetak Model A-Surat Pindah Memilih. Berbeda dari menu <em>Pindah TPS</em> yang menampilkan perpindahan terdeteksi dari data coklit.</p>
        <form method="get" action="<?= base_url('dptb') ?>" class="flex flex-wrap items-end gap-2 mt-2">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">CARI PEMILIH (NIK / NAMA)</span></span>
                <input type="text" name="q" value="<?= esc($q, 'attr') ?>" class="input input-bordered input-sm w-72" placeholder="Ketik NIK atau nama pemilih MS">
            </label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> CARI</button>
        </form>

        <?php if ($ditemukan !== null) : ?>
            <?php if ($ditemukan === []) : ?>
                <div class="alert alert-warning mt-3 py-2 text-sm">Tidak ada pemilih MS yang cocok dalam cakupan Anda.</div>
            <?php else : ?>
                <div class="overflow-x-auto mt-3">
                    <table class="table table-sm">
                        <thead><tr><th>Nama</th><th>NIK</th><th>Desa Asal</th><th>TPS</th><th>Tujuan & Alasan</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($ditemukan as $d) : ?>
                                <tr>
                                    <td class="font-semibold"><?= esc($d['NAMA']) ?></td>
                                    <td><?= esc($d['NIK']) ?></td>
                                    <td><?= esc($d['desa']) ?>, <?= esc($d['kecamatan']) ?></td>
                                    <td><?= (int) $d['TPS'] ?></td>
                                    <td>
                                        <form method="post" action="<?= base_url('dptb/simpan') ?>" class="flex flex-wrap gap-1 items-center">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="mhc_id" value="<?= (int) $d['ID'] ?>">
                                            <select name="tujuan_kelurahan_id" class="select select-bordered select-xs w-52">
                                                <option value="">— Luar kabupaten (isi teks) —</option>
                                                <?php foreach ($daftarKelurahan as $dk) : ?>
                                                    <option value="<?= (int) $dk['id'] ?>"><?= esc($dk['kecamatan']) ?> — <?= esc($dk['nama']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" name="tujuan_tps" min="1" class="input input-bordered input-xs w-20" placeholder="TPS">
                                            <input type="text" name="tujuan_teks" class="input input-bordered input-xs w-52" placeholder="Wilayah tujuan bila luar kabupaten">
                                            <select name="alasan" class="select select-bordered select-xs w-64" required>
                                                <?php foreach ($alasan as $a) : ?>
                                                    <option value="<?= esc($a, 'attr') ?>"><?= esc($a) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-xs">DAFTARKAN</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a class="btn btn-outline btn-xs" target="_blank" href="<?= base_url('dptb/cetak-tanda-bukti?nik=' . urlencode($d['NIK'])) ?>"><i class="fas fa-id-card"></i> Tanda Bukti</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-list text-primary"></i> Pengajuan Terdaftar (terbaru dulu)</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Nama</th><th>Asal</th><th>Tujuan</th><th>Alasan</th><th>Cetak</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($daftar as $p) : ?>
                        <tr>
                            <td class="font-semibold"><?= esc($p['nama']) ?><br><span class="text-xs opacity-60"><?= esc($p['nik']) ?></span></td>
                            <td><?= esc($p['asal_desa']) ?> · TPS <?= (int) $p['asal_tps'] ?></td>
                            <td><?= $p['tujuan_desa'] !== null ? esc($p['tujuan_desa']) . ', ' . esc($p['tujuan_kecamatan'] ?? '') . ' · TPS ' . (int) $p['tujuan_tps'] : esc($p['tujuan_teks'] ?? '') ?></td>
                            <td class="text-xs"><?= esc($p['alasan']) ?></td>
                            <td>
                                <a class="btn btn-outline btn-xs" target="_blank" href="<?= base_url('dptb/cetak-surat?id=' . (int) $p['id']) ?>">P15 · Surat Pindah</a>
                                <?php if ($p['tujuan_kelurahan_id'] !== null) : ?>
                                    <a class="btn btn-outline btn-xs" target="_blank" href="<?= base_url('dptb/cetak-daftar-pindahan?kelurahan_id=' . (int) $p['tujuan_kelurahan_id'] . '&tps=' . (int) $p['tujuan_tps']) ?>">P16 · Daftar TPS Tujuan</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= base_url('dptb/hapus') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-error btn-xs" data-konfirmasi="Hapus pengajuan pindah memilih <?= esc($p['nama'], 'attr') ?>?">HAPUS</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($daftar === []) : ?>
                        <tr><td colspan="6" class="text-center opacity-60">Belum ada pengajuan pindah memilih.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <form method="get" target="_blank" class="flex flex-wrap items-end gap-2 mt-3">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">DESA (PPS)</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm w-64">
                    <option value="">— Pilih desa —</option>
                    <?php foreach ($daftarKelurahan as $dk) : ?>
                        <option value="<?= (int) $dk['id'] ?>"><?= esc($dk['kecamatan']) ?> — <?= esc($dk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <input type="number" name="tps" min="1" class="input input-bordered input-sm w-24">
            </label>
            <button type="submit" formaction="<?= base_url('dptb/cetak-rekap-pindahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='pps'">P17 · Rekap Pindahan PPS</button>
            <button type="submit" formaction="<?= base_url('dptb/cetak-daftar-hadir-dpk') ?>" class="btn btn-outline btn-sm">Daftar Hadir DPK per TPS</button>
            <input type="hidden" name="jenjang" value="kabko">
            <button type="submit" formaction="<?= base_url('dptb/cetak-rekap-pindahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='ppk'">P17 · Rekap PPK</button>
            <button type="submit" formaction="<?= base_url('dptb/cetak-rekap-pindahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='kabko'">P17 · Rekap KabKo</button>
        </form>
    </div>
</div>

<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-map-marker-alt text-primary"></i> TPS Lokasi Khusus</h3>
        <form method="post" action="<?= base_url('dptb/loksus/simpan') ?>" class="flex flex-wrap items-end gap-2 mb-3">
            <?= csrf_field() ?>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">DESA/KELURAHAN</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm w-64" required>
                    <?php foreach ($daftarKelurahan as $dk) : ?>
                        <option value="<?= (int) $dk['id'] ?>"><?= esc($dk['kecamatan']) ?> — <?= esc($dk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TPS</span></span>
                <input type="number" name="tps" min="1" class="input input-bordered input-sm w-24" required>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">JENIS</span></span>
                <select name="jenis" class="select select-bordered select-sm w-52">
                    <option>Lapas/Rutan</option><option>Rumah Sakit</option><option>Panti Sosial</option>
                    <option>Panti Rehabilitasi</option><option>Pesantren</option><option>Kampus</option>
                    <option>Perusahaan/Perkebunan</option><option>Lainnya</option>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">NAMA LOKASI</span></span>
                <input type="text" name="nama_lokasi" maxlength="160" class="input input-bordered input-sm w-64" required>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">KETERANGAN</span></span>
                <input type="text" name="keterangan" maxlength="255" class="input input-bordered input-sm w-64">
            </label>
            <button type="submit" class="btn btn-primary btn-sm">TAMBAH LOKASI KHUSUS</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Lokasi</th><th>Jenis</th><th>Wilayah</th><th>TPS</th><th>Cetak</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($loksus as $l) : ?>
                        <tr>
                            <td class="font-semibold"><?= esc($l['nama_lokasi']) ?></td>
                            <td><?= esc($l['jenis']) ?></td>
                            <td><?= esc($l['desa']) ?>, <?= esc($l['kecamatan']) ?></td>
                            <td><?= (int) $l['tps'] ?></td>
                            <td><a class="btn btn-outline btn-xs" target="_blank" href="<?= base_url('dptb/cetak-loksus?id=' . (int) $l['id']) ?>">Daftar Pemilih Loksus</a></td>
                            <td>
                                <form method="post" action="<?= base_url('dptb/loksus/hapus') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                    <button type="submit" class="btn btn-error btn-xs" data-konfirmasi="Hapus lokasi khusus <?= esc($l['nama_lokasi'], 'attr') ?>?">HAPUS</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($loksus === []) : ?>
                        <tr><td colspan="6" class="text-center opacity-60">Belum ada TPS lokasi khusus terdaftar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
