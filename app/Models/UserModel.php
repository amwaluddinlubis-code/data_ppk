<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Fase 1 — Model pengguna.
 *
 * Kata sandi disimpan sebagai hash `password_hash()` (bcrypt, bawaan PHP)
 * — tidak pernah teks polos. Verifikasi lewat `cekSandi()`.
 */
class UserModel extends Model
{
    // Fase 6: pengguna selalu dari basis data UTAMA, apa pun
    // kecamatan yang sedang aktif pada sesi ini.
    protected $DBGroup = 'default';
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama', 'username', 'password_hash', 'peran', 'kecamatan', 'desa',
        'aktif', 'harus_ganti_sandi',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama'     => 'required|min_length[3]|max_length[100]',
        'username' => 'required|min_length[3]|max_length[50]|alpha_numeric_punct|is_unique[users.username,id,{id}]',
        'peran'    => 'required|in_list[admin,operator,viewer]',
    ];

    public static function labelPeran(string $peran): string
    {
        return [
            'admin'    => 'Admin Kabupaten',
            'operator' => 'Operator',
            'viewer'   => 'Pemeriksa',
        ][$peran] ?? $peran;
    }

    /**
     * Fase 7 — Label tingkat pengguna mengikuti hierarki wilayah:
     * Admin Kabupaten / PPK (per kecamatan) / PPS (per desa).
     */
    public static function labelTingkat(array $pengguna): string
    {
        if (($pengguna['peran'] ?? '') === 'admin') {
            return 'Admin Kabupaten';
        }
        $desa = trim((string) ($pengguna['desa'] ?? ''));
        if ($desa !== '') {
            return 'PPS — ' . ucwords(strtolower($desa));
        }
        $kec = trim((string) ($pengguna['kecamatan'] ?? ''));
        if ($kec !== '') {
            return 'PPK — ' . ucwords(strtolower($kec));
        }

        return self::labelPeran((string) ($pengguna['peran'] ?? ''));
    }

    public function cariDenganUsername(string $username): ?array
    {
        $baris = $this->where('username', $username)->first();

        return $baris ?: null;
    }

    public function cekSandi(array $pengguna, string $sandi): bool
    {
        return password_verify($sandi, $pengguna['password_hash']);
    }

    /** Buat hash untuk kata sandi baru (dipakai controller saat simpan). */
    public static function hashSandi(string $sandi): string
    {
        return password_hash($sandi, PASSWORD_DEFAULT);
    }
}
