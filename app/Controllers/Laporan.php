<?php

namespace App\Controllers;

/**
 * Laporan ringkasan per desa & per TPS.
 *
 * Semua angka dihitung langsung dari data coklit dan view turunannya,
 * mengikuti wilayah efektif: PPK melihat kecamatannya, PPS desanya,
 * Admin Kabupaten memilih kecamatan (atau seluruh kabupaten — tabel
 * per desa saat itu memuat kolom Kecamatan).
 * Tombol Cetak memakai cetak browser (window.print).
 */
class Laporan extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $kecEfektif = $this->kecEfektif();
        $desaUser = $this->desaPengguna();

        // Pilihan desa: nilai "KEC::DESA" agar unik lintas kecamatan.
        $daftarPilihan = [];
        $kecUntukDaftar = $kecEfektif !== null ? [$kecEfektif] : $this->daftarKecamatan();
        foreach ($kecUntukDaftar as $namaKec) {
            foreach ($this->daftarDesa($namaKec) as $namaDesa) {
                $daftarPilihan[] = [
                    'nilai' => $namaKec . '::' . $namaDesa,
                    'label' => $kecEfektif !== null ? $namaDesa : $namaDesa . ' — ' . ucwords(strtolower($namaKec)),
                    'kec'   => $namaKec,
                    'desa'  => $namaDesa,
                ];
            }
        }

        $pilihan = trim((string) $this->request->getGet('desa'));
        if ($desaUser !== null && $kecEfektif !== null) {
            $pilihan = $kecEfektif . '::' . $desaUser;
        }
        $desaDipilih = '';
        $kecDipilih = $kecEfektif;
        if ($pilihan !== '' && str_contains($pilihan, '::')) {
            [$kecDipilih, $desaDipilih] = explode('::', $pilihan, 2);
        }

        // Agregat per desa dari mhc (satu kueri) + KK dari view v_nkk
        $bDesa = $db->table('mhc')
            ->select("KEC, DESA,
                COUNT(*) AS total,
                SUM(KET IN ('0','U','B')) AS ms,
                SUM(KET = 'B') AS baru,
                SUM(KET IN ('1','2','3','4','5','6','7','8')) AS tersaring,
                SUM(KET = '8') AS pindah,
                COUNT(DISTINCT TPS) AS jumlah_tps", false);
        $this->saringWilayah($bDesa);
        $perDesa = $bDesa->groupBy('KEC, DESA')->orderBy('KEC')->orderBy('DESA')->get()->getResultArray();

        $bKk = $db->table('v_nkk')->select('KEC, DESA, COUNT(*) AS kk', false);
        $this->saringWilayah($bKk);
        $kkPerDesa = [];
        foreach ($bKk->groupBy('KEC, DESA')->get()->getResultArray() as $r) {
            $kkPerDesa[$r['KEC'] . '::' . $r['DESA']] = (int) $r['kk'];
        }
        foreach ($perDesa as &$pd) {
            $pd['kk'] = $kkPerDesa[$pd['KEC'] . '::' . $pd['DESA']] ?? 0;
        }
        unset($pd);

        // Rincian per TPS untuk desa terpilih
        $perTps = [];
        if ($desaDipilih !== '') {
            $perTps = $db->table('mhc')
                ->select("TPS,
                    COUNT(*) AS total,
                    SUM(KET IN ('0','U','B')) AS ms,
                    SUM(KET = 'B') AS baru,
                    SUM(KET IN ('1','2','3','4','5','6','7','8')) AS tersaring,
                    SUM(KET = '8') AS pindah", false)
                ->where('KEC', $kecDipilih)
                ->where('DESA', $desaDipilih)
                ->groupBy('TPS')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->get()->getResultArray();

            $kkPerTps = [];
            foreach ($db->table('v_nkk')->select('TPS, COUNT(*) AS kk', false)->where('KEC', $kecDipilih)->where('DESA', $desaDipilih)->groupBy('TPS')->get()->getResultArray() as $r) {
                $kkPerTps[$r['TPS']] = (int) $r['kk'];
            }
            foreach ($perTps as &$pt) {
                $pt['kk'] = $kkPerTps[$pt['TPS']] ?? 0;
            }
            unset($pt);
        }

        return view('laporan/index', [
            'title'        => 'Laporan Ringkasan',
            'title_page'   => 'Laporan Ringkasan Per Desa / TPS',
            'kecamatan'    => $this->kec,
            'kabupaten'    => $this->kab,
            'perDesa'      => $perDesa,
            'perTps'       => $perTps,
            'desaDipilih'  => $desaDipilih,
            'kecDipilih'   => $kecDipilih,
            'pilihanAktif' => $pilihan,
            'daftarDesa'   => $daftarPilihan,
            'tampilKec'    => $kecEfektif === null,
        ]);
    }
}
