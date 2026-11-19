<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otoritas_id',
        'nik',
        'cabang_id',
        'divisi_id',
        'jabatan_id',
        'no_hp',
        'alamat',
        'tanggal_masuk',
        'status_kerja',
        'tanggal_akhir_kontrak',
        'gaji_pokok',
        'bank',
        'no_rekening',
        'npwp',
        'status_ptkp',
        'jatah_cuti',
        'foto',
        'aktif',
    ];

    /** Sama dengan default kolom DB, supaya model yang baru dibuat langsung punya nilainya. */
    protected $attributes = ['jatah_cuti' => 12];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otoritas_id' => 'integer',
            'cabang_id' => 'integer',
            'divisi_id' => 'integer',
            'jabatan_id' => 'integer',
            'tanggal_masuk' => 'date:Y-m-d',
            'tanggal_akhir_kontrak' => 'date:Y-m-d',
            'gaji_pokok' => 'decimal:2',
            'jatah_cuti' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function otoritas(): BelongsTo
    {
        return $this->belongsTo(Otoritas::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function lembur(): HasMany
    {
        return $this->hasMany(Lembur::class);
    }

    public function komponenGaji(): BelongsToMany
    {
        return $this->belongsToMany(KomponenGaji::class, 'komponen_gaji_user')->withPivot('nominal')->withTimestamps();
    }

    /** Sisa cuti = jatah − hari cuti yang sudah tercatat di absensi tahun itu (dihitung, tidak disimpan). */
    public function sisaCuti(?int $tahun = null): int
    {
        $terpakai = $this->absensi()->where('status', 'cuti')->whereYear('tanggal', $tahun ?? now()->year)->count();

        return max(0, $this->jatah_cuti - $terpakai);
    }
}
