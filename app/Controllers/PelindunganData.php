<?php

namespace App\Controllers;

/**
 * Fase G (Pengembangan 2026) — Dasbor pelindungan data pribadi
 * (khusus admin): catatan pemrosesan aplikasi menurut UU 27/2022
 * jo. PP 33/2026 (berlaku 16 Januari 2027). Menggabungkan log akses,
 * riwayat perubahan, log ekspor, dan status permohonan perbaikan
 * data dalam satu layar pertanggungjawaban.
 */
class PelindunganData extends BaseController
{
    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return db_connect();
    }

    public function index()
    {
        if (($this->pengguna['peran'] ?? '') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Halaman Pelindungan Data hanya untuk admin.');
        }
        $db = $this->db();
        $sejak = date('Y-m-d H:i:s', time() - 30 * 24 * 3600);

        $aksesPerAksi = $db->query(
            'SELECT aksi, COUNT(*) AS jumlah FROM akses_log WHERE created_at >= ? GROUP BY aksi ORDER BY jumlah DESC LIMIT 15',
            [$sejak]
        )->getResultArray();
        $totalAkses = (int) $db->table('akses_log')->where('created_at >=', $sejak)->countAllResults();

        $ubahPerJenis = [];
        try {
            $ubahPerJenis = $db->query(
                'SELECT aksi AS jenis, COUNT(*) AS jumlah FROM riwayat_perubahan WHERE created_at >= ? GROUP BY aksi ORDER BY jumlah DESC LIMIT 15',
                [$sejak]
            )->getResultArray();
        } catch (\Throwable) {
            $ubahPerJenis = [];
        }

        $ekspor = $db->table('log_ekspor')->orderBy('id', 'DESC')->limit(50)->get()->getResultArray();
        $totalEkspor = (int) $db->table('log_ekspor')->countAllResults();

        $permohonan = $db->query(
            "SELECT status, COUNT(*) AS jumlah FROM permohonan_perbaikan GROUP BY status"
        )->getResultArray();
        $lewatSla = (int) $db->query(
            "SELECT COUNT(*) AS j FROM permohonan_perbaikan WHERE status IN ('baru','diproses') AND created_at < ?",
            [date('Y-m-d H:i:s', time() - 3 * 24 * 3600)]
        )->getRowArray()['j'];

        return view('pelindungan-data/index', [
            'title' => 'Pelindungan Data Pribadi', 'title_page' => 'Pelindungan Data Pribadi — Catatan Pemrosesan',
            'aksesPerAksi' => $aksesPerAksi, 'totalAkses' => $totalAkses,
            'ubahPerJenis' => $ubahPerJenis, 'ekspor' => $ekspor, 'totalEkspor' => $totalEkspor,
            'permohonan' => $permohonan, 'lewatSla' => $lewatSla,
        ]);
    }
}
