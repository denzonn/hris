<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanPayrollRequest;
use App\Models\PengaturanPayroll;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/** Satu baris pengaturan global: tampil & simpan saja. */
class PengaturanPayrollController extends Controller
{
    public function index(): View
    {
        return view('pages.pengaturan_payroll.index', ['pengaturan' => PengaturanPayroll::aktif()]);
    }

    public function update(PengaturanPayrollRequest $request): JsonResponse
    {
        PengaturanPayroll::aktif()->update($request->validated());

        return response()->json(['message' => 'Pengaturan payroll berhasil disimpan.']);
    }
}
