<?php

namespace App\Services;

use App\Support\XlsxReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * RubrikSheetService
 *
 * Menyelaraskan tabel `rubrik_skor` dengan spreadsheet kriteria skoring
 * (Google Spreadsheet / file .xlsx).
 *
 * Alur:
 *   1. Ambil file .xlsx (unduh dari Google Spreadsheet atau pakai file lokal)
 *   2. Parse  -> blok rubrik (per sheet, per "Aktivitas", per KUK, per varian)
 *   3. Resolve -> aktivitas_id  =  <kode_unit tanpa titik>-<elemen>-<no_kuk 2 digit>
 *   4. Upsert -> <schema>.rubrik_skor (skor_1..skor_5 + jenis_file)
 */
class RubrikSheetService
{
    private const SKOR_OK = ['1', '2', '3', '4', '5', '1.0', '2.0', '3.0', '4.0', '5.0'];

    /**
     * Ambang minimal kemiripan teks KUK saat menentukan elemen lewat
     * jalur "KUK ada di teks elemen" (lihat resolve()). Dipakai supaya blok
     * rubrik tidak ditempel ke elemen yang sebenarnya tidak nyambung.
     */
    private const MIN_ELEMEN_SCORE = 0.5;

    private string $schema;

    /**
     * Hapus baris rubrik_skor yang sudah tidak ada lagi di spreadsheet.
     * Aman karena tabel ini murni turunan spreadsheet (kolom deskripsi_skor,
     * aturan_rubrik, dan verifikator selalu kosong, dan tidak ada tabel lain
     * yang mereferensikannya).
     */
    private bool $prune;

    public function __construct(?string $schema = null, ?bool $prune = null)
    {
        $this->schema = $schema ?: config('rubrik.schema', 'geotrax_v4');
        if (!preg_match('/^[a-z0-9_]+$/i', $this->schema)) {
            throw new RuntimeException("Nama schema tidak valid: {$this->schema}");
        }
        $this->prune = $prune ?? (bool) config('rubrik.prune', true);
    }

    public function schema(): string
    {
        return $this->schema;
    }

    public function table(): string
    {
        return $this->schema . '.' . config('rubrik.table', 'rubrik_skor');
    }

    // =====================================================================
    // 1. Sumber data
    // =====================================================================

    /**
     * Unduh spreadsheet Google ke berkas lokal, kembalikan path-nya.
     */
    public function downloadSheet(?string $sheetId = null): string
    {
        $sheetId = $sheetId ?: config('rubrik.sheet_id');
        $url = sprintf(config('rubrik.export_url'), $sheetId);

        $dir = config('rubrik.cache_path', storage_path('app/rubrik'));
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Tidak bisa membuat folder: $dir");
        }
        $path = $dir . '/sheet_' . date('Ymd_His') . '.xlsx';

        $response = Http::timeout((int) config('rubrik.download_timeout', 300))
            ->withOptions(['sink' => $path])
            ->get($url);

        if (!$response->successful()) {
            @unlink($path);
            throw new RuntimeException('Gagal mengunduh spreadsheet (HTTP ' . $response->status() . '). '
                . 'Pastikan spreadsheet bisa diakses lewat link.');
        }
        if (!is_file($path) || filesize($path) < 1024) {
            throw new RuntimeException('Hasil unduhan kosong / terlalu kecil.');
        }

        $this->pruneCache($dir, $path);

        return $path;
    }

    /**
     * Hapus berkas unduhan lama supaya tidak menumpuk (tiap berkas ~30 MB).
     * Menyisakan 2 berkas terbaru.
     */
    private function pruneCache(string $dir, string $keepPath): void
    {
        try {
            $files = glob($dir . '/sheet_*.xlsx') ?: [];
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a)); // terbaru dulu
            foreach (array_slice($files, 2) as $old) {
                if ($old !== $keepPath) {
                    @unlink($old);
                }
            }
        } catch (Throwable $e) {
            // pembersihan bersifat opsional
        }
    }

    // =====================================================================
    // 2. Parsing
    // =====================================================================

    /** @return array<int,array> */
    public function parseWorkbook(string $path): array
    {
        $wb = new XlsxReader($path);
        $all = [];
        foreach ($wb->sheetNames() as $name) {
            foreach ($this->parseSheet($wb->rows($name), $name) as $b) {
                $all[] = $b;
            }
        }

        return $all;
    }

    /** @return array<int,array> */
    public function parseSheet(array $rows, string $sheet): array
    {
        $blocks = [];
        $rowNos = array_keys($rows);
        sort($rowNos);

        $maxCol = 0;
        foreach ($rows as $cells) {
            foreach ($cells as $c => $_) {
                if ($c > $maxCol) {
                    $maxCol = $c;
                }
            }
        }
        $ncol = $maxCol + 1;

        $gi = 0;
        $curLabel = null;
        $curNo = null;
        $curKukNo = null;
        $curKukDesc = null;

        foreach ($rowNos as $rn) {
            $row = [];
            for ($c = 0; $c < min($ncol, 6); $c++) {
                $row[$c] = $this->squash($rows[$rn][$c] ?? '');
            }

            // (a) baris label "Aktivitas N" / "Aktvitas N"
            $hit = null;
            for ($c = 0; $c < min($ncol, 3); $c++) {
                if ($row[$c] !== '' && preg_match('/^\s*A\w*?vitas?\b/iu', $row[$c])) {
                    $hit = $row[$c];
                    break;
                }
            }
            if ($hit !== null) {
                preg_match_all('/[0-9]+/', $hit, $m);
                $curNo = $m[0] ? (int) $m[0][0] : null;
                $curLabel = $hit;
                $gi++;
                $curKukNo = null;
                $curKukDesc = null;
                continue;
            }

            // (b) baris header rubrik: ada sel "Skor"
            $sc = null;
            for ($c = 0; $c < min($ncol, 5); $c++) {
                if (isset($row[$c]) && strtolower($row[$c]) === 'skor') {
                    $sc = $c;
                    break;
                }
            }
            if ($sc === null) {
                continue;
            }

            $headerKriteria = ($sc + 2 < $ncol) ? $this->squash($rows[$rn][$sc + 2] ?? '') : '';

            // judul KUK ada di baris tepat di atas header
            $title = '';
            $prev = $rn - 1;
            if (isset($rows[$prev])) {
                for ($c = 0; $c < min($ncol, 4); $c++) {
                    $t = $this->squash($rows[$prev][$c] ?? '');
                    if ($t !== '' && strtolower($t) !== 'skor') {
                        $title = $t;
                        break;
                    }
                }
            }
            if (preg_match('/^\s*(\d+)\s*[.)]?\s*(.*)$/u', $title, $mm)) {
                $curKukNo = (int) $mm[1];
                $curKukDesc = trim($mm[2]);
            }

            $varian = $this->parens($headerKriteria);
            if (!$varian) {
                $varian = $this->parens($title);
            }

            $scores = [];
            for ($j = 1; $j <= 5; $j++) {
                $rr = $rn + $j;
                if (!isset($rows[$rr])) {
                    break;
                }
                $sv = $this->squash($rows[$rr][$sc] ?? '');
                if (in_array($sv, self::SKOR_OK, true)) {
                    $k = (int) (float) $sv;
                    $scores[$k] = [
                        'kriteria' => ($sc + 2 < $ncol) ? $this->squash($rows[$rr][$sc + 2] ?? '') : '',
                        'evidence' => ($sc + 3 < $ncol) ? $this->squash($rows[$rr][$sc + 3] ?? '') : '',
                    ];
                }
            }
            if (count($scores) < 3) {
                continue;
            }

            $blocks[] = [
                'sheet' => $sheet,
                'group_idx' => $gi,
                'akt_label' => $curLabel,
                'akt_no' => $curNo,
                'header_kriteria' => $headerKriteria,
                'title' => $title,
                'no_kuk' => $curKukNo,
                'deskripsi_kuk' => $curKukDesc,
                'varian' => implode('; ', $varian),
                'header_row' => $rn,
                'skor_col' => $sc,
                'scores' => $scores,
            ];
        }

        return $blocks;
    }

    // =====================================================================
    // 3. Resolusi
    // =====================================================================

    public function loadReference(): array
    {
        $units = [];
        foreach (DB::table($this->schema . '.unit_kompetensi')->select('kode_unit')->get() as $r) {
            if (preg_match('/(\d{3})\.\d+$/', $r->kode_unit, $m)) {
                $units[(int) $m[1]] = $r->kode_unit;
            }
        }

        $elem = [];
        $rows = DB::table($this->schema . '.elemen_kompetensi')
            ->select('kode_unit', 'kode_elemen_excel', 'kriteria_unjuk_kerja')->get();
        foreach ($rows as $r) {
            if (!preg_match('/^(\d+)([A-Z])$/', strtoupper(trim((string) $r->kode_elemen_excel)), $m)) {
                continue;
            }
            if ($m[2] !== 'A') {
                continue; // A/B/C duplikat teks; pakai varian A
            }
            $no = (int) $m[1];
            $kuk = [];
            foreach (preg_split('/\r?\n/', (string) $r->kriteria_unjuk_kerja) as $line) {
                $line = $this->squash($line);
                if ($line === '') {
                    continue;
                }
                $kuk[] = preg_replace('/^\s*\d+\s*[.)]?\s*/u', '', $line);
            }
            $elem[$r->kode_unit][$no] = ['kode' => $m[1] . $m[2], 'kuk' => $kuk];
        }

        // Dua peta aktivitas:
        //   (a) $akt       : aktivitas_id ternormalisasi -> aktivitas_id
        //   (b) $aktByKey  : "kode_unit|ELEMEN|no_kuk"    -> aktivitas_id
        //
        // (b) dibangun lewat join elemen_id (aktivitas_kompeten.elemen_id ->
        // elemen_kompetensi.elemen_id -> kode_unit). Peta ini SENGAJA tidak
        // memakai prefix unit dari aktivitas_id, karena sebagian baris
        // aktivitas_kompeten memakai prefix unit yang salah ketik:
        //   unit R.91MUS02.022.3 -> aktivitas_id-nya "R91MUS02213-..."  (kurang satu digit)
        //   unit R.91MUS02.025.2 -> aktivitas_id-nya "R91MUS02025-..."  (kurang satu digit)
        // Kode aplikasi (SkoringController) sendiri menyusun kunci pencarian
        // dari elemen_kompetensi.uni_kode_unit, jadi yang benar adalah bentuk
        // kanonik (R91MUS020223 / R91MUS020252) — bukan bentuk salah ketik.
        $akt = [];
        $aktByKey = [];
        $join = DB::table($this->schema . '.aktivitas_kompeten as a')
            ->leftJoin($this->schema . '.elemen_kompetensi as e', 'e.elemen_id', '=', 'a.elemen_id')
            ->select('a.aktivitas_id', 'e.kode_unit')
            ->get();

        foreach ($join as $r) {
            if ($r->aktivitas_id === null || $r->aktivitas_id === '') {
                continue;
            }
            $akt[$this->normUnit($r->aktivitas_id)] = $r->aktivitas_id;

            if (!$r->kode_unit) {
                continue;
            }
            $parts = explode('-', $r->aktivitas_id);
            $n = count($parts);
            if ($n < 3) {
                continue;
            }
            $elemen = strtoupper(trim($parts[$n - 2]));
            $kuk = (int) $parts[$n - 1];
            if ($elemen === '' || $kuk <= 0) {
                continue;
            }
            $aktByKey[$r->kode_unit . '|' . $elemen . '|' . $kuk] = $r->aktivitas_id;
        }

        return ['units' => $units, 'elem' => $elem, 'akt' => $akt, 'aktByKey' => $aktByKey];
    }

    /** @return array<int,array> */
    public function resolve(array $blocks, array $ref): array
    {
        $groups = [];
        foreach ($blocks as $i => $b) {
            $groups[$b['sheet'] . '#' . $b['group_idx']][] = $i;
        }

        $rows = [];
        foreach ($groups as $idxList) {
            $first = $blocks[$idxList[0]];
            $sheet = $first['sheet'];
            $sheetNo = preg_match('/(\d+)/', $sheet, $m) ? (int) $m[1] : null;
            $kodeUnit = $sheetNo !== null ? ($ref['units'][$sheetNo] ?? null) : null;
            $elemOfUnit = $kodeUnit !== null ? ($ref['elem'][$kodeUnit] ?? []) : [];

            $groupBlocks = array_map(fn ($i) => $blocks[$i], $idxList);
            [$elemenNo, $elemenScore] = $this->bestElemen($groupBlocks, $elemOfUnit);
            $elemenKode = ($elemenNo !== null && isset($elemOfUnit[$elemenNo]))
                ? $elemOfUnit[$elemenNo]['kode'] : null;
            $jumlahKukElemen = ($elemenNo !== null && isset($elemOfUnit[$elemenNo]))
                ? count($elemOfUnit[$elemenNo]['kuk']) : 0;

            foreach ($groupBlocks as $b) {
                $aid = null;
                if ($kodeUnit !== null && $elemenKode !== null && $b['no_kuk'] !== null) {
                    // Bentuk kanonik — inilah yang dicari aplikasi
                    // (SkoringController menyusunnya dari uni_kode_unit).
                    $aid = $this->normUnit($kodeUnit) . '-' . $elemenKode . '-' . sprintf('%02d', (int) $b['no_kuk']);
                }

                $exists = $aid !== null && isset($ref['akt'][$aid]);
                $via = $exists ? 'aktivitas_id' : null;

                // Jaring pengaman 1: kalau prefix unit di aktivitas_kompeten
                // salah ketik, keberadaan aktivitas dibuktikan lewat peta
                // elemen_id (tanpa melihat prefix).
                if (!$exists && $kodeUnit !== null && $elemenKode !== null && $b['no_kuk'] !== null) {
                    $key = $kodeUnit . '|' . strtoupper($elemenKode) . '|' . (int) $b['no_kuk'];
                    if (isset($ref['aktByKey'][$key])) {
                        $exists = true;
                        $via = 'elemen_id';
                    }
                }

                // Jaring pengaman 2: KUK ada di teks elemen
                // (elemen_kompetensi.kriteria_unjuk_kerja) tetapi tidak punya
                // baris di aktivitas_kompeten. aktivitas_kompeten adalah daftar
                // TERKONDENSASI (mis. 3 aktivitas mewakili 5 KUK), sedangkan
                // SkoringController menomori KUK langsung dari teks elemen —
                // jadi rubrik tetap perlu ditulis agar tidak jatuh ke rubrik
                // generik "Sangat Kurang/Kurang/Cukup/Baik/Sangat Baik".
                if (!$exists
                    && $elemenNo !== null
                    && $b['no_kuk'] !== null
                    && $jumlahKukElemen > 0
                    && (int) $b['no_kuk'] >= 1
                    && (int) $b['no_kuk'] <= $jumlahKukElemen
                    && $elemenScore >= self::MIN_ELEMEN_SCORE
                ) {
                    $exists = true;
                    $via = 'elemen_kuk';
                }

                $rows[] = $b + [
                    'kode_unit' => $kodeUnit,
                    'elemen_no' => $elemenNo,
                    'elemen_kode' => $elemenKode,
                    'elemen_score' => round($elemenScore, 3),
                    'aktivitas_id' => $aid,
                    'via' => $via,
                    'target_exists' => $exists,
                ];
            }
        }

        return $rows;
    }

    /**
     * Pilih elemen paling cocok untuk sekumpulan blok rubrik.
     *
     * Dicocokkan lewat kemiripan teks KUK, BUKAN lewat label "Aktivitas N" di
     * spreadsheet: penomoran aktivitas di spreadsheet melompat ketika ada
     * elemen yang tidak dipakai, sehingga label sering tidak sama dengan nomor
     * elemen (terbukti lebih buruk pada 9 dari 125 grup).
     *
     * @return array{0:int|null,1:float} [nomor elemen, skor kemiripan 0..1]
     */
    private function bestElemen(array $groupBlocks, array $elemOfUnit): array
    {
        if (!$elemOfUnit) {
            return [null, 0.0];
        }
        $titles = [];
        foreach ($groupBlocks as $b) {
            $t = $b['deskripsi_kuk'] ?: $b['title'];
            if ($t !== '') {
                $titles[] = $t;
            }
        }
        if (!$titles) {
            return [array_key_first($elemOfUnit), 1.0];
        }

        $best = null;
        $bestScore = -1.0;
        foreach ($elemOfUnit as $no => $info) {
            if (!$info['kuk']) {
                continue;
            }
            $total = 0.0;
            foreach ($titles as $t) {
                $mx = 0.0;
                foreach ($info['kuk'] as $k) {
                    similar_text(strtolower($t), strtolower($k), $pct);
                    $s = $pct / 100.0;
                    if ($s > $mx) {
                        $mx = $s;
                    }
                }
                $total += $mx;
            }
            $avg = $total / count($titles);
            if ($avg > $bestScore) {
                $bestScore = $avg;
                $best = $no;
            }
        }

        return [$best, max(0.0, $bestScore)];
    }

    // =====================================================================
    // 4. Upsert
    // =====================================================================

    public function apply(array $rows, bool $dryRun = true): array
    {
        $usable = array_values(array_filter($rows, fn ($r) => $r['target_exists']));
        $skipped = array_values(array_filter($rows, fn ($r) => !$r['target_exists']));

        $byAktivitas = [];
        foreach ($usable as $r) {
            $byAktivitas[$r['aktivitas_id']][] = $r;
        }

        $report = [
            'dry_run' => $dryRun,
            'tabel' => $this->table(),
            'aktivitas_target' => count($byAktivitas),
            'baris_siap' => count($usable),
            'baris_lewat_elemen_id' => count(array_filter($usable, fn ($r) => ($r['via'] ?? null) === 'elemen_id')),
            'baris_lewat_elemen_kuk' => count(array_filter($usable, fn ($r) => ($r['via'] ?? null) === 'elemen_kuk')),
            'baris_dilewati' => count($skipped),
            'dilewati' => [],
        ];

        if ($dryRun) {
            foreach (array_slice($skipped, 0, 50) as $s) {
                $report['dilewati'][] = [
                    'sheet' => $s['sheet'],
                    'elemen' => $s['elemen_kode'],
                    'no_kuk' => $s['no_kuk'],
                    'varian' => $s['varian'],
                    'aktivitas_id_dicari' => $s['aktivitas_id'],
                ];
            }

            return $report;
        }

        $tbl = $this->table();
        $nextId = (int) DB::table($tbl)->max('rubik_id') + 1;
        $now = date('Y-m-d H:i:s');
        $nInserted = 0;

        DB::beginTransaction();
        try {
            // Hapus sekali untuk semua aktivitas (bukan satu per satu) supaya
            // tidak kena latensi jaringan: 470 round-trip ke Supabase bisa
            // memakan ~100 detik, sedangkan satu whereIn hanya sekali jalan.
            DB::table($tbl)->whereIn('aktivitas_id', array_keys($byAktivitas))->delete();

            $payload = [];
            foreach ($byAktivitas as $aid => $rs) {
                foreach ($rs as $r) {
                    $s = $r['scores'];
                    $payload[] = [
                        'rubik_id' => $nextId++,
                        'aktivitas_id' => $aid,
                        'akt_aktivitas_id' => $aid,
                        'skor_1' => ($s[1]['kriteria'] ?? '') !== '' ? $s[1]['kriteria'] : null,
                        'skor_2' => ($s[2]['kriteria'] ?? '') !== '' ? $s[2]['kriteria'] : null,
                        'skor_3' => ($s[3]['kriteria'] ?? '') !== '' ? $s[3]['kriteria'] : null,
                        'skor_4' => ($s[4]['kriteria'] ?? '') !== '' ? $s[4]['kriteria'] : null,
                        'skor_5' => ($s[5]['kriteria'] ?? '') !== '' ? $s[5]['kriteria'] : null,
                        'jenis_file' => ($s[1]['evidence'] ?? '') !== '' ? $s[1]['evidence'] : null,
                    ];
                }
            }

            foreach (array_chunk($payload, 500) as $chunk) {
                DB::table($tbl)->insert($chunk);
                $nInserted += count($chunk);
            }
            // Pangkas baris lama yang tidak lagi dihasilkan spreadsheet, supaya
            // rubrik yang dipindah atau dihapus di spreadsheet tidak tertinggal
            // sebagai data basi (aplikasi akan tetap menampilkannya kalau tidak
            // dipangkas). Pengaman: kalau spreadsheet tiba-tiba menghasilkan
            // jauh lebih sedikit baris (mis. sheet-nya sedang kosong/rusak),
            // pemangkasan dibatalkan supaya tabel tidak ikut terkuras.
            $report['baris_dipangkas'] = 0;
            if ($this->prune && $byAktivitas) {
                $existing = (int) DB::table($tbl)->distinct()->count('aktivitas_id');
                if ($existing === 0 || count($byAktivitas) >= (int) ceil($existing * 0.5)) {
                    // Pola prefix mengikuti cara aplikasi mencari (SkoringController):
                    // base_code tanpa karakter terakhir + '_' (wildcard 1 karakter).
                    // Ini penting karena data lama memakai sufiks unit yang beda
                    // satu karakter, mis. baris lama "R91MUS020263-3A-02" untuk
                    // unit R.91MUS02.026.2 (kanonik: R91MUS020262). Tanpa pola
                    // ini, baris lama tetap tertinggal dan -- karena aplikasi
                    // memakai ->orderBy('rubik_id','asc')->first() -- justru
                    // MENUTUPI rubrik baru dari spreadsheet.
                    $prefixes = [];
                    foreach ($rows as $r) {
                        if (empty($r['kode_unit'])) {
                            continue;
                        }
                        $base = $this->normUnit($r['kode_unit']);
                        if (strlen($base) > 1) {
                            $prefixes[str_replace(['%', '_'], ['\%', '\_'], substr($base, 0, -1)) . '_'] = true;
                        }
                    }
                    if ($prefixes) {
                        $q = DB::table($tbl)->whereNotIn('aktivitas_id', array_keys($byAktivitas));
                        $q->where(function ($w) use ($prefixes) {
                            foreach (array_keys($prefixes) as $p) {
                                $w->orWhere('aktivitas_id', 'like', $p . '%');
                            }
                        });
                        $report['baris_dipangkas'] = $q->delete();
                    }
                } else {
                    $report['prune_dilewati'] = true;
                    Log::warning('Rubrik sync: pemangkasan dilewati, jumlah aktivitas turun drastis ('
                        . count($byAktivitas) . ' vs ' . $existing . ' di tabel).');
                }
            }

            DB::commit();
            $report['ok'] = true;
            $report['baris_ditulis'] = $nInserted;
            $report['waktu'] = $now;
        } catch (Throwable $e) {
            DB::rollBack();
            $report['ok'] = false;
            $report['error'] = $e->getMessage();
            Log::error('Rubrik sync gagal: ' . $e->getMessage());
        }

        return $report;
    }

    // =====================================================================
    // 4b. Sinkronisasi KUK Text ke elemen_kompetensi
    // =====================================================================

    /**
     * Rekonstruksi dan update elemen_kompetensi.kriteria_unjuk_kerja
     * dari data spreadsheet yang sudah di-resolve.
     */
    public function applyElemenKuk(array $rows, bool $dryRun = true): array
    {
        $usable = array_values(array_filter($rows, fn ($r) => $r['target_exists'] && $r['kode_unit'] && $r['elemen_kode'] && $r['no_kuk'] !== null));

        // Kelompokkan per (kode_unit, elemen_kode)
        $byElemen = [];
        foreach ($usable as $r) {
            $key = $r['kode_unit'] . '|' . $r['elemen_kode'];
            $byElemen[$key][] = $r;
        }

        $report = [
            'dry_run' => $dryRun,
            'tabel' => $this->schema . '.elemen_kompetensi',
            'elemen_target' => count($byElemen),
            'elemen_ditulis' => 0,
        ];

        if ($dryRun || empty($byElemen)) {
            return $report;
        }

        $now = date('Y-m-d H:i:s');
        $tbl = $this->schema . '.elemen_kompetensi';
        $nUpdated = 0;

        foreach ($byElemen as $key => $rs) {
            [$kodeUnit, $elemenKode] = explode('|', $key, 2);

            // Ambil deskripsi unik per no_kuk (varian pertama saja)
            $kukMap = [];
            foreach ($rs as $r) {
                $n = (int) $r['no_kuk'];
                if (!isset($kukMap[$n])) {
                    $desc = $r['deskripsi_kuk'] ?: $r['title'];
                    // Bersihkan nomor di awal deskripsi
                    $desc = preg_replace('/^\s*\d+(?:\.\d+)*[a-z]?\s*[.)]?\s*/u', '', $desc);
                    $kukMap[$n] = $this->squash($desc);
                }
            }
            ksort($kukMap);

            // Rekonstruksi kriteria_unjuk_kerja
            $lines = [];
            foreach ($kukMap as $n => $desc) {
                $lines[] = $n . '. ' . $desc;
            }
            $kriteria = implode("\n", $lines);

            $updated = DB::table($tbl)
                ->where('kode_unit', $kodeUnit)
                ->where('kode_elemen_excel', $elemenKode)
                ->update(['kriteria_unjuk_kerja' => $kriteria]);

            if ($updated) {
                $nUpdated++;
            }
        }

        $report['elemen_ditulis'] = $nUpdated;

        return $report;
    }

    // =====================================================================
    // 4c. Sinkronisasi aktivitas_kompeten (insert missing rows)
    // =====================================================================

    /**
     * Pastikan setiap KUK yang punya rubrik juga punya baris di aktivitas_kompeten.
     * Insert baris baru bila aktivitas_id belum ada.
     */
    public function applyAktivitasKompeten(array $rows, bool $dryRun = true): array
    {
        $usable = array_values(array_filter($rows, fn ($r) => $r['target_exists'] && $r['aktivitas_id']));

        // Ambil elemen_id mapping
        $tblElem = $this->schema . '.elemen_kompetensi';
        $elemenMap = [];
        foreach (DB::table($tblElem)->select('elemen_id', 'kode_unit', 'kode_elemen_excel')->get() as $r) {
            $key = $r->kode_unit . '|' . $r->kode_elemen_excel;
            $elemenMap[$key] = $r->elemen_id;
        }

        // Cek aktivitas_id yang sudah ada
        $tblAkt = $this->schema . '.aktivitas_kompeten';
        $existingAkt = DB::table($tblAkt)->pluck('aktivitas_id')->flip()->toArray();

        $toInsert = [];
        $seen = [];
        foreach ($usable as $r) {
            $aid = $r['aktivitas_id'];
            if (isset($existingAkt[$aid]) || isset($seen[$aid])) {
                continue;
            }
            $seen[$aid] = true;

            $key = $r['kode_unit'] . '|' . $r['elemen_kode'];
            $elemenId = $elemenMap[$key] ?? null;
            if (!$elemenId) {
                continue;
            }

            $desc = $r['deskripsi_kuk'] ?: $r['title'];
            $desc = preg_replace('/^\s*\d+(?:\.\d+)*[a-z]?\s*[.)]?\s*/u', '', $desc);

            $toInsert[] = [
                'aktivitas_id' => $aid,
                'elemen_id' => $elemenId,
                'detail_aktivitas' => $this->squash($desc),
                'jumlah_evidence_wa' => 1,
                'kriteria_kompetens' => $this->squash($desc),
                'aktif' => 'Y',
            ];
        }

        $report = [
            'dry_run' => $dryRun,
            'tabel' => $tblAkt,
            'aktivitas_baru' => count($toInsert),
        ];

        if ($dryRun || empty($toInsert)) {
            return $report;
        }

        DB::beginTransaction();
        try {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table($tblAkt)->insert($chunk);
            }
            DB::commit();
            $report['ok'] = true;
        } catch (Throwable $e) {
            DB::rollBack();
            $report['ok'] = false;
            $report['error'] = $e->getMessage();
            Log::error('Aktivitas sync gagal: ' . $e->getMessage());
        }

        return $report;
    }

    // =====================================================================
    // 5. Orkestrasi
    // =====================================================================

    /**
     * Jalankan sinkronisasi penuh.
     *
     * @param  string|null  $localFile  pakai file lokal (kalau tidak diisi -> unduh dari Google)
     */
    public function sync(bool $dryRun = true, ?string $localFile = null, ?string $sheetId = null): array
    {
        $start = microtime(true);

        $file = $localFile ?: $this->downloadSheet($sheetId);
        $blocks = $this->parseWorkbook($file);
        $ref = $this->loadReference();
        $rows = $this->resolve($blocks, $ref);
        $report = $this->apply($rows, $dryRun);

        // Sinkronisasi KUK text dan aktivitas_kompeten
        $report['elemen_kuk'] = $this->applyElemenKuk($rows, $dryRun);
        $report['aktivitas_kompeten'] = $this->applyAktivitasKompeten($rows, $dryRun);

        $report['sumber'] = $localFile ? 'file-lokal' : 'google-spreadsheet';
        $report['berkas'] = basename($file);
        $report['jumlah_blok'] = count($blocks);
        $report['durasi_detik'] = round(microtime(true) - $start, 2);
        $report['ringkasan_tidak_ada'] = $this->summaryUnmatched($rows);

        return $report;
    }

    public function summaryUnmatched(array $rows): array
    {
        $byUnit = [];
        foreach ($rows as $r) {
            if ($r['target_exists']) {
                continue;
            }
            $ku = $r['kode_unit'] ?: '??';
            $byUnit[$ku]['total'] = ($byUnit[$ku]['total'] ?? 0) + 1;
            $byUnit[$ku]['contoh'][] = [
                'elemen' => $r['elemen_kode'],
                'no_kuk' => $r['no_kuk'],
                'varian' => $r['varian'],
                'aktivitas_id_dicari' => $r['aktivitas_id'],
            ];
            $byUnit[$ku]['contoh'] = array_slice($byUnit[$ku]['contoh'], 0, 3);
        }
        ksort($byUnit);

        return $byUnit;
    }

    // =====================================================================
    // Utilitas
    // =====================================================================

    private function normUnit(?string $u): string
    {
        return str_replace('.', '', (string) $u);
    }

    private function squash(?string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $s));
    }

    private function parens(?string $s): array
    {
        $out = [];
        if (preg_match_all('/\(([^)]*)\)/u', (string) $s, $m)) {
            foreach ($m[1] as $x) {
                $v = $this->squash($x);
                $low = strtolower($v);
                if ($v !== '' && !in_array($low, ['?', '-', 'na', 'n/a'], true)) {
                    $out[] = $v;
                }
            }
        }

        return $out;
    }
}
