<?php

namespace App\Controllers;

use App\Models\RiwayatModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Fase 3 — Impor data dari CSV/Excel.
 *
 * Dua jenis impor:
 * 1. Data coklit (`mhc`) — mode GANTI (timpa semua, dengan cadangan
 *    CSV otomatis terlebih dahulu) atau TAMBAH (lewati UID yang
 *    sudah ada). Baris pertama berkas = nama kolom seperti kolom mhc
 *    (NIK, NKK, NAMA, DESA, TPS, KET, ...). Sesudah impor ditampilkan
 *    REKONSILIASI: hitungan daftar turunan (MS, Tersaring, Baru,
 *    Pindah TPS) dari basis data — karena semuanya VIEW di atas mhc,
 *    tidak ada lagi langkah "analisis" manual.
 * 2. Keterangan status (`tms8`) — kolom NIK + STATUS; baris yang NIK-nya
 *    sudah ada diperbarui, yang belum ada ditambahkan.
 *
 * Hanya Admin PPK & Operator (tanpa cakupan desa).
 */
class Impor extends BaseController
{
    private const KOLOM_MHC = [
        'UID', 'ID', 'NKK', 'NIK', 'NAMA', 'TMPTLHR', 'TGLLHR', 'STATUS',
        'JENIS_KELAMIN', 'ALAMAT', 'RT', 'RW', 'DISABILITAS', 'EKTP', 'KET',
        'SUMBER', 'TPS', 'LAST_UPDATE', 'LAT', 'LONG', 'PROV', 'KAB', 'KEC',
        'DESA', 'TGL_COKLIT',
    ];

    private function wajibImpor()
    {
        $peran = session()->get('pengguna_peran');
        if (! in_array($peran, ['admin', 'operator'], true)
            || (session()->get('pengguna_desa') !== null && session()->get('pengguna_desa') !== '')
        ) {
            return redirect()->to('/')->with('galat', 'Impor data hanya untuk Admin PPK / Operator kecamatan.');
        }

        return null;
    }

    private function dataHalaman(string $judul): array
    {
        return [
            'title'      => $judul,
            'title_page' => $judul,
            'kecamatan'  => $this->kec,
        ];
    }

    public function index()
    {
        if ($tolak = $this->wajibImpor()) {
            return $tolak;
        }

        $data = $this->dataHalaman('Impor Data');
        $data['catatan'] = db_connect()->table('impor_log')
            ->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();

        return view('impor/index', $data);
    }

    // -- Impor mhc ---------------------------------------------------

    public function prosesMhc()
    {
        if ($tolak = $this->wajibImpor()) {
            return $tolak;
        }

        $mode = $this->request->getPost('mode') === 'ganti' ? 'ganti' : 'tambah';
        [$baris, $galatBerkas] = $this->bacaBerkas('berkas');
        if ($galatBerkas !== null) {
            return redirect()->to('impor')->with('galat', $galatBerkas);
        }

        $namaBerkas = $this->request->getFile('berkas')->getClientName();
        $db = db_connect();

        // Petakan kolom dari baris judul
        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $baris[0]);
        $peta = [];
        foreach (self::KOLOM_MHC as $kolom) {
            $idx = array_search($kolom, $judul, true);
            if ($idx !== false) {
                $peta[$kolom] = $idx;
            }
        }
        foreach (['NIK', 'NAMA', 'DESA', 'TPS', 'KET'] as $wajib) {
            if (! isset($peta[$wajib])) {
                return redirect()->to('impor')
                    ->with('galat', 'Kolom wajib "' . $wajib . '" tidak ditemukan di baris judul berkas.');
            }
        }

        $uidAda = [];
        if ($mode === 'tambah') {
            foreach ($db->table('mhc')->select('UID')->get()->getResultArray() as $r) {
                $uidAda[(string) $r['UID']] = true;
            }
        }

        $siap = [];
        $gagal = [];
        $idBerikut = (int) ($db->table('mhc')->selectMax('ID')->get()->getRowArray()['ID'] ?? 0);
        $jumlahBarisData = count($baris) - 1;

        for ($i = 1; $i < count($baris); $i++) {
            $mentah = $baris[$i];
            $ambil = static function (string $kolom) use ($peta, $mentah) {
                if (! isset($peta[$kolom])) {
                    return null;
                }
                $v = $mentah[$peta[$kolom]] ?? null;
                if (is_float($v) || is_int($v)) {
                    $v = number_format((float) $v, 0, '', '');
                }
                $v = trim((string) $v);
                return $v === '' ? null : $v;
            };

            $nik = (string) $ambil('NIK');
            if (! preg_match('/^\d{16}$/', $nik)) {
                $gagal[] = 'Baris ' . ($i + 1) . ': NIK "' . $nik . '" tidak sah (harus 16 angka) — dilewati.';
                continue;
            }

            $uid = $ambil('UID');
            if ($mode === 'tambah' && $uid !== null && isset($uidAda[$uid])) {
                $gagal[] = 'Baris ' . ($i + 1) . ': UID ' . $uid . ' sudah ada — dilewati.';
                continue;
            }
            if ($uid === null) {
                $uid = strtoupper(bin2hex(random_bytes(8)));
            }

            $row = [];
            foreach (self::KOLOM_MHC as $kolom) {
                $row[$kolom] = $ambil($kolom);
            }
            $row['UID'] = $uid;
            $idFile = $ambil('ID');
            $row['ID'] = ($mode === 'tambah' || $idFile === null) ? ++$idBerikut : (int) $idFile;
            if ($mode === 'ganti' && $idFile !== null) {
                $idBerikut = max($idBerikut, (int) $idFile);
            }
            $row['TGL_COKLIT'] = $this->normalisasiTanggal($row['TGL_COKLIT']);
            $siap[] = $row;
        }

        // Cadangan otomatis sebelum menimpa
        $berkasCadangan = null;
        if ($mode === 'ganti') {
            $berkasCadangan = $this->cadangkanMhc();
            if ($berkasCadangan === null) {
                return redirect()->to('impor')->with('galat', 'Gagal membuat cadangan data lama — impor dibatalkan demi keamanan.');
            }
        }

        $db->transStart();
        if ($mode === 'ganti') {
            $db->table('mhc')->truncate();
        }
        foreach (array_chunk($siap, 500) as $potong) {
            $db->table('mhc')->insertBatch($potong);
        }
        $db->table('impor_log')->insert([
            'jenis'             => 'mhc',
            'mode'              => $mode,
            'nama_berkas'       => $namaBerkas,
            'jumlah_baris'      => count($siap),
            'jumlah_gagal'      => count($gagal),
            'berkas_cadangan'   => $berkasCadangan,
            'pengguna_id'       => session()->get('pengguna_id'),
            'pengguna_username' => session()->get('pengguna_username'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        (new RiwayatModel())->insert([
            'tabel'             => 'mhc',
            'kunci_uid'         => null,
            'nik'               => null,
            'aksi'              => 'impor',
            'data_lama'         => null,
            'data_baru'         => json_encode([
                'mode' => $mode, 'berkas' => $namaBerkas,
                'masuk' => count($siap), 'gagal' => count($gagal),
                'cadangan' => $berkasCadangan,
            ]),
            'pengguna_id'       => session()->get('pengguna_id'),
            'pengguna_username' => session()->get('pengguna_username'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('impor')->with('galat', 'Impor gagal di tengah jalan — basis data dikembalikan seperti semula.');
        }

        $data = $this->dataHalaman('Hasil Impor Data Coklit');
        $data['ringkasan'] = [
            'jenis' => 'Data Coklit (mhc)', 'mode' => $mode, 'berkas' => $namaBerkas,
            'terbaca' => $jumlahBarisData, 'masuk' => count($siap), 'gagal' => $gagal,
            'cadangan' => $berkasCadangan,
        ];
        $data['rekonsiliasi'] = $this->rekonsiliasi();

        return view('impor/hasil', $data);
    }

    // -- Impor tms8 --------------------------------------------------

    public function prosesTms8()
    {
        if ($tolak = $this->wajibImpor()) {
            return $tolak;
        }

        [$baris, $galatBerkas] = $this->bacaBerkas('berkas_tms8');
        if ($galatBerkas !== null) {
            return redirect()->to('impor')->with('galat', $galatBerkas);
        }

        $namaBerkas = $this->request->getFile('berkas_tms8')->getClientName();
        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $baris[0]);
        $idxNik = array_search('NIK', $judul, true);
        $idxStatus = array_search('STATUS', $judul, true);
        if ($idxStatus === false) {
            $idxStatus = array_search('KETERANGAN', $judul, true);
        }
        if ($idxNik === false || $idxStatus === false) {
            return redirect()->to('impor')->with('galat', 'Berkas tms8 wajib punya kolom NIK dan STATUS (atau KETERANGAN).');
        }

        $db = db_connect();
        $masuk = 0;
        $gagal = [];
        $db->transStart();
        for ($i = 1; $i < count($baris); $i++) {
            $nik = trim((string) ($baris[$i][$idxNik] ?? ''));
            if (is_numeric($nik)) {
                $nik = number_format((float) $nik, 0, '', '');
            }
            $status = trim((string) ($baris[$i][$idxStatus] ?? ''));
            if (! preg_match('/^\d{16}$/', $nik) || $status === '') {
                $gagal[] = 'Baris ' . ($i + 1) . ': NIK/keterangan tidak lengkap — dilewati.';
                continue;
            }
            $ada = $db->table('tms8')->where('NIK', $nik)->countAllResults();
            if ($ada > 0) {
                $db->table('tms8')->where('NIK', $nik)->update(['STATUS' => $status]);
            } else {
                $db->table('tms8')->insert(['NIK' => $nik, 'STATUS' => $status]);
            }
            $masuk++;
        }
        $db->table('impor_log')->insert([
            'jenis'             => 'tms8',
            'mode'              => 'tambah',
            'nama_berkas'       => $namaBerkas,
            'jumlah_baris'      => $masuk,
            'jumlah_gagal'      => count($gagal),
            'berkas_cadangan'   => null,
            'pengguna_id'       => session()->get('pengguna_id'),
            'pengguna_username' => session()->get('pengguna_username'),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('impor')->with('galat', 'Impor tms8 gagal — basis data dikembalikan seperti semula.');
        }

        $data = $this->dataHalaman('Hasil Impor Keterangan TMS');
        $data['ringkasan'] = [
            'jenis' => 'Keterangan Status (tms8)', 'mode' => 'tambah', 'berkas' => $namaBerkas,
            'terbaca' => count($baris) - 1, 'masuk' => $masuk, 'gagal' => $gagal,
            'cadangan' => null,
        ];
        $data['rekonsiliasi'] = $this->rekonsiliasi();

        return view('impor/hasil', $data);
    }

    // -- Pembantu ----------------------------------------------------

    /**
     * Baca berkas unggahan menjadi array baris (baris 0 = judul).
     *
     * @return array{0: ?array, 1: ?string} [baris, pesanGalat]
     */
    private function bacaBerkas(string $namaField): array
    {
        $berkas = $this->request->getFile($namaField);
        if ($berkas === null || ! $berkas->isValid()) {
            return [null, 'Berkas tidak diterima. Pilih berkas CSV/XLSX terlebih dahulu.'];
        }

        $ekstensi = strtolower($berkas->getClientExtension());
        if (! in_array($ekstensi, ['csv', 'xlsx', 'xls'], true)) {
            return [null, 'Format berkas harus CSV, XLSX, atau XLS.'];
        }

        if ($berkas->getSize() > 20 * 1024 * 1024) {
            return [null, 'Ukuran berkas melebihi 20 MB.'];
        }

        $tujuan = WRITEPATH . 'uploads/impor-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ekstensi;
        $berkas->move(WRITEPATH . 'uploads', basename($tujuan));

        try {
            $reader = IOFactory::createReaderForFile($tujuan);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($tujuan);
            $sheet = $spreadsheet->getActiveSheet();
            $baris = $sheet->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            @unlink($tujuan);
            return [null, 'Berkas tidak bisa dibaca: ' . $e->getMessage()];
        }
        @unlink($tujuan);

        // Buang baris yang seluruhnya kosong
        $baris = array_values(array_filter($baris, static function ($r) {
            foreach ((array) $r as $v) {
                if ($v !== null && trim((string) $v) !== '') {
                    return true;
                }
            }
            return false;
        }));

        if (count($baris) < 2) {
            return [null, 'Berkas kosong atau hanya berisi baris judul.'];
        }

        return [$baris, null];
    }

    private function normalisasiTanggal($nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_numeric($nilai)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $ts = strtotime((string) $nilai);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    /** Ekspor seluruh mhc saat ini ke CSV cadangan. Null bila gagal. */
    private function cadangkanMhc(): ?string
    {
        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $nama = 'mhc-sebelum-impor-' . date('Ymd-His') . '.csv';
        $path = $folder . '/' . $nama;
        $fh = fopen($path, 'w');
        if ($fh === false) {
            return null;
        }
        try {
            fputcsv($fh, self::KOLOM_MHC);
            $query = db_connect()->table('mhc')->select(implode(',', self::KOLOM_MHC))->get();
            while ($row = $query->getUnbufferedRow('array')) {
                $urut = [];
                foreach (self::KOLOM_MHC as $k) {
                    $urut[] = $row[$k] ?? null;
                }
                fputcsv($fh, $urut);
            }
        } catch (\Throwable) {
            fclose($fh);
            @unlink($path);
            return null;
        }
        fclose($fh);

        return $nama;
    }

    /** Hitungan rekonsiliasi dari view turunan (selalu mutakhir). */
    private function rekonsiliasi(): array
    {
        $db = db_connect();
        $hitung = static fn (string $tabel) => (int) $db->table($tabel)->countAllResults();

        return [
            'Total hasil coklit (mhc)' => $hitung('mhc'),
            'Pemilih MS / aktif'       => $hitung('ms'),
            'Pemilih baru'             => (int) $db->table('mhc')->where('KET', 'B')->countAllResults(),
            'Pemilih tersaring (TMS)'  => $hitung('tersaring'),
            'Pindah TPS (KET 8)'       => $hitung('pindahtps'),
            'Jumlah KK (dari MS)'      => $hitung('v_nkk'),
        ];
    }
}
