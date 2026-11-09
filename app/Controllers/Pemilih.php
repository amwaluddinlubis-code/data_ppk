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
        $builder = $db->table('desa')->select('DESA');
        $data['title'] = 'PINDAH TPS';
        $data['title_page'] = 'DAFTAR PEMILIH PINDAH TPS';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $builder->get();
        return view('pemilih', $data);
    }

    public function baru()
    {
        helper('url');
        $db = db_connect();
        $builder = $db->table('desa')->select('DESA');
        $data['title'] = 'PEMILIH BARU';
        $data['title_page'] = 'DAFTAR PEMILIH BARU';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $builder->get();
        return view('pemilihbaru', $data);
    }

    public function tersaring()
    {
        helper('url');
        $db = db_connect();
        $builder = $db->table('desa')->select('DESA');
        $data['title'] = 'PEMILIH TERSARING';
        $data['title_page'] = 'DAFTAR PEMILIH TERSARING';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $builder->get();
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
        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }

    public function getPemilihBaru()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $dp4 = $this->request->getPost('dp4');
        $builder = $db->table('pemilihbaru2')->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
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

        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }

    public function getPemilihTersaring()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $ket = $this->request->getPost('ket');
        $builder = $db->table('tersaring')->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('LP', $lp);
        if ($ket != '') $builder->where('KET', $ket);

        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }

    public function aktifPrint()
    {
        helper('url');
        $db = db_connect();
        $builder = $db->table('desa')->select('DESA');
        $data['title'] = 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT';
        $data['title_page'] = 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT';
        $data['kecamatan'] = $this->kec;
        $data['arrDesa'] = $builder->get();
        return view('pemilihAktif', $data);
    }

    public function aktif()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $lp = $this->request->getPost('lp');
        $builder = $db->table('pemilih_active')->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('JK', $lp);

        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }

    public function ms()
    {
        // Cetak daftar pemilih MS (Memenuhi Syarat) per desa/TPS.
        // Mengembalikan halaman HTML siap cetak dari browser.
        // Catatan Fase 0: blok dompdf lama yang tidak terjangkau (berada
        // sesudah "return view") sudah dihapus; pembuatan PDF sungguhan
        // dijadwalkan pada Fase 4 (Cetak & Laporan).
        $dt = new PemilihModel();

        $desa = $this->request->getPostGet('desa');
        $tps = $this->request->getPostGet('tps');
        $data['dtTable'] = $dt->pemilihMS(['DESA' => $desa, 'TPS' => $tps]);
        $data['desa'] = strtoupper($desa);
        $data['tps'] = strtoupper($tps);
        $data['title'] = 'CETAK PEMILIH AKTIF - HASIL PANTARLIH | ' . strtoupper($desa) . '00' . $tps;

        return view('print/print_ms', $data);
    }

}