<?php

/**
 * Pembantu tampilan tabel HTMX: judul kolom yang bisa diurutkan.
 *
 * Mengembalikan tombol HTMX yang meminta ulang potongan tabel dengan
 * kunci urutan itu (arah dibalik bila kolom yang sama sedang aktif).
 *
 * @param array<string, mixed> $saring Parameter saringan saat ini.
 */
function judulUrut(string $label, string $kunci, string $urut, string $arah, string $urlTabel, string $target, array $saring): string
{
    $arahBaru = ($urut === $kunci && $arah === 'asc') ? 'desc' : 'asc';
    $params   = array_merge($saring, ['urut' => $kunci, 'arah' => $arahBaru, 'hal' => 1]);
    $params   = array_filter($params, static fn ($v) => $v !== '' && $v !== null);
    $url      = $urlTabel . '?' . http_build_query($params);
    $panah    = $urut === $kunci ? ($arah === 'asc' ? ' ▲' : ' ▼') : '';

    return '<button type="button" class="font-bold hover:text-primary" hx-get="' . esc($url, 'attr')
        . '" hx-target="' . esc($target, 'attr') . '" hx-swap="innerHTML">'
        . esc($label) . $panah . '</button>';
}
