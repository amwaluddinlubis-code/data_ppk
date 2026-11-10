<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * NIK sebagai identitas unik pemilih + kunci pokok yang sesungguhnya
 * (perintah AMWAL, 10 Nov 2026: "gunakan NIK sebagai unik id" dan
 * "database/kolom boleh diubah").
 *
 * 1. `mhc.ID` menjadi PRIMARY KEY AUTO_INCREMENT. Sebelumnya ID hanya
 *    kolom int biasa yang bisa NULL/ganda — pengenal baris tidak
 *    bisa diandalkan. Baris ber-ID NULL atau ganda diberi nomor baru.
 * 2. Kolom ter-generate `NIK_AKTIF` berisi NIK untuk baris aktif/MS
 *    (KET 0/U/B) dan NULL untuk baris tersaring. Indeks UNIQUE di
 *    atasnya membuat basis data sendiri menjamin: SATU NIK = SATU
 *    baris aktif di seluruh kabupaten. Baris tersaring orang yang
 *    sama tetap boleh tersimpan (NIK_AKTIF-nya NULL).
 *
 * Indeks unik hanya dipasang bila data yang ada memang bersih; bila
 * masih ada NIK aktif ganda (data lama), langkah itu dilewati dan
 * dicatat di keluaran migrasi — penegakan sementara berjalan di
 * tingkat aplikasi (impor & CRUD sudah menolak ganda aktif), dan
 * indeks bisa dipasang menyusul sesudah kegandaan dibersihkan.
 * Migration ini aman dijalankan berulang (memeriksa keadaan dulu).
 */
class NikUnikDanKunciPrimer extends Migration
{
    public function up()
    {
        $db = $this->db;

        // -- 1. ID: isi yang NULL/ganda, lalu jadikan kunci pokok ------
        $kolom = $db->getFieldNames('mhc');
        if (in_array('ID', $kolom, true)) {
            $maks = (int) ($db->table('mhc')->selectMax('ID')->get()->getRowArray()['ID'] ?? 0);
            $db->query('SET @nomor_baru := ' . $maks);
            // Baris ber-ID NULL mendapat nomor baru satu per satu.
            $db->query('UPDATE mhc SET ID = (@nomor_baru := @nomor_baru + 1) WHERE ID IS NULL');
            // Untuk setiap ID ganda: semua salinannya
            // diberi nomor baru.
            $gandaId = $db->query('SELECT ID FROM mhc GROUP BY ID HAVING COUNT(*) > 1')->getResultArray();
            foreach ($gandaId as $r) {
                $db->query('SET @nomor_baru := (SELECT MAX(ID) FROM mhc)');
                $db->query(
                    'UPDATE mhc SET ID = (@nomor_baru := @nomor_baru + 1) WHERE ID = ? LIMIT 1000000',
                    [(int) $r['ID']]
                );
            }

            $indeks = $db->query("SHOW INDEX FROM mhc WHERE Key_name = 'PRIMARY'")->getResultArray();
            if ($indeks === []) {
                $db->query('ALTER TABLE mhc MODIFY ID INT NOT NULL');
                $db->query('ALTER TABLE mhc ADD PRIMARY KEY (ID)');
                $db->query('ALTER TABLE mhc MODIFY ID INT NOT NULL AUTO_INCREMENT');
            }
        }

        // -- 2. Kolom NIK_AKTIF + indeks unik (bila data bersih) -------
        if (! in_array('NIK_AKTIF', $db->getFieldNames('mhc'), true)) {
            $db->query("ALTER TABLE mhc ADD COLUMN NIK_AKTIF VARCHAR(16)
                GENERATED ALWAYS AS (CASE WHEN KET IN ('0','U','B') THEN NIK ELSE NULL END) STORED");
        }
        $sudahUnik = $db->query("SHOW INDEX FROM mhc WHERE Key_name = 'uq_mhc_nik_aktif'")->getResultArray();
        if ($sudahUnik === []) {
            $ganda = (int) $db->query(
                "SELECT COUNT(*) AS c FROM (SELECT NIK FROM mhc WHERE KET IN ('0','U','B')
                 GROUP BY NIK HAVING COUNT(*) > 1) t"
            )->getRowArray()['c'];
            if ($ganda === 0) {
                $db->query('ALTER TABLE mhc ADD UNIQUE KEY uq_mhc_nik_aktif (NIK_AKTIF)');
            } else {
                echo "\n[NikUnik] Indeks unik NIK_AKTIF DILEWATI: masih ada {$ganda} NIK aktif ganda pada data. Bersihkan lewat halaman Analisis NIK Ganda, lalu jalankan migrasi ulang.\n";
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        if ($db->query("SHOW INDEX FROM mhc WHERE Key_name = 'uq_mhc_nik_aktif'")->getResultArray() !== []) {
            $db->query('ALTER TABLE mhc DROP INDEX uq_mhc_nik_aktif');
        }
        if (in_array('NIK_AKTIF', $db->getFieldNames('mhc'), true)) {
            $db->query('ALTER TABLE mhc DROP COLUMN NIK_AKTIF');
        }
        // Kunci pokok ID tidak diturunkan: nomor baris yang sudah
        // dibagikan ulang tidak dikembalikan ke keadaan tanpa kunci.
    }
}
