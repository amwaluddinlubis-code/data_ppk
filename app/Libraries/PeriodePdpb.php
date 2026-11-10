<?php

namespace App\Libraries;

/**
 * Periode PDPB berjalan (Fase B, PKPU 1/2025 Pasal 9 ayat (2):
 * pemutakhiran diselenggarakan KPU Kabupaten/Kota paling sedikit
 * setiap 3 bulan). Setiap perubahan data coklit dicatat ke periode
 * yang sedang berjalan lewat kolom riwayat_perubahan.periode_id;
 * penutupan periode membekukan arsip rekap (lihat Pdpb::tutup).
 */
final class PeriodePdpb
{
    /** Baris periode PDPB berstatus 'berjalan', atau null. */
    public static function aktif(): ?array
    {
        try {
            return db_connect()->table('periode')
                ->where('jenis', 'pdpb')->where('status', 'berjalan')
                ->orderBy('id', 'ASC')->get()->getRowArray();
        } catch (\Throwable) {
            return null;
        }
    }

    /** ID periode berjalan; membuat periode awal bila belum ada. */
    public static function pastikanAktif(): array
    {
        $aktif = self::aktif();
        if ($aktif !== null) {
            return $aktif;
        }

        $db = db_connect();
        $db->table('periode')->insert([
            'jenis'      => 'pdpb',
            'nama'       => self::namaBawaan(new \DateTimeImmutable()),
            'mulai'      => date('Y-m-d'),
            'status'     => 'berjalan',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return self::aktif() ?? [];
    }

    /** ID periode berjalan tanpa membuat baru (untuk pencatatan riwayat). */
    public static function idAktif(): ?int
    {
        $aktif = self::aktif();

        return $aktif !== null ? (int) $aktif['id'] : null;
    }

    /** Nama bawaan periode menurut triwulan tanggal acu: "PDPB Triwulan IV 2026". */
    public static function namaBawaan(\DateTimeImmutable $acu): string
    {
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
        $triwulan = intdiv(((int) $acu->format('n')) - 1, 3) + 1;

        return 'PDPB Triwulan ' . $romawi[$triwulan] . ' ' . $acu->format('Y');
    }
}
