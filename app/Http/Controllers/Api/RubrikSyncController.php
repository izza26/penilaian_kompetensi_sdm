<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RubrikSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * API sinkronisasi rubrik skor.
 *
 *   GET  /api/rubrik/sync?token=...            -> tarik Google Sheet lalu update DB
 *   GET  /api/rubrik/sync?token=...&dry_run=1  -> hanya pratinjau (tidak menulis)
 *   POST /api/rubrik/import?token=...          -> unggah file .xlsx
 *   GET  /api/rubrik/status                    -> info konfigurasi & isi tabel
 */
class RubrikSyncController extends Controller
{
    private function guard(Request $request): ?JsonResponse
    {
        $expected = (string) config('rubrik.token', '');
        if ($expected === '') {
            return null;
        }
        $given = (string) ($request->query('token') ?? $request->header('X-Token', ''));
        if (!hash_equals($expected, $given)) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid.'], 401);
        }

        return null;
    }

    public function sync(Request $request): JsonResponse
    {
        if ($r = $this->guard($request)) {
            return $r;
        }

        $dryRun = $request->boolean('dry_run', false) || $request->query('mode') === 'preview';
        $schema = $request->query('schema');

        try {
            $service = new RubrikSheetService($schema);
            $report = $service->sync($dryRun);
            $report['ok'] = $report['ok'] ?? true;
            $report['mode'] = $dryRun ? 'preview' : 'apply';

            return response()->json($report, ($report['ok'] ?? false) ? 200 : 500);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        if ($r = $this->guard($request)) {
            return $r;
        }
        if (!$request->hasFile('file')) {
            return response()->json(['ok' => false, 'error' => 'Field "file" (.xlsx) tidak ditemukan.'], 400);
        }

        $dryRun = $request->boolean('dry_run', false);
        $schema = $request->query('schema');

        try {
            $tmp = $request->file('file')->getRealPath();
            $service = new RubrikSheetService($schema);
            $blocks = $service->parseWorkbook($tmp);
            $ref = $service->loadReference();
            $rows = $service->resolve($blocks, $ref);
            $report = $service->apply($rows, $dryRun);
            $report['ok'] = $report['ok'] ?? true;
            $report['mode'] = $dryRun ? 'preview' : 'apply';
            $report['sumber'] = 'upload';
            $report['jumlah_blok'] = count($blocks);
            $report['ringkasan_tidak_ada'] = $service->summaryUnmatched($rows);

            return response()->json($report, ($report['ok'] ?? false) ? 200 : 500);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            $service = new RubrikSheetService($request->query('schema'));
            $tbl = $service->table();
            $total = \Illuminate\Support\Facades\DB::table($tbl)->count();

            return response()->json([
                'ok' => true,
                'schema' => $service->schema(),
                'tabel' => $tbl,
                'sheet_id' => config('rubrik.sheet_id'),
                'token_aktif' => config('rubrik.token') !== '',
                'jumlah_baris_rubrik' => $total,
                'server_time' => now()->toDateTimeString(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
