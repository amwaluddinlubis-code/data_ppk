<?php

namespace App\Controllers;

use App\Libraries\Wilayah;

/**
 * Fase G (Pengembangan 2026) — Register permohonan perbaikan data
 * pemilih dari warga. Menjalankan hak subjek data untuk perbaikan
 * data pribadi (UU 27/2022 Pasal 30/32 jo. PP 33/2026): setiap
 * permohonan tercatat dengan tenggat tindak lanjut 3x24 jam dan
 * statusnya terpantau sampai selesai.
 */
class Permohonan extends BaseController
{
    public const UNSUR = [
        'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Kawin',
        'Alamat', 'RT/RW', 'Disabilitas', 'Kepemilikan KTP-el', 'Penempatan TPS', 'Lainnya',
    ];

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return db_connect();
    }

    private function hanyaPetugas(): bool
    {
        return in_array($this->pengguna['peran'] ?? '', ['admin', 'operator'], true);
    }

    public function index()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/')->with('galat', 'Halaman permohonan hanya untuk admin dan operator.');
        }
        $db = $this->db();
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();

        $b = $db->table('permohonan_perbaikan p')
            ->select('p.*, k.nama AS kelurahan, kc.nama AS kecamatan')
            ->join('kelurahan k', 'k.id = p.kelurahan_id', 'left')
            ->join('kecamatan kc', 'kc.id = k.kecamatan_id', 'left');
        if ($kelPengguna !== null) {
            $b->where('p.kelurahan_id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $b->where('k.kecamatan_id', $kecEfektif);
        }
        $rows = $b->orderBy('p.id', 'DESC')->limit(200)->get()->getResultArray();
        $kini = time();
        foreach ($rows as &$r) {
            $batas = strtotime((string) $r['created_at']) + 3 * 24 * 3600;
            $r['batas_sla'] = date('d-m-Y H:i', $batas);
            $r['lewat_sla'] = ! in_array($r['status'], ['selesai', 'ditolak'], true) && $kini > $batas;
        }
        unset($r);

        $qKel = $db->table('kelurahan k')->select('k.id, k.nama, kc.nama AS kecamatan')
            ->join('kecamatan kc', 'kc.id = k.kecamatan_id')->orderBy('kc.nama, k.nama');
        if ($kelPengguna !== null) {
            $qKel->where('k.id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $qKel->where('k.kecamatan_id', $kecEfektif);
        }
        $daftarKel = $qKel->get()->getResultArray();

        return view('permohonan/index', [
            'title' => 'Permohonan Perbaikan Data', 'title_page' => 'Permohonan Perbaikan Data Pemilih',
            'rows' => $rows, 'daftarKel' => $daftarKel, 'unsur' => self::UNSUR,
        ]);
    }

    public function simpan()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $db = $this->db();
        $nik = preg_replace('/\D/', '', (string) ($this->request->getPost('nik') ?? ''));
        $nama = trim((string) ($this->request->getPost('nama') ?? ''));
        $unsur = (string) ($this->request->getPost('unsur') ?? '');
        $pemohon = trim((string) ($this->request->getPost('pemohon') ?? ''));
        $kelId = (int) ($this->request->getPost('kelurahan_id') ?? 0);
        if (strlen($nik) !== 16 || $nama === '' || $pemohon === '' || ! in_array($unsur, self::UNSUR, true)) {
            return redirect()->back()->with('galat', 'Periksa isian: NIK 16 digit, nama pemilih, unsur, dan nama pemohon wajib benar.');
        }
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        if ($kelPengguna !== null) {
            $kelId = $kelPengguna;
        }
        $kini = date('Y-m-d H:i:s');
        $db->table('permohonan_perbaikan')->insert([
            'nik' => $nik, 'nama' => $nama, 'kelurahan_id' => $kelId > 0 ? $kelId : null,
            'unsur' => $unsur, 'uraian' => trim((string) ($this->request->getPost('uraian') ?? '')),
            'pemohon' => $pemohon, 'kontak' => trim((string) ($this->request->getPost('kontak') ?? '')) ?: null,
            'status' => 'baru', 'dibuat_oleh' => (string) ($this->pengguna['username'] ?? ''),
            'created_at' => $kini, 'updated_at' => $kini,
        ]);
        $this->catatAkses('permohonan', 'Mencatat permohonan perbaikan unsur ' . $unsur);

        return redirect()->to('permohonan')->with('sukses', 'Permohonan tercatat. Tenggat tindak lanjut 3x24 jam sejak pencatatan.');
    }

    public function status()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $db = $this->db();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $status = (string) ($this->request->getPost('status') ?? '');
        if (! in_array($status, ['baru', 'diproses', 'selesai', 'ditolak'], true)) {
            return redirect()->back()->with('galat', 'Status tidak dikenal.');
        }
        $baris = $db->table('permohonan_perbaikan')->where('id', $id)->get()->getRowArray();
        if ($baris === null) {
            return redirect()->back()->with('galat', 'Permohonan tidak ditemukan.');
        }
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        if ($kelPengguna !== null && (int) ($baris['kelurahan_id'] ?? 0) !== $kelPengguna) {
            return redirect()->back()->with('galat', 'Permohonan di luar kelurahan Anda.');
        }
        $data = [
            'status' => $status,
            'catatan' => trim((string) ($this->request->getPost('catatan') ?? '')) ?: $baris['catatan'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($status === 'selesai' && empty($baris['selesai_pada'])) {
            $data['selesai_pada'] = date('Y-m-d H:i:s');
        }
        $db->table('permohonan_perbaikan')->where('id', $id)->update($data);
        $this->catatAkses('permohonan', 'Mengubah status permohonan #' . $id . ' menjadi ' . $status);

        return redirect()->to('permohonan')->with('sukses', 'Status permohonan diperbarui.');
    }
}
