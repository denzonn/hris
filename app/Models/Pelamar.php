<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelamar extends Model
{
    public const STATUS = ['baru', 'interview', 'diterima', 'ditolak'];

    protected $table = 'pelamar';

    protected $fillable = ['lowongan_id', 'nama', 'email', 'no_hp', 'pendidikan_terakhir', 'pesan', 'cv', 'status', 'catatan', 'user_id'];

    protected $hidden = ['cv']; // diunduh lewat route yang dicek aksesnya

    protected function casts(): array
    {
        return ['lowongan_id' => 'integer', 'user_id' => 'integer'];
    }

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(Lowongan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
