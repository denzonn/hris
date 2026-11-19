<?php

namespace App\Http\Controllers;

use App\Http\Requests\DivisiRequest;
use App\Models\Divisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisiController extends Controller
{
    public function index(): View
    {
        return view('pages.divisi.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Divisi::query()
            ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(DivisiRequest $request): JsonResponse
    {
        Divisi::create($request->validated());

        return response()->json(['message' => 'Divisi berhasil ditambahkan.']);
    }

    public function update(DivisiRequest $request, Divisi $divisi): JsonResponse
    {
        $divisi->update($request->validated());

        return response()->json(['message' => 'Divisi berhasil diperbarui.']);
    }

    public function destroy(Divisi $divisi): JsonResponse
    {
        if ($divisi->jabatan()->exists()) {
            return response()->json(['message' => 'Divisi tidak bisa dihapus karena masih memiliki jabatan.'], 422);
        }

        if ($divisi->pegawai()->exists()) {
            return response()->json(['message' => 'Divisi tidak bisa dihapus karena masih dipakai pegawai.'], 422);
        }

        $divisi->delete();

        return response()->json(['message' => 'Divisi berhasil dihapus.']);
    }
}
