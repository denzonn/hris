<?php

namespace App\Http\Controllers;

use App\Http\Requests\HariLiburRequest;
use App\Models\HariLibur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    public function index(): View
    {
        return view('pages.hari_libur.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = HariLibur::query()
            ->when($search, fn ($q) => $q->where('keterangan', 'like', "%{$search}%"))
            ->when($request->integer('tahun'), fn ($q, $tahun) => $q->whereYear('tanggal', $tahun))
            ->orderBy('tanggal')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(HariLiburRequest $request): JsonResponse
    {
        HariLibur::create($request->validated());

        return response()->json(['message' => 'Hari libur berhasil ditambahkan.']);
    }

    public function update(HariLiburRequest $request, HariLibur $hariLibur): JsonResponse
    {
        $hariLibur->update($request->validated());

        return response()->json(['message' => 'Hari libur berhasil diperbarui.']);
    }

    public function destroy(HariLibur $hariLibur): JsonResponse
    {
        $hariLibur->delete();

        return response()->json(['message' => 'Hari libur berhasil dihapus.']);
    }
}
