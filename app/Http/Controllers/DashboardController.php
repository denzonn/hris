<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Jobdesk;
use App\Models\Lembur;
use App\Models\PayrollDetail;
use App\Models\PengajuanIzin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return $request->user()->otoritas_id === 3 ? $this->karyawan($request->user()) : $this->admin($request->user());
    }

    private function admin(User $user): View
    {
        $pegawai = User::where('aktif', true)->where('otoritas_id', '!=', 1);
        $hariIni = Absensi::whereDate('tanggal', today())->whereIn('user_id', (clone $pegawai)->select('id'))
            ->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $totalAktif = (clone $pegawai)->count();

        return view('pages.dashboard.admin', [
            'totalAktif' => $totalAktif,
            'hariIni' => [
                'hadir' => (int) $hariIni->get('hadir', 0),
                'telat' => (int) $hariIni->get('telat', 0),
                'izin' => (int) ($hariIni->get('izin', 0) + $hariIni->get('sakit', 0) + $hariIni->get('cuti', 0)),
                'alpha' => (int) $hariIni->get('alpha', 0),
                'belum' => max(0, $totalAktif - $hariIni->sum()),
            ],
            'izinPending' => PengajuanIzin::where('status', 'pending')->count(),
            'lemburPending' => Lembur::where('status', 'pending')->count(),
            // termasuk yang sudah lewat tapi pegawainya masih aktif
            'kontrak' => (clone $pegawai)->with('jabatan:id,nama')
                ->where('status_kerja', '!=', 'tetap')->whereNotNull('tanggal_akhir_kontrak')
                ->whereDate('tanggal_akhir_kontrak', '<=', today()->addDays(30))
                ->orderBy('tanggal_akhir_kontrak')->limit(10)
                ->get(['id', 'name', 'nik', 'jabatan_id', 'status_kerja', 'tanggal_akhir_kontrak']),
            'bisaAbsen' => (bool) $user->cabang_id,
            'jumlah' => [
                'divisi' => Divisi::count(),
                'jabatan' => Jabatan::count(),
                'jobdesk' => Jobdesk::count(),
            ],
        ]);
    }

    private function karyawan(User $user): View
    {
        $bulanIni = $user->absensi()->whereBetween('tanggal', [today()->startOfMonth()->toDateString(), today()->toDateString()])
            ->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return view('pages.dashboard.karyawan', [
            'pengaturan' => $user->cabang?->pengaturanAbsensi,
            'hariIni' => $user->absensi()->whereDate('tanggal', today())->first(),
            'bulanIni' => $bulanIni,
            'sisaCuti' => $user->sisaCuti(),
            'pending' => PengajuanIzin::where('user_id', $user->id)->where('status', 'pending')->count()
                + Lembur::where('user_id', $user->id)->where('status', 'pending')->count(),
            'slipTerakhir' => PayrollDetail::with('payroll')->where('user_id', $user->id)
                ->whereHas('payroll', fn ($q) => $q->where('status', 'final'))
                ->latest('id')->first(),
        ]);
    }
}
