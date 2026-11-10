<?php

namespace App\Controllers;

use App\Models\PemilihModel;
use \Hermawan\DataTables\DataTable;

/**
 * Halaman-halaman daftar pemilih hasil coklit: Pindah TPS, Pemilih
 * Baru, Tersaring, daftar Aktif (siap cetak), dan cetak daftar MS
 * (HTML & PDF).
 *
 * Semua daftar memakai DataTables server-side di atas view basis data,
 * disaring wilayah efektif + saringan halaman (desa/TPS/jenis kelamin/
 * keterangan). NIK/NKK Pemeriksa disamarkan oleh BaseController.
 *
 * Bangun ulang 2026: perakitan data halaman & persiapan cetak yang
 * dulu disalin di banyak metode kini terpusat di metode pembantu.
 */
class Pemilih extends BaseController
{
    // -- Halaman ------------------------------------------------------

    public function index()
    {
        return view('pemilih', $this->dataHalaman('PINDAH TPS', 'DAFTAR PEMILIH PINDAH TPS'));
    }

    public function baru()
    {
        return view('pemilihbaru', $this->dataHalaman('PEMILIH BARU', 'DAFTAR PEMILIH BARU'));
    }

    public function tersaring()
    {
        return view('tersaring', $this->dataHalaman('PEMILIH TERSARING', 'DAFTAR PEMILIH TERSARING'));
    }

    public function aktifPrint()
    {
        return view('pemilihAktif', $this->dataHalaman('CETAK DAFTAR PEMILIH MEMENUHI SYARAT', 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT'));
    }

    /** Data bersama semua halaman daftar: judul, wilayah, daftar desa. */
    private function dataHalaman(string $title, string $titlePage): array
    {
        helper('url');

        return [
            'title'     => $title,
            'title_page' => $titlePage,
            'kecamatan' => $this->kec,
            'arrDesa'   => $this->daftarDesa(),
            'kabupaten' => $this->kab,
        ];
    }

    // -- Umpan DataTables ----------------------------------------------

    public function pindahTPS()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $stat = $this->request->getPost('stat');

        $builder = db_connect()->table('pindahtps')->select('');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('MSLP', $lp);
        }
        if ($stat != '') {
            if ($stat == 1) {
                $builder->where('MSTPS >', '1');
            } else {
                $builder->where('MSTPS IS NULL', null);
            }
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [2, 3]
        );
    }

    public function getPemilihBaru()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $dp4  = $this->request->getPost('dp4');

        $builder = db_connect()->table('pemilihbaru2')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('JENIS_KELAMIN', $lp);
        }
        if ($dp4 != '') {
            $builder->where($dp4 > 0 ? 'OLD_NAMA IS NOT NULL' : 'OLD_NAMA IS NULL', null);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3]
        );
    }

    public function getPemilihTersaring()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $ket  = $this->request->getPost('ket');

        $builder = db_connect()->table('tersaring')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('LP', $lp);
        }
        if ($ket != '') {
            $builder->where('KET', $ket);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3]
        );
    }

    public function aktif()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');

        $builder = db_connect()->table('pemilih_active')
            ->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('JK', $lp);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3, 4]
        );
    }

    // -- Cetak daftar MS -------------------------------------------------

    /** Cetak daftar pemilih MS per desa/TPS sebagai halaman HTML siap cetak. */
    public function ms()
    {
        $data = $this->siapkanCetakMs(false);
        $this->catatAkses('cetak_daftar_ms', 'Desa ' . $data['desa'] . ' TPS ' . $data['tps'] . ' (' . count($data['dtTable']) . ' pemilih)');

        return view('print/print_ms', $data);
    }

    /** Daftar MS yang sama sebagai berkas PDF (dompdf). */
    public function msPdf()
    {
        $data = $this->siapkanCetakMs(true);
        $html = view('print/print_ms', $data);

        $pdf        = new \App\Libraries\pdfGenerator();
        $namaBerkas = 'Daftar-MS-' . preg_replace('/[^A-Za-z0-9]+/', '-', $data['desa']) . '-TPS' . $data['tps'];
        $isi        = $pdf->generate($html, $namaBerkas, 'A4', 'landscape', false);

        $this->catatAkses('unduh_pdf_ms', 'Desa ' . $data['desa'] . ' TPS ' . $data['tps'] . ' (' . count($data['dtTable']) . ' pemilih)');

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaBerkas . '.pdf"')
            ->setBody($isi);
    }

    /**
     * Siapkan data cetak MS untuk desa/TPS terpilih. PPS terkunci ke
     * desanya sendiri; pengguna lain mengambil desa dari permintaan.
     *
     * @return array{dtTable: array, kecamatan: ?string, desa: string, tps: string, title: string}
     */
    private function siapkanCetakMs(bool $dariGet): array
    {
        $desa = $this->desaPengguna()
            ?? (string) ($dariGet ? $this->request->getGet('desa') : $this->request->getPostGet('desa'));
        $tps  = (string) ($dariGet ? $this->request->getGet('tps') : $this->request->getPostGet('tps'));

        $klausa = ['DESA' => $desa, 'TPS' => $tps];
        if ($this->kecEfektif() !== null) {
            $klausa['KEC'] = $this->kecEfektif();
        }

        $dt = new PemilihModel();

        return [
            'dtTable'   => $this->samarkanBarisCetak($dt->pemilihMS($klausa)),
            'kecamatan' => $this->kecEfektif(),
            'desa'      => strtoupper($desa),
            'tps'       => strtoupper($tps),
            'title'     => 'CETAK PEMILIH AKTIF - HASIL PANTARLIH | ' . strtoupper($desa) . '00' . $tps,
        ];
    }
}
