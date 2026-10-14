# syntax=docker/dockerfile:1
#
# Geotrax Museum Geologi — image produksi untuk Railway.
#
#   php:8.2-apache
#     + pdo_pgsql  -> Supabase PostgreSQL (schema geotrax_v4)
#     + zip, gd    -> phpoffice/phpspreadsheet & App\Support\XlsxReader (baca .xlsx)
#     + poppler-utils -> binary `pdftotext` yang dipakai spatie/pdf-to-text
#
# Document root diarahkan ke /public, sama seperti hosting biasa.

FROM php:8.2-apache

# ---------------------------------------------------------------------------
# 1. Dependensi sistem + ekstensi PHP
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip gd opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# 2. php.ini
#    variables_order wajib memuat "E" supaya variabel lingkungan dari Railway
#    terbaca oleh env() Laravel (kalau tidak, $_ENV kosong).
#    upload/post size dinaikkan karena bukti evidence dibatasi 5 MB per berkas,
#    dan rubrik:sync mengunduh .xlsx ~30 MB.
# ---------------------------------------------------------------------------
RUN { \
        echo 'variables_order = "EGPCS"'; \
        echo 'memory_limit = 512M'; \
        echo 'upload_max_filesize = 20M'; \
        echo 'post_max_size = 24M'; \
        echo 'max_execution_time = 300'; \
        echo 'max_input_time = 300'; \
        echo 'opcache.enable = 1'; \
    } > /usr/local/etc/php/conf.d/zz-geotrax.ini

# ---------------------------------------------------------------------------
# 3. Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# 4. Apache -> document root /var/www/html/public + izinkan .htaccess
# ---------------------------------------------------------------------------
RUN printf '%s\n' \
        '<VirtualHost *:80>' \
        '    DocumentRoot /var/www/html/public' \
        '    <Directory /var/www/html/public>' \
        '        Options -Indexes +FollowSymLinks' \
        '        AllowOverride All' \
        '        Require all granted' \
        '    </Directory>' \
        '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
        '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
        '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# 5. Dependensi PHP (layer terpisah supaya cache build tidak batal tiap ganti kode)
# ---------------------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# ---------------------------------------------------------------------------
# 6. Kode aplikasi
# ---------------------------------------------------------------------------
COPY . .

# package:discover menulis ulang bootstrap/cache/packages.php sesuai paket yang
# benar-benar terpasang (--no-dev membuang laravel/pail, pint, phpunit, dll).
RUN php artisan package:discover --ansi || true

RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
        storage/app/rubrik \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Railway mengabaikan EXPOSE dan memakai $PORT; ini hanya dokumentasi.
EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
