<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianSoftskill extends Model
{
    use HasFactory;

    protected $table = 'penilaian_softskill';

    protected $fillable = [
        'penilaian_id',
        'softskill_id',
        'nilai_aktual',
        'gap',
        'bobot_gap',
    ];

    // Relasi untuk menarik nama softskill
    public function standarSoftskill()
    {
        return $this->belongsTo(StandarSoftskill::class, 'softskill_id', 'softskill_id');
    }
}
