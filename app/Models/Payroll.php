<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = ['periode', 'tanggal_mulai', 'tanggal_selesai', 'status', 'dibuat_oleh', 'difinalkan_oleh', 'difinalkan_pada'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
            'difinalkan_pada' => 'datetime',
        ];
    }

    public function detail(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penutup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difinalkan_oleh');
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    /** Ada tanggal yang masuk periode payroll final? Data absensi/lembur/izin di periode itu dikunci. */
    public static function terkunci(string ...$tanggal): bool
    {
        $tanggal = array_filter($tanggal);

        return $tanggal && self::where('status', 'final')
            ->where(fn ($q) => collect($tanggal)->each(fn ($t) => $q->orWhere(fn ($w) => $w
                ->whereDate('tanggal_mulai', '<=', $t)->whereDate('tanggal_selesai', '>=', $t))))
            ->exists();
    }

    public static function tolakJikaTerkunci(string ...$tanggal): void
    {
        abort_if(self::terkunci(...$tanggal), 422, 'Tanggal ini masuk periode payroll yang sudah final, data tidak bisa diubah.');
    }
}
