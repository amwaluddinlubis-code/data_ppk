<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$namaTahap = ['dps' => 'DPS', 'dpshp' => 'DPSHP', 'dpt' => 'DPT'];
$judulPanjang = ['dps' => 'Daftar Pemilih Sementara', 'dpshp' => 'Daftar Pemilih Sementara Hasil Perbaikan', 'dpt' => 'Daftar Pemilih Tetap'];
?>

<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-center gap-2 mb-2">
            <h2 class="card-title text-base mb-0"><i class="fas fa-layer-group text-primary"></i> Tahapan Penetapan — <?= esc($namaKonteks) ?></h2>
            <?php if ($jenis === 'pdpb') : ?>
                <span class="badge badge-warning">Konteks aktif PDPB</span>
            <?php endif; ?>
        </div>
        <?php if ($jenis === 'pdpb') : ?>
            <p class="text-sm">Tahapan DPS → DPSHP → DPT berlaku untuk konteks <strong>Pemilu/Pilkada</strong>. Konteks aktif saat ini PDPB — ubah dulu di menu <em>Konteks Penyelenggaraan</em> (admin) untuk membuka tahap. Tahap dari penyelenggaraan lain tetap dapat dilihat dan dicetak di bawah.</p>
        <?php else : ?>
            <p class="text-sm">Setiap tahap yang <strong>ditetapkan</strong> membekukan potret daftar pemilih persis seperti saat pleno — cetakan tahap selalu membaca potret itu, bukan data yang terus berubah. Urutan tahap: DPS, lalu DPSHP, lalu DPT.</p>
        <?php endif; ?>

        <!-- Pipa tahap penyelenggaraan aktif -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
            <?php foreach (['dps', 'dpshp', 'dpt'] as $kode) : $t = $pasanganAktif[$kode] ?? null; ?>
                <div class="border rounded-box p-3 <?= $t !== null && $t['status'] === 'berjalan' ? 'border-warning' : 'border-base-300' ?>">
                    <div class="flex items-center justify-between">
                        <span class="font-bold"><?= $namaTahap[$kode] ?></span>
                        <?php if ($t === null) : ?>
                            <span class="badge badge-ghost">belum dibuka</span>
                        <?php elseif ($t['status'] === 'berjalan') : ?>
                            <span class="badge badge-warning">berjalan</span>
                        <?php else : ?>
                            <span class="badge badge-success">ditetapkan</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs opacity-70"><?= $judulPanjang[$kode] ?></div>
                    <?php if ($t !== null && $t['status'] === 'ditutup') : ?>
                        <div class="text-xs mt-1">Ditetapkan <?= esc(date('d-m-Y H:i', strtotime((string) $t['ditutup_at']))) ?><?= ! empty($t['ba_nomor']) ? ' · BA ' . esc($t['ba_nomor']) : '' ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($adalahAdmin && $jenis !== 'pdpb') : ?>
            <div class="flex flex-wrap gap-2 mt-3">
                <?php if ($tahapBerikutnya !== null) : ?>
                    <form method="post" action="<?= base_url('tahapan/buka') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-play"></i> BUKA TAHAP <?= $namaTahap[$tahapBerikutnya] ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($berjalan !== null) : ?>
                <form method="post" action="<?= base_url('tahapan/tetapkan') ?>" class="mt-4 border border-warning rounded-box p-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tahap_id" value="<?= (int) $berjalan['id'] ?>">
                    <div class="font-semibold text-sm mb-1"><i class="fas fa-gavel text-warning"></i> Tetapkan <?= $namaTahap[$berjalan['tahap']] ?> (Pleno Penetapan)</div>
                    <p class="text-xs opacity-70 mb-2">Menetapkan akan membekukan potret seluruh pemilih MS saat ini (<?= esc($namaKonteks) ?>). Isi metadata berita acara kabupaten di bawah; sesudah ditetapkan, tahap ini terkunci.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input type="text" name="ba_nomor" maxlength="80" class="input input-bordered input-sm" placeholder="Nomor Berita Acara">
                        <input type="date" name="ba_tanggal" class="input input-bordered input-sm">
                        <input type="text" name="ba_tempat" maxlength="120" class="input input-bordered input-sm" placeholder="Tempat pleno">
                        <input type="text" name="ba_ketua" maxlength="120" class="input input-bordered input-sm" placeholder="Nama Ketua KPU Kabupaten/Kota">
                        <textarea name="ba_anggota" rows="2" class="textarea textarea-bordered textarea-sm md:col-span-2" placeholder="Nama anggota (satu per baris)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm mt-2" data-konfirmasi="Tetapkan tahap <?= $namaTahap[$berjalan['tahap']] ?> sekarang? Potret daftar pemilih akan dibekukan dan tidak dapat diubah."><i class="fas fa-lock"></i> TETAPKAN & BEKUKAN POTRET</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($potret !== []) : ?>
    <div class="card bg-base-100 shadow mb-4">
        <div class="card-body p-4">
            <h3 class="font-bold text-sm mb-2"><i class="fas fa-chart-column text-primary"></i> Potret Antar Tahap — <?= esc($namaKonteks) ?></h3>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Kecamatan</th><th class="text-right">DPS</th><th class="text-right">DPSHP</th><th class="text-right">DPT</th><th class="text-right">Selisih Akhir</th></tr></thead>
                    <tbody>
                        <?php foreach ($potret as $kec => $per) : ?>
                            <tr>
                                <td class="font-semibold"><?= esc($kec) ?></td>
                                <?php foreach (['dps', 'dpshp', 'dpt'] as $kode) : ?>
                                    <td class="text-right"><?= isset($per[$kode]) ? number_format($per[$kode], 0, ',', '.') : '—' ?></td>
                                <?php endforeach; ?>
                                <td class="text-right"><?= isset($per['dpt'], $per['dps']) ? number_format($per['dpt'] - $per['dps'], 0, ',', '.') : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td>TOTAL KABUPATEN</td>
                            <?php foreach (['dps', 'dpshp', 'dpt'] as $kode) : ?>
                                <td class="text-right"><?= isset($potretTotal[$kode]) ? number_format($potretTotal[$kode], 0, ',', '.') : '—' ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><?= isset($potretTotal['dpt'], $potretTotal['dps']) ? number_format($potretTotal['dpt'] - $potretTotal['dps'], 0, ',', '.') : '—' ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Pusat cetak Kelompok 2 -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-print text-primary"></i> Cetak Formulir Tahapan (Kelompok 2)</h3>
        <form method="get" target="_blank" class="flex flex-wrap items-end gap-2">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TAHAP</span></span>
                <select name="tahap_id" class="select select-bordered select-sm w-80">
                    <?php foreach ($semua as $t) : ?>
                        <option value="<?= (int) $t['id'] ?>"><?= strtoupper($t['tahap']) ?> — <?= esc($t['konteks_nama']) ?> (<?= $t['status'] === 'ditutup' ? 'ditetapkan' : 'berjalan' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">DESA/KELURAHAN</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm w-64">
                    <option value="">— Pilih desa/kelurahan —</option>
                    <?php foreach ($daftarKelurahan as $dk) : ?>
                        <option value="<?= (int) $dk['id'] ?>"><?= esc($dk['kecamatan']) ?> — <?= esc($dk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TPS (UNTUK DAFTAR)</span></span>
                <input type="number" name="tps" min="1" class="input input-bordered input-sm w-24" placeholder="No TPS">
            </label>
            <div class="flex flex-wrap gap-2 w-full mt-1">
                <button type="submit" formaction="<?= base_url('tahapan/cetak-daftar') ?>" class="btn btn-outline btn-sm">P1 · Daftar Pemilih per TPS</button>
                <button type="submit" formaction="<?= base_url('tahapan/cetak-perubahan') ?>" class="btn btn-outline btn-sm">P5 · Daftar Perubahan per Desa</button>
                <button type="submit" formaction="<?= base_url('tahapan/cetak-laporan-coklit') ?>" class="btn btn-outline btn-sm">P3 · Laporan Hasil Coklit</button>
                <button type="submit" formaction="<?= base_url('tahapan/cetak-potensial') ?>" class="btn btn-outline btn-sm">P2 · Daftar Potensial per TPS</button>
            </div>
            <div class="flex flex-wrap gap-2 w-full">
                <button type="submit" formaction="<?= base_url('tahapan/cetak-rekap-perubahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='pps'">P6 · Rekap Perubahan PPS</button>
                <button type="submit" formaction="<?= base_url('tahapan/ba') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='pps'">BA PPS</button>
            </div>
            <input type="hidden" name="jenjang" value="kabko">
            <p class="text-xs opacity-60">Rekap perubahan & BA jenjang PPK/KabKo memakai pilihan kecamatan di bawah dan tahap terpilih. P1 tahap berjalan dicetak sebagai pratinjau dari data hidup; sesudah ditetapkan, dari potret beku.</p>
        </form>
        <form method="get" target="_blank" class="flex flex-wrap items-end gap-2 mt-3">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">TAHAP</span></span>
                <select name="tahap_id" class="select select-bordered select-sm w-80">
                    <?php foreach ($semua as $t) : ?>
                        <option value="<?= (int) $t['id'] ?>"><?= strtoupper($t['tahap']) ?> — <?= esc($t['konteks_nama']) ?> (<?= $t['status'] === 'ditutup' ? 'ditetapkan' : 'berjalan' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">KECAMATAN (UNTUK PPK)</span></span>
                <select name="kecamatan_id" class="select select-bordered select-sm w-64">
                    <option value="">— Kabupaten (semua) —</option>
                    <?php
                    $kecUnik = [];
                    foreach ($daftarKelurahan as $dk) {
                        $kecUnik[$dk['kecamatan']] = true;
                    }
                    $kecIds = [];
                    foreach ((db_connect())->table('kecamatan')->get()->getResultArray() as $k) {
                        $kecIds[$k['nama']] = (int) $k['id'];
                    }
                    foreach (array_keys($kecUnik) as $namaKec) : ?>
                        <option value="<?= $kecIds[$namaKec] ?? 0 ?>"><?= esc($namaKec) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="flex flex-wrap gap-2">
                <button type="submit" formaction="<?= base_url('tahapan/cetak-rekap-perubahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='ppk'">P10 · Rekap Perubahan PPK</button>
                <button type="submit" formaction="<?= base_url('tahapan/cetak-rekap-perubahan') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='kabko'">P11 · Rekap Perubahan KabKo</button>
                <button type="submit" formaction="<?= base_url('tahapan/cetak-rekap-kabko') ?>" class="btn btn-outline btn-sm">P13 · Rekap KabKo</button>
                <button type="submit" formaction="<?= base_url('tahapan/ba') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='ppk'">BA PPK</button>
                <button type="submit" formaction="<?= base_url('tahapan/ba') ?>" class="btn btn-outline btn-sm" onclick="this.form.querySelector('[name=jenjang]').value='kabko'">P14 · BA Kabupaten</button>
            </div>
            <input type="hidden" name="jenjang" value="kabko">
        </form>
    </div>
</div>

<?php if ($semua !== []) : ?>
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <h3 class="font-bold text-sm mb-2"><i class="fas fa-archive text-primary"></i> Semua Tahap Tercatat</h3>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Penyelenggaraan</th><th>Tahap</th><th>Status</th><th>Dibuka</th><th>Ditetapkan</th><th>Nomor BA</th></tr></thead>
                    <tbody>
                        <?php foreach ($semua as $t) : ?>
                            <tr>
                                <td><?= esc($t['konteks_nama']) ?> <span class="badge badge-ghost badge-xs"><?= esc($t['konteks_jenis']) ?></span></td>
                                <td class="font-semibold"><?= strtoupper($t['tahap']) ?></td>
                                <td><?= $t['status'] === 'ditutup' ? '<span class="badge badge-success badge-sm">ditetapkan</span>' : '<span class="badge badge-warning badge-sm">berjalan</span>' ?></td>
                                <td><?= esc(date('d-m-Y', strtotime((string) $t['created_at']))) ?></td>
                                <td><?= $t['ditutup_at'] !== null ? esc(date('d-m-Y H:i', strtotime((string) $t['ditutup_at']))) : '—' ?></td>
                                <td><?= esc($t['ba_nomor'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
