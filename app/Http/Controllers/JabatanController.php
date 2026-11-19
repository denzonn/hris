<?php

namespace App\Http\Controllers;

use App\Http\Requests\JabatanRequest;
use App\Models\Divisi;
use App\Models\Jabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JabatanController extends Controller
{
    public function index(): View
    {
        return view('pages.jabatan.index', ['divisi' => Divisi::orderBy('nama')->get(['id', 'nama'])]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Jabatan::query()
            ->with('divisi:id,nama')
            ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
            ->when($request->integer('divisi_id'), fn ($q, $id) => $q->where('divisi_id', $id))
            ->orderBy('nama')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(JabatanRequest $request): JsonResponse
    {
        Jabatan::create($request->validated());

        return response()->json(['message' => 'Jabatan berhasil ditambahkan.']);
    }

    public function update(JabatanRequest $request, Jabatan $jabatan): JsonResponse
    {
        $jabatan->update($request->validated());

        return response()->json(['message' => 'Jabatan berhasil diperbarui.']);
    }

    public function destroy(Jabatan $jabatan): JsonResponse
    {
        if ($jabatan->pegawai()->exists()) {
            return response()->json(['message' => 'Jabatan tidak bisa dihapus karena masih dipakai pegawai.'], 422);
        }

        $jabatan->delete();

        return response()->json(['message' => 'Jabatan berhasil dihapus.']);
    }
}
