<?php

namespace App\Http\Controllers;

use App\Http\Requests\LowonganRequest;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LowonganController extends Controller
{
    public function index(): View
    {
        return view('pages.lowongan.index', [
            'divisi' => Divisi::orderBy('nama')->get(['id', 'nama']),
            'jabatan' => Jabatan::orderBy('nama')->get(['id', 'nama', 'divisi_id']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Lowongan::query()
            ->with(['divisi:id,nama', 'jabatan:id,nama'])
            ->withCount('pelamar')
            ->when($search, fn ($q) => $q->where('judul', 'like', "%{$search}%"))
            ->when($request->filled('aktif'), fn ($q) => $q->where('aktif', $request->boolean('aktif')))
            ->latest()
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(LowonganRequest $request): JsonResponse
    {
        Lowongan::create($request->validated());

        return response()->json(['message' => 'Lowongan berhasil ditambahkan.']);
    }

    public function update(LowonganRequest $request, Lowongan $lowongan): JsonResponse
    {
        $lowongan->update($request->validated());

        return response()->json(['message' => 'Lowongan berhasil diperbarui.']);
    }

    public function destroy(Lowongan $lowongan): JsonResponse
    {
        if ($lowongan->pelamar()->exists()) {
            return response()->json(['message' => 'Lowongan yang sudah punya pelamar tidak bisa dihapus. Nonaktifkan saja.'], 422);
        }

        $lowongan->delete();

        return response()->json(['message' => 'Lowongan berhasil dihapus.']);
    }
}
