<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = ['nama', 'divisi_id', 'tunjangan_jabatan'];

    protected function casts(): array
    {
        return [
            'divisi_id' => 'integer',
            'tunjangan_jabatan' => 'decimal:2',
        ];
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }

    public function jobdesk(): HasMany
    {
        return $this->hasMany(Jobdesk::class);
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
