<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lowongan extends Model
{
    protected $table = 'lowongan';

    protected $fillable = ['judul', 'divisi_id', 'jabatan_id', 'deskripsi', 'kualifikasi', 'tanggal_tutup', 'aktif'];

    protected function casts(): array
    {
        return [
            'divisi_id' => 'integer',
            'jabatan_id' => 'integer',
            'tanggal_tutup' => 'date:Y-m-d',
            'aktif' => 'boolean',
        ];
    }

    /** Tampil di halaman karir: aktif & belum lewat tanggal tutup. */
    public function scopeDibuka(Builder $q): Builder
    {
        return $q->where('aktif', true)
            ->where(fn ($w) => $w->whereNull('tanggal_tutup')->orWhereDate('tanggal_tutup', '>=', today()));
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function pelamar(): HasMany
    {
        return $this->hasMany(Pelamar::class);
    }
}
