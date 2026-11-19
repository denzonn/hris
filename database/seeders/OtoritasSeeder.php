<?php

namespace Database\Seeders;

use App\Models\Otoritas;
use Illuminate\Database\Seeder;

class OtoritasSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1 => 'Super Admin', 2 => 'HR / Admin Cabang', 3 => 'Karyawan'] as $id => $nama) {
            Otoritas::updateOrCreate(['id' => $id], ['nama' => $nama]);
        }
    }
}
