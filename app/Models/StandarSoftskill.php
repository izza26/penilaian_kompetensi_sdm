<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandarSoftskill extends Model
{
    use HasFactory;

    // Sesuaikan prefix schema jika diperlukan, contoh: 'geotrax_v4.standar_softskill'
    protected $table = 'standar_softskill';
    protected $primaryKey = 'softskill_id';

    protected $fillable = [
        'nama_softskill',
        'target_skor',
        'deskripsi',
    ];
}
