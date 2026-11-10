<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

/**
 * Cakupan wilayah pengguna & saringan kueri (konsep kabupaten).
 *
 * Satu basis data = satu kabupaten. Kecamatan adalah saringan wilayah:
 * pengguna PPK/PPS terkunci ke cakupan akunnya (users.kecamatan/desa);
 * Admin Kabupaten bebas memilih saringan lewat sesi `kec_filter`
 * (null = seluruh kabupaten). Semua controller menyaring lewat kelas
 * ini — tidak ada lagi logika cakupan yang ditulis per controller.
 */
final class Wilayah
{
    /** Kecamatan cakupan akun yang masuk (PPK/PPS); null = kabupaten. */
    public static function kecPengguna(): ?string
    {
        $kec = session()->get('pengguna_kecamatan');

        return ($kec === null || $kec === '') ? null : (string) $kec;
    }

    /** Desa cakupan akun yang masuk (PPS); null = seluruh kecamatan. */
    public static function desaPengguna(): ?string
    {
        $desa = session()->get('pengguna_desa');

        return ($desa === null || $desa === '') ? null : (string) $desa;
    }

    /**
     * Kecamatan yang berlaku efektif untuk semua kueri: cakupan akun
     * bila ada; selain itu saringan sesi (admin); null = seluruh
     * kabupaten.
     */
    public static function kecEfektif(): ?string
    {
        $kecUser = self::kecPengguna();
        if ($kecUser !== null) {
            return $kecUser;
        }
        $filter = session()->get('kec_filter');

        return ($filter === null || $filter === '') ? null : (string) $filter;
    }

    /** Terapkan saringan kecamatan efektif ke builder (bila ada). */
    public static function saringKec(BaseBuilder $builder, string $kolom = 'KEC'): BaseBuilder
    {
        $kec = self::kecEfektif();
        if ($kec !== null) {
            $builder->where($kolom, $kec);
        }

        return $builder;
    }

    /**
     * Terapkan saringan wilayah penuh: kecamatan efektif + desa
     * cakupan akun (PPS hanya boleh melihat desanya sendiri).
     */
    public static function saring(BaseBuilder $builder, string $kolomKec = 'KEC', string $kolomDesa = 'DESA'): BaseBuilder
    {
        self::saringKec($builder, $kolomKec);
        $desa = self::desaPengguna();
        if ($desa !== null) {
            $builder->where($kolomDesa, $desa);
        }

        return $builder;
    }
}
