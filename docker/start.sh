#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Entrypoint container Railway.
#   - mengikat Apache ke $PORT yang diberikan Railway
#   - menyiapkan folder tulis Laravel
#   - TIDAK menjalankan `config:cache`
#     (kode memakai env('AWS_URL') & env('GEMINI_API_KEYS') di luar folder
#      config/, sehingga setelah config:cache nilai itu jadi null)
# ---------------------------------------------------------------------------
set -euo pipefail

PORT="${PORT:-8080}"

echo "==> Geotrax start: PORT=${PORT}"

# --- 0. Jaring pengaman MPM ----------------------------------------------
# Apache menolak start kalau lebih dari satu MPM aktif
# (AH00534: More than one MPM loaded). Idealnya sudah dibetulkan saat build
# (lihat Dockerfile tahap 1b), ini hanya pengaman kalau base image berubah.
mpm_count="$(ls /etc/apache2/mods-enabled/ 2>/dev/null | grep -c '^mpm_.*\.load$' || true)"
if [ "${mpm_count}" != "1" ]; then
    echo "==> Perbaiki MPM: ditemukan ${mpm_count} MPM aktif, menyisakan mpm_prefork saja"
    rm -f /etc/apache2/mods-enabled/mpm_event.*  \
          /etc/apache2/mods-enabled/mpm_worker.*
    a2enmod mpm_prefork >/dev/null 2>&1 || true
fi

# --- 1. Apache dengar di $PORT -------------------------------------------
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Jangan sampai Apache mengeluh soal ServerName di container
grep -q '^ServerName' /etc/apache2/apache2.conf || echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Log Apache masuk ke stdout/stderr supaya terbaca di Railway
ln -sf /dev/stderr /var/log/apache2/error.log
ln -sf /dev/stdout /var/log/apache2/access.log

# --- 2. Folder tulis Laravel --------------------------------------------
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    storage/app/rubrik \
    storage/app/public \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# --- 3. Persiapan ringan -------------------------------------------------
php artisan storage:link >/dev/null 2>&1 || true
php artisan view:cache   >/dev/null 2>&1 || true

# --- 4. Diagnostik singkat (tanpa membocorkan rahasia) -------------------
php -r '
    $env = fn($k, $d = "(kosong)") => ($v = getenv($k)) === false || $v === "" ? $d : $v;
    $mask = fn($v) => $v === "(kosong)" ? $v : substr($v, 0, 3) . str_repeat("*", 6);
    echo "    APP_ENV      : " . $env("APP_ENV") . PHP_EOL;
    echo "    APP_DEBUG    : " . $env("APP_DEBUG") . PHP_EOL;
    echo "    APP_URL      : " . $env("APP_URL") . PHP_EOL;
    echo "    DB_CONNECTION: " . $env("DB_CONNECTION") . PHP_EOL;
    echo "    DB_HOST      : " . $env("DB_HOST") . PHP_EOL;
    echo "    DB_SCHEMA    : " . $env("DB_SCHEMA") . PHP_EOL;
    echo "    DB_PASSWORD  : " . $mask($env("DB_PASSWORD")) . PHP_EOL;
    echo "    RUBRIK_SCHEMA: " . $env("RUBRIK_SCHEMA") . PHP_EOL;
    echo "    SESSION_DRV  : " . $env("SESSION_DRIVER") . PHP_EOL;
' || true

# --- 5. Jalankan Apache --------------------------------------------------
echo "==> Apache listening on ${PORT}"
exec apache2-foreground
