<?php

namespace App\Http\Controllers;

use App\Http\Requests\CabangRequest;
use App\Models\Cabang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CabangController extends Controller
{
    public function index(): View
    {
        return view('pages.cabang.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Cabang::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('alamat', 'like', "%{$search}%")))
            ->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(CabangRequest $request): JsonResponse
    {
        Cabang::create($request->validated());

        return response()->json(['message' => 'Cabang berhasil ditambahkan.']);
    }

    public function update(CabangRequest $request, Cabang $cabang): JsonResponse
    {
        $cabang->update($request->validated());

        return response()->json(['message' => 'Cabang berhasil diperbarui.']);
    }

    public function destroy(Cabang $cabang): JsonResponse
    {
        if ($cabang->pegawai()->exists()) {
            return response()->json(['message' => 'Cabang tidak bisa dihapus karena masih dipakai pegawai.'], 422);
        }

        $cabang->delete();

        return response()->json(['message' => 'Cabang berhasil dihapus.']);
    }
}
