<?php

namespace App\Console\Commands;

use App\Services\RubrikSheetService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sinkronisasi tabel rubrik_skor dari Google Spreadsheet.
 *
 *   php artisan rubrik:sync                 # tulis ke database
 *   php artisan rubrik:sync --dry-run       # pratinjau saja
 *   php artisan rubrik:sync --file="C:\path\breakdown.xlsx"
 *   php artisan rubrik:sync --schema=geotrax_v4
 */
class SyncRubrikSheet extends Command
{
    protected $signature = 'rubrik:sync
                            {--dry-run : Hanya pratinjau, tidak menulis ke database}
                            {--file= : Pakai berkas .xlsx lokal (tanpa unduh dari Google)}
                            {--sheet= : Override ID Google Spreadsheet}
                            {--schema= : Override schema database (mis. geotrax_v4)}
                            {--no-prune : Jangan hapus baris lama yang tidak ada lagi di spreadsheet}';

    protected $description = 'Sinkronkan rubrik skor dari Google Spreadsheet ke tabel rubrik_skor';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $service = new RubrikSheetService(
                $this->option('schema'),
                $this->option('no-prune') ? false : null
            );
            $this->info('Schema   : ' . $service->schema());
            $this->info('Tabel    : ' . $service->table());
            $this->info('Mode     : ' . ($dryRun ? 'PRATINJAU (dry-run)' : 'TULIS KE DATABASE'));
            $this->newLine();

            $report = $service->sync($dryRun, $this->option('file'), $this->option('sheet'));

            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Sumber', $report['sumber'] ?? '-'],
                    ['Berkas', $report['berkas'] ?? '-'],
                    ['Blok dibaca', ($report['jumlah_blok'] ?? 0) . ' blok'],
                    ['Aktivitas target', $report['aktivitas_target']],
                    ['Baris siap', $report['baris_siap']],
                    ['  - lewat elemen_id', $report['baris_lewat_elemen_id'] ?? 0],
                    ['  - lewat elemen_kuk', $report['baris_lewat_elemen_kuk'] ?? 0],
                    ['Baris dilewati', $report['baris_dilewati']],
                    ['Baris ditulis', $report['baris_ditulis'] ?? 0],
                    ['Baris dipangkas', $report['baris_dipangkas'] ?? 0],
                    ['Elemen KUK diupdate', ($report['elemen_kuk']['elemen_ditulis'] ?? 0) . ' elemen'],
                    ['Aktivitas baru', ($report['aktivitas_kompeten']['aktivitas_baru'] ?? 0) . ' baris'],
                    ['Durasi', ($report['durasi_detik'] ?? 0) . ' s'],
                ]
            );

            if (!empty($report['ringkasan_tidak_ada'])) {
                $this->newLine();
                $this->warn('Blok yang belum punya target aktivitas_id (tidak ikut ditulis):');
                foreach ($report['ringkasan_tidak_ada'] as $ku => $v) {
                    $this->line("  - {$ku}: {$v['total']} blok");
                }
            }

            $this->saveLastRun($report, $dryRun);

            if (!($report['ok'] ?? true)) {
                $this->error('GAGAL: ' . ($report['error'] ?? 'tidak diketahui'));

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('Selesai.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('GAGAL: ' . $e->getMessage());
            $this->saveLastRun(['ok' => false, 'error' => $e->getMessage()], $dryRun);

            return self::FAILURE;
        }
    }

    private function saveLastRun(array $report, bool $dryRun): void
    {
        try {
            $dir = config('rubrik.cache_path', storage_path('app/rubrik'));
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents($dir . '/last_sync.json', json_encode([
                'waktu' => date('c'),
                'mode' => $dryRun ? 'preview' : 'apply',
                'ok' => $report['ok'] ?? false,
                'aktivitas_target' => $report['aktivitas_target'] ?? null,
                'baris_siap' => $report['baris_siap'] ?? null,
                'baris_lewat_elemen_id' => $report['baris_lewat_elemen_id'] ?? null,
                'baris_lewat_elemen_kuk' => $report['baris_lewat_elemen_kuk'] ?? null,
                'baris_ditulis' => $report['baris_ditulis'] ?? null,
                'baris_dipangkas' => $report['baris_dipangkas'] ?? null,
                'baris_dilewati' => $report['baris_dilewati'] ?? null,
                'error' => $report['error'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            // diamkan saja, ini hanya catatan
        }
    }
}
