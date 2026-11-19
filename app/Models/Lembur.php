<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembur extends Model
{
    public const STATUS = ['pending', 'disetujui', 'ditolak'];

    protected $table = 'lembur';

    protected $fillable = ['user_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'keterangan', 'status', 'disetujui_oleh'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal' => 'date:Y-m-d',
            'disetujui_oleh' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
