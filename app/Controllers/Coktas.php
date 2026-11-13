<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use App\Libraries\PeriodePdpb;
use App\Libraries\Wilayah;

/**
 * Fase F3 (Pengembangan 2026) — Coktas: pencocokan dan penelitian
 * terbatas dalam PDPB (Surat Dinas KPU 228/PP.05-SD/13/2026).
 *
 * Daftar kerja berisi pemilih MS pada cakupan pengguna untuk
 * dicocokkan ke lapangan/dokumen; hasil per pemilih (sesuai /
 * tidak sesuai / tidak ditemukan) tercatat pada periode PDPB
 * berjalan. Halaman yang sama memuat pantau progres coklit
 * (TGL_COKLIT) dan coktas per kelurahan, rekap cetak, serta
 * filter tersimpan untuk daftar kerja.
 */
class Coktas extends BaseController
{
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
            return redirect()->to('/')->with('galat', 'Halaman Coktas hanya untuk admin dan operator.');
        }
        $db = $this->db();
        $periode = PeriodePdpb::pastikanAktif();
        $periodeId = (int) $periode['id'];
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();

        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        if ($kelPengguna !== null) {
            $kelId = $kelPengguna;
        }
        $tps = trim((string) ($this->request->getGet('tps') ?? ''));
        $status = (string) ($this->request->getGet('status') ?? 'semua');
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $hal = max(1, (int) ($this->request->getGet('hal') ?? 1));
        $perHal = 50;

        // Daftar kelurahan pada cakupan.
        $qKel = $db->table('kelurahan k')->select('k.id, k.nama, kc.nama AS kecamatan')
            ->join('kecamatan kc', 'kc.id = k.kecamatan_id')->orderBy('kc.nama, k.nama');
        if ($kelPengguna !== null) {
            $qKel->where('k.id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $qKel->where('k.kecamatan_id', $kecEfektif);
        }
        $daftarKel = $qKel->get()->getResultArray();

        // Daftar kerja: pemilih MS + hasil coktas periode berjalan.
        $b = $db->table('mhc m')
            ->select('m.ID AS id, m.NIK AS nik, m.NAMA AS nama, m.TPS AS tps, m.kelurahan_id, c.hasil, c.keterangan, c.oleh')
            ->join('coktas c', 'c.mhc_id = m.ID AND c.periode_id = ' . $periodeId, 'left')
            ->whereIn('m.KET', Keterangan::MS);
        if ($kelId > 0) {
            $b->where('m.kelurahan_id', $kelId);
        } elseif ($kelPengguna !== null) {
            $b->where('m.kelurahan_id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $b->whereIn('m.kelurahan_id', $db->table('kelurahan')->select('id')->where('kecamatan_id', $kecEfektif));
        }
        if ($tps !== '') {
            $b->where('m.TPS', (int) $tps);
        }
        if ($status === 'belum') {
            $b->where('c.id IS NULL', null, false);
        } elseif ($status === 'sudah') {
            $b->where('c.id IS NOT NULL', null, false);
        }
        if ($q !== '') {
            $b->like('m.NAMA', $q);
        }
        $total = $b->countAllResults(false);
        $rows = $b->orderBy('m.kelurahan_id, m.TPS, m.NAMA')->limit($perHal, ($hal - 1) * $perHal)->get()->getResultArray();

        // Progres per kelurahan: coklit (TGL_COKLIT) + coktas periode ini.
        $progres = $db->query(
            'SELECT k.id, k.nama, kc.nama AS kecamatan,
                COUNT(m.ID) AS ms,
                SUM(m.TGL_COKLIT IS NOT NULL AND m.TGL_COKLIT <> "") AS tercoklit,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id
                  WHERE c.periode_id = ? AND m2.kelurahan_id = k.id) AS tercoktas,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id
                  WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "sesuai") AS sesuai,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id
                  WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "tidak_sesuai") AS tidakSesuai,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id
                  WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "tidak_ditemukan") AS tidakDitemukan
             FROM kelurahan k JOIN kecamatan kc ON kc.id = k.kecamatan_id
             LEFT JOIN mhc m ON m.kelurahan_id = k.id AND m.KET IN (' . Keterangan::sqlMs() . ')
             ' . ($kelPengguna !== null ? 'WHERE k.id = ' . $kelPengguna : ($kecEfektif !== null ? 'WHERE k.kecamatan_id = ' . $kecEfektif : '')) . '
             GROUP BY k.id, k.nama, kc.nama ORDER BY kc.nama, k.nama',
            [$periodeId, $periodeId, $periodeId, $periodeId]
        )->getResultArray();

        $filterSaya = $db->table('filter_tersimpan')
            ->where('user_id', (int) ($this->pengguna['id'] ?? 0))->where('modul', 'coktas')
            ->orderBy('nama')->get()->getResultArray();

        return view('coktas/index', [
            'title' => 'Coktas PDPB', 'title_page' => 'Coktas — Pencocokan Terbatas',
            'periode' => $periode, 'rows' => $rows, 'total' => $total, 'hal' => $hal, 'perHal' => $perHal,
            'daftarKel' => $daftarKel, 'progres' => $progres, 'filterSaya' => $filterSaya,
            'f' => ['kelurahan_id' => $kelId, 'tps' => $tps, 'status' => $status, 'q' => $q],
        ]);
    }

    public function simpan()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/')->with('galat', 'Halaman Coktas hanya untuk admin dan operator.');
        }
        $db = $this->db();
        $mhcId = (int) ($this->request->getPost('mhc_id') ?? 0);
        $hasil = (string) ($this->request->getPost('hasil') ?? '');
        $keterangan = trim((string) ($this->request->getPost('keterangan') ?? ''));
        if (! in_array($hasil, ['sesuai', 'tidak_sesuai', 'tidak_ditemukan'], true)) {
            return redirect()->back()->with('galat', 'Hasil coktas tidak dikenal.');
        }
        $pemilih = $db->table('mhc')->select('ID, kelurahan_id, TPS')->where('ID', $mhcId)
            ->whereIn('KET', Keterangan::MS)->get()->getRowArray();
        if ($pemilih === null) {
            return redirect()->back()->with('galat', 'Pemilih tidak ditemukan atau bukan MS.');
        }
        // Cakupan: PPS kelurahan sendiri, PPK kecamatan sendiri.
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null && (int) $pemilih['kelurahan_id'] !== $kelPengguna) {
            return redirect()->back()->with('galat', 'Pemilih di luar kelurahan Anda.');
        }
        if ($kecEfektif !== null) {
            $kecPemilih = $db->table('kelurahan')->select('kecamatan_id')->where('id', $pemilih['kelurahan_id'])->get()->getRowArray();
            if ($kecPemilih === null || (int) $kecPemilih['kecamatan_id'] !== $kecEfektif) {
                return redirect()->back()->with('galat', 'Pemilih di luar kecamatan Anda.');
            }
        }
        $periode = PeriodePdpb::pastikanAktif();
        $kini = date('Y-m-d H:i:s');
        $ada = $db->table('coktas')->where('periode_id', $periode['id'])->where('mhc_id', $mhcId)->get()->getRowArray();
        $data = [
            'kelurahan_id' => (int) $pemilih['kelurahan_id'], 'tps' => (int) $pemilih['TPS'],
            'hasil' => $hasil, 'keterangan' => $keterangan !== '' ? $keterangan : null,
            'oleh' => (string) ($this->pengguna['username'] ?? ''), 'updated_at' => $kini,
        ];
        if ($ada !== null) {
            $db->table('coktas')->where('id', $ada['id'])->update($data);
        } else {
            $db->table('coktas')->insert($data + ['periode_id' => (int) $periode['id'], 'mhc_id' => $mhcId, 'created_at' => $kini]);
        }
        $this->catatAkses('coktas', 'Menyimpan hasil coktas pemilih ID ' . $mhcId . ': ' . $hasil);

        return redirect()->back()->with('sukses', 'Hasil coktas tersimpan.');
    }

    public function cetakRekap()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $db = $this->db();
        $periode = PeriodePdpb::pastikanAktif();
        $periodeId = (int) $periode['id'];
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $progres = $db->query(
            'SELECT k.nama, kc.nama AS kecamatan, COUNT(m.ID) AS ms,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id WHERE c.periode_id = ? AND m2.kelurahan_id = k.id) AS tercoktas,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "sesuai") AS sesuai,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "tidak_sesuai") AS tidakSesuai,
                (SELECT COUNT(*) FROM coktas c JOIN mhc m2 ON m2.ID = c.mhc_id WHERE c.periode_id = ? AND m2.kelurahan_id = k.id AND c.hasil = "tidak_ditemukan") AS tidakDitemukan
             FROM kelurahan k JOIN kecamatan kc ON kc.id = k.kecamatan_id
             LEFT JOIN mhc m ON m.kelurahan_id = k.id AND m.KET IN (' . Keterangan::sqlMs() . ')
             ' . ($kelPengguna !== null ? 'WHERE k.id = ' . $kelPengguna : ($kecEfektif !== null ? 'WHERE k.kecamatan_id = ' . $kecEfektif : '')) . '
             GROUP BY k.id, k.nama, kc.nama ORDER BY kc.nama, k.nama',
            [$periodeId, $periodeId, $periodeId, $periodeId]
        )->getResultArray();
        $pengaturan = $db->table('pengaturan')->get()->getResultArray();
        $meta = [];
        foreach ($pengaturan as $p) {
            $meta[$p['kunci']] = $p['nilai'];
        }

        return view('coktas/cetak_rekap', [
            'title' => 'Rekap Coktas', 'title_page' => 'Rekap Coktas',
            'periode' => $periode, 'progres' => $progres, 'meta' => $meta,
        ]);
    }

    /** Filter tersimpan (Fase H) untuk daftar kerja coktas. */
    public function simpanFilter()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $nama = trim((string) ($this->request->getPost('nama_filter') ?? ''));
        if ($nama === '') {
            return redirect()->back()->with('galat', 'Nama filter wajib diisi.');
        }
        $param = [
            'kelurahan_id' => (int) ($this->request->getPost('kelurahan_id') ?? 0),
            'tps' => trim((string) ($this->request->getPost('tps') ?? '')),
            'status' => (string) ($this->request->getPost('status') ?? 'semua'),
            'q' => trim((string) ($this->request->getPost('q') ?? '')),
        ];
        $this->db()->table('filter_tersimpan')->insert([
            'user_id' => (int) ($this->pengguna['id'] ?? 0), 'nama' => $nama, 'modul' => 'coktas',
            'parameter' => json_encode($param), 'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('sukses', 'Filter "' . $nama . '" tersimpan.');
    }

    public function hapusFilter(int $id)
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $this->db()->table('filter_tersimpan')->where('id', $id)
            ->where('user_id', (int) ($this->pengguna['id'] ?? 0))->delete();

        return redirect()->to('coktas')->with('sukses', 'Filter terhapus.');
    }
}
