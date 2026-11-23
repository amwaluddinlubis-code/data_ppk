<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use App\Libraries\Konteks as KonteksLib;

/**
 * Konteks Penyelenggaraan (Fase A) — admin kabupaten mengatur rezim
 * aktif basis data: Pemilu (PKPU 7/2022), Pilkada (PKPU 7/2024),
 * atau PDPB (PKPU 1/2025 jo. 1/2026). Rezim menentukan label kode
 * keterangan 5/8 di seluruh aplikasi dan batas pemilih per TPS.
 */
class Konteks extends BaseController
{
    public function index()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Halaman konteks hanya untuk admin kabupaten.');
        }

        $legenda = [];
        foreach (array_keys(KonteksLib::JENIS) as $jenis) {
            $legenda[$jenis] = [
                '5'       => Keterangan::nama('5', $jenis),
                '8'       => Keterangan::nama('8', $jenis),
                'batas'   => match ($jenis) {
                    'pemilu'  => 300,
                    'pilkada' => 600,
                    default   => null,
                },
            ];
        }

        return view('konteks/index', [
            'title'   => 'Konteks Penyelenggaraan',
            'title_page' => 'Konteks Penyelenggaraan',
            'jenis'   => KonteksLib::jenis(),
            'nama'    => KonteksLib::nama(),
            'tanggal' => KonteksLib::tanggalPemungutan() ?? '',
            'daftarJenis' => KonteksLib::JENIS,
            'legenda' => $legenda,
            'versiLogin' => \App\Libraries\Pengaturan::versiLogin(),
        ]);
    }

    /** Admin kabupaten memilih tampilan halaman masuk (versi 1 atau 2). */
    public function simpanVersiLogin()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Hanya admin kabupaten yang dapat mengubah tampilan halaman masuk.');
        }

        $versi = (string) $this->request->getPost('versi');
        if (! in_array($versi, ['1', '2'], true)) {
            return redirect()->to('konteks')->with('galat', 'Versi tampilan halaman masuk tidak sah.');
        }

        db_connect()->table('pengaturan')->replace(['kunci' => 'versi_login', 'nilai' => $versi]);

        return redirect()->to('konteks')
            ->with('sukses', 'Tampilan halaman masuk kini memakai versi ' . $versi . '. Berlaku seketika untuk semua pengguna.');
    }

    public function simpan()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Hanya admin kabupaten yang dapat mengubah konteks.');
        }

        $jenis = (string) $this->request->getPost('jenis');
        if (! array_key_exists($jenis, KonteksLib::JENIS)) {
            return redirect()->to('konteks')->with('galat', 'Jenis penyelenggaraan tidak sah.');
        }

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '' || mb_strlen($nama) > 120) {
            return redirect()->to('konteks')->with('galat', 'Nama penyelenggaraan wajib diisi (maksimal 120 karakter).');
        }

        $tgl = trim((string) $this->request->getPost('tanggal'));
        if ($tgl !== '') {
            $cek = \DateTimeImmutable::createFromFormat('Y-m-d', $tgl);
            if (! $cek || $cek->format('Y-m-d') !== $tgl) {
                return redirect()->to('konteks')->with('galat', 'Tanggal pemungutan tidak sah.');
            }
        }

        $db = db_connect();
        $db->table('pengaturan')->replace(['kunci' => 'konteks_jenis', 'nilai' => $jenis]);
        $db->table('pengaturan')->replace(['kunci' => 'konteks_nama', 'nilai' => $nama]);
        $db->table('pengaturan')->replace(['kunci' => 'tanggal_pemungutan', 'nilai' => $tgl]);

        return redirect()->to('konteks')
            ->with('sukses', 'Konteks diubah ke ' . KonteksLib::JENIS[$jenis] . ' — ' . $nama . '. Label kode 5/8 dan batas TPS kini mengikuti rezim ini.');
    }
}
