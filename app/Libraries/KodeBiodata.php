<?php

namespace App\Libraries;

/**
 * Satu sumber definisi kode biodata pada formulir Coklit.
 *
 * Kode disabilitas mengikuti pedoman teknis penyusunan daftar
 * pemilih KPU 2024: 1 Fisik, 2 Intelektual, 3 Mental, 4 Sensorik
 * Wicara, 5 Sensorik Rungu, dan 6 Sensorik Netra. Nilai 0 berarti
 * bukan penyandang disabilitas. Kode EKTP pada data Sidalih adalah
 * S (sudah memiliki KTP-el) dan B (belum memiliki KTP-el).
 */
final class KodeBiodata
{
    /** @var array<string, string> */
    public const STATUS = [
        'B' => 'B — Belum Kawin',
        'S' => 'S — Sudah Kawin',
        'P' => 'P — Pernah Kawin',
    ];

    /** @var array<int, string> */
    public const DISABILITAS = [
        0 => '0 — Bukan Disabilitas',
        1 => '1 — Disabilitas Fisik',
        2 => '2 — Disabilitas Intelektual',
        3 => '3 — Disabilitas Mental',
        4 => '4 — Disabilitas Sensorik Wicara',
        5 => '5 — Disabilitas Sensorik Rungu',
        6 => '6 — Disabilitas Sensorik Netra',
    ];

    /** @var array<string, string> */
    public const EKTP = [
        'S' => 'S — Sudah Memiliki KTP-el',
        'B' => 'B — Belum Memiliki KTP-el',
    ];
}
