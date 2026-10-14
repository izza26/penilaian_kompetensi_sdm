<?php
/**
 * Pemeriksa API in-process: memanggil endpoint /api/rubrik/* tanpa HTTP server.
 * Dipakai untuk memastikan aplikasi benar-benar hidup dan menunjuk schema yang benar.
 * Jalankan:  D:\xampp\php\php.exe _cek_api.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

function hit($kernel, string $uri, string $method = 'GET')
{
    $req = Request::create($uri, $method);
    $res = $kernel->handle($req);
    return [$res->getStatusCode(), $res->getContent()];
}

$token = config('rubrik.token');

echo "==============================================\n";
echo " PEMERIKSAAN API RUBRIK (in-process)\n";
echo "==============================================\n";
echo "APP_ENV          : " . config('app.env') . "\n";
echo "DB_SCHEMA        : " . config('database.connections.pgsql.search_path') . "\n";
echo "RUBRIK_SCHEMA    : " . config('rubrik.schema') . "\n";
echo "RUBRIK_TABLE     : " . config('rubrik.table') . "\n";
echo "RUBRIK_AUTO_SYNC : " . var_export(config('rubrik.auto_sync'), true) . "\n";
echo "RUBRIK_PRUNE     : " . var_export(config('rubrik.prune'), true) . "\n";
echo "TOKEN panjang    : " . strlen((string) $token) . " karakter\n";
echo "----------------------------------------------\n";

// 1. status
[$code, $body] = hit($kernel, '/api/rubrik/status');
echo "[1] GET /api/rubrik/status           -> HTTP $code\n";
$j = json_decode($body, true);
echo "    " . json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

// 2. sync tanpa token
[$code2, $body2] = hit($kernel, '/api/rubrik/sync');
echo "[2] GET /api/rubrik/sync (tanpa token) -> HTTP $code2\n";
echo "    " . trim(mb_substr($body2, 0, 160)) . "\n";

// 3. sync dry-run dengan token
[$code3, $body3] = hit($kernel, '/api/rubrik/sync?token=' . urlencode((string) $token) . '&dry_run=1');
echo "[3] GET /api/rubrik/sync?token=…&dry_run=1 -> HTTP $code3\n";
$j3 = json_decode($body3, true);
if (is_array($j3)) {
    foreach (['ok', 'schema', 'jumlah_blok', 'baris_siap', 'baris_dilewati', 'durasi_detik'] as $k) {
        if (array_key_exists($k, $j3)) {
            echo "    $k = " . (is_bool($j3[$k]) ? var_export($j3[$k], true) : $j3[$k]) . "\n";
        }
    }
    if (!empty($j3['ringkasan_tidak_ada'])) {
        echo "    ringkasan_tidak_ada = " . json_encode($j3['ringkasan_tidak_ada']) . "\n";
    }
} else {
    echo "    " . trim(mb_substr($body3, 0, 300)) . "\n";
}

// 4. jumlah baris rubrik di schema aktif
try {
    $n = DB::table(config('rubrik.schema') . '.' . config('rubrik.table'))->count();
    echo "[4] Jumlah baris rubrik_skor        : $n\n";
} catch (\Throwable $e) {
    echo "[4] GAGAL hitung rubrik_skor: " . $e->getMessage() . "\n";
}

// 5. view pegawai
try {
    $n = DB::table(config('rubrik.schema') . '.pegawai')->count();
    echo "[5] Jumlah baris view pegawai       : $n\n";
} catch (\Throwable $e) {
    echo "[5] GAGAL baca pegawai: " . $e->getMessage() . "\n";
}

echo "==============================================\n";
