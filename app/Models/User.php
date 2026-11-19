<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
