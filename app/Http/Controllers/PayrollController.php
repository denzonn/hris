<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollRequest;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PengaturanPayroll;
use App\Services\HitungPayroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Draft → bisa generate ulang/hapus. Final → terkunci (termasuk absensi/lembur/izin di rentangnya). */
class PayrollController extends Controller
{
    public function index(): View
    {
        return view('pages.payroll.index');
    }

    public function data(Request $request): JsonResponse
    {
        $data = Payroll::query()
            ->withCount('detail')
            ->withSum('detail as total_gaji', 'gaji_bersih')
            ->when($request->integer('tahun'), fn ($q, $tahun) => $q->where('periode', 'like', "{$tahun}-%"))
            ->orderByDesc('periode')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        [$mulai, $selesai] = PengaturanPayroll::aktif()->rentang($request->validated('periode'));

        $jumlah = DB::transaction(function () use ($request, $mulai, $selesai) {
            $payroll = Payroll::create([
                'periode' => $request->validated('periode'),
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'dibuat_oleh' => $request->user()->id,
            ]);

            return HitungPayroll::generate($payroll);
        });

        return response()->json(['message' => "Payroll berhasil dibuat untuk {$jumlah} pegawai."]);
    }

    public function show(Payroll $payroll): View
    {
        return view('pages.payroll.show', [
            'payroll' => $payroll->loadSum('detail as total_gaji', 'gaji_bersih')->load('penutup:id,name'),
        ]);
    }

    public function detail(Request $request, Payroll $payroll): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = $payroll->detail()
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('nama', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")))
            ->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function generate(Payroll $payroll): JsonResponse
    {
        $jumlah = HitungPayroll::generate($payroll);

        return response()->json(['message' => "Payroll berhasil di-generate ulang untuk {$jumlah} pegawai."]);
    }

    public function final(Request $request, Payroll $payroll): JsonResponse
    {
        abort_if($payroll->isFinal(), 422, 'Payroll ini sudah final.');
        abort_unless($payroll->detail()->exists(), 422, 'Payroll belum berisi data pegawai.');

        $payroll->update(['status' => 'final', 'difinalkan_oleh' => $request->user()->id, 'difinalkan_pada' => now()]);

        return response()->json(['message' => "Payroll {$payroll->periode} sudah final dan dikunci."]);
    }

    public function destroy(Payroll $payroll): JsonResponse
    {
        abort_if($payroll->isFinal(), 422, 'Payroll yang sudah final tidak bisa dihapus.');

        $payroll->delete();

        return response()->json(['message' => 'Payroll draft berhasil dihapus.']);
    }

    public function slip(Payroll $payroll, PayrollDetail $detail): View
    {
        abort_unless($detail->payroll_id === $payroll->id, 404);

        return view('pages.payroll.slip', ['payroll' => $payroll, 'd' => $detail]);
    }
}
