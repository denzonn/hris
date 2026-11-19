<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanAbsensiRequest;
use App\Models\Cabang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Satu pengaturan per cabang: tidak ada tambah/hapus, hanya simpan (upsert) per cabang. */
class PengaturanAbsensiController extends Controller
{
    public function index(): View
    {
        return view('pages.pengaturan_absensi.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Cabang::query()
            ->select('id', 'nama')
            ->with('pengaturanAbsensi')
            ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function update(PengaturanAbsensiRequest $request, Cabang $cabang): JsonResponse
    {
        $data = $request->validated();
        $data['hari_kerja'] = collect($data['hari_kerja'])->map(fn ($h) => (int) $h)->sort()->values()->all();

        $cabang->pengaturanAbsensi()->updateOrCreate([], $data);

        return response()->json(['message' => "Pengaturan absensi {$cabang->nama} berhasil disimpan."]);
    }
}
