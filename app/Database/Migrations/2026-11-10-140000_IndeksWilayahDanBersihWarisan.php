<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tahap T1+T2 dari Analisis Struktur Basis Data (10 Nov 2026,
 * atas perintah AMWAL):
 *
 * T1 — Indeks wilayah untuk `mhc`. Semua halaman menyaring berdasar
 * KEC/DESA/TPS/KET tetapi tabel hanya terindeks di (NIK, KET); uji
 * pada 338 ribu baris menunjukkan hitungan tersaring ±5–6× lebih
 * cepat dengan indeks gabungan wilayah.
 *
 * T2 — Pembersihan objek warisan yang tidak lagi dirujuk kode mana
 * pun (terverifikasi): 9 view warisan di-DROP (definisinya tersimpan
 * di down() untuk pemulihan), sedangkan 3 tabel warisan TIDAK
 * dihapus melainkan diganti nama menjadi arsip_* — datanya utuh,
 * hanya keluar dari daftar objek hidup, dan dapat dihapus manual
 * kapan pun sesudah dipastikan tidak dibutuhkan.
 */
class IndeksWilayahDanBersihWarisan extends Migration
{
    /** View warisan yang di-drop pada up(). */
    private array $viewWarisan = [
        'desa', 'kec', 'group_nik', 'analisis_nik_ganda',
        'vpt_tms', 'v_count_kk', 'lap10_1', 'lap10_2', 'lap10_3',
    ];

    /** Tabel warisan => nama arsipnya. */
    private array $tabelWarisan = [
        'to_sql'       => 'arsip_to_sql_20261110',
        'config'       => 'arsip_config_20261110',
        'nik_move_tps' => 'arsip_nik_move_tps_20261110',
    ];

    public function up()
    {
        $db = $this->db;

        // -- T1: indeks wilayah (lewati bila sudah ada) ----------------
        $indeksAda = array_column(
            $db->query('SHOW INDEX FROM mhc')->getResultArray(),
            'Key_name'
        );
        if (! in_array('idx_mhc_wilayah', $indeksAda, true)) {
            $db->query('ALTER TABLE mhc ADD INDEX idx_mhc_wilayah (KEC, DESA, TPS, KET)');
        }
        if (! in_array('idx_mhc_ket_kec', $indeksAda, true)) {
            $db->query('ALTER TABLE mhc ADD INDEX idx_mhc_ket_kec (KET, KEC)');
        }

        // -- T2a: drop view warisan ------------------------------------
        foreach ($this->viewWarisan as $v) {
            $db->query('DROP VIEW IF EXISTS `' . $v . '`');
        }

        // -- T2b: arsipkan tabel warisan (ganti nama, data utuh) -------
        $tabelAda = $db->listTables();
        foreach ($this->tabelWarisan as $asal => $arsip) {
            if (in_array($asal, $tabelAda, true) && ! in_array($arsip, $tabelAda, true)) {
                $db->query('RENAME TABLE `' . $asal . '` TO `' . $arsip . '`');
            }
        }
    }

    public function down()
    {
        $db = $this->db;

        // Kembalikan nama tabel arsip.
        $tabelAda = $db->listTables();
        foreach ($this->tabelWarisan as $asal => $arsip) {
            if (in_array($arsip, $tabelAda, true) && ! in_array($asal, $tabelAda, true)) {
                $db->query('RENAME TABLE `' . $arsip . '` TO `' . $asal . '`');
            }
        }

        // Hidupkan lagi view warisan dengan definisi aslinya.
        $definisi = [
            'desa'    => 'SELECT DISTINCT DESA FROM mhc GROUP BY DESA ORDER BY DESA',
            'kec'     => 'SELECT KEC FROM mhc LIMIT 0, 1',
            'group_nik' => 'SELECT COUNT(NIK) AS JLH_TERCOKLIT, NIK FROM ms GROUP BY NIK',
            'analisis_nik_ganda' => "SELECT a.KEC, a.DESA, a.TPS, a.KET, a.NIK, a.NKK, a.NAMA FROM ms a WHERE a.NIK IN (SELECT b.NIK FROM group_nik b WHERE b.JLH_TERCOKLIT > 1) ORDER BY a.NIK",
            'vpt_tms' => "SELECT REPLACE(CONCAT(a.DESA, a.TPS, a.KET), ' ', '') AS ID_TMS, a.DESA, a.TPS, a.KET, COUNT(a.KET) AS JLH_TERSARING FROM tersaring a GROUP BY a.DESA, a.TPS, a.KET",
            'v_count_kk' => "SELECT v_nkk.DESA, CONCAT('TPS 00', v_nkk.TPS) AS TPS, COUNT(v_nkk.NKK) AS JLH_KK FROM v_nkk GROUP BY v_nkk.DESA, v_nkk.TPS",
            'lap10_1' => "SELECT * FROM mhc WHERE TGL_COKLIT BETWEEN '2023-02-12' AND '2023-02-21'",
            'lap10_2' => "SELECT * FROM mhc WHERE TGL_COKLIT BETWEEN '2023-02-22' AND '2023-03-03'",
            'lap10_3' => "SELECT * FROM mhc WHERE TGL_COKLIT BETWEEN '2023-03-04' AND '2023-03-14'",
        ];
        foreach ($definisi as $nama => $sql) {
            $db->query('CREATE OR REPLACE VIEW `' . $nama . '` AS ' . $sql);
        }

        // Lepas indeks wilayah.
        $indeksAda = array_column(
            $db->query('SHOW INDEX FROM mhc')->getResultArray(),
            'Key_name'
        );
        foreach (['idx_mhc_wilayah', 'idx_mhc_ket_kec'] as $idx) {
            if (in_array($idx, $indeksAda, true)) {
                $db->query('ALTER TABLE mhc DROP INDEX `' . $idx . '`');
            }
        }
    }
}
