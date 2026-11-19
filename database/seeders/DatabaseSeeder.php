<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OtoritasSeeder::class);

        User::updateOrCreate(['email' => 'admin@hris.test'], [
            'name' => 'Super Admin',
            'password' => 'password',
            'otoritas_id' => 1,
            'email_verified_at' => now(),
        ]);
    }
}
