<?php

namespace App\Libraries;

/**
 * Alasan pindah memilih (DPTb) menurut ketentuan penyelenggaraan
 * Pemilu/Pilkada — dipakai formulir pengajuan dan Model A-Surat
 * Pindah Memilih.
 */
class AlasanPindah
{
    public const DAFTAR = [
        'Menjalankan tugas di tempat lain pada hari pemungutan suara',
        'Menjalani rawat inap di rumah sakit atau fasilitas kesehatan',
        'Penyandang disabilitas yang menjalani perawatan di panti sosial/panti rehabilitasi',
        'Menjalani rehabilitasi narkoba',
        'Menjadi tahanan di rumah tahanan/lembaga pemasyarakatan',
        'Tugas belajar atau menempuh pendidikan menengah/tinggi',
        'Pindah domisili',
        'Tertimpa bencana alam',
        'Bekerja di luar domisilinya',
        'Keadaan tertentu lainnya sesuai peraturan perundang-undangan',
    ];

    public static function sah(string $alasan): bool
    {
        return in_array($alasan, self::DAFTAR, true);
    }
}
