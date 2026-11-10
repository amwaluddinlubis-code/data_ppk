<?php

namespace App\Controllers;

use App\Models\RiwayatModel;

/**
 * Fase 2 — Halaman Riwayat Perubahan (jejak audit data coklit).
 *
 * Terbaca oleh Admin PPK & Operator. Pemulihan baris yang terhapus
 * hanya oleh Admin PPK.
 */
class Riwayat extends BaseController
{
    private function dataHalaman(string $judul): array
    {
        return [
            'title'      => $judul,
            'title_page' => $judul,
            'kecamatan'  => $this->kec,
            'kabupaten'  => $this->kab,
        ];
    }

    public function index()
    {
        if (! in_array(session()->get('pengguna_peran'), ['admin', 'operator'], true)) {
            return redirect()->to('/')->with('galat', 'Peran Anda hanya boleh melihat data.');
        }

        $aksi    = trim((string) $this->request->getGet('aksi'));
        $halaman = max(1, (int) $this->request->getGet('hal'));
        $perHal  = 25;

        $model = new RiwayatModel();
        $builder = $model->builder();
        if ($this->kecEfektif() !== null) {
            $builder->where('kecamatan', $this->kecEfektif());
        }
        if ($this->desaPengguna() !== null) {
            $builder->where('desa', $this->desaPengguna());
        }
        if ($aksi !== '') {
            $builder->where('aksi', $aksi);
        }
        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('id', 'DESC')->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        foreach ($rows as &$r) {
            $sumber = json_decode((string) ($r['data_baru'] ?? ''), true)
                ?: json_decode((string) ($r['data_lama'] ?? ''), true) ?: [];
            $r['nama_tampil'] = $sumber['NAMA'] ?? '';
            $r['desa_tampil'] = $sumber['DESA'] ?? '';
        }
        unset($r);

        $data = $this->dataHalaman('Riwayat Perubahan Data');
        $data['rows']     = $rows;
        $data['aksi']     = $aksi;
        $data['total']    = $total;
        $data['halaman']  = $halaman;
        $data['perHal']   = $perHal;
        $data['totalHal'] = max(1, (int) ceil($total / $perHal));
        $data['isAdmin']  = session()->get('pengguna_peran') === 'admin';

        return view('riwayat/index', $data);
    }

    /** Pulihkan baris mhc dari catatan 'hapus'. Hanya Admin PPK. */
    public function pulih($id = null)
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('riwayat')->with('galat', 'Pemulihan hanya boleh dilakukan Admin PPK.');
        }

        $model = new RiwayatModel();
        $catatan = $model->find((int) $id);
        if ($catatan === null || $catatan['aksi'] !== 'hapus') {
            return redirect()->to('riwayat')->with('galat', 'Catatan penghapusan tidak ditemukan.');
        }

        $baris = json_decode((string) $catatan['data_lama'], true);
        if (! is_array($baris) || empty($baris['UID'])) {
            return redirect()->to('riwayat')->with('galat', 'Salinan baris pada catatan ini tidak lengkap.');
        }

        $db = db_connect();
        $sudahAda = $db->table('mhc')->where('UID', $baris['UID'])->countAllResults();
        if ($sudahAda > 0) {
            return redirect()->to('riwayat')->with('galat', 'Baris dengan UID itu sudah ada — mungkin sudah dipulihkan.');
        }

        $db->transStart();
        $db->table('mhc')->insert($baris);
        $model->catat('pulih', null, $baris);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('riwayat')->with('galat', 'Gagal memulihkan. Coba lagi.');
        }

        return redirect()->to('riwayat')->with('sukses', 'Baris ' . ($baris['NAMA'] ?? '') . ' dipulihkan ke data coklit.');
    }
}
