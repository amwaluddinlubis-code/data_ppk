<?php

namespace App\Controllers;

use App\Models\RiwayatModel;
use CodeIgniter\Database\BaseConnection;

/**
 * CRUD data coklit (tabel `mhc`).
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
        return \App\Libraries\Wilayah::desaPengguna();
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

    /** Ambil satu baris mhc berdasar kunci pokok ID; hormati cakupan wilayah. */
    private function ambilBaris(string $id): ?array
    {
        $row = $this->db()->table('mhc')->where('ID', (int) $id)->get()->getRowArray();
        if ($row === null) {
            return null;
        }
        // Wilayah baris kini berupa kelurahan_id: nama kecamatan/desa
        // diselesaikan dari tabel master untuk pemeriksaan cakupan.
        $master = new \App\Libraries\WilayahMaster($this->db());
        $kelId = (int) ($row['kelurahan_id'] ?? 0);
        $kecEfektif = $this->kecEfektif();
        if ($kecEfektif !== null && $master->namaKecamatanDariKelurahan($kelId) !== $kecEfektif) {
            return null;
        }
        $cakupan = $this->desaCakupan();
        if ($cakupan !== null && ($master->kelurahan($kelId)['nama'] ?? null) !== $cakupan) {
            return null;
        }

        return $row;
    }

    public static function labelKet(): array
    {
        // Bangun ulang 2026: definisi kode keterangan kini terpusat di
        // App\Libraries\Keterangan — metode ini dipertahankan agar
        // pemanggil lama tetap bekerja.
        return \App\Libraries\Keterangan::daftar();
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
        $perHal  = (int) $this->request->getGet('perhal');
        if (! in_array($perHal, [10, 25, 50, 100], true)) {
            $perHal = 25;
        }

        $builder = $this->db()->table('mhc')
            ->select('mhc.*, kelurahan.nama AS DESA, kecamatan.nama AS KEC')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
        $this->saringWilayahId($builder);
        if ($desa !== '') {
            $master = new \App\Libraries\WilayahMaster($this->db());
            $kecNama = $this->kecEfektif();
            $kelId = $kecNama !== null ? $master->cariKelurahanId($kecNama, $desa) : null;
            $builder->where('mhc.kelurahan_id', $kelId ?? -1);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('mhc.NIK', $q)->orLike('mhc.NAMA', $q)->orLike('mhc.NKK', $q)
                ->groupEnd();
        }

        $total    = $builder->countAllResults(false);
        $totalHal = max(1, (int) ceil($total / $perHal));
        $halaman  = min($halaman, $totalHal);
        $rows  = $builder->orderBy('DESA', 'ASC')->orderBy('mhc.TPS', 'ASC')->orderBy('mhc.NAMA', 'ASC')
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
        $data['totalHal']  = $totalHal;
        $data['labelKet']  = \App\Libraries\Keterangan::daftar();

        return view('coklit/index', $data);
    }

    // -- Tambah ------------------------------------------------------

    public function tambah()
    {
        if ($tolak = $this->wajibTulis()) {
            return $tolak;
        }

        $data = $this->dataHalaman('Tambah Pemilih');
        $data['daftarKecamatan'] = $this->daftarKecamatan();
        $data['kecTarget']  = $this->kecEfektif();
        $data['baris']      = null;
        $data['daftarDesa'] = $this->daftarDesa();
        $data['cakupan']    = $this->desaCakupan();
        $data['labelKet']   = \App\Libraries\Keterangan::daftar();
        $data['daftarStatus'] = \App\Libraries\KodeBiodata::STATUS;
        $data['daftarDisabilitas'] = \App\Libraries\KodeBiodata::DISABILITAS;
        $data['daftarEktp'] = \App\Libraries\KodeBiodata::EKTP;

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

        $bersih['TGL_LAHIR'] = \App\Libraries\ImporCoklit::tanggalLahirIso($bersih['TGLLHR'] ?? null);
        $db  = $this->db();
        $uid = strtoupper(bin2hex(random_bytes(8)));
        $bersih['UID'] = $uid;
        // ID tidak diisi manual: kolomnya kini AUTO_INCREMENT.
        $bersih['LAST_UPDATE'] = date('Y-m-d H:i:s');

        $db->transStart();
        $db->table('mhc')->insert($bersih);
        $baru = $db->table('mhc')->where('UID', $uid)->get()->getRowArray();
        (new RiwayatModel())->catat('tambah', null, $baru);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('galat', 'Gagal menyimpan. Coba lagi.');
        }

        $this->sentuhVersiData();

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
        // Nama wilayah untuk isian form (mhc sendiri hanya menyimpan
        // kelurahan_id) — kunci tampilan saja, tidak ikut tersimpan.
        $master = new \App\Libraries\WilayahMaster($this->db());
        $kelId = (int) ($baris['kelurahan_id'] ?? 0);
        $baris['DESA'] = $master->kelurahan($kelId)['nama'] ?? '';
        $baris['KEC']  = $master->namaKecamatanDariKelurahan($kelId) ?? '';

        $data = $this->dataHalaman('Ubah Pemilih');
        $data['daftarKecamatan'] = $this->daftarKecamatan();
        $data['kecTarget']  = $this->kecEfektif();
        $data['baris']      = $baris;
        $data['daftarDesa'] = $this->daftarDesa();
        $data['cakupan']    = $this->desaCakupan();
        $data['labelKet']   = \App\Libraries\Keterangan::daftar();
        $data['daftarStatus'] = \App\Libraries\KodeBiodata::STATUS;
        $data['daftarDisabilitas'] = \App\Libraries\KodeBiodata::DISABILITAS;
        $data['daftarEktp'] = \App\Libraries\KodeBiodata::EKTP;

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

        $bersih['TGL_LAHIR']   = \App\Libraries\ImporCoklit::tanggalLahirIso($bersih['TGLLHR'] ?? null);
        $bersih['LAST_UPDATE'] = date('Y-m-d H:i:s');

        $db = $this->db();
        $db->transStart();
        $db->table('mhc')->where('ID', (int) $uid)->update($bersih);
        $baru = $db->table('mhc')->where('ID', (int) $uid)->get()->getRowArray();
        (new RiwayatModel())->catat('ubah', $lama, $baru);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('galat', 'Gagal mengubah. Coba lagi.');
        }

        $this->sentuhVersiData();

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
        $db->table('mhc')->where('ID', (int) $uid)->delete();
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('galat', 'Gagal menghapus. Coba lagi.');
        }

        $this->sentuhVersiData();

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

        // Kecamatan tujuan baris: cakupan/saringan efektif; Admin
        // Kabupaten dalam tampilan seluruh kabupaten memilih lewat form.
        $kecTarget = $this->kecEfektif() ?? $ambil('KEC');
        if ($kecTarget === '' || ! in_array($kecTarget, $this->daftarKecamatan(), true)) {
            $galat[] = 'Kecamatan tujuan tidak dikenal.';
        }

        $desa = $ambil('DESA');
        $cakupan = $this->desaCakupan();
        if ($cakupan !== null) {
            $desa = $cakupan;
        } elseif ($kecTarget !== '' && ! in_array($desa, $this->daftarDesa($kecTarget), true)) {
            $galat[] = 'Desa tidak dikenal di kecamatan tujuan.';
        }

        $tps = $ambil('TPS');
        if (! ctype_digit($tps) || (int) $tps < 1 || (int) $tps > 99) {
            $galat[] = 'TPS harus angka 1–99.';
        }

        $ket = $ambil('KET');
        if (! array_key_exists($ket, \App\Libraries\Keterangan::daftar())) {
            $galat[] = 'Kode KET tidak dikenal.';
        }

        $lp = $ambil('JENIS_KELAMIN');
        if (! in_array($lp, ['L', 'P'], true)) {
            $galat[] = 'Jenis kelamin harus L atau P.';
        }

        // Cegah NIK ganda di daftar aktif: NIK yang sama tidak boleh
        // muncul dua kali pada kelompok MS (0/U/B).
        if ($galat === [] && in_array($ket, \App\Libraries\Keterangan::MS, true)) {
            $builder = $this->db()->table('mhc')
                ->where('NIK', $nik)->whereIn('KET', \App\Libraries\Keterangan::MS);
            if ($uidSelf !== null) {
                $builder->where('ID !=', (int) $uidSelf);
            }
            if ($builder->countAllResults() > 0) {
                $galat[] = 'NIK ' . $nik . ' sudah ada di daftar aktif (MS). Periksa halaman NIK Ganda / data yang bersangkutan.';
            }
        }

        // Tanggal lahir dipilih lewat pemilih tanggal (ISO Y-m-d),
        // lalu disimpan kembali dalam format data Sidalih DD|MM|YYYY
        // agar kolom TGLLHR dan ekspor tidak berubah formatnya.
        $tglPilih = $ambil('TGL_LAHIR_INPUT');
        $tglLhr   = $ambil('TGLLHR');
        if ($tglPilih !== '') {
            $cekTgl = \DateTimeImmutable::createFromFormat('Y-m-d', $tglPilih);
            if (! $cekTgl || $cekTgl->format('Y-m-d') !== $tglPilih) {
                $galat[] = 'Tanggal lahir tidak sah.';
            } else {
                $tglLhr = $cekTgl->format('d|m|Y');
            }
        }
        if ($tglLhr !== '' && \App\Libraries\ImporCoklit::tanggalLahirIso($tglLhr) === null) {
            $galat[] = 'Tanggal lahir tidak sah.';
        }

        $status = strtoupper($ambil('STATUS'));
        if ($status !== '' && ! isset(\App\Libraries\KodeBiodata::STATUS[$status])) {
            $galat[] = 'Status perkawinan tidak dikenal.';
        }

        $disabilitas = $ambil('DISABILITAS');
        if ($disabilitas !== '' && (! ctype_digit($disabilitas)
            || ! isset(\App\Libraries\KodeBiodata::DISABILITAS[(int) $disabilitas]))) {
            $galat[] = 'Kode disabilitas harus 0–6.';
        }

        $ektp = strtoupper($ambil('EKTP'));
        if ($ektp !== '' && ! isset(\App\Libraries\KodeBiodata::EKTP[$ektp])) {
            $galat[] = 'Status kepemilikan KTP-el harus S atau B.';
        }

        $tglCoklit = $ambil('TGL_COKLIT');

        // Wilayah disimpan sebagai kelurahan_id (tabel master):
        // pasangan kecamatan + desa dari form harus sudah terdaftar.
        $kelurahanId = null;
        if ($galat === []) {
            $master = new \App\Libraries\WilayahMaster($this->db());
            $kelurahanId = $master->cariKelurahanId((string) $kecTarget, $desa);
            if ($kelurahanId === null) {
                $galat[] = 'Pasangan kecamatan/desa tidak terdaftar di master wilayah.';
            }
        }

        $bersih = [
            'NIK'           => $nik,
            'NKK'           => $nkk,
            'NAMA'          => strtoupper($nama),
            'TMPTLHR'       => $ambil('TMPTLHR'),
            'TGLLHR'        => $tglLhr,
            'STATUS'        => $status,
            'JENIS_KELAMIN' => $lp,
            'ALAMAT'        => $ambil('ALAMAT'),
            'RT'            => $ambil('RT'),
            'RW'            => $ambil('RW'),
            'DISABILITAS'   => $disabilitas === '' ? null : (int) $disabilitas,
            'EKTP'          => $ektp,
            'KET'           => $ket,
            'SUMBER'        => $ambil('SUMBER'),
            'TPS'           => $tps,
            'kelurahan_id'  => $kelurahanId,
            'TGL_COKLIT'    => $tglCoklit !== '' ? $tglCoklit : null,
        ];

        return [$galat, $bersih];
    }
}
