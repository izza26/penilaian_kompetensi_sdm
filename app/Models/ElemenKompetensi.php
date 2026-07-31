<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ElemenKompetensi extends Model
{
    protected $table = 'elemen_kompetensi';
    protected $primaryKey = 'elemen_id';
    public $timestamps = false;
    protected $guarded = [];

    // Relasi: 1 Elemen punya banyak Aktivitas
    public function aktivitas()
    {
        return $this->hasMany(AktivitasKompeten::class, 'elemen_id', 'elemen_id');
    }
}