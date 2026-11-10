<?php
/**
 * Pagination bersama halaman Coklit & Riwayat.
 *
 * Parameter: $basisUrl, $paramPagination, $halaman, $totalHal,
 * $total, dan $perHal. Menyediakan pilihan jumlah baris, rentang
 * baris yang tampil, tombol pertama/terakhir + jendela nomor, dan
 * lompat ke halaman tertentu.
 */
$paramPagination = $paramPagination ?? [];
$tautan = static function (int $h, int $per) use ($basisUrl, $paramPagination): string {
    $params = array_merge($paramPagination, ['hal' => $h, 'perhal' => $per]);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    return $basisUrl . '?' . http_build_query($params);
};
$awal  = $total === 0 ? 0 : (($halaman - 1) * $perHal) + 1;
$akhir = min($total, $halaman * $perHal);
$jendela = range(max(1, $halaman - 2), min($totalHal, $halaman + 2));
?>
<div class="flex flex-wrap items-center gap-3 mt-4">
    <form method="get" action="<?= esc($basisUrl, 'attr') ?>" class="flex items-center gap-2">
        <?php foreach ($paramPagination as $nama => $nilai) : ?>
            <?php if ($nilai !== '' && $nilai !== null) : ?>
                <input type="hidden" name="<?= esc($nama, 'attr') ?>" value="<?= esc($nilai, 'attr') ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <label class="text-sm" for="perhal-<?= esc(str_replace('/', '-', parse_url($basisUrl, PHP_URL_PATH) ?? 'halaman'), 'attr') ?>">Per halaman</label>
        <select id="perhal-<?= esc(str_replace('/', '-', parse_url($basisUrl, PHP_URL_PATH) ?? 'halaman'), 'attr') ?>" name="perhal" class="select select-bordered select-sm" onchange="this.form.submit()">
            <?php foreach ([10, 25, 50, 100] as $pilihan) : ?>
                <option value="<?= $pilihan ?>" <?= $perHal === $pilihan ? 'selected' : '' ?>><?= $pilihan ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <span class="text-sm opacity-70">Menampilkan <?= number_format($awal, 0, ',', '.') ?>–<?= number_format($akhir, 0, ',', '.') ?> dari <?= number_format($total, 0, ',', '.') ?></span>

    <div class="join ml-auto">
        <?php if ($halaman > 1) : ?>
            <a href="<?= $tautan(1, $perHal) ?>" class="btn btn-sm join-item" title="Halaman pertama">« Awal</a>
            <a href="<?= $tautan($halaman - 1, $perHal) ?>" class="btn btn-sm join-item" title="Halaman sebelumnya">‹</a>
        <?php else : ?>
            <button type="button" class="btn btn-sm join-item" disabled>« Awal</button>
            <button type="button" class="btn btn-sm join-item" disabled>‹</button>
        <?php endif; ?>

        <?php if (($jendela[0] ?? 1) > 1) : ?>
            <button type="button" class="btn btn-sm join-item" disabled>…</button>
        <?php endif; ?>
        <?php foreach ($jendela as $h) : ?>
            <a href="<?= $tautan($h, $perHal) ?>" class="btn btn-sm join-item <?= $h === $halaman ? 'btn-primary' : '' ?>"><?= $h ?></a>
        <?php endforeach; ?>
        <?php if (($jendela[count($jendela) - 1] ?? 1) < $totalHal) : ?>
            <button type="button" class="btn btn-sm join-item" disabled>…</button>
        <?php endif; ?>

        <?php if ($halaman < $totalHal) : ?>
            <a href="<?= $tautan($halaman + 1, $perHal) ?>" class="btn btn-sm join-item" title="Halaman berikutnya">›</a>
            <a href="<?= $tautan($totalHal, $perHal) ?>" class="btn btn-sm join-item" title="Halaman terakhir">Akhir »</a>
        <?php else : ?>
            <button type="button" class="btn btn-sm join-item" disabled>›</button>
            <button type="button" class="btn btn-sm join-item" disabled>Akhir »</button>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= esc($basisUrl, 'attr') ?>" class="flex items-center gap-2">
        <?php foreach ($paramPagination as $nama => $nilai) : ?>
            <?php if ($nilai !== '' && $nilai !== null) : ?>
                <input type="hidden" name="<?= esc($nama, 'attr') ?>" value="<?= esc($nilai, 'attr') ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="perhal" value="<?= $perHal ?>">
        <label class="text-sm" for="lompat-<?= esc(str_replace('/', '-', parse_url($basisUrl, PHP_URL_PATH) ?? 'halaman'), 'attr') ?>">Ke halaman</label>
        <input id="lompat-<?= esc(str_replace('/', '-', parse_url($basisUrl, PHP_URL_PATH) ?? 'halaman'), 'attr') ?>" type="number" name="hal" value="<?= $halaman ?>" min="1" max="<?= $totalHal ?>" class="input input-bordered input-sm w-24">
        <button type="submit" class="btn btn-sm">Pergi</button>
    </form>
    <span class="text-sm">dari <?= number_format($totalHal, 0, ',', '.') ?> halaman</span>
</div>
