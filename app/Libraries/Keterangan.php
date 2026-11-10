<?php

namespace App\Libraries;

/**
 * Satu-satunya sumber definisi kode keterangan (KET) pemilih.
 *
 * Bangun ulang 2026: sebelumnya daftar kode ini disalin dengan tangan
 * ke belasan tempat (controller, view, migration) — perbedaan kecil
 * di antaranya adalah akar temuan audit T2 (kode 4/5 tidak terdaftar
 * di mana pun sehingga 24 pemilih menggantung). Sekarang semua tempat
 * wajib mengambil dari kelas ini.
 *
 * Standar kode coklit: MS = 0 (sesuai), U (ubah), B (baru);
 * TMS = 1 Meninggal, 2 Ganda, 3 Di Bawah Umur, 4 Pindah Domisili,
 * 5 WNA, 6 TNI, 7 Polri, 8 Salah Penempatan TPS.
 *
 * SADAR REZIM (Fase A, analisis PKPU 11 Nov 2026): legenda resmi
 * kode 5 dan 8 berbeda antarformulir — Pemilu & Pilkada (Model A,
 * PKPU 7/2022): 5 = Tidak Dikenal, 8 = Salah Penempatan TPS;
 * PDPB (PKPU 1/2025): 5 = WNA, 8 = Hak Politik Dicabut.
 * KODE tidak pernah berubah; hanya LABEL mengikuti konteks aktif
 * (lihat Konteks) atau rezim yang diminta terus terang pemanggil.
 */
final class Keterangan
{
    /** @var list<string> Kode pemilih memenuhi syarat. */
    public const MS = ['0', 'U', 'B'];

    /** @var list<string> Kode pemilih tidak memenuhi syarat. */
    public const TMS = ['1', '2', '3', '4', '5', '6', '7', '8'];

    /** @var array<string, string> */
    private const LABEL = [
        '0' => 'Pemilih Sesuai',
        'U' => 'Pemilih Ubah',
        'B' => 'Pemilih Baru',
        '1' => 'Meninggal',
        '2' => 'Ganda',
        '3' => 'Di Bawah Umur',
        '4' => 'Pindah Domisili',
        '5' => 'WNA',
        '6' => 'TNI',
        '7' => 'Polri',
        '8' => 'Salah Penempatan TPS',
    ];

    /** Label pengganti khusus rezim PDPB (formulir PKPU 1/2025). */
    private const LABEL_PDPB = [
        '5' => 'WNA',
        '8' => 'Hak Politik Dicabut',
    ];

    /** Label pengganti khusus rezim penyelenggaraan (Model A PKPU 7/2022). */
    private const LABEL_PENYELENGGARAAN = [
        '5' => 'Tidak Dikenal',
        '8' => 'Salah Penempatan TPS',
    ];

    /** Label mentah satu kode menurut rezim (bawaan: konteks aktif). */
    private static function mentah(string $kode, ?string $jenis): string
    {
        $jenis ??= Konteks::jenis();
        $peta = $jenis === 'pdpb' ? self::LABEL_PDPB : self::LABEL_PENYELENGGARAAN;

        return $peta[$kode] ?? self::LABEL[$kode] ?? $kode;
    }

    /** @return array<string, string> kode => 'kode — Label', untuk pilihan formulir & sel tabel. */
    public static function daftar(?string $jenis = null): array
    {
        $hasil = [];
        foreach (self::LABEL as $kode => $label) {
            $hasil[$kode] = $kode . ' — ' . self::mentah($kode, $jenis);
        }

        return $hasil;
    }

    /** Label lengkap satu kode ('4 — Pindah Domisili'); kode itu sendiri bila tak dikenal. */
    public static function label(string $kode, ?string $jenis = null): string
    {
        return isset(self::LABEL[$kode]) ? $kode . ' — ' . self::mentah($kode, $jenis) : $kode;
    }

    /** Nama keterangan tanpa kode ('Pindah Domisili'). */
    public static function nama(string $kode, ?string $jenis = null): string
    {
        return isset(self::LABEL[$kode]) ? self::mentah($kode, $jenis) : $kode;
    }

    public static function isMs(string $kode): bool
    {
        return in_array($kode, self::MS, true);
    }

    public static function isTms(string $kode): bool
    {
        return in_array($kode, self::TMS, true);
    }

    /** Untuk klausa SQL mentah: '0','U','B' */
    public static function sqlMs(): string
    {
        return "'" . implode("','", self::MS) . "'";
    }

    /** Untuk klausa SQL mentah: '1','2','3','4','5','6','7','8' */
    public static function sqlTms(): string
    {
        return "'" . implode("','", self::TMS) . "'";
    }
}
