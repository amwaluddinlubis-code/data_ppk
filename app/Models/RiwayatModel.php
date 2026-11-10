<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model jejak audit (riwayat_perubahan).
 *
 * Skema Final 2026: catatan memuat ID wilayah (kecamatan_id /
 * kelurahan_id) dari baris yang diubah — diselesaikan dari
 * `kelurahan_id` baris mhc lewat tabel master — agar halaman Riwayat
 * dapat disaring mengikuti wilayah efektif pengguna.
 */
class RiwayatModel extends Model
{
    protected $table = 'riwayat_perubahan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'tabel', 'kunci_uid', 'nik', 'aksi', 'data_lama', 'data_baru',
        'kecamatan_id', 'kelurahan_id',
        'pengguna_id', 'pengguna_username', 'created_at',
    ];
    protected $useTimestamps = false;

    /** Catat satu perubahan beserta pelakunya dari sesi. */
    public function catat(string $aksi, ?array $lama, ?array $baru, string $tabel = 'mhc'): bool
    {
        $acuan = $baru ?? $lama;
        $kelurahanId = isset($acuan['kelurahan_id']) ? (int) $acuan['kelurahan_id'] : null;
        $kecamatanId = null;
        if ($kelurahanId !== null) {
            try {
                $kel = $this->db->table('kelurahan')->select('kecamatan_id')
                    ->where('id', $kelurahanId)->get()->getRowArray();
                $kecamatanId = $kel !== null ? (int) $kel['kecamatan_id'] : null;
            } catch (\Throwable) {
                $kecamatanId = null;
            }
        }

        return (bool) $this->insert([
            'tabel'             => $tabel,
            'kunci_uid'         => $acuan['UID'] ?? null,
            'nik'               => $acuan['NIK'] ?? null,
            'aksi'              => $aksi,
            'data_lama'         => $lama !== null ? json_encode($lama) : null,
            'data_baru'         => $baru !== null ? json_encode($baru) : null,
            'kecamatan_id'      => $kecamatanId,
            'kelurahan_id'      => $kelurahanId,
            'pengguna_id'       => session()->get('pengguna_id'),
            'pengguna_username' => session()->get('pengguna_username'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }
}
