<?php

namespace App\Controllers;

use App\Models\RiwayatModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Fase 2 — CRUD data coklit (tabel `mhc`).
 *
 * Hanya Admin PPK & Operator. Operator yang punya cakupan desa
 * (kolom users.desa terisi, mis. PPS) hanya melihat & mengubah
 * baris desanya sendiri. Setiap perubahan dicatat ke
 * `riwayat_perubahan` (lihat RiwayatModel) dalam satu transaksi.
 *
 * Daftar aktif/MS adalah VIEW di atas mhc, jadi koreksi di sini
 * langsung tercermin di semua halaman baca (Aktif, Tersaring, dsb.).
 * Penghapusan bersifat permanen pada mhc, tetapi salinan baris utuh
 * tersimpan di riwayat dan bisa dipulihkan dari halaman Riwayat.
 */
class Coklit extends BaseController
{
    /** Kode KET yang berarti pemilih berada di daftar MS/aktif. */
    private const KET_MS = ['0', 'U', 'B'];

    private function db(): BaseConnection
    {
        return db_connect();
    }

    private function wajibTulis()
    {
        if (! in_array(session()->get('pengguna_peran'), ['admin', 'operator'], true)) {
            return redirect()->to('/')->with('galat', 'Peran Anda hanya boleh melihat data.');
        }

        return null;
    }

    /** Desa cakupan pengguna (null = seluruh kecamatan). */
    private function desaCakupan(): ?string
    {
        $desa = session()->get('pengguna_desa');

        return ($desa === null || $desa === '') ? null : (string) $desa;
    }

    private function dataHalaman(string $judul): array
    {
        return [
            'title'      => $judul,
            'title_page' => $judul,
            'kecamatan'  => $this->kec,
        ];
    }

    private function daftarDesa(): array
    {
        $rows = $this->db()->table('desa')->select('DESA')->orderBy('DESA')->get()->getResultArray();

        return array_map(static fn ($r) => $r['DESA'], $rows);
    }

    /** Ambil satu baris mhc; hormati cakupan desa pengguna. */
    private function ambilBaris(string $uid): ?array
    {
        $row = $this->db()->table('mhc')->where('UID', $uid)->get()->getRowArray();
        if ($row === null) {
            return null;
        }
        $cakupan = $this->desaCakupan();
        if ($cakupan !== null && ($row['DESA'] ?? null) !== $cakupan) {
            return null;
        }

        return $row;
    }

    public static function labelKet(): array
    {
        return [
            '0' => '0 — Pemilih Sesuai',
            'U' => 'U — Pemilih Ubah',
            'B' => 'B — Pemilih Baru',
            '1' => '1 — Meninggal',
            '2' => '2 — Ganda',
            '3' => '3 — Di Bawah Umur',
            '4' => '4 — Kode 4',
            '6' => '6 — TNI',
            '7' => '7 — Polri',
            '8' => '8 — Salah Penempatan TPS',
        ];
    }

    // -- Daftar ------------------------------------------------------

    public function index()
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $q       = trim((string) $this->request->getGet('q'));
        $cakupan = $this->desaCakupan();
        $desa    = $cakupan ?? trim((string) $this->request->getGet('desa'));
        $halaman = max(1, (int) $this->request->getGet('hal'));
        $perHal  = 25;

        $builder = $this->db()->table('mhc');
        if ($desa !== '') {
            $builder->where('DESA', $desa);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('NIK', $q)->orLike('NAMA', $q)->orLike('NKK', $q)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('DESA', 'ASC')->orderBy('TPS', 'ASC')->orderBy('NAMA', 'ASC')
            ->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        $data = $this->dataHalaman('Kelola Data Coklit');
        $data['rows']      = $rows;
        $data['q']         = $q;
        $data['desa']      = $desa;
        $data['cakupan']   = $cakupan;
        $data['daftarDesa'] = $this->daftarDesa();
        $data['total']     = $total;
        $data['halaman']   = $halaman;
        $data['perHal']    = $perHal;
        $data['totalHal']  = max(1, (int) ceil($total / $perHal));
        $data['labelKet']  = self::labelKet();

        return view('coklit/index', $data);
    }

    // -- Tambah ------------------------------------------------------

    public function tambah()
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $data = $this->dataHalaman('Tambah Pemilih');
        $data['baris']      = null;
        $data['daftarDesa'] = $this->daftarDesa();
        $data['cakupan']    = $this->desaCakupan();
        $data['labelKet']   = self::labelKet();

        return view('coklit/form', $data);
    }

    public function simpan()
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        [$galat, $bersih] = $this->validasi($this->request->getPost(), null);
        if ($galat !== []) {
            return redirect()->back()->withInput()
                ->with('galat', implode(' ', $galat));
        }

        $db  = $this->db();
        $uid = strtoupper(bin2hex(random_bytes(8)));
        $bersih['UID'] = $uid;
        $bersih['ID']  = (int) ($db->table('mhc')->selectMax('ID')->get()->getRowArray()['ID'] ?? 0) + 1;
        $bersih['LAST_UPDATE'] = date('Y-m-d H:i:s');

        $db->transStart();
        $db->table('mhc')->insert($bersih);
        $baru = $db->table('mhc')->where('UID', $uid)->get()->getRowArray();
        (new RiwayatModel())->catat('tambah', null, $baru);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('galat', 'Gagal menyimpan. Coba lagi.');
        }

        return redirect()->to('coklit')->with('sukses', 'Pemilih ' . $bersih['NAMA'] . ' ditambahkan.');
    }

    // -- Ubah --------------------------------------------------------

    public function edit($uid = null)
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $baris = $this->ambilBaris((string) $uid);
        if ($baris === null) {
            return redirect()->to('coklit')->with('galat', 'Baris tidak ditemukan (atau di luar desa cakupan Anda).');
        }

        $data = $this->dataHalaman('Ubah Pemilih');
        $data['baris']      = $baris;
        $data['daftarDesa'] = $this->daftarDesa();
        $data['cakupan']    = $this->desaCakupan();
        $data['labelKet']   = self::labelKet();

        return view('coklit/form', $data);
    }

    public function update($uid = null)
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $lama = $this->ambilBaris((string) $uid);
        if ($lama === null) {
            return redirect()->to('coklit')->with('galat', 'Baris tidak ditemukan.');
        }

        [$galat, $bersih] = $this->validasi($this->request->getPost(), (string) $uid);
        if ($galat !== []) {
            return redirect()->back()->withInput()
                ->with('galat', implode(' ', $galat));
        }

        $bersih['LAST_UPDATE'] = date('Y-m-d H:i:s');

        $db = $this->db();
        $db->transStart();
        $db->table('mhc')->where('UID', (string) $uid)->update($bersih);
        $baru = $db->table('mhc')->where('UID', (string) $uid)->get()->getRowArray();
        (new RiwayatModel())->catat('ubah', $lama, $baru);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('galat', 'Gagal mengubah. Coba lagi.');
        }

        return redirect()->to('coklit')->with('sukses', 'Pemilih ' . $bersih['NAMA'] . ' diperbarui.');
    }

    // -- Hapus -------------------------------------------------------

    public function hapus($uid = null)
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $lama = $this->ambilBaris((string) $uid);
        if ($lama === null) {
            return redirect()->to('coklit')->with('galat', 'Baris tidak ditemukan.');
        }

        $db = $this->db();
        $db->transStart();
        (new RiwayatModel())->catat('hapus', $lama, null);
        $db->table('mhc')->where('UID', (string) $uid)->delete();
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('galat', 'Gagal menghapus. Coba lagi.');
        }

        return redirect()->to('coklit')->with('sukses', 'Pemilih ' . $lama['NAMA'] . ' dihapus. Salinannya tersimpan di Riwayat Perubahan dan bisa dipulihkan.');
    }

    // -- Validasi ----------------------------------------------------

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validasi(array $post, ?string $uidSelf): array
    {
        $galat = [];
        $ambil = static fn (string $k) => trim((string) ($post[$k] ?? ''));

        $nik = $ambil('NIK');
        $nkk = $ambil('NKK');
        if (! preg_match('/^\d{16}$/', $nik)) {
            $galat[] = 'NIK harus tepat 16 angka.';
        }
        if (! preg_match('/^\d{16}$/', $nkk)) {
            $galat[] = 'NKK harus tepat 16 angka.';
        }

        $nama = $ambil('NAMA');
        if ($nama === '') {
            $galat[] = 'Nama wajib diisi.';
        }

        $desa = $ambil('DESA');
        $cakupan = $this->desaCakupan();
        if ($cakupan !== null) {
            $desa = $cakupan;
        } elseif (! in_array($desa, $this->daftarDesa(), true)) {
            $galat[] = 'Desa tidak dikenal.';
        }

        $tps = $ambil('TPS');
        if (! ctype_digit($tps) || (int) $tps < 1 || (int) $tps > 99) {
            $galat[] = 'TPS harus angka 1–99.';
        }

        $ket = $ambil('KET');
        if (! array_key_exists($ket, self::labelKet())) {
            $galat[] = 'Kode KET tidak dikenal.';
        }

        $lp = $ambil('JENIS_KELAMIN');
        if (! in_array($lp, ['L', 'P'], true)) {
            $galat[] = 'Jenis kelamin harus L atau P.';
        }

        // Cegah NIK ganda di daftar aktif: NIK yang sama tidak boleh
        // muncul dua kali pada kelompok MS (0/U/B).
        if ($galat === [] && in_array($ket, self::KET_MS, true)) {
            $builder = $this->db()->table('mhc')
                ->where('NIK', $nik)->whereIn('KET', self::KET_MS);
            if ($uidSelf !== null) {
                $builder->where('UID !=', $uidSelf);
            }
            if ($builder->countAllResults() > 0) {
                $galat[] = 'NIK ' . $nik . ' sudah ada di daftar aktif (MS). Periksa halaman NIK Ganda / data yang bersangkutan.';
            }
        }

        $tglCoklit = $ambil('TGL_COKLIT');

        // Baris baru mewarisi PROV/KAB/KEC dari data kecamatan yang ada,
        // supaya tidak ada kolom wilayah yang kosong.
        $prov = null;
        $kab  = null;
        if ($uidSelf === null) {
            $acuan = $this->db()->table('mhc')->select('PROV, KAB')->limit(1)->get()->getRowArray();
            $prov  = $acuan['PROV'] ?? null;
            $kab   = $acuan['KAB'] ?? null;
        }

        $bersih = [
            'NIK'           => $nik,
            'NKK'           => $nkk,
            'NAMA'          => strtoupper($nama),
            'TMPTLHR'       => $ambil('TMPTLHR'),
            'TGLLHR'        => $ambil('TGLLHR'),
            'STATUS'        => $ambil('STATUS'),
            'JENIS_KELAMIN' => $lp,
            'ALAMAT'        => $ambil('ALAMAT'),
            'RT'            => $ambil('RT'),
            'RW'            => $ambil('RW'),
            'DISABILITAS'   => $ambil('DISABILITAS'),
            'EKTP'          => $ambil('EKTP'),
            'KET'           => $ket,
            'SUMBER'        => $ambil('SUMBER'),
            'TPS'           => $tps,
            'DESA'          => $desa,
            'KEC'           => $this->kec,
            'TGL_COKLIT'    => $tglCoklit !== '' ? $tglCoklit : null,
        ];

        if ($uidSelf === null) {
            $bersih['PROV'] = $prov;
            $bersih['KAB']  = $kab;
        }

        return [$galat, $bersih];
    }
}
