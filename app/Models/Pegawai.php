<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pegawai extends Authenticatable
{
    use Notifiable;

    protected $table = 'pegawai';
    protected $primaryKey = 'pegawai_id';
    public $timestamps = false;

    protected $fillable = [
        'pegawai_id', 
        'nip_nik', 
        'pegawai_nama', 
        'email', 
        'no_hp', 
        'jabatan', 
        'unit_kerja', 
        'password', 
        'role', 
        'status_aktif',
        'user_id' // <--- Tambahkan ini
    ];

    protected $hidden = [
        'password',
    ];
}