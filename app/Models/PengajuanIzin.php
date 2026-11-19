<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanIzin extends Model
{
    public const JENIS = ['izin', 'sakit', 'cuti'];

    protected $table = 'pengajuan_izin';

    protected $fillable = ['user_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'alasan', 'lampiran', 'status', 'disetujui_oleh'];

    protected $hidden = ['lampiran']; // diunduh lewat route yang dicek aksesnya

    protected $appends = ['ada_lampiran'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_mulai' => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
            'disetujui_oleh' => 'integer',
        ];
    }

    public function getAdaLampiranAttribute(): bool
    {
        return (bool) $this->lampiran;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * Tanggal yang terkena pengajuan: hari kerja cabang pegawai (tanpa hari libur).
     *
     * @return list<string>
     */
    public function tanggalEfektif(): array
    {
        $pengaturan = $this->user->cabang?->pengaturanAbsensi;

        // ponytail: cabang tanpa pengaturan → semua tanggal dihitung
        return $pengaturan
            ? $pengaturan->tanggalKerja($this->tanggal_mulai, $this->tanggal_selesai)
            : collect(CarbonPeriod::create($this->tanggal_mulai, $this->tanggal_selesai))->map->toDateString()->all();
    }
}
