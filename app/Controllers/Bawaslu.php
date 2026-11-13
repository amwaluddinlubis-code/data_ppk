<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use App\Libraries\PeriodePdpb;
use App\Libraries\Wilayah;

/**
 * Fase H (Pengembangan 2026) — Dukungan pengawasan Bawaslu atas
 * PDPB (Perbawaslu 1/2025): paket uji petik (agregat wilayah +
 * daftar perubahan per periode, NIK tersamar) dan register saran
 * perbaikan tertulis (Pasal 9) beserta tindak lanjutnya.
 */
class Bawaslu extends BaseController
{
    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return db_connect();
    }

    private function hanyaPetugas(): bool
    {
        return in_array($this->pengguna['peran'] ?? '', ['admin', 'operator'], true);
    }

    /** Samarkan NIK: 6 digit awal + 6 bintang + 4 digit akhir. */
    private function samar(string $nik): string
    {
        return strlen($nik) >= 10 ? substr($nik, 0, 6) . '******' . substr($nik, -4) : $nik;
    }

    public function index()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/')->with('galat', 'Halaman pengawasan hanya untuk admin dan operator.');
        }
        $db = $this->db();
        $saran = $db->table('saran_perbaikan s')
            ->select('s.*, p.nama AS periode_nama')
            ->join('periode p', 'p.id = s.periode_id', 'left')
            ->orderBy('s.id', 'DESC')->limit(100)->get()->getResultArray();
        $periodeList = $db->table('periode')->where('jenis', 'pdpb')->orderBy('id', 'DESC')->get()->getResultArray();
        $periode = PeriodePdpb::pastikanAktif();

        return view('bawaslu/index', [
            'title' => 'Pengawasan Bawaslu', 'title_page' => 'Pengawasan Bawaslu — Uji Petik & Saran Perbaikan',
            'saran' => $saran, 'periodeList' => $periodeList, 'periodeAktif' => $periode,
        ]);
    }

    public function saranSimpan()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $nomor = trim((string) ($this->request->getPost('nomor_surat') ?? ''));
        $uraian = trim((string) ($this->request->getPost('uraian') ?? ''));
        if ($nomor === '' || $uraian === '') {
            return redirect()->back()->with('galat', 'Nomor surat dan uraian saran wajib diisi.');
        }
        $kini = date('Y-m-d H:i:s');
        $this->db()->table('saran_perbaikan')->insert([
            'periode_id' => (int) ($this->request->getPost('periode_id') ?? 0) ?: null,
            'nomor_surat' => $nomor,
            'tanggal' => trim((string) ($this->request->getPost('tanggal') ?? '')) ?: null,
            'uraian' => $uraian,
            'penanggung_jawab' => trim((string) ($this->request->getPost('penanggung_jawab') ?? '')) ?: null,
            'status' => 'baru', 'dibuat_oleh' => (string) ($this->pengguna['username'] ?? ''),
            'created_at' => $kini, 'updated_at' => $kini,
        ]);
        $this->catatAkses('bawaslu', 'Mencatat saran perbaikan Bawaslu ' . $nomor);

        return redirect()->to('bawaslu')->with('sukses', 'Saran perbaikan tercatat.');
    }

    public function saranStatus()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        $status = (string) ($this->request->getPost('status') ?? '');
        if (! in_array($status, ['baru', 'ditindaklanjuti', 'selesai'], true)) {
            return redirect()->back()->with('galat', 'Status tidak dikenal.');
        }
        $this->db()->table('saran_perbaikan')->where('id', $id)->update([
            'status' => $status,
            'tindak_lanjut' => trim((string) ($this->request->getPost('tindak_lanjut') ?? '')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->catatAkses('bawaslu', 'Mengubah status saran #' . $id . ' menjadi ' . $status);

        return redirect()->to('bawaslu')->with('sukses', 'Status saran diperbarui.');
    }

    /** Data uji petik untuk periode terpilih: agregat + perubahan. */
    private function dataUjiPetik(int $periodeId): array
    {
        $db = $this->db();
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $saring = '';
        if ($kelPengguna !== null) {
            $saring = ' AND k.id = ' . $kelPengguna;
        } elseif ($kecEfektif !== null) {
            $saring = ' AND k.kecamatan_id = ' . $kecEfektif;
        }
        $agregat = $db->query(
            'SELECT kc.nama AS kecamatan, k.nama AS kelurahan,
                SUM(m.KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                SUM(m.KET IN (' . Keterangan::sqlTms() . ')) AS tms,
                SUM(m.JENIS_KELAMIN = "L") AS laki, SUM(m.JENIS_KELAMIN = "P") AS perempuan
             FROM kelurahan k JOIN kecamatan kc ON kc.id = k.kecamatan_id
             LEFT JOIN mhc m ON m.kelurahan_id = k.id
             WHERE 1=1' . $saring . '
             GROUP BY k.id, kc.nama, k.nama ORDER BY kc.nama, k.nama'
        )->getResultArray();
        $perubahan = $db->query(
            'SELECT r.created_at, r.aksi AS jenis, r.nik, r.dokumen_jenis, r.dokumen_keterangan AS keterangan, r.pengguna_username AS username
             FROM riwayat_perubahan r
             WHERE r.periode_id = ?' . ($kelPengguna !== null ? ' AND r.kelurahan_id = ' . $kelPengguna : '') . '
             ORDER BY r.id DESC LIMIT 2000',
            [$periodeId]
        )->getResultArray();

        return [$agregat, $perubahan];
    }

    public function ujiPetik()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $periodeId = (int) ($this->request->getGet('periode_id') ?? 0);
        $periode = $periodeId > 0
            ? $this->db()->table('periode')->where('id', $periodeId)->get()->getRowArray()
            : PeriodePdpb::pastikanAktif();
        [$agregat, $perubahan] = $this->dataUjiPetik((int) $periode['id']);
        foreach ($perubahan as &$p) {
            $p['nik'] = $this->samar((string) $p['nik']);
        }
        unset($p);
        $this->catatAkses('bawaslu', 'Membuka paket uji petik periode ' . ($periode['nama'] ?? ''));

        return view('bawaslu/uji_petik', [
            'title' => 'Paket Uji Petik Bawaslu', 'title_page' => 'Paket Uji Petik',
            'periode' => $periode, 'agregat' => $agregat, 'perubahan' => $perubahan,
        ]);
    }

    public function ujiPetikCsv()
    {
        if (! $this->hanyaPetugas()) {
            return redirect()->to('/');
        }
        $periodeId = (int) ($this->request->getGet('periode_id') ?? 0);
        $periode = $periodeId > 0
            ? $this->db()->table('periode')->where('id', $periodeId)->get()->getRowArray()
            : PeriodePdpb::pastikanAktif();
        [$agregat, $perubahan] = $this->dataUjiPetik((int) $periode['id']);
        $this->catatEkspor('Paket Uji Petik Bawaslu (CSV)', 'Periode ' . ($periode['nama'] ?? ''), count($perubahan));

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['PAKET UJI PETIK BAWASLU — PDPB'], ';');
        fputcsv($out, ['Periode', $periode['nama'] ?? ''], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['AGREGAT PER DESA/KELURAHAN'], ';');
        fputcsv($out, ['Kecamatan', 'Desa/Kelurahan', 'MS', 'TMS', 'Laki-laki', 'Perempuan'], ';');
        foreach ($agregat as $a) {
            fputcsv($out, [$a['kecamatan'], $a['kelurahan'], (int) $a['ms'], (int) $a['tms'], (int) $a['laki'], (int) $a['perempuan']], ';');
        }
        fputcsv($out, [], ';');
        fputcsv($out, ['DAFTAR PERUBAHAN PERIODE (NIK TERSAMAR)'], ';');
        fputcsv($out, ['Waktu', 'Aksi', 'NIK', 'Dokumen Bukti', 'Keterangan', 'Petugas'], ';');
        foreach ($perubahan as $p) {
            fputcsv($out, [$p['created_at'], $p['jenis'], $this->samar((string) $p['nik']), $p['dokumen_jenis'], $p['keterangan'], $p['username']], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="uji-petik-' . (int) $periode['id'] . '.csv"')
            ->setBody($csv);
    }
}
