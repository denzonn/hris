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

    /** Jarak (meter) dari titik cabang ke koordinat, rumus haversine. */
    public function jarakKe(float $lat, float $lng): float
    {
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
