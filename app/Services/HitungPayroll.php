<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PengaturanPayroll;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rumus payroll (semua angka integer rupiah):
 * gaji_bersih = gaji_pokok + tunjangan_jabatan + Σ tunjangan + upah_lembur
 *             − potongan_telat − potongan_alpha − Σ potongan
 */
class HitungPayroll
{
    /**
     * Upah lembur 1 kali lembur di hari kerja biasa (Kepmenaker 102/2004):
     * upah sejam = 1/173 × upah sebulan; jam ke-1 × 1,5, jam berikutnya × 2 (dihitung proporsional per menit).
     * ponytail: lembur di hari libur (×2/×3/×4) belum dibedakan.
     */
    public static function upahLembur(int $upahSebulan, int $menit): int
    {
        $jamPertama = min($menit, 60);
        $berikutnya = max($menit - 60, 0);

        // × 2 di pembilang & penyebut supaya 1,5 tetap bilangan bulat
        return (int) round($upahSebulan * (3 * $jamPertama + 4 * $berikutnya) / (173 * 60 * 2));
    }

    /**
     * @param  array{
     *     gaji_pokok: int, tunjangan_jabatan: int, hari_hadir: int, menit_telat: int, jumlah_alpha: int,
     *     komponen: list<array{nama: string, tipe: string, sifat: string, nominal: int}>,
     *     lembur_menit: list<int>, potongan_per_menit_telat: int, potongan_per_alpha: int,
     * }  $d
     */
    public static function hitung(array $d): array
    {
        $rincian = array_map(fn ($k) => $k + [
            'jumlah' => $k['sifat'] === 'per_hari_hadir' ? $k['nominal'] * $d['hari_hadir'] : $k['nominal'],
        ], $d['komponen']);

        $jumlah = fn (callable $filter) => array_sum(array_column(array_filter($rincian, $filter), 'jumlah'));
        $totalTunjangan = $jumlah(fn ($k) => $k['tipe'] === 'tunjangan');
        $totalPotongan = $jumlah(fn ($k) => $k['tipe'] === 'potongan');
        $tunjanganTetap = $jumlah(fn ($k) => $k['tipe'] === 'tunjangan' && $k['sifat'] === 'tetap');

        $upahSebulan = $d['gaji_pokok'] + $d['tunjangan_jabatan'] + $tunjanganTetap;
        $upahLembur = array_sum(array_map(fn ($m) => self::upahLembur($upahSebulan, $m), $d['lembur_menit']));
        $potonganTelat = $d['menit_telat'] * $d['potongan_per_menit_telat'];
        $potonganAlpha = $d['jumlah_alpha'] * $d['potongan_per_alpha'];

        return [
            'gaji_pokok' => $d['gaji_pokok'],
            'tunjangan_jabatan' => $d['tunjangan_jabatan'],
            'total_tunjangan' => $totalTunjangan,
            'upah_lembur' => $upahLembur,
            'potongan_telat' => $potonganTelat,
            'potongan_alpha' => $potonganAlpha,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $d['gaji_pokok'] + $d['tunjangan_jabatan'] + $totalTunjangan + $upahLembur
                - $potonganTelat - $potonganAlpha - $totalPotongan,
            'rincian' => $rincian,
        ];
    }

    /** Hitung ulang seluruh detail payroll (draft) dari data absensi, lembur & master saat ini. */
    public static function generate(Payroll $payroll): int
    {
        abort_if($payroll->isFinal(), 422, 'Payroll yang sudah final tidak bisa di-generate ulang.');

        $pengaturan = PengaturanPayroll::aktif();
        $mulai = $payroll->tanggal_mulai->toDateString();
        $selesai = $payroll->tanggal_selesai->toDateString();
        $periode = fn ($q) => $q->whereBetween('tanggal', [$mulai, $selesai]);

        $pegawai = User::query()
            ->with(['jabatan', 'cabang.pengaturanAbsensi', 'komponenGaji', 'absensi' => $periode,
                'lembur' => fn ($q) => $periode($q)->where('status', 'disetujui')])
            ->where('aktif', true)->where('otoritas_id', '!=', 1)
            ->where(fn ($q) => $q->whereNull('tanggal_masuk')->orWhereDate('tanggal_masuk', '<=', $selesai))
            ->get();

        DB::transaction(function () use ($payroll, $pegawai, $pengaturan) {
            $payroll->detail()->delete();

            foreach ($pegawai as $u) {
                $jadwal = $u->cabang?->pengaturanAbsensi;
                $lemburMenit = $u->lembur->map(fn ($l) => intdiv(strtotime($l->jam_selesai) - strtotime($l->jam_mulai), 60))->all();
                $kehadiran = [
                    'hari_hadir' => $u->absensi->whereIn('status', ['hadir', 'telat'])->count(),
                    'menit_telat' => (int) $u->absensi->sum('menit_telat'),
                    'jumlah_alpha' => $u->jumlahAlpha($u->absensi, $payroll->tanggal_mulai, $payroll->tanggal_selesai),
                ];

                $hasil = self::hitung($kehadiran + [
                    'gaji_pokok' => (int) $u->gaji_pokok,
                    'tunjangan_jabatan' => (int) $u->jabatan?->tunjangan_jabatan,
                    'komponen' => $u->komponenGaji->map(fn ($k) => [
                        'nama' => $k->nama, 'tipe' => $k->tipe, 'sifat' => $k->sifat, 'nominal' => (int) $k->pivot->nominal,
                    ])->all(),
                    'lembur_menit' => $lemburMenit,
                    'potongan_per_menit_telat' => (int) $pengaturan->potongan_per_menit_telat,
                    'potongan_per_alpha' => (int) $pengaturan->potongan_per_alpha,
                ]);

                PayrollDetail::create($hasil + $kehadiran + [
                    'payroll_id' => $payroll->id,
                    'user_id' => $u->id,
                    'nama' => $u->name,
                    'nik' => $u->nik,
                    'jabatan' => $u->jabatan?->nama,
                    'cabang' => $u->cabang?->nama,
                    'bank' => $u->bank,
                    'no_rekening' => $u->no_rekening,
                    'hari_kerja' => $jadwal ? count($jadwal->tanggalKerja($payroll->tanggal_mulai, $payroll->tanggal_selesai)) : 0,
                    'menit_lembur' => array_sum($lemburMenit),
                ]);
            }
        });

        return $pegawai->count();
    }
}
