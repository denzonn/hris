<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** Satu baris global. Ambil lewat PengaturanPayroll::aktif(). */
class PengaturanPayroll extends Model
{
    protected $table = 'pengaturan_payroll';

    protected $fillable = ['tanggal_cutoff', 'potongan_per_menit_telat', 'potongan_per_alpha'];

    protected function casts(): array
    {
        return [
            'tanggal_cutoff' => 'integer',
            'potongan_per_menit_telat' => 'decimal:2',
            'potongan_per_alpha' => 'decimal:2',
        ];
    }

    public static function aktif(): self
    {
        return self::firstOrCreate([]);
    }

    /**
     * Rentang absensi untuk bulan gajian Y-m.
     * Cut-off 25 → periode 2026-11 = 26 Okt – 25 Nov. Tanpa cut-off → 1 – akhir bulan.
     *
     * @return array{Carbon, Carbon}
     */
    public function rentang(string $periode): array
    {
        $bulan = Carbon::createFromFormat('Y-m-d', "{$periode}-01")->startOfDay();

        if (! $this->tanggal_cutoff) {
            return [$bulan->copy(), $bulan->copy()->endOfMonth()->startOfDay()];
        }

        return [
            $bulan->copy()->subMonthNoOverflow()->day($this->tanggal_cutoff + 1),
            $bulan->copy()->day($this->tanggal_cutoff),
        ];
    }
}
