<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Indeks pendukung analisis (optimasi performance, 11 Nov 2026).
 *
 * Hasil uji performance pada data kabupaten penuh (338 ribu baris)
 * menunjukkan kueri analisis berat melakukan pemindaian + penyortiran
 * tabel penuh. Indeks gabungan berikut melayani pola kueri yang
 * dominan:
 *
 * - (KET, NAMA, TGL_LAHIR)  duplikat terselubung (GROUP BY nama+tgl)
 * - (KET, NKK)              KK tersebar (GROUP BY NKK)
 * - (KET, TGL_LAHIR)        umur ekstrem (saring + urut tanggal)
 * - (NIK)                   kegandaan NIK (GROUP BY NIK) + cari awalan
 * - (NKK)                   pencarian global awalan NKK
 */
class IndeksOptimasi extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE `mhc`
            ADD INDEX `idx_mhc_ket_nama_tgl` (`KET`, `NAMA`, `TGL_LAHIR`),
            ADD INDEX `idx_mhc_ket_nkk` (`KET`, `NKK`),
            ADD INDEX `idx_mhc_ket_tgl` (`KET`, `TGL_LAHIR`),
            ADD INDEX `idx_mhc_nik` (`NIK`),
            ADD INDEX `idx_mhc_nkk` (`NKK`)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `mhc`
            DROP INDEX `idx_mhc_ket_nama_tgl`,
            DROP INDEX `idx_mhc_ket_nkk`,
            DROP INDEX `idx_mhc_ket_tgl`,
            DROP INDEX `idx_mhc_nik`,
            DROP INDEX `idx_mhc_nkk`');
    }
}
