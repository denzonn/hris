<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    public const STATUS = ['hadir', 'telat', 'izin', 'sakit', 'cuti', 'alpha', 'libur'];

    protected $table = 'absensi';

    protected $fillable = [
        'user_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'foto_masuk', 'foto_pulang',
        'lat_masuk', 'lng_masuk', 'lat_pulang', 'lng_pulang', 'status', 'menit_telat', 'keterangan', 'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal' => 'date:Y-m-d',
            'lat_masuk' => 'decimal:7',
            'lng_masuk' => 'decimal:7',
            'lat_pulang' => 'decimal:7',
            'lng_pulang' => 'decimal:7',
            'menit_telat' => 'integer',
            'diubah_oleh' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
