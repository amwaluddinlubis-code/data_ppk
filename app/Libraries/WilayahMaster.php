<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Master wilayah: kecamatan & kelurahan (perintah AMWAL 10 Nov 2026).
 *
 * Satu-satunya tempat yang membaca/menulis tabel `kecamatan` dan
 * `kelurahan`. Impor, CRUD, dan pengelolaan pengguna menyelesaikan
 * wilayah lewat kelas ini:
 *
 * - Pencarian utama berdasar KODE resmi Sidalih (KEC_ID / KEL_ID)
 *   bila berkas memuatnya; kode yang sudah dikenal tidak pernah
 *   digandakan walau ejaan nama berubah.
 * - Berkas tanpa kode (format coklit) diselesaikan berdasar NAMA
 *   (huruf besar, spasi dirapikan): kecamatan dicari dari namanya,
 *   kelurahan dari pasangan (kecamatan, nama). Master yang belum ada
 *   dibuat dengan kode kosong; kode resminya dilengkapi otomatis
 *   begitu impor berkas berkode menyentuhnya.
 * - Semua hasil di-cache per permintaan/proses agar impor ratusan
 *   ribu baris tidak mengulang kueri yang sama.
 */
final class WilayahMaster
{
    /** @var array<string, int> kode kecamatan => id */
    private array $kecKode = [];
    /** @var array<string, int> nama kecamatan => id */
    private array $kecNama = [];
    /** @var array<int, array> id => baris kecamatan */
    private array $kecBaris = [];
    /** @var array<string, int> kode kelurahan => id */
    private array $kelKode = [];
    /** @var array<string, int> "kecamatan_id|nama" => id kelurahan */
    private array $kelNama = [];
    /** @var array<int, array> id => baris kelurahan */
    private array $kelBaris = [];

    public function __construct(private BaseConnection $db)
    {
        foreach ($db->table('kecamatan')->get()->getResultArray() as $r) {
            $id = (int) $r['id'];
            $this->kecBaris[$id] = $r;
            $this->kecNama[self::rapi($r['nama'])] = $id;
            if ($r['kode'] !== null && $r['kode'] !== '') {
                $this->kecKode[(string) $r['kode']] = $id;
            }
        }
        foreach ($db->table('kelurahan')->get()->getResultArray() as $r) {
            $id = (int) $r['id'];
            $this->kelBaris[$id] = $r;
            $this->kelNama[(int) $r['kecamatan_id'] . '|' . self::rapi($r['nama'])] = $id;
            if ($r['kode'] !== null && $r['kode'] !== '') {
                $this->kelKode[(string) $r['kode']] = $id;
            }
        }
    }

    /** Rapikan nama wilayah untuk pencocokan: huruf besar, spasi tunggal. */
    public static function rapi(?string $nama): string
    {
        return preg_replace('/\s+/', ' ', strtoupper(trim((string) $nama)));
    }

    /** Id kecamatan dari kode dan/atau nama; dibuat bila belum ada. */
    public function kecamatanId(?string $kode, string $nama): int
    {
        $kode = $kode !== null ? trim($kode) : '';
        $nama = self::rapi($nama);
        if ($kode !== '' && isset($this->kecKode[$kode])) {
            return $this->kecKode[$kode];
        }
        if (isset($this->kecNama[$nama])) {
            $id = $this->kecNama[$nama];
            if ($kode !== '' && ($this->kecBaris[$id]['kode'] === null || $this->kecBaris[$id]['kode'] === '')) {
                $this->db->table('kecamatan')->where('id', $id)->update(['kode' => $kode]);
                $this->kecBaris[$id]['kode'] = $kode;
                $this->kecKode[$kode] = $id;
            }

            return $id;
        }
        $this->db->table('kecamatan')->insert([
            'kode' => $kode !== '' ? $kode : null,
            'nama' => $nama,
        ]);
        $id = (int) $this->db->insertID();
        $this->kecBaris[$id] = ['id' => $id, 'kode' => $kode !== '' ? $kode : null, 'nama' => $nama];
        $this->kecNama[$nama] = $id;
        if ($kode !== '') {
            $this->kecKode[$kode] = $id;
        }

        return $id;
    }

    /** Id kelurahan dari kecamatan + kode dan/atau nama; dibuat bila belum ada. */
    public function kelurahanId(int $kecamatanId, ?string $kode, string $nama): int
    {
        $kode = $kode !== null ? trim($kode) : '';
        $nama = self::rapi($nama);
        if ($kode !== '' && isset($this->kelKode[$kode])) {
            return $this->kelKode[$kode];
        }
        $kunci = $kecamatanId . '|' . $nama;
        if (isset($this->kelNama[$kunci])) {
            $id = $this->kelNama[$kunci];
            if ($kode !== '' && ($this->kelBaris[$id]['kode'] === null || $this->kelBaris[$id]['kode'] === '')) {
                $this->db->table('kelurahan')->where('id', $id)->update(['kode' => $kode]);
                $this->kelBaris[$id]['kode'] = $kode;
                $this->kelKode[$kode] = $id;
            }

            return $id;
        }
        $this->db->table('kelurahan')->insert([
            'kecamatan_id' => $kecamatanId,
            'kode'         => $kode !== '' ? $kode : null,
            'nama'         => $nama,
        ]);
        $id = (int) $this->db->insertID();
        $this->kelBaris[$id] = ['id' => $id, 'kecamatan_id' => $kecamatanId, 'kode' => $kode !== '' ? $kode : null, 'nama' => $nama];
        $this->kelNama[$kunci] = $id;
        if ($kode !== '') {
            $this->kelKode[$kode] = $id;
        }

        return $id;
    }

    /** Id kelurahan dari pasangan NAMA kecamatan + nama desa (tanpa kode). */
    public function kelurahanDariNama(string $namaKec, string $namaDesa): int
    {
        return $this->kelurahanId($this->kecamatanId(null, $namaKec), null, $namaDesa);
    }

    /** Cari id kelurahan yang SUDAH ada dari nama (tanpa membuat baru). */
    public function cariKelurahanId(string $namaKec, string $namaDesa): ?int
    {
        $kecId = $this->kecNama[self::rapi($namaKec)] ?? null;
        if ($kecId === null) {
            return null;
        }

        return $this->kelNama[$kecId . '|' . self::rapi($namaDesa)] ?? null;
    }

    /** Cari id kecamatan yang SUDAH ada dari nama (tanpa membuat baru). */
    public function cariKecamatanId(string $namaKec): ?int
    {
        return $this->kecNama[self::rapi($namaKec)] ?? null;
    }

    /** Baris kecamatan dari id (atau null). */
    public function kecamatan(int $id): ?array
    {
        return $this->kecBaris[$id] ?? null;
    }

    /** Baris kelurahan dari id (atau null). */
    public function kelurahan(int $id): ?array
    {
        return $this->kelBaris[$id] ?? null;
    }

    /** Nama kecamatan pemilik satu kelurahan (atau null). */
    public function namaKecamatanDariKelurahan(int $kelurahanId): ?string
    {
        $kel = $this->kelBaris[$kelurahanId] ?? null;
        if ($kel === null) {
            return null;
        }
        $kec = $this->kecBaris[(int) $kel['kecamatan_id']] ?? null;

        return $kec['nama'] ?? null;
    }

    /** @return list<array> Semua kecamatan urut nama. */
    public function semuaKecamatan(): array
    {
        $daftar = array_values($this->kecBaris);
        usort($daftar, static fn ($a, $b) => strcmp((string) $a['nama'], (string) $b['nama']));

        return $daftar;
    }

    /** @return list<array> Semua kelurahan pada satu kecamatan, urut nama. */
    public function kelurahanDi(int $kecamatanId): array
    {
        $daftar = array_values(array_filter(
            $this->kelBaris,
            static fn ($r) => (int) $r['kecamatan_id'] === $kecamatanId
        ));
        usort($daftar, static fn ($a, $b) => strcmp((string) $a['nama'], (string) $b['nama']));

        return $daftar;
    }
}
