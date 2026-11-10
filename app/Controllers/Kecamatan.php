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
        $rows = $db->table('kecamatan')
            ->select('kecamatan.id AS id, kecamatan.nama AS nama,
                COUNT(mhc.ID) AS pemilih,
                COUNT(DISTINCT mhc.kelurahan_id) AS desa,
                COUNT(DISTINCT mhc.kelurahan_id, mhc.TPS) AS tps', false)
            ->join('kelurahan', 'kelurahan.kecamatan_id = kecamatan.id', 'left')
            ->join('mhc', 'mhc.kelurahan_id = kelurahan.id', 'left')
            ->groupBy('kecamatan.id')
            ->orderBy('kecamatan.nama')
            ->get()->getResultArray();
        $daftar = [];
        foreach ($rows as $r) {
            $daftar[] = [
                'nama'    => (string) $r['nama'],
                'pemilih' => (int) ($r['pemilih'] ?? 0),
                'desa'    => (int) ($r['desa'] ?? 0),
                'tps'     => (int) ($r['tps'] ?? 0),
                'aktif'   => $this->kecEfektif() === $r['nama'],
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
            session()->remove('kec_filter_id');
            $this->catatAkses('ganti_kecamatan', 'Saringan wilayah: seluruh kabupaten');

            return redirect()->to('/')->with('sukses', 'Menampilkan seluruh kabupaten.');
        }

        if (! in_array($pilihan, $this->daftarKecamatan(), true)) {
            return redirect()->to('kecamatan')->with('galat', 'Kecamatan itu tidak ada di data coklit.');
        }

        $master = new \App\Libraries\WilayahMaster(db_connect());
        session()->set('kec_filter', $pilihan);
        session()->set('kec_filter_id', $master->cariKecamatanId($pilihan));
        $this->catatAkses('ganti_kecamatan', 'Saringan wilayah: Kecamatan ' . $pilihan);

        return redirect()->to('/')->with('sukses', 'Kecamatan aktif sekarang: ' . $pilihan . '. Semua halaman mengikuti kecamatan itu.');
    }
}
