<?php

namespace App\Support;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Pembaca .xlsx ringan tanpa dependensi eksternal (hanya ekstensi zip + xmlreader).
 *
 * @see \App\Services\RubrikSheetService
 */
class XlsxReader
{
    private ZipArchive $zip;
    /** @var string[] */
    private array $shared = [];
    /** @var array<string,string> nama sheet => path internal zip */
    private array $sheets = [];
    /** @var string[] */
    private array $order = [];

    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    public function __construct(string $path)
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi zip tidak aktif.');
        }
        if (!is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: $path");
        }
        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new RuntimeException('Gagal membuka .xlsx (file tidak valid?).');
        }
        $this->loadSharedStrings();
        $this->loadWorkbook();
    }

    public function __destruct()
    {
        if (isset($this->zip)) {
            $this->zip->close();
        }
    }

    /** @return string[] */
    public function sheetNames(): array
    {
        return $this->order;
    }

    public function sheetCount(): int
    {
        return count($this->order);
    }

    /**
     * @return array<int,array<int,string>> [rowNo => [colIdx => value]]
     */
    public function rows(string $sheetName): array
    {
        $path = $this->sheets[$sheetName] ?? null;
        if ($path === null) {
            return [];
        }
        $xml = $this->zip->getFromName($path);
        if ($xml === false) {
            return [];
        }
        $r = new XMLReader();
        $r->XML($xml);

        $rows = [];
        $rowNo = 0;
        $type = null;
        $ref = null;

        while ($r->read()) {
            if ($r->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            switch ($r->name) {
                case 'row':
                    $rowNo = (int) $r->getAttribute('r');
                    break;
                case 'c':
                    $ref = $r->getAttribute('r');
                    $type = $r->getAttribute('t');
                    break;
                case 'v':
                    $v = $r->readString();
                    if ($type === 's') {
                        $v = $this->shared[(int) $v] ?? '';
                    }
                    $rows[$rowNo][self::colIndex($ref)] = $v;
                    break;
                case 't':
                    if ($type === 'inlineStr') {
                        $rows[$rowNo][self::colIndex($ref)] = $r->readString();
                    }
                    break;
            }
        }
        $r->close();

        return $rows;
    }

    private function loadSharedStrings(): void
    {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return;
        }
        $r = new XMLReader();
        $r->XML($xml);
        $cur = null;
        $inT = false;
        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT) {
                if ($r->name === 'si') {
                    $cur = '';
                } elseif ($r->name === 't') {
                    $inT = true;
                }
            } elseif ($r->nodeType === XMLReader::TEXT || $r->nodeType === XMLReader::CDATA) {
                if ($inT && $cur !== null) {
                    $cur .= $r->value;
                }
            } elseif ($r->nodeType === XMLReader::END_ELEMENT) {
                if ($r->name === 't') {
                    $inT = false;
                } elseif ($r->name === 'si') {
                    $this->shared[] = $cur ?? '';
                    $cur = null;
                }
            }
        }
        $r->close();
    }

    private function loadWorkbook(): void
    {
        $wbXml = $this->zip->getFromName('xl/workbook.xml');
        $relXml = $this->zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbXml === false) {
            throw new RuntimeException('workbook.xml tidak ada.');
        }

        $rel = [];
        if ($relXml !== false) {
            $rx = new XMLReader();
            $rx->XML($relXml);
            while ($rx->read()) {
                if ($rx->nodeType === XMLReader::ELEMENT && $rx->name === 'Relationship') {
                    $rel[$rx->getAttribute('Id')] = $rx->getAttribute('Target');
                }
            }
            $rx->close();
        }

        $r = new XMLReader();
        $r->XML($wbXml);
        while ($r->read()) {
            if ($r->nodeType === XMLReader::ELEMENT && $r->name === 'sheet') {
                $name = $r->getAttribute('name');
                $rid = $this->attr($r, 'id', self::NS_REL);
                if ($rid === null) {
                    $rid = $r->getAttribute('r:id');
                }
                $target = $rid !== null ? ($rel[$rid] ?? null) : null;
                if ($target !== null) {
                    if ($target[0] !== '/') {
                        $target = 'xl/' . ltrim($target, '/');
                    }
                    $this->sheets[$name] = $target;
                    $this->order[] = $name;
                }
            }
        }
        $r->close();
    }

    private function attr(XMLReader $r, string $local, ?string $ns = null): ?string
    {
        if (!$r->moveToFirstAttribute()) {
            return null;
        }
        $found = null;
        do {
            if ($r->localName === $local && ($ns === null || $r->namespaceURI === $ns)) {
                $found = $r->value;
                break;
            }
        } while ($r->moveToNextAttribute());
        $r->moveToElement();

        return $found;
    }

    public static function colIndex(?string $ref): int
    {
        if ($ref === null || $ref === '') {
            return 0;
        }
        $letters = '';
        $n = strlen($ref);
        for ($i = 0; $i < $n; $i++) {
            $ch = $ref[$i];
            if (($ch >= 'A' && $ch <= 'Z') || ($ch >= 'a' && $ch <= 'z')) {
                $letters .= strtoupper($ch);
            } else {
                break;
            }
        }
        $idx = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - 64);
        }

        return $idx - 1;
    }
}
