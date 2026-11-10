<?php

namespace App\Controllers;

use App\Libraries\AlasanPindah;
use App\Libraries\Keterangan;
use App\Libraries\Konteks;
use App\Libraries\Wilayah;

/**
 * Fase D — DPTb (pindah memilih), DPK, dan TPS lokasi khusus.
 *
 * Register pindah_memilih adalah jalur FORMAL pengajuan pindah
 * memilih (berbeda dari menu "Pindah TPS" yang menampilkan perpindahan
 * terdeteksi dari data coklit). Dari register ini tercetak Model
 * A-Surat Pindah Memilih (P15), Model A-Daftar Pemilih Pindahan per
 * TPS tujuan (P16), dan Model A-Rekap Pemilih Pindahan (P17).
 * Kelompok 3 dilengkapi P4 Model A-Tanda Bukti Terdaftar dan daftar
 * hadir DPK per TPS (format register resmi, diisi KPPS hari
 * pemungutan).
 */
class Dptb extends BaseController
{
    private function db()
    {
        return db_connect();
    }

    private function pengaturanPeta(): array
    {
        $peta = [];
        foreach ($this->db()->table('pengaturan')->get()->getResultArray() as $p) {
            $peta[$p['kunci']] = $p['nilai'];
        }

        return $peta;
    }

    private function namaKelurahan(int $id): ?array
    {
        return $this->db()->table('kelurahan')->select('kelurahan.*, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->where('kelurahan.id', $id)->get()->getRowArray();
    }

    public function index()
    {
        $db = $this->db();
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $ditemukan = null;
        if ($q !== '') {
            $pemilih = $db->table('mhc')->whereIn('KET', Keterangan::MS);
            $kelPengguna = Wilayah::kelurahanIdPengguna();
            $kecEfektif = Wilayah::kecEfektifId();
            if ($kelPengguna !== null) {
                $pemilih->where('kelurahan_id', $kelPengguna);
            } elseif ($kecEfektif !== null) {
                $pemilih->whereIn('kelurahan_id', $db->table('kelurahan')->select('id')->where('kecamatan_id', $kecEfektif));
            }
            $ditemukan = $pemilih->groupStart()->like('NIK', $q)->orLike('NAMA', $q)->groupEnd()
                ->orderBy('NAMA')->limit(20)->get()->getResultArray();
            foreach ($ditemukan as &$d) {
                $kel = $this->namaKelurahan((int) $d['kelurahan_id']);
                $d['desa'] = $kel['nama'] ?? '';
                $d['kecamatan'] = $kel['kecamatan'] ?? '';
            }
            unset($d);
        }

        $daftar = $db->table('pindah_memilih')->select('pindah_memilih.*,
                ka.nama AS asal_desa, kb.nama AS tujuan_desa, kecb.nama AS tujuan_kecamatan')
            ->join('kelurahan ka', 'ka.id = pindah_memilih.asal_kelurahan_id')
            ->join('kelurahan kb', 'kb.id = pindah_memilih.tujuan_kelurahan_id', 'left')
            ->join('kecamatan kecb', 'kecb.id = kb.kecamatan_id', 'left');
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null) {
            $daftar->groupStart()->where('asal_kelurahan_id', $kelPengguna)->orWhere('tujuan_kelurahan_id', $kelPengguna)->groupEnd();
        } elseif ($kecEfektif !== null) {
            $daftar->groupStart()
                ->whereIn('asal_kelurahan_id', $db->table('kelurahan')->select('id')->where('kecamatan_id', $kecEfektif))
                ->orWhereIn('tujuan_kelurahan_id', $db->table('kelurahan')->select('id')->where('kecamatan_id', $kecEfektif))
                ->groupEnd();
        }
        $daftar = $daftar->orderBy('pindah_memilih.id', 'DESC')->limit(100)->get()->getResultArray();

        $daftarKelurahan = $db->table('kelurahan')->select('kelurahan.id, kelurahan.nama, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->orderBy('kecamatan.nama')->orderBy('kelurahan.nama')->get()->getResultArray();

        $loksus = $db->table('tps_khusus')->select('tps_khusus.*, kelurahan.nama AS desa, kecamatan.nama AS kecamatan')
            ->join('kelurahan', 'kelurahan.id = tps_khusus.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
        if ($kelPengguna !== null) {
            $loksus->where('kelurahan_id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $loksus->where('kelurahan.kecamatan_id', $kecEfektif);
        }
        $loksus = $loksus->orderBy('kecamatan.nama')->orderBy('kelurahan.nama')->orderBy('tps_khusus.tps')->get()->getResultArray();

        return view('dptb/index', [
            'title' => 'DPTb & Lokasi Khusus', 'title_page' => 'DPTb — Pindah Memilih, DPK, Lokasi Khusus',
            'q' => $q, 'ditemukan' => $ditemukan, 'daftar' => $daftar,
            'daftarKelurahan' => $daftarKelurahan, 'loksus' => $loksus,
            'alasan' => AlasanPindah::DAFTAR,
            'konteksNama' => Konteks::nama(),
        ]);
    }

    /** Daftarkan pengajuan pindah memilih untuk satu pemilih MS. */
    public function simpan()
    {
        $db = $this->db();
        $mhcId = (int) $this->request->getPost('mhc_id');
        $pemilih = $db->table('mhc')->where('id', $mhcId)->whereIn('KET', Keterangan::MS)->get()->getRowArray();
        if ($pemilih === null) {
            return redirect()->to('dptb')->with('galat', 'Pemilih tidak ditemukan atau tidak memenuhi syarat.');
        }
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null && (int) $pemilih['kelurahan_id'] !== $kelPengguna) {
            return redirect()->to('dptb')->with('galat', 'Pemilih di luar desa Anda.');
        }
        if ($kecEfektif !== null) {
            $kelAsal = $this->namaKelurahan((int) $pemilih['kelurahan_id']);
            if ($kelAsal === null || (int) $kelAsal['kecamatan_id'] !== $kecEfektif) {
                return redirect()->to('dptb')->with('galat', 'Pemilih di luar kecamatan Anda.');
            }
        }
        $alasan = trim((string) $this->request->getPost('alasan'));
        if (! AlasanPindah::sah($alasan)) {
            return redirect()->to('dptb')->with('galat', 'Alasan pindah tidak dikenal.');
        }
        $tujuanKel = (int) ($this->request->getPost('tujuan_kelurahan_id') ?? 0);
        $tujuanTps = (int) ($this->request->getPost('tujuan_tps') ?? 0);
        $tujuanTeks = mb_substr(trim((string) $this->request->getPost('tujuan_teks')), 0, 200);
        if ($tujuanKel > 0) {
            if ($this->namaKelurahan($tujuanKel) === null || $tujuanTps <= 0) {
                return redirect()->to('dptb')->with('galat', 'TPS tujuan dalam kabupaten wajib dipilih.');
            }
            $tujuanTeks = '';
        } else {
            $tujuanKel = null;
            $tujuanTps = null;
            if ($tujuanTeks === '') {
                return redirect()->to('dptb')->with('galat', 'Isi tujuan: pilih desa+TPS dalam kabupaten, atau tulis wilayah tujuan di luar kabupaten.');
            }
        }
        if ($db->table('pindah_memilih')->where('mhc_id', $mhcId)->countAllResults() > 0) {
            return redirect()->to('dptb')->with('galat', 'Pemilih ini sudah terdaftar pindah memilih — hapus pengajuan lama dulu bila berubah.');
        }
        $sekarang = date('Y-m-d H:i:s');
        $db->table('pindah_memilih')->insert([
            'mhc_id' => $mhcId, 'nik' => $pemilih['NIK'], 'nama' => $pemilih['NAMA'],
            'asal_kelurahan_id' => (int) $pemilih['kelurahan_id'], 'asal_tps' => (int) $pemilih['TPS'],
            'tujuan_kelurahan_id' => $tujuanKel, 'tujuan_tps' => $tujuanTps,
            'tujuan_teks' => $tujuanTeks !== '' ? $tujuanTeks : null,
            'alasan' => $alasan,
            'konteks_jenis' => Konteks::jenis(), 'konteks_nama' => Konteks::nama(),
            'dibuat_oleh' => (int) session()->get('pengguna_id'),
            'created_at' => $sekarang, 'updated_at' => $sekarang,
        ]);

        return redirect()->to('dptb')->with('sukses', 'Pengajuan pindah memilih untuk ' . $pemilih['NAMA'] . ' terdaftar.');
    }

    /** Hapus pengajuan (tercakup: asal atau tujuan dalam wilayah pengguna). */
    public function hapus()
    {
        $db = $this->db();
        $id = (int) $this->request->getPost('id');
        $row = $db->table('pindah_memilih')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to('dptb')->with('galat', 'Pengajuan tidak ditemukan.');
        }
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $boleh = $kelPengguna === null && $kecEfektif === null;
        if ($kelPengguna !== null) {
            $boleh = (int) $row['asal_kelurahan_id'] === $kelPengguna || (int) ($row['tujuan_kelurahan_id'] ?? 0) === $kelPengguna;
        } elseif ($kecEfektif !== null) {
            $asal = $this->namaKelurahan((int) $row['asal_kelurahan_id']);
            $tujuan = $row['tujuan_kelurahan_id'] !== null ? $this->namaKelurahan((int) $row['tujuan_kelurahan_id']) : null;
            $boleh = ($asal !== null && (int) $asal['kecamatan_id'] === $kecEfektif)
                || ($tujuan !== null && (int) $tujuan['kecamatan_id'] === $kecEfektif);
        }
        if (! $boleh) {
            return redirect()->to('dptb')->with('galat', 'Pengajuan di luar cakupan wilayah Anda.');
        }
        $db->table('pindah_memilih')->where('id', $id)->delete();

        return redirect()->to('dptb')->with('sukses', 'Pengajuan dihapus.');
    }

    // -- Lokasi khusus ---------------------------------------------------

    public function loksusSimpan()
    {
        $db = $this->db();
        $kelId = (int) $this->request->getPost('kelurahan_id');
        $kel = $this->namaKelurahan($kelId);
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kel === null
            || ($kelPengguna !== null && $kelId !== $kelPengguna)
            || ($kecEfektif !== null && (int) $kel['kecamatan_id'] !== $kecEfektif)) {
            return redirect()->to('dptb')->with('galat', 'Desa lokasi khusus di luar cakupan.');
        }
        $jenis = trim((string) $this->request->getPost('jenis'));
        $jenisSah = ['Lapas/Rutan', 'Rumah Sakit', 'Panti Sosial', 'Panti Rehabilitasi', 'Pesantren', 'Kampus', 'Perusahaan/Perkebunan', 'Lainnya'];
        $tps = (int) $this->request->getPost('tps');
        $nama = mb_substr(trim((string) $this->request->getPost('nama_lokasi')), 0, 160);
        if (! in_array($jenis, $jenisSah, true) || $tps <= 0 || $nama === '') {
            return redirect()->to('dptb')->with('galat', 'Jenis, nomor TPS, dan nama lokasi wajib sah.');
        }
        $sekarang = date('Y-m-d H:i:s');
        $db->table('tps_khusus')->insert([
            'kelurahan_id' => $kelId, 'tps' => $tps, 'nama_lokasi' => $nama, 'jenis' => $jenis,
            'keterangan' => mb_substr(trim((string) $this->request->getPost('keterangan')), 0, 255) ?: null,
            'created_at' => $sekarang, 'updated_at' => $sekarang,
        ]);

        return redirect()->to('dptb')->with('sukses', 'TPS lokasi khusus terdaftar.');
    }

    public function loksusHapus()
    {
        $db = $this->db();
        $id = (int) $this->request->getPost('id');
        $row = $db->table('tps_khusus')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to('dptb')->with('galat', 'Lokasi khusus tidak ditemukan.');
        }
        $kel = $this->namaKelurahan((int) $row['kelurahan_id']);
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if (($kelPengguna !== null && (int) $row['kelurahan_id'] !== $kelPengguna)
            || ($kecEfektif !== null && ($kel === null || (int) $kel['kecamatan_id'] !== $kecEfektif))) {
            return redirect()->to('dptb')->with('galat', 'Lokasi khusus di luar cakupan.');
        }
        $db->table('tps_khusus')->where('id', $id)->delete();

        return redirect()->to('dptb')->with('sukses', 'Lokasi khusus dihapus.');
    }

    // -- Cetak Kelompok 3 ------------------------------------------------

    private function cekKelurahan(int $kelId): ?array
    {
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null && $kelId !== $kelPengguna) {
            return null;
        }
        $kel = $this->namaKelurahan($kelId);
        if ($kel === null || ($kecEfektif !== null && (int) $kel['kecamatan_id'] !== $kecEfektif)) {
            return null;
        }

        return $kel;
    }

    /** P15 — Model A-Surat Pindah Memilih per pengajuan (Lampiran XVII PKPU 7/2022). */
    public function cetakSurat()
    {
        $db = $this->db();
        $id = (int) ($this->request->getGet('id') ?? 0);
        $row = $db->table('pindah_memilih')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to('dptb')->with('galat', 'Pengajuan tidak ditemukan.');
        }
        $asal = $this->namaKelurahan((int) $row['asal_kelurahan_id']);
        $tujuan = $row['tujuan_kelurahan_id'] !== null ? $this->namaKelurahan((int) $row['tujuan_kelurahan_id']) : null;
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $boleh = $kelPengguna === null && $kecEfektif === null
            || ($kelPengguna !== null && ((int) $row['asal_kelurahan_id'] === $kelPengguna || (int) ($row['tujuan_kelurahan_id'] ?? 0) === $kelPengguna))
            || ($kecEfektif !== null && $asal !== null && (int) $asal['kecamatan_id'] === $kecEfektif);
        if (! $boleh || $asal === null) {
            return redirect()->to('dptb')->with('galat', 'Surat di luar cakupan wilayah Anda.');
        }
        $pemilih = $db->table('mhc')->where('id', (int) $row['mhc_id'])->get()->getRowArray();
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_surat', [
            'title' => 'Model A-Surat Pindah Memilih — ' . $row['nama'],
            'row' => $row, 'asal' => $asal, 'tujuan' => $tujuan, 'pemilih' => $pemilih,
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P16 — Model A-Daftar Pemilih Pindahan per TPS tujuan (Lampiran XVIII). */
    public function cetakDaftarPindahan()
    {
        $db = $this->db();
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $tps = (int) ($this->request->getGet('tps') ?? 0);
        $kel = $this->cekKelurahan($kelId);
        if ($kel === null || $tps <= 0) {
            return redirect()->to('dptb')->with('galat', 'Parameter daftar pindahan tidak lengkap/di luar cakupan.');
        }
        $rows = $db->table('pindah_memilih')->select('pindah_memilih.*, mhc.NKK, mhc.TMPTLHR, mhc.TGLLHR, mhc.STATUS, mhc.JENIS_KELAMIN, mhc.ALAMAT, mhc.RT, mhc.RW, mhc.DISABILITAS, mhc.EKTP, ka.nama AS asal_desa')
            ->join('mhc', 'mhc.id = pindah_memilih.mhc_id')
            ->join('kelurahan ka', 'ka.id = pindah_memilih.asal_kelurahan_id')
            ->where('pindah_memilih.tujuan_kelurahan_id', $kelId)->where('pindah_memilih.tujuan_tps', $tps)
            ->orderBy('pindah_memilih.nama')->get()->getResultArray();
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_daftar_pindahan', [
            'title' => 'Model A-Daftar Pemilih Pindahan — TPS ' . $tps . ' ' . $kel['nama'],
            'kel' => $kel, 'tps' => $tps, 'rows' => $rows, 'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P17 — Model A-Rekap Pemilih Pindahan per jenjang (Lampiran XXX). */
    public function cetakRekapPindahan()
    {
        $db = $this->db();
        $jenjang = (string) ($this->request->getGet('jenjang') ?? 'kabko');
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $baris = [];
        $label = '';
        if ($jenjang === 'pps') {
            $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
            $kel = $this->cekKelurahan($kelId);
            if ($kel === null) {
                return redirect()->to('dptb')->with('galat', 'Desa di luar cakupan.');
            }
            $masuk = (int) $db->table('pindah_memilih')->where('tujuan_kelurahan_id', $kelId)->countAllResults();
            $keluar = (int) $db->table('pindah_memilih')->where('asal_kelurahan_id', $kelId)->countAllResults();
            $baris[] = ['satuan' => $kel['nama'], 'masuk' => $masuk, 'keluar' => $keluar];
            $label = $kel['nama'] . ' — ' . $kel['kecamatan'];
        } elseif ($jenjang === 'ppk') {
            $kecId = $kecEfektif ?? (int) ($this->request->getGet('kecamatan_id') ?? 0);
            if ($kecId <= 0 || $kelPengguna !== null) {
                return redirect()->to('dptb')->with('galat', 'Kecamatan di luar cakupan.');
            }
            $rows = $db->query("SELECT k.nama AS satuan,
                    SUM(p.tujuan_kelurahan_id = k.id) AS masuk,
                    SUM(p.asal_kelurahan_id = k.id) AS keluar
                FROM kelurahan k LEFT JOIN pindah_memilih p
                    ON p.tujuan_kelurahan_id = k.id OR p.asal_kelurahan_id = k.id
                WHERE k.kecamatan_id = ? GROUP BY k.id, k.nama ORDER BY k.nama", [$kecId])->getResultArray();
            foreach ($rows as $r) {
                $baris[] = ['satuan' => $r['satuan'], 'masuk' => (int) $r['masuk'], 'keluar' => (int) $r['keluar']];
            }
            $kec = $db->table('kecamatan')->where('id', $kecId)->get()->getRowArray();
            $label = 'Kecamatan ' . ($kec['nama'] ?? '');
        } else {
            if ($kelPengguna !== null || $kecEfektif !== null) {
                return redirect()->to('dptb')->with('galat', 'Rekap kabupaten hanya untuk admin kabupaten.');
            }
            $masukPerKec = [];
            foreach ($db->query('SELECT kb.kecamatan_id AS kec, COUNT(*) AS c FROM pindah_memilih p JOIN kelurahan kb ON kb.id = p.tujuan_kelurahan_id GROUP BY kb.kecamatan_id')->getResultArray() as $r) {
                $masukPerKec[(int) $r['kec']] = (int) $r['c'];
            }
            $keluarPerKec = [];
            foreach ($db->query('SELECT ka.kecamatan_id AS kec, COUNT(*) AS c FROM pindah_memilih p JOIN kelurahan ka ON ka.id = p.asal_kelurahan_id GROUP BY ka.kecamatan_id')->getResultArray() as $r) {
                $keluarPerKec[(int) $r['kec']] = (int) $r['c'];
            }
            foreach ($db->table('kecamatan')->orderBy('nama')->get()->getResultArray() as $k) {
                $baris[] = ['satuan' => $k['nama'],
                    'masuk' => $masukPerKec[(int) $k['id']] ?? 0,
                    'keluar' => $keluarPerKec[(int) $k['id']] ?? 0];
            }
            $label = $this->pengaturanPeta()['kabupaten'] ?? '';
        }
        $total = ['masuk' => 0, 'keluar' => 0];
        foreach ($baris as $b) {
            $total['masuk'] += $b['masuk'];
            $total['keluar'] += $b['keluar'];
        }
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_rekap_pindahan', [
            'title' => 'Model A-Rekap Pemilih Pindahan — ' . $label,
            'jenjang' => $jenjang, 'label' => $label, 'baris' => $baris, 'total' => $total,
            'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P4 — Model A-Tanda Bukti Terdaftar per pemilih (Lampiran V): 4 bukti per lembar A4. */
    public function cetakTandaBukti()
    {
        $db = $this->db();
        $nik = trim((string) ($this->request->getGet('nik') ?? ''));
        $pemilih = $nik !== '' ? $db->table('mhc')->where('NIK', $nik)->whereIn('KET', Keterangan::MS)->get()->getRowArray() : null;
        if ($pemilih === null) {
            return redirect()->to('dptb')->with('galat', 'Pemilih dengan NIK itu tidak ditemukan dalam daftar MS.');
        }
        $kel = $this->cekKelurahan((int) $pemilih['kelurahan_id']);
        if ($kel === null) {
            return redirect()->to('dptb')->with('galat', 'Pemilih di luar cakupan wilayah Anda.');
        }
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_tanda_bukti', [
            'title' => 'Model A-Tanda Bukti Terdaftar — ' . $pemilih['NAMA'],
            'pemilih' => $pemilih, 'kel' => $kel, 'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** Daftar hadir DPK per TPS: register resmi kosong (diisi KPPS pada hari pemungutan). */
    public function cetakDaftarHadirDpk()
    {
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $tps = (int) ($this->request->getGet('tps') ?? 0);
        $kel = $this->cekKelurahan($kelId);
        if ($kel === null || $tps <= 0) {
            return redirect()->to('dptb')->with('galat', 'Parameter daftar hadir tidak lengkap/di luar cakupan.');
        }
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_daftar_hadir_dpk', [
            'title' => 'Daftar Hadir DPK — TPS ' . $tps . ' ' . $kel['nama'],
            'kel' => $kel, 'tps' => $tps, 'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** Daftar pemilih pada satu TPS lokasi khusus (dari data MS pada TPS itu). */
    public function cetakLoksus()
    {
        $db = $this->db();
        $id = (int) ($this->request->getGet('id') ?? 0);
        $lok = $db->table('tps_khusus')->where('id', $id)->get()->getRowArray();
        if ($lok === null) {
            return redirect()->to('dptb')->with('galat', 'Lokasi khusus tidak ditemukan.');
        }
        $kel = $this->cekKelurahan((int) $lok['kelurahan_id']);
        if ($kel === null) {
            return redirect()->to('dptb')->with('galat', 'Lokasi khusus di luar cakupan.');
        }
        $baris = $db->table('mhc')->where('kelurahan_id', (int) $lok['kelurahan_id'])->where('TPS', (int) $lok['tps'])
            ->whereIn('KET', Keterangan::MS)->orderBy('NAMA')->get()->getResultArray();
        $peta = $this->pengaturanPeta();

        return view('dptb/cetak_loksus', [
            'title' => 'Daftar Pemilih Lokasi Khusus — ' . $lok['nama_lokasi'],
            'lok' => $lok, 'kel' => $kel, 'baris' => $baris, 'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }
}
