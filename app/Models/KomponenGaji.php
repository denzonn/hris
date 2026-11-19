<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KomponenGaji extends Model
{
    public const TIPE = ['tunjangan', 'potongan'];

    public const SIFAT = ['tetap', 'per_hari_hadir'];

    protected $table = 'komponen_gaji';

    protected $fillable = ['nama', 'tipe', 'sifat'];

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'komponen_gaji_user')->withPivot('nominal')->withTimestamps();
    }
}
