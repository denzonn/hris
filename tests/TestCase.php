<?php

namespace Tests;

use Database\Seeders\OtoritasSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Data otoritas (1-3) wajib ada karena users.otoritas_id adalah foreign key.
    protected $seed = true;

    protected $seeder = OtoritasSeeder::class;
}
