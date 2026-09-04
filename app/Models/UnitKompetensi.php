<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UnitKompetensi extends Model
{
    protected $table = 'geotrax_v3.unit_kompetensi';
    protected $primaryKey = 'kode_unit';
    public $incrementing = false; // Karena primary key-nya string (huruf)
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    // Relasi: 1 Unit punya banyak Elemen
    public function elemen()
    {
        return $this->hasMany(ElemenKompetensi::class, 'kode_unit', 'kode_unit');
    }
}