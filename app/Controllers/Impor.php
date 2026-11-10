<?php

namespace App\Controllers;

use App\Libraries\ImporCoklit;
use App\Libraries\Keterangan;
use App\Libraries\WilayahMaster;
use App\Models\RiwayatModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor data dari CSV/Excel lewat web.
 *
 * Dua jenis impor:
 * 1. Data coklit (`mhc`) — mode GANTI (timpa kecamatan tujuan, dengan
 *    cadangan CSV otomatis terlebih dahulu) atau TAMBAH (lewati baris
 *    yang sama persis). Dua format berkas dikenali: format aplikasi
 *    (judul = nama kolom mhc + KEC/DESA) dan format ekspor Sidalih
 *    (KEC_ID/KECAMATAN/KEL_ID/KELURAHAN/DPID/KETERANGAN).
 * 2. Keterangan status (`tms8`) — kolom NIK + STATUS; baris yang
 *    NIK-nya sudah ada diperbarui, yang belum ada ditambahkan.
 *
 * WILAYAH TERNORMALISASI (Skema Final 2026): wilayah baris diselesaikan
 * ke tabel master lewat App\Libraries\WilayahMaster dan mhc hanya
 * menyimpan `kelurahan_id`. Impor selalu UNTUK SATU kecamatan tujuan:
 * mode GANTI hanya menimpa baris kecamatan tujuan; baris berkas dari
 * kecamatan lain dilewati.
 *
 * Hanya Admin Kabupaten & Operator PPK (tanpa cakupan desa).
 */
class Impor extends BaseController
{
    private function wajibImpor()
    {
        $peran = session()->get('pengguna_peran');
        if (! in_array($peran, ['admin', 'operator'], true)
            || session()->get('pengguna_kelurahan_id') !== null
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
        $data['daftarKecamatan'] = $this->daftarKecamatanLengkap();
        $kecIdTerkunci = session()->get('pengguna_kecamatan_id') !== null
            ? (int) session()->get('pengguna_kecamatan_id') : null;
        $data['kecTerkunci'] = $kecIdTerkunci;
        $data['kecTerkunciNama'] = $kecIdTerkunci !== null
            ? (new WilayahMaster($db = db_connect()))->kecamatan($kecIdTerkunci)['nama'] ?? null : null;

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
        $master = new WilayahMaster($db);

        // Kecamatan tujuan: akun PPK terkunci ke kecamatannya; Admin
        // memilih kecamatan master dari form, atau menulis nama
        // kecamatan baru (master dibuat otomatis).
        $targetId = session()->get('pengguna_kecamatan_id') !== null
            ? (int) session()->get('pengguna_kecamatan_id') : null;
        if ($targetId === null) {
            $namaBaru = WilayahMaster::rapi((string) $this->request->getPost('kecamatan_baru'));
            if ($namaBaru !== '') {
                $targetId = $master->kecamatanId(null, $namaBaru);
            } else {
                $targetId = (int) $this->request->getPost('kecamatan_target') ?: null;
            }
            if ($targetId === null || $master->kecamatan($targetId) === null) {
                return redirect()->to('impor')->with('galat', 'Pilih dulu kecamatan tujuan impor.');
            }
        }
        $targetNama = (string) ($master->kecamatan($targetId)['nama'] ?? '');

        // Petakan kolom dari baris judul (dua format dikenali).
        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $baris[0]);
        $format = ImporCoklit::deteksiFormat($judul);
        if ($format === null) {
            return redirect()->to('impor')
                ->with('galat', 'Format berkas tidak dikenal (judul bukan format aplikasi maupun Sidalih).');
        }
        $peta = ImporCoklit::petaKolom($judul, $format);
        $petaW = ImporCoklit::petaWilayah($judul);
        foreach (ImporCoklit::KOLOM_WAJIB as $wajib) {
            if (! isset($peta[$wajib])) {
                return redirect()->to('impor')
                    ->with('galat', 'Kolom wajib "' . $wajib . '" tidak ditemukan di baris judul berkas.');
            }
        }
        if (! isset($petaW['kel_nama'])) {
            return redirect()->to('impor')
                ->with('galat', 'Berkas tidak mempunyai kolom desa/kelurahan (DESA atau KELURAHAN).');
        }

        $kelurahanTarget = array_map(
            static fn ($k) => (int) $k['id'],
            $master->kelurahanDi($targetId)
        );

        // Mode tambah: saring duplikat dengan kunci gabungan
        // UID+KET+KELURAHAN+TPS (App\Libraries\ImporCoklit), BUKAN UID
        // saja — satu UID dipakai bersama baris tersaring + baris baru
        // orang yang sama.
        $kunciAda = [];
        if ($mode === 'tambah') {
            foreach ($db->table('mhc')->select('UID, KET, kelurahan_id, TPS')->get()->getResultArray() as $r) {
                $kunciAda[ImporCoklit::kunciBaris($r['UID'], $r['KET'], $r['kelurahan_id'], $r['TPS'])] = true;
            }
        }

        // Penanda NIK yang sudah mempunyai baris aktif (satu NIK satu
        // baris aktif). Mode ganti: baris aktif kecamatan tujuan sendiri
        // tidak menghalangi karena akan diganti.
        $nikAktifAda = [];
        $qAktif = $db->table('mhc')->select('NIK')->whereIn('KET', Keterangan::MS);
        if ($mode === 'ganti' && $kelurahanTarget !== []) {
            $qAktif->whereNotIn('kelurahan_id', $kelurahanTarget);
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
            $ambilW = static function (string $kunci) use ($petaW, $mentah) {
                if (! isset($petaW[$kunci])) {
                    return null;
                }
                $v = trim((string) ($mentah[$petaW[$kunci]] ?? ''));
                return $v === '' ? null : $v;
            };

            $nik = (string) $ambil('NIK');
            if (! preg_match('/^\d{16}$/', $nik)) {
                $gagal[] = 'Baris ' . ($i + 1) . ': NIK "' . $nik . '" tidak sah (harus 16 angka) — dilewati.';
                continue;
            }
            if ($ambil('NAMA') === null || $ambil('TPS') === null) {
                $gagal[] = 'Baris ' . ($i + 1) . ': NAMA/TPS kosong — dilewati.';
                continue;
            }
            $ket = (string) $ambil('KET');
            if (! array_key_exists($ket, Keterangan::daftar())) {
                $gagal[] = 'Baris ' . ($i + 1) . ': kode keterangan "' . $ket . '" tidak dikenal — dilewati.';
                continue;
            }

            // Wilayah baris: kecamatan berkas harus sama dengan tujuan.
            $kecNamaBaris = $ambilW('kec_nama');
            if ($kecNamaBaris !== null && WilayahMaster::rapi($kecNamaBaris) !== $targetNama) {
                $gagal[] = 'Baris ' . ($i + 1) . ': kecamatan "' . $kecNamaBaris . '" bukan kecamatan tujuan (' . $targetNama . ') — dilewati.';
                continue;
            }
            $kelNama = $ambilW('kel_nama');
            if ($kelNama === null) {
                $gagal[] = 'Baris ' . ($i + 1) . ': desa/kelurahan kosong — dilewati.';
                continue;
            }
            $kelId = $master->kelurahanId($targetId, $ambilW('kel_kode'), $kelNama);

            $row = [];
            foreach (ImporCoklit::KOLOM_ISI as $kolom) {
                $row[$kolom] = $ambil($kolom);
            }
            $row['UID'] ??= strtoupper(bin2hex(random_bytes(8)));
            $row['kelurahan_id'] = $kelId;
            $row['TGL_COKLIT'] = ImporCoklit::normalisasiTanggal($row['TGL_COKLIT']);
            if ($format === 'sidalih') {
                $row['LAST_UPDATE'] = ImporCoklit::normalisasiTanggal($row['LAST_UPDATE']);
            }
            $row['TGL_LAHIR'] = ImporCoklit::tanggalLahirIso($row['TGLLHR']);

            if (Keterangan::isMs($ket)) {
                if (isset($nikAktifAda[$nik])) {
                    $gagal[] = 'Baris ' . ($i + 1) . ': NIK ' . $nik . ' sudah mempunyai baris aktif — dialihkan (satu NIK satu baris aktif).';
                    continue;
                }
                $nikAktifAda[$nik] = true;
            }
            if ($mode === 'tambah') {
                $kunci = ImporCoklit::kunciBaris($row['UID'], $row['KET'], $row['kelurahan_id'], $row['TPS']);
                if (isset($kunciAda[$kunci])) {
                    $gagal[] = 'Baris ' . ($i + 1) . ': baris yang sama (UID+KET+Kelurahan+TPS) sudah ada — dilewati.';
                    continue;
                }
                $kunciAda[$kunci] = true;
            }

            $siap[] = $row;
        }

        // Cadangan otomatis sebelum menimpa
        $berkasCadangan = null;
        if ($mode === 'ganti') {
            $berkasCadangan = $this->cadangkanMhc($targetId, $targetNama);
            if ($berkasCadangan === null) {
                return redirect()->to('impor')->with('galat', 'Gagal membuat cadangan data lama — impor dibatalkan demi keamanan.');
            }
        }

        $db->transStart();
        if ($mode === 'ganti' && $kelurahanTarget !== []) {
            $db->table('mhc')->whereIn('kelurahan_id', $kelurahanTarget)->delete();
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
            'kecamatan_id'      => $targetId,
            'kelurahan_id'      => null,
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
            'kecamatan' => $targetNama,
            'terbaca' => $jumlahBarisData, 'masuk' => count($siap), 'gagal' => $gagal,
            'cadangan' => $berkasCadangan,
        ];
        $data['rekonsiliasi'] = $this->rekonsiliasi($targetId);

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
        $data['rekonsiliasi'] = $this->rekonsiliasi(
            session()->get('pengguna_kecamatan_id') !== null
                ? (int) session()->get('pengguna_kecamatan_id') : null
        );

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

    /** Ekspor mhc satu kecamatan ke CSV cadangan. Null bila gagal. */
    private function cadangkanMhc(int $kecId, string $kecNama): ?string
    {
        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $kecNama);
        $nama = 'mhc-' . $slug . '-sebelum-impor-' . date('Ymd-His') . '.csv';
        $path = $folder . '/' . $nama;
        $fh = fopen($path, 'w');
        if ($fh === false) {
            return null;
        }
        try {
            fputcsv($fh, ImporCoklit::KOLOM_EKSPOR);
            $query = db_connect()->table('mhc')
                ->select('kecamatan.kode AS KEC_ID, kecamatan.nama AS KECAMATAN,
                    kelurahan.kode AS KEL_ID, kelurahan.nama AS KELURAHAN,
                    mhc.TPS, mhc.UID, mhc.NKK, mhc.NIK, mhc.NAMA, mhc.TMPTLHR,
                    mhc.TGLLHR, mhc.STATUS, mhc.JENIS_KELAMIN, mhc.ALAMAT, mhc.RT,
                    mhc.RW, mhc.DISABILITAS, mhc.EKTP, mhc.KET, mhc.SUMBER,
                    mhc.LAST_UPDATE, mhc.TGL_COKLIT', false)
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->where('kecamatan.id', $kecId)
                ->get();
            while ($row = $query->getUnbufferedRow('array')) {
                $urut = [];
                foreach (ImporCoklit::KOLOM_EKSPOR as $k) {
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

    /** Hitungan rekonsiliasi (dari view turunan + mhc), per kecamatan bila diminta. */
    private function rekonsiliasi(?int $kecId = null): array
    {
        $db = db_connect();
        $master = new WilayahMaster($db);
        $kecNama = $kecId !== null ? ($master->kecamatan($kecId)['nama'] ?? null) : null;
        $hitungView = static function (string $tabel) use ($db, $kecNama) {
            $b = $db->table($tabel);
            if ($kecNama !== null) {
                $b->where('KEC', $kecNama);
            }

            return (int) $b->countAllResults();
        };
        $hitungMhc = static function (?string $ket) use ($db, $kecId) {
            $b = $db->table('mhc');
            if ($ket !== null) {
                $b->where('mhc.KET', $ket);
            }
            if ($kecId !== null) {
                $b->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                  ->where('kelurahan.kecamatan_id', $kecId);
            }

            return (int) $b->countAllResults();
        };

        return [
            'Total hasil coklit (mhc)' => $hitungMhc(null),
            'Pemilih MS / aktif'       => $hitungView('ms'),
            'Pemilih baru'             => $hitungMhc('B'),
            'Pemilih tersaring (TMS)'  => $hitungView('tersaring'),
            'Pindah TPS (KET 8)'       => $hitungView('pindahtps'),
            'Jumlah KK (dari MS)'      => $hitungView('v_nkk'),
        ];
    }
}
