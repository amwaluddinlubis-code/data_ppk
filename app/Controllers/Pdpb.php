<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use App\Libraries\Konteks;
use App\Libraries\PeriodePdpb;
use App\Libraries\Wilayah;

/**
 * PDPB — Pemutakhiran Data Pemilih Berkelanjutan (Fase B;
 * PKPU 1/2025 jo. PKPU 1/2026).
 *
 * Rekap perubahan per periode dihitung dari riwayat_perubahan yang
 * terikat periode berjalan: Baru (tambah), TMS (ubah masuk kode
 * 1–8, dirinci per kode), Ubah elemen data, Hapus, dan Pulih.
 * "Pemilih Awal" diturunkan dari Pemilih Akhir (data hidup) dikurang
 * perubahan bersih periode — tanpa tabel potret per baris.
 * Penutupan periode (admin) membekukan arsip agregat per wilayah.
 */
class Pdpb extends BaseController
{
    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return db_connect();
    }

    public function index()
    {
        $db = $this->db();
        $periode = PeriodePdpb::pastikanAktif();
        $kecId = Wilayah::kecEfektifId();
        $kelId = Wilayah::kelurahanIdPengguna();

        $rekap = $this->hitungRekap((int) $periode['id'], $kecId, $kelId);
        $hidup = $this->hitungHidup($kecId, $kelId);

        // Gabung per kelurahan: rekap perubahan + jumlah akhir hidup.
        $perKel = [];
        foreach ($hidup as $kelurahanId => $h) {
            $r = $rekap['perKel'][$kelurahanId] ?? $this->kerangkaRekap();
            $perKel[$kelurahanId] = [
                'nama' => $h['nama'], 'kecamatan' => $h['kecamatan'], 'kecamatan_id' => $h['kecamatan_id'],
                'rekap' => $r, 'akhir' => $h['ms'], 'awal' => $h['ms'] - $r['delta'],
            ];
        }
        foreach ($rekap['perKel'] as $kelurahanId => $r) {
            if (! isset($perKel[$kelurahanId])) {
                $perKel[$kelurahanId] = [
                    'nama' => $r['nama'], 'kecamatan' => $r['kecamatan'], 'kecamatan_id' => $r['kecamatan_id'],
                    'rekap' => $r, 'akhir' => 0, 'awal' => -$r['delta'],
                ];
            }
        }
        uasort($perKel, static fn ($a, $b) => [$a['kecamatan'], $a['nama']] <=> [$b['kecamatan'], $b['nama']]);

        // Arsip periode lampau pada tingkat cakupan pengguna.
        $arsipPeriode = $db->table('periode')->where('jenis', 'pdpb')
            ->where('status', 'ditutup')->orderBy('id', 'DESC')->get()->getResultArray();
        $arsipRingkasan = [];
        foreach ($arsipPeriode as $p) {
            $tingkat = $kelId !== null ? 'kelurahan' : ($kecId !== null ? 'kecamatan' : 'kabupaten');
            $q = $db->table('arsip_rekap')->where('periode_id', (int) $p['id'])->where('tingkat', $tingkat);
            if ($tingkat === 'kecamatan') {
                $q->where('kecamatan_id', $kecId);
            } elseif ($tingkat === 'kelurahan') {
                $q->where('kelurahan_id', $kelId);
            }
            $baris = $q->get()->getRowArray();
            if ($baris !== null) {
                $arsipRingkasan[] = ['periode' => $p, 'metrik' => json_decode((string) $baris['metrik'], true) ?? []];
            }
        }

        // Detail satu arsip (per kelurahan) bila diminta.
        $detailArsip = null;
        $arsipId = (int) ($this->request->getGet('arsip') ?? 0);
        if ($arsipId > 0) {
            $p = $db->table('periode')->where('id', $arsipId)->where('status', 'ditutup')->get()->getRowArray();
            if ($p !== null) {
                $q = $db->table('arsip_rekap')->select('arsip_rekap.*, kelurahan.nama AS kel_nama, kecamatan.nama AS kec_nama')
                    ->join('kelurahan', 'kelurahan.id = arsip_rekap.kelurahan_id', 'left')
                    ->join('kecamatan', 'kecamatan.id = arsip_rekap.kecamatan_id', 'left')
                    ->where('arsip_rekap.periode_id', $arsipId)->where('arsip_rekap.tingkat', 'kelurahan');
                if ($kelId !== null) {
                    $q->where('arsip_rekap.kelurahan_id', $kelId);
                } elseif ($kecId !== null) {
                    $q->where('arsip_rekap.kecamatan_id', $kecId);
                }
                $detailArsip = ['periode' => $p, 'baris' => $q->orderBy('kecamatan.nama')->orderBy('kelurahan.nama')->get()->getResultArray()];
            }
        }

        $daftarKelurahan = $db->table('kelurahan')
            ->select('kelurahan.id, kelurahan.nama, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->orderBy('kecamatan.nama')->orderBy('kelurahan.nama');
        if ($kelId !== null) {
            $daftarKelurahan->where('kelurahan.id', $kelId);
        } elseif ($kecId !== null) {
            $daftarKelurahan->where('kelurahan.kecamatan_id', $kecId);
        }

        return view('pdpb/index', [
            'title'    => 'PDPB Berkelanjutan',
            'title_page' => 'PDPB — Pemutakhiran Data Pemilih Berkelanjutan',
            'periode'  => $periode,
            'perKel'   => $perKel,
            'total'    => $rekap['total'],
            'tmsKode'  => $rekap['tmsKode'],
            'dokumenAda' => $rekap['dokumenAda'],
            'dokumenTotal' => $rekap['dokumenTotal'],
            'arsip'    => $arsipRingkasan,
            'detailArsip' => $detailArsip,
            'daftarKelurahan' => $daftarKelurahan->get()->getResultArray(),
            'konteksNama' => Konteks::nama(),
            'adalahAdmin' => session()->get('pengguna_peran') === 'admin',
        ]);
    }

    /** Tutup periode berjalan: bekukan arsip agregat, buka periode baru. Admin saja. */
    public function tutup()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('pdpb')->with('galat', 'Hanya admin kabupaten yang dapat menutup periode.');
        }

        $db = $this->db();
        $periode = PeriodePdpb::pastikanAktif();
        $pid = (int) $periode['id'];

        $rekap = $this->hitungRekap($pid, null, null);
        $hidup = $this->hitungHidup(null, null);
        $perKet = $this->hitungPerKet();

        $db->transStart();
        $perKec = [];
        foreach ($hidup as $kelurahanId => $h) {
            $r = $rekap['perKel'][$kelurahanId] ?? $this->kerangkaRekap();
            $metrik = [
                'ms' => $h['ms'], 'laki' => $h['laki'], 'perempuan' => $h['perempuan'], 'kk' => $h['kk'],
                'perKet' => $perKet[$kelurahanId] ?? [],
                'rekap' => ['baru' => $r['baru'], 'tms' => $r['tms'], 'ubah' => $r['ubah'], 'hapus' => $r['hapus'], 'pulih' => $r['pulih']],
            ];
            $db->table('arsip_rekap')->insert([
                'periode_id' => $pid, 'tingkat' => 'kelurahan',
                'kecamatan_id' => $h['kecamatan_id'], 'kelurahan_id' => $kelurahanId,
                'metrik' => json_encode($metrik), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $kec = $h['kecamatan_id'];
            $perKec[$kec] ??= ['ms' => 0, 'laki' => 0, 'perempuan' => 0, 'kk' => 0, 'perKet' => [], 'rekap' => ['baru' => 0, 'tms' => 0, 'ubah' => 0, 'hapus' => 0, 'pulih' => 0]];
            $this->jumlahkan($perKec[$kec], $metrik);
        }
        $kab = ['ms' => 0, 'laki' => 0, 'perempuan' => 0, 'kk' => 0, 'perKet' => [], 'rekap' => ['baru' => 0, 'tms' => 0, 'ubah' => 0, 'hapus' => 0, 'pulih' => 0]];
        foreach ($perKec as $kecId => $m) {
            $db->table('arsip_rekap')->insert([
                'periode_id' => $pid, 'tingkat' => 'kecamatan', 'kecamatan_id' => $kecId,
                'metrik' => json_encode($m), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->jumlahkan($kab, $m);
        }
        $db->table('arsip_rekap')->insert([
            'periode_id' => $pid, 'tingkat' => 'kabupaten',
            'metrik' => json_encode($kab), 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('periode')->where('id', $pid)->update([
            'status' => 'ditutup', 'selesai' => date('Y-m-d'),
            'ditutup_oleh' => session()->get('pengguna_username'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $namaBaru = PeriodePdpb::namaBawaan(new \DateTimeImmutable());
        $sudahAda = $db->table('periode')->where('nama', $namaBaru)->countAllResults() > 0;
        $db->table('periode')->insert([
            'jenis' => 'pdpb', 'nama' => $namaBaru . ($sudahAda ? ' (lanjutan)' : ''),
            'mulai' => date('Y-m-d'), 'status' => 'berjalan',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('pdpb')->with('galat', 'Gagal menutup periode. Coba lagi.');
        }

        return redirect()->to('pdpb')->with('sukses', 'Periode "' . $periode['nama'] . '" ditutup dan arsip rekapnya dibekukan. Periode baru telah dibuka.');
    }

    /** Ganti nama periode berjalan. Admin saja. */
    public function gantiNama()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('pdpb')->with('galat', 'Hanya admin kabupaten.');
        }
        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '' || mb_strlen($nama) > 120) {
            return redirect()->to('pdpb')->with('galat', 'Nama periode tidak sah.');
        }
        $periode = PeriodePdpb::pastikanAktif();
        $this->db()->table('periode')->where('id', (int) $periode['id'])
            ->update(['nama' => $nama, 'updated_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('pdpb')->with('sukses', 'Nama periode diubah.');
    }

    /** Cetak Model A-Daftar Pemilih-PDPB satu desa/kelurahan. */
    public function cetak()
    {
        $db = $this->db();
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null && $kelId !== $kelPengguna) {
            return redirect()->to('pdpb')->with('galat', 'Di luar cakupan wilayah Anda.');
        }

        $kel = $db->table('kelurahan')->select('kelurahan.*, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->where('kelurahan.id', $kelId)->get()->getRowArray();
        if ($kel === null || ($kecEfektif !== null && (int) $kel['kecamatan_id'] !== $kecEfektif)) {
            return redirect()->to('pdpb')->with('galat', 'Desa/kelurahan tidak ditemukan dalam cakupan.');
        }

        $baris = $db->table('mhc')->where('kelurahan_id', $kelId)
            ->orderBy('TPS')->orderBy('NAMA')->get()->getResultArray();
        $pengaturan = $db->table('pengaturan')->get()->getResultArray();
        $peta = [];
        foreach ($pengaturan as $p) {
            $peta[$p['kunci']] = $p['nilai'];
        }

        return view('pdpb/cetak', [
            'title' => 'Model A-Daftar Pemilih-PDPB — ' . $kel['nama'],
            'kel' => $kel, 'baris' => $baris,
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
            'periode' => PeriodePdpb::pastikanAktif(),
        ]);
    }

    // -- Hitungan -----------------------------------------------------

    private function kerangkaRekap(): array
    {
        return ['nama' => '', 'kecamatan' => '', 'kecamatan_id' => 0,
            'baru' => 0, 'tms' => 0, 'ubah' => 0, 'hapus' => 0, 'pulih' => 0, 'delta' => 0];
    }

    /**
     * Klasifikasi perubahan satu periode dari riwayat.
     *
     * @return array{perKel: array<int, array>, total: array, tmsKode: array<string, int>, dokumenAda: int, dokumenTotal: int}
     */
    private function hitungRekap(int $periodeId, ?int $kecId, ?int $kelId): array
    {
        $db = $this->db();
        $q = $db->table('riwayat_perubahan')
            ->select('riwayat_perubahan.aksi, riwayat_perubahan.data_lama, riwayat_perubahan.data_baru, riwayat_perubahan.kelurahan_id, riwayat_perubahan.dokumen_jenis, kelurahan.nama AS kel_nama, kecamatan.nama AS kec_nama, kelurahan.kecamatan_id')
            ->join('kelurahan', 'kelurahan.id = riwayat_perubahan.kelurahan_id', 'left')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id', 'left')
            ->where('riwayat_perubahan.periode_id', $periodeId)
            ->where('riwayat_perubahan.tabel', 'mhc');
        if ($kelId !== null) {
            $q->where('riwayat_perubahan.kelurahan_id', $kelId);
        } elseif ($kecId !== null) {
            $q->where('riwayat_perubahan.kecamatan_id', $kecId);
        }

        $perKel = [];
        $total = $this->kerangkaRekap();
        $tmsKode = [];
        $dokAda = 0;
        $dokTotal = 0;

        foreach ($q->get()->getResultArray() as $r) {
            $lama = $r['data_lama'] !== null ? (json_decode((string) $r['data_lama'], true) ?? []) : [];
            $baru = $r['data_baru'] !== null ? (json_decode((string) $r['data_baru'], true) ?? []) : [];
            $msLama = $lama !== [] && Keterangan::isMs((string) ($lama['KET'] ?? ''));
            $msBaru = $baru !== [] && Keterangan::isMs((string) ($baru['KET'] ?? ''));

            $kelurahanId = (int) ($r['kelurahan_id'] ?? 0);
            $perKel[$kelurahanId] ??= $this->kerangkaRekap();
            $perKel[$kelurahanId]['nama'] = (string) ($r['kel_nama'] ?? '');
            $perKel[$kelurahanId]['kecamatan'] = (string) ($r['kec_nama'] ?? '');
            $perKel[$kelurahanId]['kecamatan_id'] = (int) ($r['kecamatan_id'] ?? 0);
            $sel = &$perKel[$kelurahanId];

            $dokTotal++;
            if (! empty($r['dokumen_jenis'])) {
                $dokAda++;
            }

            switch ($r['aksi']) {
                case 'tambah':
                    $sel['baru']++;
                    $sel['delta'] += $msBaru ? 1 : 0;
                    break;
                case 'hapus':
                    $sel['hapus']++;
                    $sel['delta'] -= $msLama ? 1 : 0;
                    break;
                case 'pulih':
                    $sel['pulih']++;
                    $sel['delta'] += $msBaru ? 1 : 0;
                    break;
                default: // ubah
                    if ($msLama && ! $msBaru) {
                        $sel['tms']++;
                        $kode = (string) ($baru['KET'] ?? '');
                        $tmsKode[$kode] = ($tmsKode[$kode] ?? 0) + 1;
                    } else {
                        $sel['ubah']++;
                    }
                    $sel['delta'] += ($msBaru ? 1 : 0) - ($msLama ? 1 : 0);
            }

            unset($sel);
        }

        // Total dihitung ulang dari per-kelurahan agar pasti konsisten.
        $total = $this->kerangkaRekap();
        foreach ($perKel as $r) {
            foreach (['baru', 'tms', 'ubah', 'hapus', 'pulih', 'delta'] as $k) {
                $total[$k] += $r[$k];
            }
        }
        ksort($tmsKode);

        return ['perKel' => $perKel, 'total' => $total, 'tmsKode' => $tmsKode,
            'dokumenAda' => $dokAda, 'dokumenTotal' => $dokTotal];
    }

    /** @return array<int, array{nama: string, kecamatan: string, kecamatan_id: int, ms: int, laki: int, perempuan: int, kk: int}> */
    private function hitungHidup(?int $kecId, ?int $kelId): array
    {
        $db = $this->db();
        $q = $db->table('mhc')
            ->select("mhc.kelurahan_id, kelurahan.nama AS kel_nama, kecamatan.nama AS kec_nama, kelurahan.kecamatan_id, COUNT(*) AS ms, SUM(mhc.JENIS_KELAMIN = 'L') AS laki, SUM(mhc.JENIS_KELAMIN = 'P') AS perempuan, COUNT(DISTINCT NULLIF(mhc.NKK, '')) AS kk", false)
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->whereIn('mhc.KET', Keterangan::MS)->groupBy('mhc.kelurahan_id');
        if ($kelId !== null) {
            $q->where('mhc.kelurahan_id', $kelId);
        } elseif ($kecId !== null) {
            $q->where('kelurahan.kecamatan_id', $kecId);
        }

        $hasil = [];
        foreach ($q->get()->getResultArray() as $r) {
            $hasil[(int) $r['kelurahan_id']] = [
                'nama' => (string) $r['kel_nama'], 'kecamatan' => (string) $r['kec_nama'],
                'kecamatan_id' => (int) $r['kecamatan_id'],
                'ms' => (int) $r['ms'], 'laki' => (int) $r['laki'],
                'perempuan' => (int) $r['perempuan'], 'kk' => (int) $r['kk'],
            ];
        }

        return $hasil;
    }

    /** @return array<int, array<string, int>> jumlah per kode KET per kelurahan (semua baris). */
    private function hitungPerKet(): array
    {
        $hasil = [];
        $rows = $this->db()->table('mhc')->select('kelurahan_id, KET, COUNT(*) AS c', false)
            ->groupBy('kelurahan_id, KET')->get()->getResultArray();
        foreach ($rows as $r) {
            $hasil[(int) $r['kelurahan_id']][(string) $r['KET']] = (int) $r['c'];
        }

        return $hasil;
    }

    /** Jumlahkan metrik $dari ke $ke (akumulator arsip). */
    private function jumlahkan(array &$ke, array $dari): void
    {
        foreach (['ms', 'laki', 'perempuan', 'kk'] as $k) {
            $ke[$k] += (int) ($dari[$k] ?? 0);
        }
        foreach (($dari['perKet'] ?? []) as $kode => $n) {
            $ke['perKet'][$kode] = ($ke['perKet'][$kode] ?? 0) + (int) $n;
        }
        foreach (['baru', 'tms', 'ubah', 'hapus', 'pulih'] as $k) {
            $ke['rekap'][$k] += (int) ($dari['rekap'][$k] ?? 0);
        }
    }
}
