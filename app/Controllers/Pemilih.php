<?php

namespace App\Controllers;

use App\Models\PemilihModel;
use \Hermawan\DataTables\DataTable;

class Pemilih extends BaseController
{
    public function index()
    {
        helper('url');
        $db = db_connect();
        $data['title'] = 'PINDAH TPS';
        $data['title_page'] = 'DAFTAR PEMILIH PINDAH TPS';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $this->daftarDesa();
        $data['kabupaten'] = $this->kab;
        return view('pemilih', $data);
    }

    public function baru()
    {
        helper('url');
        $db = db_connect();
        $data['title'] = 'PEMILIH BARU';
        $data['title_page'] = 'DAFTAR PEMILIH BARU';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $this->daftarDesa();
        $data['kabupaten'] = $this->kab;
        return view('pemilihbaru', $data);
    }

    public function tersaring()
    {
        helper('url');
        $db = db_connect();
        $data['title'] = 'PEMILIH TERSARING';
        $data['title_page'] = 'DAFTAR PEMILIH TERSARING';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $this->daftarDesa();
        $data['kabupaten'] = $this->kab;
        return view('tersaring', $data);
    }

    public function pindahTPS()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $stat = $this->request->getPost('stat');
        $builder = $db->table('pindahtps')->select('');
        $this->saringWilayah($builder);
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('MSLP', $lp);
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
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $dp4 = $this->request->getPost('dp4');
        $builder = $db->table('pemilihbaru2')->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
        $this->saringWilayah($builder);
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('JENIS_KELAMIN', $lp);
        if ($dp4 != '') {
            if ($dp4 > 0) {
                $builder->where('OLD_NAMA IS NOT NULL', null);
            } else {
                $builder->where('OLD_NAMA IS NULL', null);
            }
        }

        return $this->samarkanUntukPemeriksa(
    DataTable::of($builder)->addNumbering()->toJson(),
    [3]
);
    }

    public function getPemilihTersaring()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $ket = $this->request->getPost('ket');
        $builder = $db->table('tersaring')->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        $this->saringWilayah($builder);
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('LP', $lp);
        if ($ket != '') $builder->where('KET', $ket);

        return $this->samarkanUntukPemeriksa(
    DataTable::of($builder)->addNumbering()->toJson(),
    [3]
);
    }

    public function aktifPrint()
    {
        helper('url');
        $db = db_connect();
        $data['title'] = 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT';
        $data['title_page'] = 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $this->daftarDesa();
        $data['kabupaten'] = $this->kab;
        return view('pemilihAktif', $data);
    }

    public function aktif()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $builder = $db->table('pemilih_active')->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        $this->saringWilayah($builder);
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('JK', $lp);

        return $this->samarkanUntukPemeriksa(
    DataTable::of($builder)->addNumbering()->toJson(),
    [3, 4]
);
    }

    public function ms()
    {
        // Cetak daftar pemilih MS (Memenuhi Syarat) per desa/TPS.
        // Mengembalikan halaman HTML siap cetak dari browser.
        // Catatan Fase 0: blok dompdf lama yang tidak terjangkau (berada
        // sesudah "return view") sudah dihapus; pembuatan PDF sungguhan
        // dijadwalkan pada Fase 4 (Cetak & Laporan).
        $dt = new PemilihModel();

        $desa = $this->desaPengguna() ?? $this->request->getPostGet('desa');
        $tps = $this->request->getPostGet('tps');
        $klausa = ['DESA' => $desa, 'TPS' => $tps];
        if ($this->kecEfektif() !== null) {
            $klausa['KEC'] = $this->kecEfektif();
        }
        $data['dtTable'] = $this->samarkanBarisCetak($dt->pemilihMS($klausa));
        $data['kecamatan'] = $this->kecEfektif();
        $data['desa'] = strtoupper($desa);
        $data['tps'] = strtoupper($tps);
        $data['title'] = 'CETAK PEMILIH AKTIF - HASIL PANTARLIH | ' . strtoupper($desa) . '00' . $tps;

        $this->catatAkses('cetak_daftar_ms', 'Desa ' . strtoupper((string) $desa) . ' TPS ' . $tps . ' (' . count($data['dtTable']) . ' pemilih)');

        return view('print/print_ms', $data);
    }

    /**
     * Fase 4 — Daftar MS yang sama sebagai berkas PDF (dompdf),
     * dirender dari tampilan cetak yang sama dengan versi HTML.
     */
    public function msPdf()
    {
        $dt = new PemilihModel();

        $desa = $this->desaPengguna() ?? (string) $this->request->getGet('desa');
        $tps  = (string) $this->request->getGet('tps');
        $klausa = ['DESA' => $desa, 'TPS' => $tps];
        if ($this->kecEfektif() !== null) {
            $klausa['KEC'] = $this->kecEfektif();
        }
        $data['dtTable'] = $this->samarkanBarisCetak($dt->pemilihMS($klausa));
        $data['kecamatan'] = $this->kecEfektif();
        $data['desa'] = strtoupper($desa);
        $data['tps'] = strtoupper($tps);
        $data['title'] = 'CETAK PEMILIH AKTIF - HASIL PANTARLIH | ' . strtoupper($desa) . '00' . $tps;

        $html = view('print/print_ms', $data);

        $pdf = new \App\Libraries\pdfGenerator();
        $namaBerkas = 'Daftar-MS-' . preg_replace('/[^A-Za-z0-9]+/', '-', strtoupper($desa)) . '-TPS' . $tps;
        $isi = $pdf->generate($html, $namaBerkas, 'A4', 'landscape', false);

        $this->catatAkses('unduh_pdf_ms', 'Desa ' . strtoupper($desa) . ' TPS ' . $tps . ' (' . count($data['dtTable']) . ' pemilih)');

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaBerkas . '.pdf"')
            ->setBody($isi);
    }

}