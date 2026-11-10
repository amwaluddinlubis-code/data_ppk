<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use App\Libraries\Konteks;
use App\Libraries\Wilayah;

/**
 * Fase C — Tahapan penetapan DPS → DPSHP → DPT (konteks pemilu/pilkada)
 * beserta cetakan Kelompok 2.
 *
 * Penetapan membekukan POTRET BARIS daftar MS ke arsip_pemilih dan
 * agregatnya ke arsip_tahap; cetakan tahap selalu membaca potret itu,
 * bukan data hidup, sehingga dokumen penetapan dapat direproduksi
 * persis kapan pun. Perbandingan antar tahap (daftar/rekap perubahan)
 * adalah selisih dua potret yang dihitung dengan SQL.
 */
class Tahapan extends BaseController
{
    private const URUTAN = ['dps', 'dpshp', 'dpt'];
    private const JUDUL = [
        'dps'   => 'DAFTAR PEMILIH SEMENTARA',
        'dpshp' => 'DAFTAR PEMILIH SEMENTARA HASIL PERBAIKAN',
        'dpt'   => 'DAFTAR PEMILIH TETAP',
    ];

    private function db()
    {
        return db_connect();
    }

    private function tahapById(int $id): ?array
    {
        return $this->db()->table('tahap_penetapan')->where('id', $id)->get()->getRowArray();
    }

    /** Tahap sebelumnya (sudah ditutup) dalam penyelenggaraan yang sama. */
    private function tahapSebelumnya(array $tahap): ?array
    {
        $idx = array_search($tahap['tahap'], self::URUTAN, true);
        if ($idx === false || $idx === 0) {
            return null;
        }

        return $this->db()->table('tahap_penetapan')
            ->where('konteks_jenis', $tahap['konteks_jenis'])
            ->where('konteks_nama', $tahap['konteks_nama'])
            ->where('tahap', self::URUTAN[$idx - 1])
            ->where('status', 'ditutup')
            ->get()->getRowArray();
    }

    // -- Halaman utama --------------------------------------------------

    public function index()
    {
        $db = $this->db();
        $jenis = Konteks::jenis();
        $namaKonteks = Konteks::nama();
        $semua = $db->table('tahap_penetapan')->orderBy('id', 'DESC')->get()->getResultArray();

        $pasanganAktif = [];
        $berjalan = null;
        foreach ($semua as $t) {
            if ($t['konteks_jenis'] === $jenis && $t['konteks_nama'] === $namaKonteks) {
                $pasanganAktif[$t['tahap']] = $t;
                if ($t['status'] === 'berjalan') {
                    $berjalan = $t;
                }
            }
        }
        $tahapBerikutnya = null;
        if ($jenis !== 'pdpb' && $berjalan === null) {
            foreach (self::URUTAN as $kode) {
                if (! isset($pasanganAktif[$kode])) {
                    $tahapBerikutnya = $kode;
                    break;
                }
            }
        }

        // Potret antar tahap untuk penyelenggaraan aktif (per kecamatan).
        $potret = [];
        $potretTotal = [];
        if ($pasanganAktif !== []) {
            $ids = array_column($pasanganAktif, 'id');
            $rows = $db->table('arsip_tahap')->select('arsip_tahap.tahap_id, arsip_tahap.metrik, kecamatan.nama AS kec_nama')
                ->join('kecamatan', 'kecamatan.id = arsip_tahap.kecamatan_id')
                ->whereIn('arsip_tahap.tahap_id', $ids)->where('arsip_tahap.tingkat', 'kecamatan')
                ->orderBy('kecamatan.nama')->get()->getResultArray();
            foreach ($rows as $r) {
                $m = json_decode((string) $r['metrik'], true) ?? [];
                $tahapKode = $pasanganAktif[array_search((int) $r['tahap_id'], array_column($pasanganAktif, 'id'), true)]['tahap'] ?? '';
                $potret[$r['kec_nama']][$tahapKode] = (int) ($m['ms'] ?? 0);
                $potretTotal[$tahapKode] = ($potretTotal[$tahapKode] ?? 0) + (int) ($m['ms'] ?? 0);
            }
        }

        $daftarKelurahan = $db->table('kelurahan')->select('kelurahan.id, kelurahan.nama, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null) {
            $daftarKelurahan->where('kelurahan.id', $kelPengguna);
        } elseif ($kecEfektif !== null) {
            $daftarKelurahan->where('kelurahan.kecamatan_id', $kecEfektif);
        }

        return view('tahapan/index', [
            'title' => 'Tahapan DPS–DPT', 'title_page' => 'Tahapan Penetapan DPS — DPSHP — DPT',
            'jenis' => $jenis, 'namaKonteks' => $namaKonteks,
            'semua' => $semua, 'pasanganAktif' => $pasanganAktif,
            'berjalan' => $berjalan, 'tahapBerikutnya' => $tahapBerikutnya,
            'potret' => $potret, 'potretTotal' => $potretTotal,
            'daftarKelurahan' => $daftarKelurahan->orderBy('kecamatan.nama')->orderBy('kelurahan.nama')->get()->getResultArray(),
            'adalahAdmin' => session()->get('pengguna_peran') === 'admin',
        ]);
    }

    /** Buka tahap berikutnya untuk konteks penyelenggaraan aktif (admin). */
    public function buka()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('tahapan')->with('galat', 'Hanya admin kabupaten.');
        }
        $jenis = Konteks::jenis();
        if ($jenis === 'pdpb') {
            return redirect()->to('tahapan')->with('galat', 'Tahapan DPS/DPSHP/DPT hanya untuk konteks Pemilu/Pilkada — ubah konteks di menu Konteks Penyelenggaraan.');
        }
        $db = $this->db();
        $nama = Konteks::nama();
        if ($db->table('tahap_penetapan')->where('konteks_jenis', $jenis)->where('konteks_nama', $nama)->where('status', 'berjalan')->countAllResults() > 0) {
            return redirect()->to('tahapan')->with('galat', 'Masih ada tahap yang berjalan — tetapkan dulu tahap itu.');
        }
        $ada = $db->table('tahap_penetapan')->where('konteks_jenis', $jenis)->where('konteks_nama', $nama)->get()->getResultArray();
        $terisi = array_column($ada, 'tahap');
        $berikutnya = null;
        foreach (self::URUTAN as $kode) {
            if (! in_array($kode, $terisi, true)) {
                $berikutnya = $kode;
                break;
            }
        }
        if ($berikutnya === null) {
            return redirect()->to('tahapan')->with('galat', 'Ketiga tahap sudah lengkap untuk penyelenggaraan ini.');
        }
        $sekarang = date('Y-m-d H:i:s');
        $db->table('tahap_penetapan')->insert([
            'konteks_jenis' => $jenis, 'konteks_nama' => $nama, 'tahap' => $berikutnya,
            'status' => 'berjalan', 'dibuat_oleh' => (int) session()->get('pengguna_id'),
            'created_at' => $sekarang, 'updated_at' => $sekarang,
        ]);

        return redirect()->to('tahapan')->with('sukses', 'Tahap ' . strtoupper($berikutnya) . ' dibuka. Perbaiki data pemilih sesuai tahap ini, lalu tetapkan untuk membekukan potretnya.');
    }

    /** Tetapkan tahap berjalan: bekukan potret baris + agregat + metadata BA (admin). */
    public function tetapkan()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('tahapan')->with('galat', 'Hanya admin kabupaten.');
        }
        $db = $this->db();
        $id = (int) $this->request->getPost('tahap_id');
        $tahap = $this->tahapById($id);
        if ($tahap === null || $tahap['status'] !== 'berjalan') {
            return redirect()->to('tahapan')->with('galat', 'Tahap tidak ditemukan atau sudah ditetapkan.');
        }
        $tgl = trim((string) $this->request->getPost('ba_tanggal'));
        if ($tgl !== '') {
            $cek = \DateTimeImmutable::createFromFormat('Y-m-d', $tgl);
            if (! $cek || $cek->format('Y-m-d') !== $tgl) {
                return redirect()->to('tahapan')->with('galat', 'Tanggal pleno tidak sah.');
            }
        }
        $potong = static fn ($v, $n) => mb_substr(trim((string) $v), 0, $n);

        // Potret baris: seluruh pemilih MS saat ini.
        $db->query("INSERT INTO arsip_pemilih (tahap_id, kelurahan_id, tps, nkk, nik, nama, tmptlhr, tgllhr, status, jenis_kelamin, alamat, rt, rw, disabilitas, ektp, ket)
            SELECT ?, kelurahan_id, TPS, NKK, NIK, NAMA, TMPTLHR, TGLLHR, STATUS, JENIS_KELAMIN, ALAMAT, RT, RW, DISABILITAS, EKTP, KET
            FROM mhc WHERE KET IN ('0','U','B')", [$id]);
        $jumlah = (int) $db->table('arsip_pemilih')->where('tahap_id', $id)->countAllResults();
        if ($jumlah === 0) {
            return redirect()->to('tahapan')->with('galat', 'Tidak ada pemilih MS — penetapan dibatalkan.');
        }

        // Agregat per kelurahan dari potret.
        $perKel = $db->query("SELECT kelurahan_id,
                COUNT(*) AS ms,
                SUM(jenis_kelamin = 'L') AS laki,
                SUM(jenis_kelamin = 'P') AS perempuan,
                COUNT(DISTINCT nkk) AS kk,
                COUNT(DISTINCT tps) AS tps
            FROM arsip_pemilih WHERE tahap_id = ? GROUP BY kelurahan_id", [$id])->getResultArray();
        $sekarang = date('Y-m-d H:i:s');
        $metrikKel = [];
        foreach ($perKel as $r) {
            $metrik = ['ms' => (int) $r['ms'], 'laki' => (int) $r['laki'], 'perempuan' => (int) $r['perempuan'], 'kk' => (int) $r['kk'], 'tps' => (int) $r['tps']];
            $metrikKel[(int) $r['kelurahan_id']] = $metrik;
            $db->table('arsip_tahap')->insert([
                'tahap_id' => $id, 'tingkat' => 'kelurahan', 'kelurahan_id' => (int) $r['kelurahan_id'],
                'metrik' => json_encode($metrik), 'created_at' => $sekarang,
            ]);
        }
        $kecDariKel = [];
        foreach ($db->table('kelurahan')->select('id, kecamatan_id')->get()->getResultArray() as $k) {
            $kecDariKel[(int) $k['id']] = (int) $k['kecamatan_id'];
        }
        $perKec = [];
        foreach ($metrikKel as $kelId => $m) {
            $kecId = $kecDariKel[$kelId] ?? 0;
            $perKec[$kecId] ??= ['ms' => 0, 'laki' => 0, 'perempuan' => 0, 'kk' => 0, 'tps' => 0, 'desa' => 0];
            foreach (['ms', 'laki', 'perempuan', 'kk', 'tps'] as $k) {
                $perKec[$kecId][$k] += $m[$k];
            }
            $perKec[$kecId]['desa']++;
        }
        $total = ['ms' => 0, 'laki' => 0, 'perempuan' => 0, 'kk' => 0, 'tps' => 0, 'desa' => 0, 'kecamatan' => 0];
        foreach ($perKec as $kecId => $m) {
            $db->table('arsip_tahap')->insert([
                'tahap_id' => $id, 'tingkat' => 'kecamatan', 'kecamatan_id' => $kecId,
                'metrik' => json_encode($m), 'created_at' => $sekarang,
            ]);
            foreach (['ms', 'laki', 'perempuan', 'kk', 'tps', 'desa'] as $k) {
                $total[$k] += $m[$k];
            }
            $total['kecamatan']++;
        }
        $db->table('arsip_tahap')->insert([
            'tahap_id' => $id, 'tingkat' => 'kabupaten', 'metrik' => json_encode($total), 'created_at' => $sekarang,
        ]);

        $db->table('tahap_penetapan')->where('id', $id)->update([
            'status' => 'ditutup', 'ditutup_oleh' => (int) session()->get('pengguna_id'), 'ditutup_at' => $sekarang,
            'ba_nomor' => $potong($this->request->getPost('ba_nomor'), 80) ?: null,
            'ba_tanggal' => $tgl !== '' ? $tgl : null,
            'ba_tempat' => $potong($this->request->getPost('ba_tempat'), 120) ?: null,
            'ba_ketua' => $potong($this->request->getPost('ba_ketua'), 120) ?: null,
            'ba_anggota' => $potong($this->request->getPost('ba_anggota'), 2000) ?: null,
            'updated_at' => $sekarang,
        ]);

        return redirect()->to('tahapan')->with('sukses', 'Tahap ' . strtoupper($tahap['tahap']) . ' ditetapkan: potret ' . number_format($jumlah, 0, ',', '.') . ' pemilih dibekukan.');
    }

    // -- Cetak Kelompok 2 ----------------------------------------------

    /** @return array<string, string> */
    private function pengaturanPeta(): array
    {
        $peta = [];
        foreach ($this->db()->table('pengaturan')->get()->getResultArray() as $p) {
            $peta[$p['kunci']] = $p['nilai'];
        }

        return $peta;
    }

    /** Kelurahan tercakup pengguna, atau null bila di luar cakupan. */
    private function cekKelurahan(int $kelId): ?array
    {
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();
        if ($kelPengguna !== null && $kelId !== $kelPengguna) {
            return null;
        }
        $kel = $this->db()->table('kelurahan')->select('kelurahan.*, kecamatan.nama AS kecamatan')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->where('kelurahan.id', $kelId)->get()->getRowArray();
        if ($kel === null || ($kecEfektif !== null && (int) $kel['kecamatan_id'] !== $kecEfektif)) {
            return null;
        }

        return $kel;
    }

    /** P1 — Model A-Daftar Pemilih per TPS dari potret tahap (Lampiran II PKPU 7/2022). */
    public function cetakDaftar()
    {
        $db = $this->db();
        $tahap = $this->tahapById((int) ($this->request->getGet('tahap_id') ?? 0));
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $tps = (int) ($this->request->getGet('tps') ?? 0);
        $kel = $tahap !== null ? $this->cekKelurahan($kelId) : null;
        if ($tahap === null || $kel === null || $tps <= 0) {
            return redirect()->to('tahapan')->with('galat', 'Parameter cetak daftar tidak lengkap/di luar cakupan.');
        }
        if ($tahap['status'] === 'ditutup') {
            $rows = $db->table('arsip_pemilih')
                ->where('tahap_id', (int) $tahap['id'])->where('kelurahan_id', $kelId)->where('tps', $tps)
                ->orderBy('nama')->get()->getResultArray();
            $baris = array_map(static fn ($r) => [
                'NKK' => $r['nkk'], 'NIK' => $r['nik'], 'NAMA' => $r['nama'], 'TMPTLHR' => $r['tmptlhr'],
                'TGLLHR' => $r['tgllhr'], 'STATUS' => $r['status'], 'JENIS_KELAMIN' => $r['jenis_kelamin'],
                'ALAMAT' => $r['alamat'], 'RT' => $r['rt'], 'RW' => $r['rw'],
                'DISABILITAS' => $r['disabilitas'], 'EKTP' => $r['ektp'], 'KET' => $r['ket'],
            ], $rows);
            $pratinjau = false;
        } else {
            $rows = $db->table('mhc')->where('kelurahan_id', $kelId)->where('TPS', $tps)
                ->whereIn('KET', Keterangan::MS)->orderBy('NAMA')->get()->getResultArray();
            $baris = $rows;
            $pratinjau = true;
        }
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_daftar', [
            'title' => self::JUDUL[$tahap['tahap']] . ' — TPS ' . $tps . ' ' . $kel['nama'],
            'tahap' => $tahap, 'kel' => $kel, 'tps' => $tps, 'baris' => $baris,
            'judulTahap' => self::JUDUL[$tahap['tahap']], 'pratinjau' => $pratinjau,
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P2 — Model A-Daftar Potensial Pemilih (Lampiran III): bahan coklit dari data hidup per TPS. */
    public function cetakPotensial()
    {
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $tps = (int) ($this->request->getGet('tps') ?? 0);
        $kel = $this->cekKelurahan($kelId);
        if ($kel === null || $tps <= 0) {
            return redirect()->to('tahapan')->with('galat', 'Parameter cetak potensial tidak lengkap/di luar cakupan.');
        }
        $baris = $this->db()->table('mhc')->where('kelurahan_id', $kelId)->where('TPS', $tps)
            ->whereIn('KET', Keterangan::MS)->orderBy('NAMA')->get()->getResultArray();
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_potensial', [
            'title' => 'Model A-Daftar Potensial Pemilih — TPS ' . $tps . ' ' . $kel['nama'],
            'kel' => $kel, 'tps' => $tps, 'baris' => $baris,
            'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P3 — Model A-Laporan Hasil Coklit (Lampiran IV): rekap per TPS dari jejak coklit. */
    public function cetakLaporanCoklit()
    {
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $kel = $this->cekKelurahan($kelId);
        if ($kel === null) {
            return redirect()->to('tahapan')->with('galat', 'Desa/kelurahan di luar cakupan.');
        }
        $rows = $this->db()->query("SELECT TPS AS tps,
                COUNT(DISTINCT NKK) AS kk,
                COUNT(*) AS terdaftar,
                SUM(TGL_COKLIT IS NOT NULL AND TGL_COKLIT <> '') AS tercoklit,
                SUM(KET IN ('0','U','B')) AS ms,
                SUM(KET IN ('1','2','3','4','5','6','7','8')) AS tms
            FROM mhc WHERE kelurahan_id = ? GROUP BY TPS ORDER BY TPS", [$kelId])->getResultArray();
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_laporan_coklit', [
            'title' => 'Model A-Laporan Hasil Coklit — ' . $kel['nama'],
            'kel' => $kel, 'rows' => $rows, 'konteksNama' => Konteks::nama(),
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** Sidik jari baris potret untuk deteksi perubahan elemen data. */
    private function sidikJari(string $alias): string
    {
        return "CONCAT_WS('|', {$alias}.nama, {$alias}.tgllhr, {$alias}.status, {$alias}.jenis_kelamin, {$alias}.alamat, {$alias}.rt, {$alias}.rw, {$alias}.disabilitas, {$alias}.ektp, {$alias}.kelurahan_id, {$alias}.tps)";
    }

    /**
     * Selisih dua potret per satuan wilayah.
     *
     * @return array<int, array{awal:int, baru:int, tms:int, perbaikan:int, akhir:int}>
     */
    private function diffPotret(int $idA, int $idB, string $satuan, ?int $kelId = null, ?int $kecId = null): array
    {
        $db = $this->db();
        $kolom = match ($satuan) {
            'tps' => 'a.tps',
            'kelurahan' => 'a.kelurahan_id',
            default => 'kec.kecamatan_id',
        };
        $gabung = $satuan === 'kecamatan'
            ? 'JOIN kelurahan kec ON kec.id = a.kelurahan_id'
            : '';
        $syarat = '';
        $ikat = [$idA, $idB];
        if ($kelId !== null) {
            $syarat = ' AND a.kelurahan_id = ?';
            $ikat[] = $kelId;
        } elseif ($kecId !== null) {
            $syarat = $satuan === 'kecamatan' ? ' AND kec.id IN (SELECT id FROM kelurahan WHERE kecamatan_id = ?)' : ' AND a.kelurahan_id IN (SELECT id FROM kelurahan WHERE kecamatan_id = ?)';
            $ikat[] = $kecId;
        }
        $fp = $this->sidikJari('a');
        $fpB = $this->sidikJari('b');
        $sql = "SELECT {$kolom} AS satuan,
                COUNT(DISTINCT a.nik) AS awal,
                COUNT(DISTINCT b.nik) AS akhir,
                SUM(CASE WHEN b.nik IS NULL THEN 1 ELSE 0 END) AS pergi,
                SUM(CASE WHEN b.nik IS NOT NULL AND NOT ({$fp} <=> {$fpB}) THEN 1 ELSE 0 END) AS perbaikan
            FROM arsip_pemilih a {$gabung}
            LEFT JOIN arsip_pemilih b ON b.tahap_id = ? AND b.nik = a.nik
            WHERE a.tahap_id = ? {$syarat}
            GROUP BY {$kolom}";
        $ikatSql = [$idB, $idA, ...array_slice($ikat, 2)];
        $hasil = [];
        foreach ($db->query($sql, $ikatSql)->getResultArray() as $r) {
            $hasil[(int) $r['satuan']] = [
                'awal' => (int) $r['awal'], 'akhir' => (int) $r['akhir'],
                'tms' => (int) $r['pergi'], 'perbaikan' => (int) $r['perbaikan'],
                'baru' => (int) $r['akhir'] - (int) $r['awal'] + (int) $r['pergi'],
            ];
        }
        // Satuan yang HANYA ada di potret B (mis. TPS baru) tidak muncul di sisi A.
        $kolomB = str_replace('a.', 'b.', $kolom);
        $gabungB = $satuan === 'kecamatan' ? 'JOIN kelurahan kec ON kec.id = b.kelurahan_id' : '';
        $syaratB = str_replace('a.', 'b.', $syarat);
        $sqlB = "SELECT {$kolomB} AS satuan, COUNT(*) AS akhir FROM arsip_pemilih b {$gabungB}
            WHERE b.tahap_id = ? {$syaratB} GROUP BY {$kolomB}";
        foreach ($db->query($sqlB, [$idB, ...array_slice($ikat, 2)])->getResultArray() as $r) {
            $k = (int) $r['satuan'];
            if (! isset($hasil[$k])) {
                $hasil[$k] = ['awal' => 0, 'baru' => (int) $r['akhir'], 'tms' => 0, 'perbaikan' => 0, 'akhir' => (int) $r['akhir']];
            } else {
                // Akhir yang sah adalah hitungan potret B itu sendiri (baris
                // yang HANYA ada di B tidak terlihat dari gabungan sisi A).
                $hasil[$k]['akhir'] = (int) $r['akhir'];
                $hasil[$k]['baru'] = $hasil[$k]['akhir'] - $hasil[$k]['awal'] + $hasil[$k]['tms'];
            }
        }

        return $hasil;
    }

    /** P5 — Model A-Daftar Perubahan Pemilih (Lampiran XI): baris berubah antar dua potret, per desa. */
    public function cetakPerubahan()
    {
        $db = $this->db();
        $tahap = $this->tahapById((int) ($this->request->getGet('tahap_id') ?? 0));
        $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
        $kel = $tahap !== null ? $this->cekKelurahan($kelId) : null;
        if ($tahap === null || $kel === null || $tahap['status'] !== 'ditutup') {
            return redirect()->to('tahapan')->with('galat', 'Daftar perubahan memerlukan tahap yang sudah ditetapkan.');
        }
        $sebelum = $this->tahapSebelumnya($tahap);
        if ($sebelum === null) {
            return redirect()->to('tahapan')->with('galat', 'Tidak ada tahap sebelumnya untuk dibandingkan (DPS adalah potret awal).');
        }
        $fp = $this->sidikJari('a');
        $fpB = $this->sidikJari('b');
        $rows = $db->query("SELECT b.*, 'B' AS perubahan FROM arsip_pemilih b
                LEFT JOIN arsip_pemilih a ON a.tahap_id = ? AND a.nik = b.nik
                WHERE b.tahap_id = ? AND b.kelurahan_id = ? AND a.nik IS NULL
            UNION ALL
            SELECT b.*, 'U' AS perubahan FROM arsip_pemilih b
                JOIN arsip_pemilih a ON a.tahap_id = ? AND a.nik = b.nik
                WHERE b.tahap_id = ? AND b.kelurahan_id = ? AND NOT ({$fp} <=> {$fpB})
            UNION ALL
            SELECT a.*, 'X' AS perubahan FROM arsip_pemilih a
                LEFT JOIN arsip_pemilih b ON b.tahap_id = ? AND b.nik = a.nik
                WHERE a.tahap_id = ? AND a.kelurahan_id = ? AND b.nik IS NULL",
            [(int) $sebelum['id'], (int) $tahap['id'], $kelId,
             (int) $sebelum['id'], (int) $tahap['id'], $kelId,
             (int) $tahap['id'], (int) $sebelum['id'], $kelId])->getResultArray();

        $ketLive = [];
        $nikPergi = array_column(array_filter($rows, static fn ($r) => $r['perubahan'] === 'X'), 'nik');
        if ($nikPergi !== []) {
            foreach ($db->table('mhc')->select('NIK, KET')->whereIn('NIK', $nikPergi)->get()->getResultArray() as $m) {
                $ketLive[$m['NIK']] = $m['KET'];
            }
        }
        $baris = [];
        foreach ($rows as $r) {
            $ket = match ($r['perubahan']) {
                'B' => 'B',
                'U' => 'U',
                default => $ketLive[$r['nik']] ?? 'X',
            };
            $baris[] = ['data' => [
                'NKK' => $r['nkk'], 'NIK' => $r['nik'], 'NAMA' => $r['nama'], 'TMPTLHR' => $r['tmptlhr'],
                'TGLLHR' => $r['tgllhr'], 'STATUS' => $r['status'], 'JENIS_KELAMIN' => $r['jenis_kelamin'],
                'ALAMAT' => $r['alamat'], 'RT' => $r['rt'], 'RW' => $r['rw'],
                'DISABILITAS' => $r['disabilitas'], 'EKTP' => $r['ektp'],
            ], 'ket' => $ket];
        }
        usort($baris, static fn ($a, $b) => ($a['data']['NAMA'] ?? '') <=> ($b['data']['NAMA'] ?? ''));
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_perubahan', [
            'title' => 'Model A-Daftar Perubahan Pemilih — ' . $kel['nama'],
            'tahap' => $tahap, 'sebelum' => $sebelum, 'kel' => $kel, 'baris' => $baris,
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P6/P10/P11 — Model A-Rekap Perubahan Pemilih per jenjang (Lampiran XII/XIX/XXIV). */
    public function cetakRekapPerubahan()
    {
        $db = $this->db();
        $tahap = $this->tahapById((int) ($this->request->getGet('tahap_id') ?? 0));
        $jenjang = (string) ($this->request->getGet('jenjang') ?? 'kabko');
        if ($tahap === null || $tahap['status'] !== 'ditutup' || ! in_array($jenjang, ['pps', 'ppk', 'kabko'], true)) {
            return redirect()->to('tahapan')->with('galat', 'Rekap perubahan memerlukan tahap yang sudah ditetapkan.');
        }
        $sebelum = $this->tahapSebelumnya($tahap);
        if ($sebelum === null) {
            return redirect()->to('tahapan')->with('galat', 'Tidak ada tahap sebelumnya untuk dibandingkan.');
        }
        $kelPengguna = Wilayah::kelurahanIdPengguna();
        $kecEfektif = Wilayah::kecEfektifId();

        $baris = [];
        $label = '';
        if ($jenjang === 'pps') {
            $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
            $kel = $this->cekKelurahan($kelId);
            if ($kel === null) {
                return redirect()->to('tahapan')->with('galat', 'Desa di luar cakupan.');
            }
            $diff = $this->diffPotret((int) $sebelum['id'], (int) $tahap['id'], 'tps', $kelId);
            foreach ($diff as $tps => $d) {
                $baris[] = ['satuan' => 'TPS ' . $tps] + $d;
            }
            $label = $kel['nama'] . ' — ' . $kel['kecamatan'];
        } elseif ($jenjang === 'ppk') {
            $kecId = $kecEfektif ?? (int) ($this->request->getGet('kecamatan_id') ?? 0);
            if ($kecId <= 0 || ($kecEfektif !== null && $kecId !== $kecEfektif) || $kelPengguna !== null) {
                return redirect()->to('tahapan')->with('galat', 'Kecamatan di luar cakupan.');
            }
            $diff = $this->diffPotret((int) $sebelum['id'], (int) $tahap['id'], 'kelurahan', null, $kecId);
            $namaKel = [];
            foreach ($db->table('kelurahan')->where('kecamatan_id', $kecId)->get()->getResultArray() as $k) {
                $namaKel[(int) $k['id']] = $k['nama'];
            }
            foreach ($diff as $kelId => $d) {
                $baris[] = ['satuan' => $namaKel[$kelId] ?? ('Kelurahan ' . $kelId)] + $d;
            }
            $kec = $db->table('kecamatan')->where('id', $kecId)->get()->getRowArray();
            $label = 'Kecamatan ' . ($kec['nama'] ?? '');
        } else {
            if ($kelPengguna !== null || $kecEfektif !== null) {
                return redirect()->to('tahapan')->with('galat', 'Rekap kabupaten hanya untuk admin kabupaten.');
            }
            $diff = $this->diffPotret((int) $sebelum['id'], (int) $tahap['id'], 'kecamatan');
            $namaKec = [];
            foreach ($db->table('kecamatan')->get()->getResultArray() as $k) {
                $namaKec[(int) $k['id']] = $k['nama'];
            }
            foreach ($diff as $kecId => $d) {
                $baris[] = ['satuan' => $namaKec[$kecId] ?? ('Kecamatan ' . $kecId)] + $d;
            }
            $label = $this->pengaturanPeta()['kabupaten'] ?? '';
        }
        usort($baris, static fn ($a, $b) => $a['satuan'] <=> $b['satuan']);
        $total = ['awal' => 0, 'baru' => 0, 'tms' => 0, 'perbaikan' => 0, 'akhir' => 0];
        foreach ($baris as $b) {
            foreach ($total as $k => $_) {
                $total[$k] += $b[$k];
            }
        }
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_rekap_perubahan', [
            'title' => 'Model A-Rekap ' . strtoupper($jenjang) . ' Perubahan Pemilih — ' . $label,
            'tahap' => $tahap, 'sebelum' => $sebelum, 'jenjang' => $jenjang, 'label' => $label,
            'baris' => $baris, 'total' => $total,
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P13 — Model A-Rekap KabKo (Lampiran XXVI) dari agregat potret tahap. */
    public function cetakRekapKabko()
    {
        $db = $this->db();
        $tahap = $this->tahapById((int) ($this->request->getGet('tahap_id') ?? 0));
        if ($tahap === null || $tahap['status'] !== 'ditutup') {
            return redirect()->to('tahapan')->with('galat', 'Rekap KabKo memerlukan tahap yang sudah ditetapkan.');
        }
        if (Wilayah::kelurahanIdPengguna() !== null || Wilayah::kecEfektifId() !== null) {
            return redirect()->to('tahapan')->with('galat', 'Rekap kabupaten hanya untuk admin kabupaten.');
        }
        $rows = $db->table('arsip_tahap')->select('arsip_tahap.metrik, kecamatan.nama AS kec_nama')
            ->join('kecamatan', 'kecamatan.id = arsip_tahap.kecamatan_id')
            ->where('arsip_tahap.tahap_id', (int) $tahap['id'])->where('arsip_tahap.tingkat', 'kecamatan')
            ->orderBy('kecamatan.nama')->get()->getResultArray();
        $baris = [];
        $total = ['desa' => 0, 'tps' => 0, 'ms' => 0];
        foreach ($rows as $r) {
            $m = json_decode((string) $r['metrik'], true) ?? [];
            $baris[] = ['kecamatan' => $r['kec_nama'], 'desa' => (int) ($m['desa'] ?? 0), 'tps' => (int) ($m['tps'] ?? 0), 'ms' => (int) ($m['ms'] ?? 0)];
            $total['desa'] += (int) ($m['desa'] ?? 0);
            $total['tps'] += (int) ($m['tps'] ?? 0);
            $total['ms'] += (int) ($m['ms'] ?? 0);
        }
        $peta = $this->pengaturanPeta();

        return view('tahapan/cetak_rekap_kabko', [
            'title' => 'Model A-Rekap KabKo — ' . self::JUDUL[$tahap['tahap']],
            'tahap' => $tahap, 'baris' => $baris, 'total' => $total, 'judulTahap' => self::JUDUL[$tahap['tahap']],
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }

    /** P7/P8/P14 — Berita Acara pleno rekapitulasi/penetapan per jenjang. */
    public function baCetak()
    {
        $db = $this->db();
        $tahap = $this->tahapById((int) ($this->request->getGet('tahap_id') ?? 0));
        $jenjang = (string) ($this->request->getGet('jenjang') ?? 'kabko');
        if ($tahap === null || $tahap['status'] !== 'ditutup' || ! in_array($jenjang, ['pps', 'ppk', 'kabko'], true)) {
            return redirect()->to('tahapan')->with('galat', 'Berita Acara memerlukan tahap yang sudah ditetapkan.');
        }
        $label = '';
        $angka = ['ms' => 0, 'laki' => 0, 'perempuan' => 0, 'satuan' => []];
        if ($jenjang === 'kabko') {
            if (Wilayah::kelurahanIdPengguna() !== null || Wilayah::kecEfektifId() !== null) {
                return redirect()->to('tahapan')->with('galat', 'Berita Acara kabupaten hanya untuk admin.');
            }
            $kab = $db->table('arsip_tahap')->where('tahap_id', (int) $tahap['id'])->where('tingkat', 'kabupaten')->get()->getRowArray();
            $m = $kab !== null ? (json_decode((string) $kab['metrik'], true) ?? []) : [];
            $angka = ['ms' => (int) ($m['ms'] ?? 0), 'laki' => (int) ($m['laki'] ?? 0), 'perempuan' => (int) ($m['perempuan'] ?? 0),
                'satuan' => ['Kecamatan' => (int) ($m['kecamatan'] ?? 0), 'Desa/Kelurahan' => (int) ($m['desa'] ?? 0), 'TPS' => (int) ($m['tps'] ?? 0)]];
            $label = $this->pengaturanPeta()['kabupaten'] ?? '';
        } elseif ($jenjang === 'ppk') {
            $kecId = Wilayah::kecEfektifId() ?? (int) ($this->request->getGet('kecamatan_id') ?? 0);
            if ($kecId <= 0 || Wilayah::kelurahanIdPengguna() !== null) {
                return redirect()->to('tahapan')->with('galat', 'Kecamatan di luar cakupan.');
            }
            $row = $db->table('arsip_tahap')->where('tahap_id', (int) $tahap['id'])->where('tingkat', 'kecamatan')->where('kecamatan_id', $kecId)->get()->getRowArray();
            $m = $row !== null ? (json_decode((string) $row['metrik'], true) ?? []) : [];
            $angka = ['ms' => (int) ($m['ms'] ?? 0), 'laki' => (int) ($m['laki'] ?? 0), 'perempuan' => (int) ($m['perempuan'] ?? 0),
                'satuan' => ['Desa/Kelurahan' => (int) ($m['desa'] ?? 0), 'TPS' => (int) ($m['tps'] ?? 0)]];
            $kec = $db->table('kecamatan')->where('id', $kecId)->get()->getRowArray();
            $label = 'Kecamatan ' . ($kec['nama'] ?? '');
        } else {
            $kelId = (int) ($this->request->getGet('kelurahan_id') ?? 0);
            $kel = $this->cekKelurahan($kelId);
            if ($kel === null) {
                return redirect()->to('tahapan')->with('galat', 'Desa di luar cakupan.');
            }
            $row = $db->table('arsip_tahap')->where('tahap_id', (int) $tahap['id'])->where('tingkat', 'kelurahan')->where('kelurahan_id', $kelId)->get()->getRowArray();
            $m = $row !== null ? (json_decode((string) $row['metrik'], true) ?? []) : [];
            $angka = ['ms' => (int) ($m['ms'] ?? 0), 'laki' => (int) ($m['laki'] ?? 0), 'perempuan' => (int) ($m['perempuan'] ?? 0),
                'satuan' => ['TPS' => (int) ($m['tps'] ?? 0)]];
            $label = $kel['nama'] . ', Kecamatan ' . $kel['kecamatan'];
        }
        $peta = $this->pengaturanPeta();

        return view('tahapan/ba_cetak', [
            'title' => 'Berita Acara ' . strtoupper($jenjang) . ' — ' . self::JUDUL[$tahap['tahap']],
            'tahap' => $tahap, 'jenjang' => $jenjang, 'label' => $label, 'angka' => $angka,
            'judulTahap' => self::JUDUL[$tahap['tahap']],
            'provinsi' => $peta['provinsi'] ?? '', 'kabupaten' => $peta['kabupaten'] ?? '',
        ]);
    }
}
