<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StrukturPegawaiSeeder extends Seeder
{
    public function run()
    {
        // Bersihkan data lama agar tidak dobel (opsional)
        DB::table('pegawai')->delete();
        DB::table('users')->delete();

        $data = [
            // --- LEVEL PIMPINAN ---
            ['bsantoso', 'Budi Santoso', 'Budi123!', 'Kepala Museum', 'pimpinan'],
            ['aprasetyo', 'Andi Prasetyo', 'Andi123!', 'Koordinator (Registrasi & Konservasi)', 'pimpinan'],
            ['dlestari', 'Dewi Lestari', 'Dewi123!', 'Manajer (Edukasi & Program Publik)', 'pimpinan'],
            ['hgunawan', 'Hendra Gunawan', 'Hendra123!', 'Manajer (Humas & Pemasaran)', 'pimpinan'],
            ['saminah', 'Siti Aminah', 'Siti123!', 'Kurator', 'pimpinan'],
            
            // --- LEVEL PEGAWAI (BAWAHAN) ---
            ['rwulandari', 'Rina Wulandari', 'Rina123!', 'Konservator', 'pegawai'],
            ['whidayat', 'Wahyu Hidayat', 'Wahyu123!', 'Register', 'pegawai'],
            ['fnugroho', 'Fajar Nugroho', 'Fajar123!', 'Edukator', 'pegawai'],
            ['msuryani', 'Melati Suryani', 'Melati123!', 'Edukator', 'pegawai'],
            ['pramadhani', 'Putri Ramadhani', 'Putri123!', 'Humas', 'pegawai'],
        ];

        $pegawai_id_counter = 1;

        foreach ($data as $item) {
            // 1. Insert ke tabel users
            $userId = DB::table('users')->insertGetId([
                'username'     => $item[0],
                'nama_lengkap' => $item[1],
                'email'        => $item[0] . '@museum.go.id',
                'role'         => $item[4],
                'password'     => Hash::make($item[2]), // Enkripsi password untuk Laravel
                'status'       => 'Aktif'
            ]);

            // 2. Insert ke tabel pegawai
            DB::table('pegawai')->insert([
                'pegawai_id'   => $pegawai_id_counter++,
                'user_id'      => $userId,
                'nip_nik'      => '1980' . rand(100000, 999999), // NIP Dummy
                'pegawai_nama' => $item[1],
                'unit_kerja'   => 'Museum Geologi',
                'jabatan'      => $item[3],
                'role'         => $item[4],
                'status_aktif' => 'Aktif'
            ]);
        }
    }
}