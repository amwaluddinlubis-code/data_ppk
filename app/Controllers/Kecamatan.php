<?php

namespace App\Controllers;

/**
 * Pilih kecamatan aktif (saringan wilayah).
 *
 * Satu basis data berisi seluruh kabupaten; halaman ini mengganti
 * kecamatan yang sedang ditampilkan untuk pengguna tingkat kabupaten
 * (Admin). Pengguna PPK/PPS terkunci ke cakupan akunnya — halaman ini
 * hanya menampilkan keterangan wilayahnya.
 *
 * (Menggantikan Fase 6 yang mengganti basis data per kecamatan.)
 */
class Kecamatan extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $daftar = [];
        foreach ($this->daftarKecamatan() as $namaKec) {
            $ringkas = $db->table('mhc')
                ->select('COUNT(*) AS pemilih, COUNT(DISTINCT DESA) AS desa, COUNT(DISTINCT DESA, TPS) AS tps', false)
                ->where('KEC', $namaKec)->get()->getRowArray();
            $daftar[] = [
                'nama'    => $namaKec,
                'pemilih' => (int) ($ringkas['pemilih'] ?? 0),
                'desa'    => (int) ($ringkas['desa'] ?? 0),
                'tps'     => (int) ($ringkas['tps'] ?? 0),
                'aktif'   => $this->kecEfektif() === $namaKec,
            ];
        }

        $totalKab = (int) $db->table('mhc')->countAllResults();

        return view('kecamatan/index', [
            'title'      => 'Kecamatan',
            'title_page' => 'Kecamatan — Saringan Wilayah',
            'kecamatan'  => $this->kec,
            'kabupaten'  => $this->kab,
            'daftar'     => $daftar,
            'totalKab'   => $totalKab,
            'semuaAktif' => $this->kecEfektif() === null,
            'terkunci'   => $this->kecPengguna() !== null,
        ]);
    }

    public function aktifkan()
    {
        if ($this->kecPengguna() !== null) {
            return redirect()->to('kecamatan')
                ->with('galat', 'Wilayah Anda terkunci mengikuti cakupan akun (' . $this->kecPengguna() . ').');
        }

        $pilihan = trim((string) $this->request->getPost('kecamatan'));
        if ($pilihan === '' || $pilihan === '__semua__') {
            session()->remove('kec_filter');
            $this->catatAkses('ganti_kecamatan', 'Saringan wilayah: seluruh kabupaten');

            return redirect()->to('/')->with('sukses', 'Menampilkan seluruh kabupaten.');
        }

        if (! in_array($pilihan, $this->daftarKecamatan(), true)) {
            return redirect()->to('kecamatan')->with('galat', 'Kecamatan itu tidak ada di data coklit.');
        }

        session()->set('kec_filter', $pilihan);
        $this->catatAkses('ganti_kecamatan', 'Saringan wilayah: Kecamatan ' . $pilihan);

        return redirect()->to('/')->with('sukses', 'Kecamatan aktif sekarang: ' . $pilihan . '. Semua halaman mengikuti kecamatan itu.');
    }
}
