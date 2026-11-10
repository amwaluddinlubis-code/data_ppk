<?php

namespace App\Libraries;

use XMLReader;
use ZipArchive;

/**
 * Pembaca XLSX mengalir (streaming) untuk berkas yang sangat besar.
 *
 * PhpSpreadsheet memuat seluruh berkas ke memori — ekspor Sidalih satu
 * kabupaten (ratusan ribu baris) bisa menghabiskan ratusan MB. Kelas
 * ini membaca dengan XMLReader baris demi baris:
 *
 * - XML di dalam ZIP disalin mengalir ke berkas sementara di cakram.
 * - Tabel string bersama (sharedStrings) ditulis sekali ke berkas
 *   sementara beserta indeks offset-nya, jadi pencarian string adalah
 *   lompatan cakram, bukan muatan memori.
 * - Setiap baris lembar kerja diurai satu per satu dan diserahkan
 *   sebagai generator.
 *
 * Nilai sel dikembalikan apa adanya sebagai string (angka tetap teks
 * angka), sama seperti hasil baca PhpSpreadsheet mode data-only untuk
 * berkas ekspor umumnya.
 */
final class PembacaXlsx
{
    private const NS_UTAMA = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private string $pathLembarTmp;
    private ?string $pathStringTmp = null;

    /** @var list<array{0: int, 1: int}> [offset, panjang] tiap string bersama pada berkas sementaranya. */
    private array $indeksString = [];

    /** @var resource|null */
    private $fhString = null;

    private string $tmpDir;

    public function __construct(string $pathXlsx)
    {
        $zip = new ZipArchive();
        if ($zip->open($pathXlsx) !== true) {
            throw new \RuntimeException('Berkas XLSX tidak bisa dibuka: ' . $pathXlsx);
        }

        $this->tmpDir = sys_get_temp_dir() . '/bacaxlsx-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0775, true);

        try {
            $pathLembar = $this->tentukanLembar($zip);
            $this->pathLembarTmp = $this->salinKeTmp($zip, $pathLembar);
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $mentah = $this->salinKeTmp($zip, 'xl/sharedStrings.xml');
                $this->bangunIndeksString($mentah);
                unlink($mentah);
            }
        } finally {
            $zip->close();
        }
    }

    public function __destruct()
    {
        if ($this->fhString !== null) {
            fclose($this->fhString);
        }
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
    }

    /**
     * Hasilkan baris-baris lembar kerja satu per satu.
     *
     * @return \Generator<int, list<string|null>> baris terindeks 0 dari kolom A
     */
    public function baris(): \Generator
    {
        $reader = new XMLReader();
        $reader->open($this->pathLembarTmp);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            $xml = simplexml_load_string($reader->readOuterXml());
            if ($xml === false) {
                continue;
            }
            $sel = [];
            $maks = -1;
            foreach ($xml->children(self::NS_UTAMA)->c as $c) {
                // Sesudah children(ns), atribut wajib diambil lewat
                // attributes() — akses $c['r'] mengembalikan kosong.
                $attrs = $c->attributes();
                $ref   = (string) ($attrs['r'] ?? '');
                $kolom = self::indeksKolom($ref);
                $tipe  = (string) ($attrs['t'] ?? '');
                $anak  = $c->children(self::NS_UTAMA);
                $nilai = null;
                if ($tipe === 'inlineStr') {
                    $nilai = self::teksDalam($anak->is ?? null);
                } else {
                    $v = isset($anak->v) ? (string) $anak->v : null;
                    if ($v !== null && $v !== '') {
                        $nilai = $tipe === 's' ? $this->ambilString((int) $v) : $v;
                    } elseif (isset($anak->is)) {
                        $nilai = self::teksDalam($anak->is);
                    }
                }
                $sel[$kolom] = $nilai;
                $maks = max($maks, $kolom);
            }
            if ($maks < 0) {
                continue;
            }
            $baris = array_fill(0, $maks + 1, null);
            foreach ($sel as $k => $v) {
                $baris[$k] = $v;
            }
            yield $baris;
        }
        $reader->close();
    }

    /** Teks gabungan semua keturunan <t> dari sebuah simpul (untuk inlineStr & si). */
    private static function teksDalam(?\SimpleXMLElement $simpul): ?string
    {
        if ($simpul === null) {
            return null;
        }
        $bagian = $simpul->xpath('.//*[local-name()="t"]') ?: [];
        $teks = '';
        foreach ($bagian as $t) {
            $teks .= (string) $t;
        }

        return $teks === '' ? null : $teks;
    }

    /** Indeks kolom 0-based dari referensi sel ('BC12' => 54). */
    private static function indeksKolom(string $ref): int
    {
        $huruf = preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?? '';
        $n = 0;
        foreach (str_split($huruf) as $h) {
            $n = $n * 26 + (ord($h) - 64);
        }

        return max(0, $n - 1);
    }

    /** Salin satu entri ZIP ke berkas sementara secara mengalir. */
    private function salinKeTmp(ZipArchive $zip, string $nama): string
    {
        $aliran = $zip->getStream($nama);
        if ($aliran === false) {
            throw new \RuntimeException('Entri ZIP tidak ditemukan: ' . $nama);
        }
        $tujuan = $this->tmpDir . '/' . basename($nama);
        $fh = fopen($tujuan, 'wb');
        stream_copy_to_stream($aliran, $fh);
        fclose($fh);
        fclose($aliran);

        return $tujuan;
    }

    /** Tentukan path lembar kerja pertama dari workbook.xml + rels. */
    private function tentukanLembar(ZipArchive $zip): string
    {
        try {
            $wb = $zip->getFromName('xl/workbook.xml');
            $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($wb !== false && $rels !== false) {
                $xmlWb = simplexml_load_string($wb);
                $xmlWb->registerXPathNamespace('m', self::NS_UTAMA);
                $xmlWb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $lembar = $xmlWb->xpath('//m:sheets/m:sheet[1]');
                if ($lembar !== []) {
                    $attrs = $lembar[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $rid = (string) $attrs['id'];
                    $xmlRels = simplexml_load_string($rels);
                    foreach ($xmlRels->children() as $rel) {
                        if ((string) $rel['Id'] === $rid) {
                            $target = ltrim((string) $rel['Target'], '/');
                            return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Jatuh ke tebakan bawaan di bawah.
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /** Baca sharedStrings.xml sekali, tulis terindeks ke berkas sementara. */
    private function bangunIndeksString(string $pathMentah): void
    {
        $this->pathStringTmp = $this->tmpDir . '/strings.txt';
        $keluar = fopen($this->pathStringTmp, 'wb');

        $reader = new XMLReader();
        $reader->open($pathMentah);
        $offset = 0;
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }
            $xml = simplexml_load_string($reader->readOuterXml());
            $teks = $xml === false ? '' : (self::teksDalam($xml) ?? '');
            $aman = str_replace(['\\', "\r", "\n"], ['\\\\', '\\r', '\\n'], $teks);
            $tulis = $aman . "\n";
            fwrite($keluar, $tulis);
            $this->indeksString[] = [$offset, strlen($aman)];
            $offset += strlen($tulis);
        }
        $reader->close();
        fclose($keluar);
        $this->fhString = fopen($this->pathStringTmp, 'rb');
    }

    /** Ambil string bersama ke-$i lewat lompatan cakram. */
    private function ambilString(int $i): ?string
    {
        if ($this->fhString === null || ! isset($this->indeksString[$i])) {
            return null;
        }
        [$offset, $panjang] = $this->indeksString[$i];
        fseek($this->fhString, $offset);
        $mentah = $panjang > 0 ? (string) fread($this->fhString, $panjang) : '';

        return str_replace(['\\n', '\\r', '\\\\'], ["\n", "\r", '\\'], $mentah);
    }
}
