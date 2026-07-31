<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodePenilaian extends Model
{
    protected $table = 'periode_penilaian';
    protected $primaryKey = 'periode_id';
    public $timestamps = false;
    protected $guarded = [];
}