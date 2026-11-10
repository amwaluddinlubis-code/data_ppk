<?php

namespace App\Controllers;

use App\Libraries\ImporCoklit;
use App\Libraries\Keterangan;
use App\Libraries\PembacaXlsx;
use App\Libraries\Wilayah;

/**
 * Fase E (bagian 1) — Ekspor-banding Sidalih.
 *
 * Ekspor menulis daftar pemilih dengan JUDUL KOLOM SIDALIH yang
 * persis (DPID, KETERANGAN, TEMPAT LAHIR, ...) agar berkas dapat
 * disandingkan kolom-per-kolom dengan ekspor Sidalih. Banding
 * menerima unggahan ekspor Sidalih, menyimpannya di banding_temp,
 * lalu menghitung selisih terhadap mhc per NIK: hanya di Sidalih,
 * hanya di aplikasi, beda elemen, dan sama.
 */
class Sidalih extends BaseController
{
    /** Judul kolom ekspor Sidalih, urut seperti berkas Sidalih. */
    private const JUDUL = [
        'KEC_ID', 'KECAMATAN', 'KEL_ID', 'KELURAHAN', 'TPS', 'DPID', 'NKK', 'NIK',
        'NAMA', 'TEMPAT LAHIR', 'TANGGAL LAHIR', 'STS KAWIN', 'KELAMIN', 'ALAMAT',
        'RT', 'RW', 'DISABILITAS', 'EKTP', 'KETERANGAN', 'SUMBER', 'UPDATED_AT',
    ];

    private function db()
    {
        return db_connect();
    }

    public function index()
    {
        $db = $this->db();
        $jumlahTemp = (int) $db->table('banding_temp')->countAllResults();
        $ringkasan = null;
        if ($jumlahTemp > 0) {
            $ringkasan = $this->hitungRingkasan();
        }
        $meta = [];
        foreach ($db->table('pengaturan')->like('kunci', 'banding_', 'after')->get()->getResultArray() as $p) {
            $meta[$p['kunci']] = $p['nilai'];
        }

        return view('sidalih/index', [
            'title' => 'Banding Sidalih', 'title_page' => 'Ekspor & Banding Sidalih',
            'jumlahTemp' => $jumlahTemp, 'ringkasan' => $ringkasan, 'meta' => $meta,
        ]);
    }

    /** Ringkasan selisih banding_temp vs mhc (seluruh kabupaten, per NIK). */
    private function hitungRingkasan(): array
    {
        $db = $this->db();
        $hanyaSidalih = (int) $db->query('SELECT COUNT(*) AS c FROM banding_temp t LEFT JOIN mhc m ON m.NIK = t.nik WHERE m.NIK IS NULL')->getRowArray()['c'];
        $hanyaAplikasi = (int) $db->query('SELECT COUNT(*) AS c FROM mhc m LEFT JOIN banding_temp t ON t.nik = m.NIK WHERE t.nik IS NULL')->getRowArray()['c'];
        $beda = (int) $db->query("SELECT COUNT(*) AS c FROM banding_temp t JOIN mhc m ON m.NIK = t.nik
            JOIN kelurahan k ON k.id = m.kelurahan_id
            WHERE NOT (UPPER(TRIM(t.nama)) <=> UPPER(TRIM(m.NAMA)))
               OR NOT (t.ket <=> m.KET)
               OR NOT (t.tps <=> m.TPS)
               OR NOT (UPPER(TRIM(t.desa_nama)) <=> UPPER(TRIM(k.nama)))")->getRowArray()['c'];
        $kedua = (int) $db->query('SELECT COUNT(*) AS c FROM banding_temp t JOIN mhc m ON m.NIK = t.nik')->getRowArray()['c'];

        return [
            'hanya_sidalih' => $hanyaSidalih,
            'hanya_aplikasi' => $hanyaAplikasi,
            'beda' => $beda,
            'sama' => $kedua - $beda,
            'kedua' => $kedua,
        ];
    }

    /** Ekspor CSV format Sidalih (mengalir, tercakup wilayah pengguna). */
    public function ekspor()
    {
        $db = $this->db();
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();

        $nama = 'ekspor-sidalih-' . date('Ymd-His') . '.csv';
        $this->catatAkses('ekspor_sidalih', 'format Sidalih (CSV)');

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '"')
            ->setBody($this->bangunCsvEkspor($db, $kelPengguna, $kecEfektif));
    }

    private function bangunCsvEkspor($db, ?int $kelPengguna, ?int $kecEfektif): string
    {
        // Ditulis per kelurahan (maks 404 kueri kecil) ke berkas sementara —
        // penomoran halaman OFFSET pada 338 ribu baris terlalu lambat.
        @set_time_limit(600);
        $tmp = tempnam(sys_get_temp_dir(), 'eks');
        $out = fopen($tmp, 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::JUDUL, ';');
        $qKel = $db->table('kelurahan')->select('kelurahan.id, kelurahan.kode AS kel_id, kelurahan.nama AS desa, kecamatan.kode AS kec_id, kecamatan.nama AS kec')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->orderBy('kecamatan.nama')->orderBy('kelurahan.nama');
        if ($kelPengguna !== null) {
            $qKel->where('kelurahan.id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $qKel->where('kelurahan.kecamatan_id', $kecEfektif);
        }
        foreach ($qKel->get()->getResultArray() as $kel) {
            $rows = $db->table('mhc')->select('TPS, UID, NKK, NIK, NAMA, TMPTLHR, TGLLHR, STATUS, JENIS_KELAMIN,
                    ALAMAT, RT, RW, DISABILITAS, EKTP, KET, SUMBER, LAST_UPDATE')
                ->where('kelurahan_id', (int) $kel['id'])
                ->orderBy('TPS')->orderBy('NAMA')->get()->getResultArray();
            foreach ($rows as $r) {
                fputcsv($out, [
                    $kel['kec_id'], $kel['kec'], $kel['kel_id'], $kel['desa'], $r['TPS'], $r['UID'], $r['NKK'], $r['NIK'],
                    $r['NAMA'], $r['TMPTLHR'], $r['TGLLHR'], $r['STATUS'], $r['JENIS_KELAMIN'], $r['ALAMAT'],
                    $r['RT'], $r['RW'], $r['DISABILITAS'], $r['EKTP'], $r['KET'], $r['SUMBER'], $r['LAST_UPDATE'],
                ], ';');
            }
        }
        fclose($out);
        $isi = (string) file_get_contents($tmp);
        unlink($tmp);

        return $isi;
    }

    /** Unggah ekspor Sidalih (xlsx/csv) ke banding_temp. */
    public function unggah()
    {
        $db = $this->db();
        $berkas = $this->request->getFile('berkas');
        if ($berkas === null || ! $berkas->isValid() || $berkas->getSize() > 64 * 1024 * 1024) {
            return redirect()->to('sidalih')->with('galat', 'Berkas tidak sah atau melebihi 64 MB.');
        }
        @set_time_limit(600);
        $ext = strtolower($berkas->getExtension() ?: pathinfo((string) $berkas->getClientName(), PATHINFO_EXTENSION));
        $path = $berkas->getTempName();
        if (! in_array($ext, ['xlsx', 'csv'], true)) {
            return redirect()->to('sidalih')->with('galat', 'Format berkas harus .xlsx atau .csv.');
        }

        $db->table('banding_temp')->truncate();
        $batch = [];
        $jumlah = 0;
        $judul = null;
        $format = null;
        $proses = function (array $atas) use ($db, &$batch, &$jumlah, &$format) {
            $nik = preg_replace('/\D/', '', (string) ($atas['NIK'] ?? ''));
            if ($nik === '') {
                return;
            }
            $batch[] = [
                'nik' => $nik,
                'nama' => mb_substr((string) ($atas['NAMA'] ?? ''), 0, 120),
                'ket' => (string) ($format === 'sidalih' ? ($atas['KETERANGAN'] ?? '') : ($atas['KET'] ?? '')),
                'tps' => (int) ($atas['TPS'] ?? 0),
                'kec_nama' => mb_substr((string) ($atas['KECAMATAN'] ?? ''), 0, 80),
                'desa_nama' => mb_substr((string) ($atas['KELURAHAN'] ?? $atas['DESA'] ?? ''), 0, 80),
            ];
            if (count($batch) >= 1000) {
                $db->table('banding_temp')->insertBatch($batch);
                $jumlah += count($batch);
                $batch = [];
            }
        };
        try {
            if ($ext === 'xlsx') {
                $pembaca = new PembacaXlsx($path);
                foreach ($pembaca->baris() as $r) {
                    if ($judul === null) {
                        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $r);
                        $format = ImporCoklit::deteksiFormat($judul);
                        if ($format === null) {
                            return redirect()->to('sidalih')->with('galat', 'Judul kolom tidak dikenali sebagai ekspor Sidalih atau format aplikasi.');
                        }
                        continue;
                    }
                    $proses(array_combine($judul, array_pad(array_map(static fn ($c) => is_string($c) ? trim($c) : $c, $r), count($judul), '')));
                }
            } else {
                $fh = fopen($path, 'rb');
                while (($r = fgetcsv($fh, 0, ';')) !== false) {
                    if ($judul === null) {
                        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $r);
                        $format = ImporCoklit::deteksiFormat($judul);
                        if ($format === null) {
                            fclose($fh);
                            return redirect()->to('sidalih')->with('galat', 'Judul kolom tidak dikenali sebagai ekspor Sidalih atau format aplikasi.');
                        }
                        continue;
                    }
                    $proses(array_combine($judul, array_pad($r, count($judul), '')));
                }
                fclose($fh);
            }
        } catch (\Throwable $e) {
            return redirect()->to('sidalih')->with('galat', 'Berkas tidak dapat dibaca: ' . $e->getMessage());
        }
        if ($batch !== []) {
            $db->table('banding_temp')->insertBatch($batch);
            $jumlah += count($batch);
        }
        if ($jumlah === 0) {
            return redirect()->to('sidalih')->with('galat', 'Tidak ada baris ber-NIK pada berkas.');
        }
        $sekarang = date('Y-m-d H:i:s');
        foreach (['banding_waktu' => $sekarang, 'banding_berkas' => (string) $berkas->getClientName(), 'banding_jumlah' => (string) $jumlah] as $k => $v) {
            $db->table('pengaturan')->replace(['kunci' => $k, 'nilai' => $v]);
        }

        return redirect()->to('sidalih')->with('sukses', 'Berkas terbaca: ' . number_format($jumlah, 0, ',', '.') . ' baris masuk tabel banding.');
    }

    /** Kosongkan hasil banding. */
    public function bersihkan()
    {
        $db = $this->db();
        $db->table('banding_temp')->truncate();
        $db->table('pengaturan')->like('kunci', 'banding_', 'after')->delete();

        return redirect()->to('sidalih')->with('sukses', 'Hasil banding dikosongkan.');
    }

    /** Unduh CSV selisih (hanya di Sidalih / hanya di aplikasi / beda elemen). */
    public function unduhSelisih()
    {
        $db = $this->db();
        if ((int) $db->table('banding_temp')->countAllResults() === 0) {
            return redirect()->to('sidalih')->with('galat', 'Belum ada hasil banding.');
        }
        @set_time_limit(600);
        $tmp = tempnam(sys_get_temp_dir(), 'sel');
        $out = fopen($tmp, 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['KATEGORI', 'NIK', 'NAMA_SIDALIH', 'NAMA_APLIKASI', 'KET_SIDALIH', 'KET_APLIKASI', 'TPS_SIDALIH', 'TPS_APLIKASI', 'DESA_SIDALIH', 'DESA_APLIKASI'], ';');
        $q1 = $db->query('SELECT t.nik, t.nama, t.ket, t.tps, t.desa_nama FROM banding_temp t LEFT JOIN mhc m ON m.NIK = t.nik WHERE m.NIK IS NULL ORDER BY t.nik');
        foreach ($q1->getResultArray() as $r) {
            fputcsv($out, ['HANYA_DI_SIDALIH', $r['nik'], $r['nama'], '', $r['ket'], '', $r['tps'], '', $r['desa_nama'], ''], ';');
        }
        // Hanya-di-aplikasi bisa ratusan ribu baris: tulis per potong 20 ribu.
        $offset = 0;
        do {
            $rows = $db->query("SELECT m.NIK, m.NAMA, m.KET, m.TPS, k.nama AS desa FROM mhc m JOIN kelurahan k ON k.id = m.kelurahan_id LEFT JOIN banding_temp t ON t.nik = m.NIK WHERE t.nik IS NULL ORDER BY m.NIK, m.ID LIMIT 20000 OFFSET {$offset}")->getResultArray();
            foreach ($rows as $r) {
                fputcsv($out, ['HANYA_DI_APLIKASI', $r['NIK'], '', $r['NAMA'], '', $r['KET'], '', $r['TPS'], '', $r['desa']], ';');
            }
            $offset += 20000;
        } while (count($rows) === 20000);
        $q3 = $db->query("SELECT t.nik, t.nama AS nama_s, m.NAMA AS nama_a, t.ket AS ket_s, m.KET AS ket_a, t.tps AS tps_s, m.TPS AS tps_a, t.desa_nama AS desa_s, k.nama AS desa_a
            FROM banding_temp t JOIN mhc m ON m.NIK = t.nik JOIN kelurahan k ON k.id = m.kelurahan_id
            WHERE NOT (UPPER(TRIM(t.nama)) <=> UPPER(TRIM(m.NAMA))) OR NOT (t.ket <=> m.KET) OR NOT (t.tps <=> m.TPS) OR NOT (UPPER(TRIM(t.desa_nama)) <=> UPPER(TRIM(k.nama)))
            ORDER BY t.nik");
        foreach ($q3->getResultArray() as $r) {
            fputcsv($out, ['BEDA_ELEMEN', $r['nik'], $r['nama_s'], $r['nama_a'], $r['ket_s'], $r['ket_a'], $r['tps_s'], $r['tps_a'], $r['desa_s'], $r['desa_a']], ';');
        }
        fclose($out);
        $isi = (string) file_get_contents($tmp);
        unlink($tmp);
        $this->catatAkses('unduh_selisih_sidalih', 'CSV selisih banding Sidalih');

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="selisih-sidalih-' . date('Ymd-His') . '.csv"')
            ->setBody($isi);
    }
}
