<?php

namespace App\Http\Controllers;

use App\Models\PayrollDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Slip gaji milik sendiri, hanya dari payroll yang sudah final. */
class SlipGajiController extends Controller
{
    public function index(): View
    {
        return view('pages.slip_gaji.index');
    }

    public function data(Request $request): JsonResponse
    {
        $data = PayrollDetail::query()
            ->select('payroll_detail.id', 'payroll_id', 'gaji_bersih', 'hari_hadir', 'hari_kerja')
            ->with('payroll:id,periode,tanggal_mulai,tanggal_selesai')
            ->where('user_id', $request->user()->id)
            ->whereHas('payroll', fn ($q) => $q->where('status', 'final'))
            ->orderByDesc('payroll_id')
            ->paginate(min($request->integer('per_page', 12), 100));

        return response()->json($data);
    }

    public function show(Request $request, PayrollDetail $detail): View
    {
        abort_unless($detail->user_id === $request->user()->id, 403);
        abort_unless($detail->payroll->isFinal(), 404);

        return view('pages.payroll.slip', ['payroll' => $detail->payroll, 'd' => $detail]);
    }
}
