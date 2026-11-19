<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanAbsensi extends Model
{
    protected $table = 'pengaturan_absensi';

    protected $fillable = ['cabang_id', 'jam_masuk', 'jam_pulang', 'toleransi_telat_menit', 'hari_kerja', 'wajib_foto', 'wajib_lokasi'];

    protected function casts(): array
    {
        return [
            'cabang_id' => 'integer',
            'toleransi_telat_menit' => 'integer',
            'hari_kerja' => 'array',
            'wajib_foto' => 'boolean',
            'wajib_lokasi' => 'boolean',
        ];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }
}
