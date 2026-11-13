<?php

namespace App\Libraries;

/**
 * Konteks penyelenggaraan aktif (Fase A, analisis PKPU 11 Nov 2026).
 *
 * Satu basis data kabupaten melayani tiga rezim yang berganti menurut
 * masa: Pemilu (PKPU 7/2022 jo. 7/2023), Pilkada (PKPU 7/2024), dan
 * masa antar-penyelenggaraan PDPB (PKPU 1/2025 jo. 1/2026). Rezim
 * aktif menentukan label kode keterangan 5/8 (lihat Keterangan) dan
 * batas maksimal pemilih per TPS. Nilai disimpan di tabel
 * `pengaturan`; bawaan 'pdpb' karena masa berjalan adalah masa PDPB.
 */
final class Konteks
{
    /** @var array<string, string> jenis => label tampilan. */
    public const JENIS = [
        'pemilu'          => 'Pemilihan Umum (Pemilu)',
        'pilkada'         => 'Pemilihan Kepala Daerah (Pilkada)',
        'pdpb'            => 'Pemutakhiran Data Pemilih Berkelanjutan (PDPB)',
        'pemilu_nasional' => 'Pemilu Nasional (DPR, DPD, Presiden)',
        'pemilu_daerah'   => 'Pemilu Daerah (DPRD & Kepala Daerah)',
    ];

    /** @var array<string, string|null> memo per request. */
    private static array $memo = [];

    private static function baca(string $kunci): ?string
    {
        if (! array_key_exists($kunci, self::$memo)) {
            try {
                $baris = db_connect()->table('pengaturan')->select('nilai')
                    ->where('kunci', $kunci)->get()->getRowArray();
                self::$memo[$kunci] = $baris !== null ? (string) $baris['nilai'] : null;
            } catch (\Throwable) {
                self::$memo[$kunci] = null;
            }
        }

        return self::$memo[$kunci];
    }

    /** Jenis rezim aktif: pemilu|pilkada|pdpb (bawaan pdpb). */
    public static function jenis(): string
    {
        $jenis = (string) (self::baca('konteks_jenis') ?? 'pdpb');

        return array_key_exists($jenis, self::JENIS) ? $jenis : 'pdpb';
    }

    /** Nama penyelenggaraan, mis. "Pemilu 2029" atau "PDPB 2026". */
    public static function nama(): string
    {
        $nama = trim((string) (self::baca('konteks_nama') ?? ''));

        return $nama !== '' ? $nama : self::JENIS[self::jenis()];
    }

    public static function labelJenis(): string
    {
        return self::JENIS[self::jenis()];
    }

    /** Tanggal pemungutan (acuan umur & konteks tahapan), bila diatur. */
    public static function tanggalPemungutan(): ?string
    {
        $tgl = self::baca('tanggal_pemungutan');

        return ($tgl !== null && $tgl !== '') ? $tgl : null;
    }

    /**
     * Batas maksimal pemilih per TPS menurut rezim:
     * Pemilu 300 (PKPU 7/2022 Pasal 15 ayat (3)); Pilkada 600
     * (PKPU 7/2024 Pasal 10 ayat (2)); PDPB tidak membentuk TPS (null).
     */
    public static function batasTps(): ?int
    {
        return match (self::jenis()) {
            'pemilu', 'pemilu_nasional' => 300,
            'pilkada', 'pemilu_daerah'  => 600,
            default                     => null,
        };
    }
}
