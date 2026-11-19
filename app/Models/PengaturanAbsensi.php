<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanAbsensi extends Model
{
    protected $table = 'pengaturan_absensi';

    protected $fillable = ['cabang_id', 'jam_masuk', 'jam_pulang', 'toleransi_telat_menit', 'hari_kerja', 'wajib_foto', 'wajib_lokasi'];

    protected function casts(): array
    {
        return [
            'cabang_id' => 'integer',
            'toleransi_telat_menit' => 'integer',
            'hari_kerja' => 'array',
            'wajib_foto' => 'boolean',
            'wajib_lokasi' => 'boolean',
        ];
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    /**
     * Menit telat dihitung dari jam_masuk jadwal (bukan dari batas toleransi).
     * Toleransi hanya batas: telat ≤ toleransi dianggap 0.
     * Contoh: jadwal 08:00, toleransi 15 → absen 08:15 = 0, absen 08:16 = 16.
     */
    public function menitTelat(string $jamAbsen): int
    {
        $menit = intdiv(strtotime($jamAbsen) - strtotime($this->jam_masuk), 60);

        return $menit > $this->toleransi_telat_menit ? $menit : 0;
    }

    /** Hari kerja = hari ada di hari_kerja & bukan hari libur. */
    public function isHariKerja(CarbonInterface $tanggal): bool
    {
        return $this->tanggalKerja($tanggal, $tanggal) !== [];
    }

    /** @return list<string> daftar tanggal (Y-m-d) hari kerja dalam rentang, inklusif. */
    public function tanggalKerja(CarbonInterface $mulai, CarbonInterface $selesai): array
    {
        $libur = HariLibur::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->pluck('tanggal')->map(fn ($t) => $t->toDateString())->all();

        return collect(CarbonPeriod::create($mulai->toDateString(), $selesai->toDateString()))
            ->filter(fn ($t) => in_array($t->dayOfWeekIso, $this->hari_kerja, true) && ! in_array($t->toDateString(), $libur, true))
            ->map(fn ($t) => $t->toDateString())
            ->values()->all();
    }
}
