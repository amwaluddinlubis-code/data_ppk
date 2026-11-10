<?php
/**
 * Paginasi bersama untuk tabel-tabel HTMX (sejak konversi HTMX).
 *
 * Variabel: $urlTabel, $target, $saring (parameter saringan saat ini),
 * $total, $halaman, $totalHal, $perHal, $bagian ('atas' = info +
 * pilihan per halaman, 'bawah' = tombol halaman).
 */
$awal   = $total === 0 ? 0 : (($halaman - 1) * $perHal) + 1;
$akhir  = min($total, $halaman * $perHal);
$tautan = static function (int $h) use ($urlTabel, $saring, $perHal): string {
    $params = array_merge($saring, ['perhal' => $perHal, 'hal' => $h]);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);

    return $urlTabel . '?' . http_build_query($params);
};
$jendela = range(max(1, $halaman - 2), min($totalHal, $halaman + 2));
?>
<?php if (($bagian ?? 'atas') === 'atas') : ?>
<div class="flex flex-wrap items-center gap-3 mb-2">
    <span class="text-sm opacity-70">Menampilkan <?= number_format($awal, 0, ',', '.') ?>–<?= number_format($akhir, 0, ',', '.') ?> dari <?= number_format($total, 0, ',', '.') ?> baris</span>
    <label class="text-sm ml-auto flex items-center gap-2">Per halaman
        <select name="perhal" class="select select-bordered select-sm"
                hx-get="<?= $urlTabel ?>" hx-target="<?= $target ?>" hx-swap="innerHTML"
                hx-vals='<?= esc(json_encode(array_merge($saring, ['hal' => 1])), 'attr') ?>'>
            <?php foreach ([10, 25, 50, 100] as $pilihan) : ?>
                <option value="<?= $pilihan ?>" <?= $perHal === $pilihan ? 'selected' : '' ?>><?= $pilihan ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</div>
<?php else : ?>
<div class="join mt-3">
    <?php if ($halaman > 1) : ?>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan(1) ?>" hx-target="<?= $target ?>" hx-swap="innerHTML">« Awal</button>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($halaman - 1) ?>" hx-target="<?= $target ?>" hx-swap="innerHTML">‹</button>
    <?php else : ?>
        <button type="button" class="btn btn-sm join-item" disabled>« Awal</button>
        <button type="button" class="btn btn-sm join-item" disabled>‹</button>
    <?php endif; ?>
    <?php foreach ($jendela as $h) : ?>
        <button type="button" class="btn btn-sm join-item <?= $h === $halaman ? 'btn-primary' : '' ?>" hx-get="<?= $tautan($h) ?>" hx-target="<?= $target ?>" hx-swap="innerHTML"><?= $h ?></button>
    <?php endforeach; ?>
    <?php if ($halaman < $totalHal) : ?>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($halaman + 1) ?>" hx-target="<?= $target ?>" hx-swap="innerHTML">›</button>
        <button type="button" class="btn btn-sm join-item" hx-get="<?= $tautan($totalHal) ?>" hx-target="<?= $target ?>" hx-swap="innerHTML">Akhir »</button>
    <?php else : ?>
        <button type="button" class="btn btn-sm join-item" disabled>›</button>
        <button type="button" class="btn btn-sm join-item" disabled>Akhir »</button>
    <?php endif; ?>
</div>
<?php endif; ?>
