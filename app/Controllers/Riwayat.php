<?php

namespace App\Controllers;

use App\Models\RiwayatModel;

/**
 * Halaman Riwayat Perubahan (jejak audit data coklit).
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
        $perHal  = (int) $this->request->getGet('perhal');
        if (! in_array($perHal, [10, 25, 50, 100], true)) {
            $perHal = 25;
        }

        $model = new RiwayatModel();
        $builder = $model->builder();
        if (\App\Libraries\Wilayah::kecEfektifId() !== null) {
            $builder->where('kecamatan_id', \App\Libraries\Wilayah::kecEfektifId());
        }
        if (\App\Libraries\Wilayah::kelurahanIdPengguna() !== null) {
            $builder->where('kelurahan_id', \App\Libraries\Wilayah::kelurahanIdPengguna());
        }
        if ($aksi !== '') {
            $builder->where('aksi', $aksi);
        }
        $total    = $builder->countAllResults(false);
        $totalHal = max(1, (int) ceil($total / $perHal));
        $halaman  = min($halaman, $totalHal);
        $rows  = $builder->orderBy('id', 'DESC')->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        // Nama desa tampilan diselesaikan dari master (snapshot baris
        // kini hanya menyimpan kelurahan_id).
        $master = new \App\Libraries\WilayahMaster(db_connect());
        foreach ($rows as &$r) {
            $sumber = json_decode((string) ($r['data_baru'] ?? ''), true)
                ?: json_decode((string) ($r['data_lama'] ?? ''), true) ?: [];
            $r['nama_tampil'] = $sumber['NAMA'] ?? '';
            $kelId = (int) ($r['kelurahan_id'] ?? ($sumber['kelurahan_id'] ?? 0));
            $r['desa_tampil'] = $kelId > 0
                ? ($master->kelurahan($kelId)['nama'] ?? '')
                : ($sumber['DESA'] ?? '');
        }
        unset($r);

        $data = $this->dataHalaman('Riwayat Perubahan Data');
        $data['rows']     = $rows;
        $data['aksi']     = $aksi;
        $data['total']    = $total;
        $data['halaman']  = $halaman;
        $data['perHal']   = $perHal;
        $data['totalHal'] = $totalHal;
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

        // ID lama tidak dipakai lagi: kunci pokok dibangkitkan basis
        // data agar tidak bertabrakan dengan baris yang sudah ada.
        // Saring ke kolom mhc skema final agar salinan lama (bila ada
        // yang memuat kolom tambahan) tidak menggagalkan penyisipan.
        unset($baris['ID'], $baris['NIK_AKTIF']);
        $baris = array_intersect_key($baris, array_flip(\App\Libraries\ImporCoklit::KOLOM_MHC));
        if (empty($baris['kelurahan_id'])) {
            return redirect()->to('riwayat')->with('galat', 'Salinan baris tidak memuat wilayah (kelurahan) — tidak bisa dipulihkan.');
        }
        $db->transStart();
        $db->table('mhc')->insert($baris);
        $model->catat('pulih', null, $baris);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('riwayat')->with('galat', 'Gagal memulihkan. Coba lagi.');
        }

        $this->sentuhVersiData();

        return redirect()->to('riwayat')->with('sukses', 'Baris ' . ($baris['NAMA'] ?? '') . ' dipulihkan ke data coklit.');
    }
}
