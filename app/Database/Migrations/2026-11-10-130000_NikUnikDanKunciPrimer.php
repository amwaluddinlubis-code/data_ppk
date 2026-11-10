<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RINTISAN KOSONG — migration lama yang sudah digantikan.
 *
 * Sejak Skema Konsolidasi 2026 (migration 2026-11-10-200000 s.d.
 * 230000), struktur basis data ditetapkan langsung dalam bentuk
 * akhirnya oleh migration-migration baru. Berkas ini sengaja
 * dikosongkan agar:
 * - basis data BARU tidak lagi membentuk objek warisan, dan
 * - basis data LAMA yang sudah mencatat versi ini tidak terganggu.
 *
 * Jangan dihapus: penghapusan berkas migration yang sudah tercatat
 * dapat membingungkan riwayat migrasi pada basis data lama.
 */
class NikUnikDanKunciPrimer extends Migration
{
    public function up()
    {
        // Digantikan Skema Konsolidasi 2026.
    }

    public function down()
    {
        // Digantikan Skema Konsolidasi 2026.
    }
}
