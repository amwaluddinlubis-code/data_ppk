<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Fase 2 — Model jejak audit (riwayat_perubahan).
 *
 * DBGroup TIDAK dikunci: catatan mengikuti basis data kecamatan yang
 * sedang aktif, sama seperti data coklit yang diubah.
 */
class RiwayatModel extends Model
{
    protected $table = 'riwayat_perubahan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'tabel', 'kunci_uid', 'nik', 'aksi', 'data_lama', 'data_baru',
        'pengguna_id', 'pengguna_username', 'created_at',
    ];
    protected $useTimestamps = false;

    /** Catat satu perubahan beserta pelakunya dari sesi. */
    public function catat(string $aksi, ?array $lama, ?array $baru, string $tabel = 'mhc'): bool
    {
        $acuan = $baru ?? $lama;

        return (bool) $this->insert([
            'tabel'             => $tabel,
            'kunci_uid'         => $acuan['UID'] ?? null,
            'nik'               => $acuan['NIK'] ?? null,
            'aksi'              => $aksi,
            'data_lama'         => $lama !== null ? json_encode($lama) : null,
            'data_baru'         => $baru !== null ? json_encode($baru) : null,
            'pengguna_id'       => session()->get('pengguna_id'),
            'pengguna_username' => session()->get('pengguna_username'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }
}
