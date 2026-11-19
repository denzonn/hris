<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function pegawai(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pengaturanAbsensi(): HasOne
    {
        return $this->hasOne(PengaturanAbsensi::class);
    }
}
