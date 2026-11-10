<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaikan audit 10 Nov 2026 (temuan T1 & T2):
 *
 * T1 — View `pemilihbaru2` menggandakan baris. Definisinya menggabung
 * `pemilihbaru` dengan `tersaring` berdasarkan NIK untuk menampilkan
 * nama/desa lama (OLD_NAMA/OLD_DESA). Bila satu NIK punya lebih dari
 * satu baris tersaring (mis. tercatat ganda di dua TPS), baris pemilih
 * barunya ikut berlipat: halaman Pemilih Baru menampilkan 2.871 baris
 * padahal pemilih berkode B hanya 2.865. Perbaikan: OLD_NAMA/OLD_DESA
 * diambil lewat subkueri skalar (satu baris tersaring paling awal per
 * NIK), sehingga satu baris pemilih baru selalu menghasilkan satu baris.
 *
 * T2 — Kode keterangan 4 (dan 5) tidak masuk kelompok mana pun. Standar
 * kode TMS coklit: 1 Meninggal, 2 Ganda, 3 Di Bawah Umur, 4 Pindah
 * Domisili, 5 WNA, 6 TNI, 7 Polri, 8 Salah Penempatan TPS. Aplikasi
 * asal hanya memakai 1,2,3,6,7,8 sehingga 24 baris berkode 4 terhitung
 * sebagai "pemilih aktif" tetapi tidak sebagai MS — menggantung.
 * Perbaikan: daftar kode TMS pada view `tersaring` dan `pemilih_active`
 * dilengkapi menjadi 1–8. Sesudah ini berlaku: MS + Tersaring = Total,
 * dan halaman Pemilih Aktif = MS.
 */
class PerbaikanViewBaruDanKodeTms extends Migration
{
    public function up()
    {
        $views = [
            'pemilihbaru2'   => "CREATE OR REPLACE VIEW `pemilihbaru2` AS SELECT `a`.`UID` AS `UID`, `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`TMPTLHR` AS `TMPTLHR`, `a`.`TGLLHR` AS `TGLLHR`, `a`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `a`.`ALAMAT` AS `ALAMAT`, `a`.`RT` AS `RT`, `a`.`RW` AS `RW`, `a`.`DISABILITAS` AS `DISABILITAS`, `a`.`EKTP` AS `EKTP`, `a`.`KET` AS `KET`, `a`.`SUMBER` AS `SUMBER`, `a`.`TPS` AS `TPS`, `a`.`LAST_UPDATE` AS `LAST_UPDATE`, `a`.`LAT` AS `LAT`, `a`.`LONG` AS `LONG`, `a`.`PROV` AS `PROV`, `a`.`KAB` AS `KAB`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, (SELECT `b`.`NAMA` FROM `tersaring` `b` WHERE `b`.`NIK` = `a`.`NIK` ORDER BY `b`.`ID` ASC LIMIT 1) AS `OLD_NAMA`, (SELECT `b`.`DESA` FROM `tersaring` `b` WHERE `b`.`NIK` = `a`.`NIK` ORDER BY `b`.`ID` ASC LIMIT 1) AS `OLD_DESA` FROM `pemilihbaru` `a`",
            'tersaring'      => "CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR` FROM (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` in (1,2,3,4,5,6,7,8)",
            'pemilih_active' => "CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, concat(left(`a`.`NKK`,4),'******',right(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)) AS `BDATE`, timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)),'2024-02-14') AS `UMUR` FROM `mhc` AS `a` WHERE `a`.`KET` not in (1,2,3,4,5,6,7,8)",
        ];
        foreach ($views as $sql) {
            $this->db->query($sql);
        }
    }

    public function down()
    {
        $views = [
            "CREATE OR REPLACE VIEW `pemilihbaru2` AS SELECT `a`.`UID` AS `UID`, `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`TMPTLHR` AS `TMPTLHR`, `a`.`TGLLHR` AS `TGLLHR`, `a`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `a`.`ALAMAT` AS `ALAMAT`, `a`.`RT` AS `RT`, `a`.`RW` AS `RW`, `a`.`DISABILITAS` AS `DISABILITAS`, `a`.`EKTP` AS `EKTP`, `a`.`KET` AS `KET`, `a`.`SUMBER` AS `SUMBER`, `a`.`TPS` AS `TPS`, `a`.`LAST_UPDATE` AS `LAST_UPDATE`, `a`.`LAT` AS `LAT`, `a`.`LONG` AS `LONG`, `a`.`PROV` AS `PROV`, `a`.`KAB` AS `KAB`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `b`.`NAMA` AS `OLD_NAMA`, `b`.`DESA` AS `OLD_DESA` FROM (`pemilihbaru` `a` left join `tersaring` `b` on(`a`.`NIK` = `b`.`NIK`))",
            "CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR` FROM (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` in (1,2,3,6,7,8)",
            "CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, concat(left(`a`.`NKK`,4),'******',right(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)) AS `BDATE`, timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)),'2024-02-14') AS `UMUR` FROM `mhc` AS `a` WHERE `a`.`KET` not in (1,2,3,6,7,8)",
        ];
        foreach ($views as $sql) {
            $this->db->query($sql);
        }
    }
}
