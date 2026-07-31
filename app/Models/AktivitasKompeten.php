<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AktivitasKompeten extends Model
{
    protected $table = 'aktivitas_kompeten';
    protected $primaryKey = 'aktivitas_id';
    public $incrementing = false; // PK berupa string
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];
}