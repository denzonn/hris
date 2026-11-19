<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 'cabang';

    protected $fillable = ['nama', 'alamat', 'latitude', 'longitude', 'radius_meter'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_meter' => 'integer',
        ];
    }
}
