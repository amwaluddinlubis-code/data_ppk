<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$fmt = static fn ($n) => number_format((int) $n, 0, ',', '.');
?>

<!-- Kepala periode -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="card-title text-base"><i class="fas fa-sync-alt text-primary"></i> PDPB — Pemutakhiran Data Pemilih Berkelanjutan</h2>
                <p class="text-sm opacity-70">Konteks aktif: <strong><?= esc($konteksNama) ?></strong> · Periode berjalan: <strong><?= esc($periode['nama']) ?></strong> (sejak <?= esc(date('d-m-Y', strtotime((string) $periode['mulai']))) ?>). Rekap dihitung dari perubahan tercatat pada periode ini (PKPU 1/2025 jo. PKPU 1/2026).</p>
            </div>
            <?php if ($adalahAdmin) : ?>
                <div class="flex flex-wrap gap-2">
                    <form method="post" action="<?= base_url('pdpb/nama') ?>" class="join">
                        <?= csrf_field() ?>
                        <input type="text" name="nama" value="<?= esc($periode['nama'], 'attr') ?>" maxlength="120" class="input input-bordered input-sm join-item w-56">
                        <button type="submit" class="btn btn-ghost btn-sm join-item">Ganti Nama</button>
                    </form>
                    <form method="post" action="<?= base_url('pdpb/tutup') ?>" onsubmit="return confirm('Tutup periode ini? Arsip rekap akan dibekukan dan periode baru dibuka.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-lock"></i> Tutup Periode &amp; Arsipkan</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-3">
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-[11px]">PEMILIH BARU</div><div class="stat-value text-2xl"><?= $fmt($total['baru']) ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-[11px]">MASUK TMS</div><div class="stat-value text-2xl"><?= $fmt($total['tms']) ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-[11px]">UBAH ELEMEN DATA</div><div class="stat-value text-2xl"><?= $fmt($total['ubah']) ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-[11px]">DIHAPUS</div><div class="stat-value text-2xl"><?= $fmt($total['hapus']) ?></div></div>
            <div class="stat bg-base-200 rounded-box p-3"><div class="stat-title text-[11px]">DIPULIHKAN</div><div class="stat-value text-2xl"><?= $fmt($total['pulih']) ?></div></div>
        </div>
        <p class="text-xs opacity-60 mt-2">Kelengkapan dokumen bukti: <?= $fmt($dokumenAda) ?> dari <?= $fmt($dokumenTotal) ?> perubahan periode ini mencantumkan dokumen (Pasal 17 ayat (4) PKPU 1/2025).</p>
    </div>
</div>

<!-- Rincian TMS per kode -->
<?php if ($tmsKode !== []) : ?>
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Rincian Masuk TMS Periode Ini (cakupan Anda)</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($tmsKode as $kode => $n) : ?>
                <span class="badge badge-error badge-outline gap-1"><?= esc(\App\Libraries\Keterangan::label((string) $kode, 'pdpb')) ?>: <strong><?= $fmt($n) ?></strong></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Rekap per desa -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2">Rekap Perubahan per Desa/Kelurahan</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm w-full">
                <thead>
                    <tr><th>Kecamatan</th><th>Desa/Kelurahan</th><th class="text-right">Pemilih Awal</th><th class="text-right">Baru</th><th class="text-right">TMS</th><th class="text-right">Ubah</th><th class="text-right">Hapus</th><th class="text-right">Pulih</th><th class="text-right">Pemilih Akhir</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($perKel as $baris) : $r = $baris['rekap']; ?>
                        <tr>
                            <td class="opacity-70"><?= esc($baris['kecamatan']) ?></td>
                            <td class="font-semibold"><?= esc($baris['nama']) ?></td>
                            <td class="text-right"><?= $fmt($baris['awal']) ?></td>
                            <td class="text-right"><?= $fmt($r['baru']) ?></td>
                            <td class="text-right"><?= $fmt($r['tms']) ?></td>
                            <td class="text-right"><?= $fmt($r['ubah']) ?></td>
                            <td class="text-right"><?= $fmt($r['hapus']) ?></td>
                            <td class="text-right"><?= $fmt($r['pulih']) ?></td>
                            <td class="text-right font-bold"><?= $fmt($baris['akhir']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs opacity-60 mt-2">Pemilih Akhir = jumlah MS hidup saat ini. Pemilih Awal = Pemilih Akhir dikurangi perubahan bersih periode (baru, masuk/keluar TMS, hapus, pulih) dari riwayat — angka pleno awal periode tersedia persis pada arsip setelah periode ditutup.</p>
    </div>
</div>

<!-- Cetak Model A-PDPB -->
<div class="card bg-base-100 shadow mb-4">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-print text-primary"></i> Cetak Model A-Daftar Pemilih-PDPB</h3>
        <form method="get" action="<?= base_url('pdpb/cetak') ?>" target="_blank" class="flex flex-wrap items-end gap-2">
            <label class="form-control">
                <span class="label"><span class="label-text font-semibold">DESA/KELURAHAN</span></span>
                <select name="kelurahan_id" class="select select-bordered select-sm w-72" required>
                    <option value="">— Pilih desa/kelurahan —</option>
                    <?php foreach ($daftarKelurahan as $dk) : ?>
                        <option value="<?= (int) $dk['id'] ?>"><?= esc($dk['kecamatan']) ?> — <?= esc($dk['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> CETAK</button>
        </form>
    </div>
</div>

<!-- Arsip periode lampau -->
<div class="card bg-base-100 shadow">
    <div class="card-body p-4">
        <h3 class="font-bold text-sm mb-2"><i class="fas fa-archive text-primary"></i> Arsip Rekap Periode Lampau</h3>
        <?php if ($arsip === []) : ?>
            <p class="text-sm opacity-60">Belum ada periode yang ditutup. Arsip muncul di sini setelah admin menutup periode — rekapnya dibekukan apa adanya seperti saat pleno.</p>
        <?php else : ?>
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead><tr><th>Periode</th><th>Ditutup</th><th class="text-right">Pemilih MS</th><th class="text-right">Baru</th><th class="text-right">TMS</th><th class="text-right">Ubah</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($arsip as $a) : $m = $a['metrik']; $rk = $m['rekap'] ?? []; ?>
                            <tr>
                                <td class="font-semibold"><?= esc($a['periode']['nama']) ?></td>
                                <td><?= esc(date('d-m-Y', strtotime((string) ($a['periode']['selesai'] ?? '')))) ?> oleh <?= esc($a['periode']['ditutup_oleh'] ?? '') ?></td>
                                <td class="text-right"><?= $fmt($m['ms'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['baru'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['tms'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['ubah'] ?? 0) ?></td>
                                <td><a class="btn btn-ghost btn-xs" href="<?= base_url('pdpb?arsip=' . (int) $a['periode']['id']) ?>">Rincian per Desa</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($detailArsip !== null) : ?>
            <h4 class="font-bold text-sm mt-4 mb-2">Rincian Arsip: <?= esc($detailArsip['periode']['nama']) ?></h4>
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead><tr><th>Kecamatan</th><th>Desa/Kelurahan</th><th class="text-right">MS</th><th class="text-right">L</th><th class="text-right">P</th><th class="text-right">KK</th><th class="text-right">Baru</th><th class="text-right">TMS</th><th class="text-right">Ubah</th></tr></thead>
                    <tbody>
                        <?php foreach ($detailArsip['baris'] as $b) : $m = json_decode((string) $b['metrik'], true) ?? []; $rk = $m['rekap'] ?? []; ?>
                            <tr>
                                <td class="opacity-70"><?= esc($b['kec_nama'] ?? '') ?></td>
                                <td class="font-semibold"><?= esc($b['kel_nama'] ?? '') ?></td>
                                <td class="text-right"><?= $fmt($m['ms'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($m['laki'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($m['perempuan'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($m['kk'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['baru'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['tms'] ?? 0) ?></td>
                                <td class="text-right"><?= $fmt($rk['ubah'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
