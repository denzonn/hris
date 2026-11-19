<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Rekap absensi per karyawan dalam periode. Export CSV tanpa package. */
class RekapAbsensiController extends Controller
{
    private const KOLOM = [
        'nik' => 'NIK', 'name' => 'Nama', 'cabang' => 'Cabang', 'divisi' => 'Divisi', 'hari_kerja' => 'Hari Kerja',
        'hadir' => 'Hadir', 'telat' => 'Telat (x)', 'menit_telat' => 'Telat (menit)', 'izin' => 'Izin', 'sakit' => 'Sakit',
        'cuti' => 'Cuti', 'alpha' => 'Alpha', 'lembur_menit' => 'Lembur (menit)',
    ];

    public function index(): View
    {
        return view('pages.rekap_absensi.index', [
            'cabang' => Cabang::orderBy('nama')->get(['id', 'nama']),
            'divisi' => Divisi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$mulai, $selesai] = $this->periode($request);

        $data = $this->query($request, $mulai, $selesai)->paginate(min($request->integer('per_page', 10), 100));
        $data->setCollection($this->baris($data->getCollection(), $mulai, $selesai));

        return response()->json($data);
    }

    public function export(Request $request): StreamedResponse
    {
        [$mulai, $selesai] = $this->periode($request);
        $nama = "rekap-absensi-{$mulai->toDateString()}-sd-{$selesai->toDateString()}.csv";

        return response()->streamDownload(function () use ($request, $mulai, $selesai) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, array_values(self::KOLOM), ';'); // ';' = pemisah default Excel locale Indonesia

            $this->query($request, $mulai, $selesai)->chunk(200, function ($users) use ($out, $mulai, $selesai) {
                foreach ($this->baris($users, $mulai, $selesai) as $row) {
                    fputcsv($out, array_map(fn ($k) => $row[$k], array_keys(self::KOLOM)), ';');
                }
            });

            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{Carbon, Carbon} */
    private function periode(Request $request): array
    {
        $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
        ]);

        $mulai = $request->date('tanggal_mulai') ?? now()->startOfMonth();
        $selesai = $request->date('tanggal_selesai') ?? now()->endOfMonth();
        abort_if($mulai->diffInDays($selesai) > 92, 422, 'Periode rekap maksimal 3 bulan.');

        return [$mulai->startOfDay(), $selesai->startOfDay()];
    }

    private function query(Request $request, Carbon $mulai, Carbon $selesai): Builder
    {
        $search = $request->string('search')->trim()->value();
        $periode = fn ($q) => $q->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()]);
        $status = fn (array $s) => fn ($q) => $periode($q)->whereIn('status', $s);

        return User::query()
            ->with(['cabang.pengaturanAbsensi', 'divisi:id,nama'])
            ->where('aktif', true)->where('otoritas_id', '!=', 1)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")))
            ->when($request->integer('cabang_id'), fn ($q, $id) => $q->where('cabang_id', $id))
            ->when($request->integer('divisi_id'), fn ($q, $id) => $q->where('divisi_id', $id))
            ->withCount([
                'absensi as hadir' => $status(['hadir', 'telat']),
                'absensi as telat' => $status(['telat']),
                'absensi as izin' => $status(['izin']),
                'absensi as sakit' => $status(['sakit']),
                'absensi as cuti' => $status(['cuti']),
            ])
            ->withSum(['absensi as menit_telat' => $periode], 'menit_telat')
            ->with([
                'lembur' => fn ($q) => $periode($q)->where('status', 'disetujui'),
                // untuk alpha: hari kerja tanpa catatan juga dihitung (User::jumlahAlpha)
                'absensi' => fn ($q) => $periode($q)->select('id', 'user_id', 'tanggal', 'status'),
            ])
            ->orderBy('name');
    }

    private function baris(Collection $users, Carbon $mulai, Carbon $selesai): Collection
    {
        $hariKerja = []; // cache per cabang

        return $users->map(function (User $u) use ($mulai, $selesai, &$hariKerja) {
            $pengaturan = $u->cabang?->pengaturanAbsensi;
            $hariKerja[$u->cabang_id] ??= $pengaturan ? count($pengaturan->tanggalKerja($mulai, $selesai)) : 0;

            return [
                'id' => $u->id,
                'nik' => $u->nik,
                'name' => $u->name,
                'cabang' => $u->cabang?->nama,
                'divisi' => $u->divisi?->nama,
                'hari_kerja' => $hariKerja[$u->cabang_id],
                'hadir' => $u->hadir,
                'telat' => $u->telat,
                'menit_telat' => (int) $u->menit_telat,
                'izin' => $u->izin,
                'sakit' => $u->sakit,
                'cuti' => $u->cuti,
                'alpha' => $u->jumlahAlpha($u->absensi, $mulai, $selesai),
                'lembur_menit' => $u->lembur->sum(fn ($l) => intdiv(strtotime($l->jam_selesai) - strtotime($l->jam_mulai), 60)),
            ];
        });
    }
}
