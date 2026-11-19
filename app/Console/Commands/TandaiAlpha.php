<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/** Pegawai aktif yang tidak punya baris absensi di hari kerja → alpha. Baris yang sudah ada (izin/cuti/hadir) tidak disentuh. */
class TandaiAlpha extends Command
{
    protected $signature = 'absensi:alpha {tanggal? : Y-m-d, default hari ini}';

    protected $description = 'Tandai alpha untuk pegawai yang tidak absen di hari kerja';

    public function handle(): int
    {
        $tanggal = Carbon::parse($this->argument('tanggal') ?? today());
        $hariKerja = []; // cache per cabang
        $jumlah = 0;

        User::query()
            ->with('cabang.pengaturanAbsensi')
            ->where('aktif', true)
            ->whereNotNull('cabang_id')
            ->where(fn ($q) => $q->whereNull('tanggal_masuk')->orWhereDate('tanggal_masuk', '<=', $tanggal))
            ->whereDoesntHave('absensi', fn ($q) => $q->whereDate('tanggal', $tanggal))
            ->each(function (User $user) use ($tanggal, &$hariKerja, &$jumlah) {
                $pengaturan = $user->cabang->pengaturanAbsensi;
                if (! ($hariKerja[$user->cabang_id] ??= (bool) $pengaturan?->isHariKerja($tanggal))) {
                    return;
                }

                Absensi::firstOrCreate(
                    ['user_id' => $user->id, 'tanggal' => $tanggal->toDateString()],
                    ['status' => 'alpha', 'keterangan' => 'Tidak absen (otomatis)'],
                );
                $jumlah++;
            });

        $this->info("{$jumlah} pegawai ditandai alpha pada {$tanggal->toDateString()}.");

        return self::SUCCESS;
    }
}
