<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Fase 1 — Login, keluar, ganti sandi, dan kelola pengguna (admin).
 *
 * Halaman login/keluar bersifat publik; semua metode lain berada di
 * balik filter `auth` (lihat Routes.php). Kelola pengguna hanya untuk
 * peran admin — diperiksa lagi di controller meski rutenya sudah
 * terlindung filter, supaya pemanggilan URL langsung pun tertolak.
 */
class Auth extends BaseController
{
    private function dataHalaman(string $judul): array
    {
        return [
            'title'      => $judul,
            'title_page' => $judul,
            'kecamatan'  => $this->kec,
        ];
    }

    private function wajibAdmin()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Halaman itu hanya untuk Admin PPK.');
        }

        return null;
    }

    // -- Masuk -------------------------------------------------------

    public function login()
    {
        if (session()->get('pengguna_id')) {
            return redirect()->to('/');
        }

        return view('auth/login', $this->dataHalaman('Masuk — Aplikasi Pemilih PPK'));
    }

    public function masuk()
    {
        $username = trim((string) $this->request->getPost('username'));
        $sandi    = (string) $this->request->getPost('sandi');

        $model    = new UserModel();
        $pengguna = $model->cariDenganUsername($username);

        if ($pengguna === null || ! $model->cekSandi($pengguna, $sandi)) {
            $this->catatAkses('masuk_gagal', 'Percobaan masuk gagal', $username, null);
            return redirect()->to('login')->withInput()
                ->with('galat', 'Nama pengguna atau kata sandi salah.');
        }

        if ((int) $pengguna['aktif'] !== 1) {
            return redirect()->to('login')->withInput()
                ->with('galat', 'Akun ini sudah dinonaktifkan. Hubungi Admin PPK.');
        }

        session()->regenerate();
        session()->set([
            'pengguna_id'               => (int) $pengguna['id'],
            'pengguna_nama'             => $pengguna['nama'],
            'pengguna_username'         => $pengguna['username'],
            'pengguna_peran'            => $pengguna['peran'],
            'pengguna_desa'             => $pengguna['desa'],
            'pengguna_harus_ganti_sandi' => (int) $pengguna['harus_ganti_sandi'],
        ]);

        $this->catatAkses('masuk', 'Masuk sebagai ' . UserModel::labelPeran($pengguna['peran']));

        if ((int) $pengguna['harus_ganti_sandi'] === 1) {
            return redirect()->to('ganti-sandi')
                ->with('info', 'Ini masuk pertama Anda — ganti dulu kata sandi awal.');
        }

        return redirect()->to('/');
    }

    public function keluar()
    {
        $this->catatAkses('keluar');
        session()->destroy();

        return redirect()->to('login')->with('info', 'Anda sudah keluar.');
    }

    // -- Ganti sandi -------------------------------------------------

    public function gantiSandi()
    {
        return view('auth/ganti_sandi', $this->dataHalaman('Ganti Kata Sandi'));
    }

    public function simpanSandi()
    {
        $sandiLama = (string) $this->request->getPost('sandi_lama');
        $sandiBaru = (string) $this->request->getPost('sandi_baru');
        $konfirmasi = (string) $this->request->getPost('sandi_baru_konfirmasi');

        $model    = new UserModel();
        $pengguna = $model->find(session()->get('pengguna_id'));

        if ($pengguna === null || ! $model->cekSandi($pengguna, $sandiLama)) {
            return redirect()->back()->with('galat', 'Kata sandi lama salah.');
        }

        if (strlen($sandiBaru) < 8) {
            return redirect()->back()->with('galat', 'Kata sandi baru minimal 8 karakter.');
        }

        if ($sandiBaru !== $konfirmasi) {
            return redirect()->back()->with('galat', 'Konfirmasi kata sandi baru tidak cocok.');
        }

        $model->update($pengguna['id'], [
            'password_hash'     => UserModel::hashSandi($sandiBaru),
            'harus_ganti_sandi' => 0,
        ]);
        session()->set('pengguna_harus_ganti_sandi', 0);

        return redirect()->to('/')->with('sukses', 'Kata sandi sudah diganti.');
    }

    // -- Kelola pengguna (admin) -------------------------------------

    public function pengguna()
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $data = $this->dataHalaman('Kelola Pengguna');
        $data['daftar'] = (new UserModel())->orderBy('peran', 'ASC')->orderBy('nama', 'ASC')->findAll();

        return view('auth/pengguna', $data);
    }

    public function tambahPengguna()
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $nama     = trim((string) $this->request->getPost('nama'));
        $username = trim((string) $this->request->getPost('username'));
        $peran    = (string) $this->request->getPost('peran');
        $desa     = trim((string) $this->request->getPost('desa'));
        $sandi    = (string) $this->request->getPost('sandi');

        $model = new UserModel();

        if ($nama === '' || $username === '' || $sandi === '') {
            return redirect()->back()->withInput()->with('galat', 'Nama, nama pengguna, dan kata sandi wajib diisi.');
        }

        if (! in_array($peran, ['admin', 'operator', 'viewer'], true)) {
            return redirect()->back()->withInput()->with('galat', 'Peran tidak dikenal.');
        }

        if (strlen($sandi) < 8) {
            return redirect()->back()->withInput()->with('galat', 'Kata sandi awal minimal 8 karakter.');
        }

        if ($model->cariDenganUsername($username) !== null) {
            return redirect()->back()->withInput()->with('galat', 'Nama pengguna "' . $username . '" sudah dipakai.');
        }

        $model->insert([
            'nama'              => $nama,
            'username'          => $username,
            'password_hash'     => UserModel::hashSandi($sandi),
            'peran'             => $peran,
            'desa'              => $desa !== '' ? $desa : null,
            'aktif'             => 1,
            'harus_ganti_sandi' => 1,
        ]);

        return redirect()->to('pengguna')
            ->with('sukses', 'Pengguna "' . $username . '" dibuat. Ia wajib ganti sandi saat pertama masuk.');
    }

    public function alihAktif($id = null)
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $model    = new UserModel();
        $pengguna = $model->find($id);

        if ($pengguna === null) {
            return redirect()->to('pengguna')->with('galat', 'Pengguna tidak ditemukan.');
        }

        if ((int) $pengguna['id'] === (int) session()->get('pengguna_id')) {
            return redirect()->to('pengguna')->with('galat', 'Akun sendiri tidak bisa dinonaktifkan.');
        }

        $model->update($pengguna['id'], ['aktif' => (int) $pengguna['aktif'] === 1 ? 0 : 1]);

        return redirect()->to('pengguna')->with('sukses', 'Status akun "' . $pengguna['username'] . '" diubah.');
    }

    public function resetSandi($id = null)
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $model    = new UserModel();
        $pengguna = $model->find($id);

        if ($pengguna === null) {
            return redirect()->to('pengguna')->with('galat', 'Pengguna tidak ditemukan.');
        }

        // Kata sandi sementara; pengguna dipaksa menggantinya saat masuk.
        $sementara = 'ppk12345';
        $model->update($pengguna['id'], [
            'password_hash'     => UserModel::hashSandi($sementara),
            'harus_ganti_sandi' => 1,
        ]);

        return redirect()->to('pengguna')
            ->with('sukses', 'Kata sandi "' . $pengguna['username'] . '" direset menjadi "' . $sementara . '" — ia wajib menggantinya saat masuk.');
    }
}
