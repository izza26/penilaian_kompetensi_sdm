<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabel 1: Master Standar Softskill (Dikelola Admin)
        Schema::create('standar_softskill', function (Blueprint $table) {
            $table->id('softskill_id');
            $table->string('nama_softskill'); // Contoh: "Komunikasi", "Inisiatif"
            $table->integer('target_skor')->default(4); // Target nilai ideal (1-5)
            $table->text('deskripsi')->nullable(); // Keterangan perilaku
            $table->timestamps();
        });

        // Tabel 2: Hasil Penilaian Softskill per Pegawai (Diinput Pimpinan)
        Schema::create('penilaian_softskill', function (Blueprint $table) {
            $table->id('id');
            // Jika database kamu mewajibkan prefix skema 'geotrax_v4', sesuaikan relasinya
            $table->unsignedBigInteger('penilaian_id'); // Relasi ke penilaian_header
            $table->unsignedBigInteger('softskill_id'); // Relasi ke standar_softskill
            $table->integer('nilai_aktual'); // Nilai 1-5 dari Pimpinan
            $table->integer('gap'); // Aktual - Target
            $table->decimal('bobot_gap', 5, 2); // Nilai konversi Profile Matching
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('penilaian_softskill');
        Schema::dropIfExists('standar_softskill');
    }
};
