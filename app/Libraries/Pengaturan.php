<?php

namespace App\Libraries;

/**
 * Pembaca tabel `pengaturan` (kunci-nilai) dengan memo per request —
 * pola yang sama dengan pembaca konteks penyelenggaraan.
 */
final class Pengaturan
{
    /** @var array<string, string|null> memo per request. */
    private static array $memo = [];

    public static function baca(string $kunci): ?string
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

    /** Versi tampilan halaman masuk yang aktif: '1' (bawaan) atau '2'. */
    public static function versiLogin(): string
    {
        return self::versiLoginDari(self::baca('versi_login'));
    }

    /** Putuskan versi dari nilai mentah pengaturan; selain '2' berarti '1'. */
    public static function versiLoginDari(?string $nilai): string
    {
        return $nilai === '2' ? '2' : '1';
    }
}
