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
 * Hanya Admin Kabupaten & Operator PPK (tanpa cakupan desa).
 *
 * Fase 7: impor selalu UNTUK SATU kecamatan tujuan. Mode GANTI hanya
 * menimpa baris kecamatan tujuan itu (dengan cadangan khusus kecamatan
 * itu) — baris kecamatan lain di basis data kabupaten tidak tersentuh.
 * Baris berkas yang kolom KEC-nya berbeda dari tujuan dilewati.
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
            'kabupaten'  => $this->kab,
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
        $data['daftarKecamatan'] = $this->daftarKecamatan();
        $data['kecTerkunci'] = $this->kecPengguna();

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

        // Kecamatan tujuan impor: akun PPK terkunci ke kecamatannya;
        // Admin memilih dari form (atau menulis nama kecamatan baru
        // untuk kecamatan yang datanya pertama kali masuk).
        $target = $this->kecPengguna();
        if ($target === null) {
            $target = trim((string) $this->request->getPost('kecamatan_target'));
            $namaBaru = strtoupper(trim((string) $this->request->getPost('kecamatan_baru')));
            if ($namaBaru !== '') {
                $target = $namaBaru;
            }
            if ($target === '') {
                return redirect()->to('impor')->with('galat', 'Pilih dulu kecamatan tujuan impor.');
            }
        }

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

        // Mode tambah: saring duplikat dengan kunci gabungan
        // UID+KET+DESA+TPS (App\Libraries\ImporCoklit), BUKAN UID
        // saja — satu UID dipakai bersama baris tersaring + baris baru
        // orang yang sama, jadi saringan UID saja membuang baris sah.
        $kunciAda = [];
        if ($mode === 'tambah') {
            foreach ($db->table('mhc')->select('UID, KET, DESA, TPS')->get()->getResultArray() as $r) {
                $kunciAda[\App\Libraries\ImporCoklit::kunciBaris($r['UID'], $r['KET'], $r['DESA'], $r['TPS'])] = true;
            }
        }

        // Penanda NIK yang sudah mempunyai baris aktif (satu NIK satu
        // baris aktif). Mode ganti: baris aktif kecamatan tujuan sendiri
        // tidak menghalangi karena akan diganti.
        $nikAktifAda = [];
        $qAktif = $db->table('mhc')->select('NIK')->whereIn('KET', \App\Libraries\Keterangan::MS);
        if ($mode === 'ganti') {
            $qAktif->where('KEC !=', $target);
        }
        foreach ($qAktif->get()->getResultArray() as $r) {
            $nikAktifAda[(string) $r['NIK']] = true;
        }

        $siap = [];
        $gagal = [];
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
            if ($uid === null) {
                $uid = strtoupper(bin2hex(random_bytes(8)));
            }

            $row = [];
            foreach (self::KOLOM_MHC as $kolom) {
                $row[$kolom] = $ambil($kolom);
            }
            $row['UID'] = $uid;
            // ID tidak diisi dari berkas: kini AUTO_INCREMENT.
            unset($row['ID']);
            $row['TGL_COKLIT'] = $this->normalisasiTanggal($row['TGL_COKLIT']);

            $kecBerkas = $row['KEC'] !== null ? strtoupper(trim((string) $row['KEC'])) : '';
            if ($kecBerkas !== '' && $kecBerkas !== strtoupper($target)) {
                $gagal[] = 'Baris ' . ($i + 1) . ': KEC "' . $kecBerkas . '" bukan kecamatan tujuan (' . $target . ') — dilewati.';
                continue;
            }
            $row['KEC'] = $target;

            if (\App\Libraries\Keterangan::isMs((string) $row['KET'])) {
                if (isset($nikAktifAda[$nik])) {
                    $gagal[] = 'Baris ' . ($i + 1) . ': NIK ' . $nik . ' sudah mempunyai baris aktif — dialihkan (satu NIK satu baris aktif).';
                    continue;
                }
                $nikAktifAda[$nik] = true;
            }
            if ($mode === 'tambah') {
                $kunci = \App\Libraries\ImporCoklit::kunciBaris($row['UID'], $row['KET'], $row['DESA'], $row['TPS']);
                if (isset($kunciAda[$kunci])) {
                    $gagal[] = 'Baris ' . ($i + 1) . ': baris yang sama (UID+KET+Desa+TPS) sudah ada — dilewati.';
                    continue;
                }
                $kunciAda[$kunci] = true;
            }

            $siap[] = $row;
        }

        // Cadangan otomatis sebelum menimpa
        $berkasCadangan = null;
        if ($mode === 'ganti') {
            $berkasCadangan = $this->cadangkanMhc($target);
            if ($berkasCadangan === null) {
                return redirect()->to('impor')->with('galat', 'Gagal membuat cadangan data lama — impor dibatalkan demi keamanan.');
            }
        }

        $db->transStart();
        if ($mode === 'ganti') {
            $db->table('mhc')->where('KEC', $target)->delete();
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
            'kecamatan'         => $target,
            'desa'              => null,
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
            'kecamatan' => $target,
            'terbaca' => $jumlahBarisData, 'masuk' => count($siap), 'gagal' => $gagal,
            'cadangan' => $berkasCadangan,
        ];
        $data['rekonsiliasi'] = $this->rekonsiliasi($target);

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
        $data['rekonsiliasi'] = $this->rekonsiliasi($this->kecEfektif());

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

    /** Ekspor mhc (satu kecamatan) ke CSV cadangan. Null bila gagal. */
    private function cadangkanMhc(?string $kec = null): ?string
    {
        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $slug = $kec !== null ? preg_replace('/[^A-Za-z0-9]+/', '-', $kec) . '-' : '';
        $nama = 'mhc-' . $slug . 'sebelum-impor-' . date('Ymd-His') . '.csv';
        $path = $folder . '/' . $nama;
        $fh = fopen($path, 'w');
        if ($fh === false) {
            return null;
        }
        try {
            fputcsv($fh, self::KOLOM_MHC);
            $builderCad = db_connect()->table('mhc')->select(implode(',', self::KOLOM_MHC));
            if ($kec !== null) {
                $builderCad->where('KEC', $kec);
            }
            $query = $builderCad->get();
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
    private function rekonsiliasi(?string $kec = null): array
    {
        $db = db_connect();
        $hitung = static function (string $tabel) use ($db, $kec) {
            $b = $db->table($tabel);
            if ($kec !== null) {
                $b->where('KEC', $kec);
            }

            return (int) $b->countAllResults();
        };
        $baru = $db->table('mhc')->where('KET', 'B');
        if ($kec !== null) {
            $baru->where('KEC', $kec);
        }

        return [
            'Total hasil coklit (mhc)' => $hitung('mhc'),
            'Pemilih MS / aktif'       => $hitung('ms'),
            'Pemilih baru'             => (int) $baru->countAllResults(),
            'Pemilih tersaring (TMS)'  => $hitung('tersaring'),
            'Pindah TPS (KET 8)'       => $hitung('pindahtps'),
            'Jumlah KK (dari MS)'      => $hitung('v_nkk'),
        ];
    }
}
