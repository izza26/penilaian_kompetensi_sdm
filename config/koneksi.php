<?php
// Railway akan membaca variabel DB_PASSWORD dari tab Variables yang tadi kita isi
$password = getenv('DB_PASSWORD'); 

$conn_string = "host=aws-1-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.wdixqhymjgbruywjcamn password=$password sslmode=require";

$koneksi = @pg_connect($conn_string);

if (!$koneksi) {
    // Kalau gagal konek, nanti muncul di log Railway
    die("Koneksi gagal.");
}
?>