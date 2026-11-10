<?php

namespace App\Controllers;

use App\Libraries\Konteks;
use App\Libraries\Wilayah;

/**
 * Fase E (bagian 2) — Analisis Beban TPS.
 *
 * Beban TPS = pemilih MS terdaftar pada TPS itu + pemilih pindahan
 * (DPTb) yang masuk ke TPS itu, dibandingkan dengan batas konteks:
 * Pemilu 300 (PKPU 7/2022 Pasal 15 ayat (3)), Pilkada 600 (PKPU
 * 7/2024 Pasal 10 ayat (2)). Pada konteks PDPB dipakai batas Pemilu
 * sebagai acuan perencanaan. TPS >= 90% batas ditandai "mendekati".
 */
class BebanTps extends BaseController
{
    private function db()
    {
        return db_connect();
    }

    public function index()
    {
        $db = $this->db();
        $batas = Konteks::batasTps() ?? 300;
        $acuan = Konteks::batasTps() === null ? 'Acuan Pemilu (300) — konteks PDPB' : ('Batas konteks ' . Konteks::jenis());
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        $kecFilter = (int) ($this->request->getGet('kecamatan_id') ?? 0);
        if ($kecEfektif !== null) {
            $kecFilter = $kecEfektif;
        }

        $jumlahPindah = (int) $db->table('pindah_memilih')->countAllResults();
        $kunci = 'bebantps_' . $batas . '_' . $jumlahPindah . '_' . ($kelPengguna ?? 0) . '_' . $kecFilter;
        $data = $this->ambilCacheAnalisis($kunci, function () use ($db, $kelPengguna, $kecFilter) {
            $q = $db->query("SELECT m.kelurahan_id, k.kecamatan_id, m.TPS AS tps,
                    COUNT(*) AS ms,
                    (SELECT COUNT(*) FROM pindah_memilih p WHERE p.tujuan_kelurahan_id = m.kelurahan_id AND p.tujuan_tps = m.TPS) AS masuk
                FROM mhc m JOIN kelurahan k ON k.id = m.kelurahan_id
                WHERE m.KET IN ('0','U','B')
                GROUP BY m.kelurahan_id, k.kecamatan_id, m.TPS");
            $perTps = [];
            foreach ($q->getResultArray() as $r) {
                if ($kelPengguna !== null && (int) $r['kelurahan_id'] !== $kelPengguna) {
                    continue;
                }
                if ($kecFilter > 0 && (int) $r['kecamatan_id'] !== $kecFilter) {
                    continue;
                }
                $perTps[] = [
                    'kelurahan_id' => (int) $r['kelurahan_id'], 'kecamatan_id' => (int) $r['kecamatan_id'],
                    'tps' => (int) $r['tps'], 'ms' => (int) $r['ms'], 'masuk' => (int) $r['masuk'],
                    'total' => (int) $r['ms'] + (int) $r['masuk'],
                ];
            }

            return $perTps;
        });

        $namaKec = [];
        $namaKel = [];
        foreach ($db->table('kecamatan')->get()->getResultArray() as $k) {
            $namaKec[(int) $k['id']] = $k['nama'];
        }
        foreach ($db->table('kelurahan')->get()->getResultArray() as $k) {
            $namaKel[(int) $k['id']] = $k['nama'];
        }

        $perKec = [];
        $bermasalah = [];
        $ringkas = ['tps' => 0, 'lewat' => 0, 'mendekati' => 0, 'maks' => 0];
        foreach ($data as $t) {
            $status = $t['total'] > $batas ? 'lewat' : ($t['total'] >= (int) ceil($batas * 0.9) ? 'mendekati' : 'aman');
            $kecId = $t['kecamatan_id'];
            $perKec[$kecId] ??= ['nama' => $namaKec[$kecId] ?? '', 'tps' => 0, 'lewat' => 0, 'mendekati' => 0, 'maks' => 0, 'pemilih' => 0];
            $perKec[$kecId]['tps']++;
            $perKec[$kecId]['pemilih'] += $t['total'];
            $perKec[$kecId]['maks'] = max($perKec[$kecId]['maks'], $t['total']);
            $ringkas['tps']++;
            $ringkas['maks'] = max($ringkas['maks'], $t['total']);
            if ($status !== 'aman') {
                $perKec[$kecId][$status]++;
                $ringkas[$status]++;
                $bermasalah[] = $t + [
                    'status' => $status,
                    'desa' => $namaKel[$t['kelurahan_id']] ?? '',
                    'kecamatan' => $namaKec[$kecId] ?? '',
                    'kelebihan' => $t['total'] - $batas,
                ];
            }
        }
        usort($bermasalah, static fn ($a, $b) => $b['total'] <=> $a['total']);
        $perKec = array_values($perKec);
        usort($perKec, static fn ($a, $b) => $a['nama'] <=> $b['nama']);

        $daftarKecamatan = $kelPengguna === null ? $db->table('kecamatan')->orderBy('nama')->get()->getResultArray() : [];

        return view('bebantps/index', [
            'title' => 'Beban TPS', 'title_page' => 'Analisis Beban TPS',
            'batas' => $batas, 'acuan' => $acuan, 'konteksNama' => Konteks::nama(),
            'ringkas' => $ringkas, 'perKec' => $perKec, 'bermasalah' => $bermasalah,
            'daftarKecamatan' => $daftarKecamatan, 'kecFilter' => $kecFilter,
            'terkunci' => $kecEfektif !== null || $kelPengguna !== null,
        ]);
    }
}
