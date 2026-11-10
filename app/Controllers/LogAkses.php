<?php

namespace App\Controllers;

/**
 * Halaman Log Akses (hanya Admin Kabupaten).
 *
 * Menampilkan catatan akses data sensitif dari tabel `akses_log`
 * di basis data utama.
 */
class LogAkses extends BaseController
{
    public function index()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Log akses hanya untuk Admin PPK.');
        }

        $halaman = max(1, (int) $this->request->getGet('hal'));
        $perHal  = 30;

        $builder = \Config\Database::connect('default')->table('akses_log');
        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('id', 'DESC')->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        return view('logakses/index', [
            'title'      => 'Log Akses',
            'title_page' => 'Log Akses Data',
            'kecamatan'  => $this->kec,
            'rows'       => $rows,
            'total'      => $total,
            'halaman'    => $halaman,
            'totalHal'   => max(1, (int) ceil($total / $perHal)),
        ]);
    }
}
