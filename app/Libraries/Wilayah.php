<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

/**
 * Cakupan wilayah pengguna & saringan kueri (konsep kabupaten).
 *
 * Satu basis data = satu kabupaten. Kecamatan adalah saringan wilayah:
 * pengguna PPK/PPS terkunci ke cakupan akunnya (users.kecamatan_id /
 * kelurahan_id); Admin Kabupaten bebas memilih saringan lewat sesi
 * `kec_filter` (null = seluruh kabupaten). Semua controller menyaring
 * lewat kelas ini — tidak ada lagi logika cakupan per controller.
 *
 * Skema Final 2026 (wilayah ternormalisasi): sesi menyimpan ID master
 * (pengguna_kecamatan_id / pengguna_kelurahan_id / kec_filter_id)
 * sekaligus NAMANYA. Kueri ke VIEW (yang menyajikan kolom KEC/DESA
 * hasil JOIN master) disaring berdasar nama seperti biasa; kueri
 * langsung ke tabel `mhc` disaring berdasar ID lewat saringId().
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

    /** Id master kecamatan cakupan akun; null = kabupaten. */
    public static function kecIdPengguna(): ?int
    {
        $id = session()->get('pengguna_kecamatan_id');

        return ($id === null || $id === '') ? null : (int) $id;
    }

    /** Id master kelurahan cakupan akun (PPS); null = seluruh kecamatan. */
    public static function kelurahanIdPengguna(): ?int
    {
        $id = session()->get('pengguna_kelurahan_id');

        return ($id === null || $id === '') ? null : (int) $id;
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

    /** Id master kecamatan efektif (cakupan akun / saringan sesi). */
    public static function kecEfektifId(): ?int
    {
        $idUser = self::kecIdPengguna();
        if ($idUser !== null) {
            return $idUser;
        }
        $filter = session()->get('kec_filter_id');

        return ($filter === null || $filter === '') ? null : (int) $filter;
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
     * Untuk kueri ke view yang mempunyai kolom KEC/DESA.
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

    /**
     * Saringan wilayah berdasar ID untuk kueri langsung ke tabel mhc
     * (yang hanya mempunyai kolom kelurahan_id): PPS dibatasi ke
     * kelurahannya; PPK/Admin-tersaring dibatasi ke semua kelurahan
     * pada kecamatan efektif.
     */
    public static function saringId(BaseBuilder $builder, string $kolomKelurahan = 'mhc.kelurahan_id'): BaseBuilder
    {
        $kelId = self::kelurahanIdPengguna();
        if ($kelId !== null) {
            $builder->where($kolomKelurahan, $kelId);

            return $builder;
        }
        $kecId = self::kecEfektifId();
        if ($kecId !== null) {
            $sub = \Config\Database::connect()->table('kelurahan')
                ->select('id')->where('kecamatan_id', $kecId);
            $builder->whereIn($kolomKelurahan, $sub);
        }

        return $builder;
    }
}
