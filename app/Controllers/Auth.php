<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Login, keluar, ganti sandi, dan kelola pengguna (admin).
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
            'kabupaten'  => $this->kab,
        ];
    }

    private function wajibAdmin()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Halaman itu hanya untuk Admin Kabupaten.');
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
                ->with('galat', 'Akun ini sudah dinonaktifkan. Hubungi Admin Kabupaten.');
        }

        // Cakupan wilayah akun kini berbasis ID master; nama wilayah
        // diselesaikan sekali di sini dan disimpan di sesi untuk
        // tampilan & saringan view (kolom KEC/DESA hasil JOIN).
        $db = db_connect();
        $namaKec = null;
        $namaDesa = null;
        if (! empty($pengguna['kelurahan_id'])) {
            $kel = $db->table('kelurahan')->where('id', (int) $pengguna['kelurahan_id'])->get()->getRowArray();
            if ($kel !== null) {
                $namaDesa = $kel['nama'];
            }
        }
        if (! empty($pengguna['kecamatan_id'])) {
            $kecRow = $db->table('kecamatan')->where('id', (int) $pengguna['kecamatan_id'])->get()->getRowArray();
            if ($kecRow !== null) {
                $namaKec = $kecRow['nama'];
            }
        }

        session()->regenerate();
        session()->remove('grup_db');    // sisa Fase 6
        session()->remove('kec_filter'); // saringan wilayah mulai bersih
        session()->remove('kec_filter_id');
        session()->set([
            'pengguna_id'               => (int) $pengguna['id'],
            'pengguna_nama'             => $pengguna['nama'],
            'pengguna_username'         => $pengguna['username'],
            'pengguna_peran'            => $pengguna['peran'],
            'pengguna_kecamatan_id'     => ! empty($pengguna['kecamatan_id']) ? (int) $pengguna['kecamatan_id'] : null,
            'pengguna_kelurahan_id'     => ! empty($pengguna['kelurahan_id']) ? (int) $pengguna['kelurahan_id'] : null,
            'pengguna_kecamatan'        => $namaKec,
            'pengguna_desa'             => $namaDesa,
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
        // Perkaya baris pengguna dengan nama wilayah dari master
        // (tabel users kini hanya menyimpan ID cakupan).
        $master = new \App\Libraries\WilayahMaster(db_connect());
        $daftar = (new UserModel())->orderBy('peran', 'ASC')->orderBy('nama', 'ASC')->findAll();
        foreach ($daftar as &$u) {
            $u['kecamatan'] = ! empty($u['kecamatan_id'])
                ? ($master->kecamatan((int) $u['kecamatan_id'])['nama'] ?? null) : null;
            $u['desa'] = ! empty($u['kelurahan_id'])
                ? ($master->kelurahan((int) $u['kelurahan_id'])['nama'] ?? null) : null;
        }
        unset($u);
        $data['daftar'] = $daftar;
        $data['daftarKecamatan'] = $this->daftarKecamatanLengkap();
        $data['daftarDesaSemua'] = $this->daftarDesaSemua();

        return view('auth/pengguna', $data);
    }

    /** @return list<array{id: int, nama: string, kecamatan_id: int, kecamatan_nama: string}> Semua kelurahan di kabupaten (untuk pilihan PPS). */
    private function daftarDesaSemua(): array
    {
        try {
            $rows = db_connect()->table('kelurahan')
                ->select('kelurahan.id AS id, kelurahan.nama AS nama, kelurahan.kecamatan_id AS kecamatan_id, kecamatan.nama AS kecamatan_nama')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->orderBy('kelurahan.nama', 'ASC')->get()->getResultArray();

            return array_map(static fn ($r) => [
                'id'             => (int) $r['id'],
                'nama'           => (string) $r['nama'],
                'kecamatan_id'   => (int) $r['kecamatan_id'],
                'kecamatan_nama' => (string) $r['kecamatan_nama'],
            ], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    public function tambahPengguna()
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $nama     = trim((string) $this->request->getPost('nama'));
        $username = trim((string) $this->request->getPost('username'));
        $peran    = (string) $this->request->getPost('peran');
        $kecId    = (int) $this->request->getPost('kecamatan_id') ?: null;
        $kelId    = (int) $this->request->getPost('kelurahan_id') ?: null;
        $sandi    = (string) $this->request->getPost('sandi');

        $model = new UserModel();

        if ($nama === '' || $username === '' || $sandi === '') {
            return redirect()->back()->withInput()->with('galat', 'Nama, nama pengguna, dan kata sandi wajib diisi.');
        }

        if (! in_array($peran, ['admin', 'operator', 'viewer'], true)) {
            return redirect()->back()->withInput()->with('galat', 'Peran tidak dikenal.');
        }

        // Admin selalu tingkat kabupaten (tanpa cakupan); peran lain
        // wajib punya cakupan kecamatan, dan desa harus berada di
        // kecamatan itu (keduanya ID tabel master wilayah).
        $master = new \App\Libraries\WilayahMaster(db_connect());
        if ($peran === 'admin') {
            $kecId = null;
            $kelId = null;
        } else {
            if ($kecId === null || $master->kecamatan($kecId) === null) {
                return redirect()->back()->withInput()->with('galat', 'Pilih kecamatan cakupan untuk pengguna PPK/PPS.');
            }
            if ($kelId !== null) {
                $kel = $master->kelurahan($kelId);
                if ($kel === null || (int) $kel['kecamatan_id'] !== $kecId) {
                    return redirect()->back()->withInput()->with('galat', 'Desa itu tidak berada di kecamatan terpilih.');
                }
            }
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
            'kecamatan_id'      => $kecId,
            'kelurahan_id'      => $kelId,
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

    /** Kata sandi sementara acak kriptografis, dikelompokkan agar mudah disalin. */
    private function buatSandiSementara(): string
    {
        $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $batas = strlen($huruf) - 1;
        $mentah = '';
        for ($i = 0; $i < 16; $i++) {
            $mentah .= $huruf[random_int(0, $batas)];
        }

        return implode('-', str_split($mentah, 4));
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

        // Kata sandi sementara dibuat acak untuk setiap reset dan
        // hanya diteruskan satu kali lewat flashdata ke panel khusus
        // di halaman pengguna; tidak ada sandi bawaan yang sama untuk
        // semua akun. Pengguna tetap dipaksa menggantinya saat masuk.
        $sementara = $this->buatSandiSementara();
        $model->update($pengguna['id'], [
            'password_hash'     => UserModel::hashSandi($sementara),
            'harus_ganti_sandi' => 1,
        ]);
        $this->catatAkses('reset_sandi', 'Reset sandi pengguna ' . $pengguna['username']);

        return redirect()->to('pengguna')
            ->with('sukses', 'Kata sandi "' . $pengguna['username'] . '" sudah direset.')
            ->with('sandi_reset', [
                'username' => $pengguna['username'],
                'sandi'    => $sementara,
            ]);
    }
}
