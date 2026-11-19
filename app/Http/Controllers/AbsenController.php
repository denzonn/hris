<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbsenRequest;
use App\Models\Absensi;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Absen mandiri (masuk/pulang). Waktu, jarak & telat selalu dihitung di server. */
class AbsenController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('pages.absen.index', [
            'pengaturan' => $user->cabang?->pengaturanAbsensi,
            'hariIni' => $user->absensi()->whereDate('tanggal', today())->first(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $bulan = $request->string('bulan')->value(); // Y-m

        $data = $request->user()->absensi()
            ->when(preg_match('/^\d{4}-\d{2}$/', $bulan), fn ($q) => $q
                ->whereYear('tanggal', substr($bulan, 0, 4))->whereMonth('tanggal', substr($bulan, 5, 2)))
            ->orderByDesc('tanggal')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function masuk(AbsenRequest $request): JsonResponse
    {
        $user = $request->user();
        $pengaturan = $this->pengaturan($user);
        $now = now();

        abort_unless($pengaturan->isHariKerja($now), 422, 'Hari ini bukan hari kerja atau hari libur.');

        $hariIni = $user->absensi()->whereDate('tanggal', $now->toDateString())->first();
        if ($hariIni) {
            abort(422, $hariIni->jam_masuk ? 'Anda sudah absen masuk hari ini.' : "Hari ini Anda tercatat {$hariIni->status}.");
        }

        $this->cekLokasi($request, $user, $pengaturan);

        $jam = $now->format('H:i:s');
        $menitTelat = $pengaturan->menitTelat($jam);

        try {
            Absensi::create([
                'user_id' => $user->id,
                'tanggal' => $now->toDateString(),
                'jam_masuk' => $jam,
                'foto_masuk' => $request->file('foto')?->store('absensi/'.$now->format('Y-m'), 'public'),
                'lat_masuk' => $request->input('latitude'),
                'lng_masuk' => $request->input('longitude'),
                'status' => $menitTelat ? 'telat' : 'hadir',
                'menit_telat' => $menitTelat,
            ]);
        } catch (UniqueConstraintViolationException) {
            abort(422, 'Anda sudah absen masuk hari ini.'); // double submit
        }

        return response()->json(['message' => $menitTelat
            ? "Absen masuk {$now->format('H:i')} tercatat, telat {$menitTelat} menit."
            : "Absen masuk {$now->format('H:i')} tercatat."]);
    }

    public function pulang(AbsenRequest $request): JsonResponse
    {
        $user = $request->user();
        $pengaturan = $this->pengaturan($user);
        $now = now();

        $hariIni = $user->absensi()->whereDate('tanggal', $now->toDateString())->first();
        abort_unless($hariIni?->jam_masuk, 422, 'Anda belum absen masuk hari ini.');
        abort_if((bool) $hariIni->jam_pulang, 422, 'Anda sudah absen pulang hari ini.');

        $this->cekLokasi($request, $user, $pengaturan);

        $hariIni->update([
            'jam_pulang' => $now->format('H:i:s'),
            'foto_pulang' => $request->file('foto')?->store('absensi/'.$now->format('Y-m'), 'public'),
            'lat_pulang' => $request->input('latitude'),
            'lng_pulang' => $request->input('longitude'),
        ]);

        return response()->json(['message' => "Absen pulang {$now->format('H:i')} tercatat."]);
    }

    private function pengaturan(User $user): PengaturanAbsensi
    {
        abort_unless($user->cabang_id, 422, 'Akun Anda belum terdaftar di cabang mana pun.');

        return $user->cabang->pengaturanAbsensi
            ?? abort(422, 'Pengaturan absensi cabang Anda belum dibuat. Hubungi HR.');
    }

    private function cekLokasi(Request $request, User $user, PengaturanAbsensi $pengaturan): void
    {
        if (! $pengaturan->wajib_lokasi) {
            return;
        }

        $cabang = $user->cabang;
        abort_if($cabang->latitude === null || $cabang->longitude === null, 422, 'Titik lokasi cabang belum diatur. Hubungi HR.');

        $jarak = $cabang->jarakKe((float) $request->input('latitude'), (float) $request->input('longitude'));
        abort_if($jarak > $cabang->radius_meter, 422,
            sprintf('Anda di luar area cabang (jarak %d m, maksimal %d m).', round($jarak), $cabang->radius_meter));
    }
}
