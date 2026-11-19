<?php

namespace App\Http\Controllers;

use App\Http\Requests\KomponenGajiRequest;
use App\Models\KomponenGaji;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KomponenGajiController extends Controller
{
    public function index(): View
    {
        return view('pages.komponen_gaji.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = KomponenGaji::query()
            ->withCount('pegawai')
            ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
            ->when(in_array($request->input('tipe'), KomponenGaji::TIPE, true), fn ($q) => $q->where('tipe', $request->input('tipe')))
            ->orderBy('tipe')->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(KomponenGajiRequest $request): JsonResponse
    {
        KomponenGaji::create($request->validated());

        return response()->json(['message' => 'Komponen gaji berhasil ditambahkan.']);
    }

    public function update(KomponenGajiRequest $request, KomponenGaji $komponenGaji): JsonResponse
    {
        $komponenGaji->update($request->validated());

        return response()->json(['message' => 'Komponen gaji berhasil diperbarui.']);
    }

    public function destroy(KomponenGaji $komponenGaji): JsonResponse
    {
        if ($komponenGaji->pegawai()->exists()) {
            return response()->json(['message' => 'Komponen tidak bisa dihapus karena masih dipakai pegawai.'], 422);
        }

        $komponenGaji->delete();

        return response()->json(['message' => 'Komponen gaji berhasil dihapus.']);
    }
}
