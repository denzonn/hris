<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobdeskRequest;
use App\Models\Jabatan;
use App\Models\Jobdesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobdeskController extends Controller
{
    public function index(): View
    {
        return view('pages.jobdesk.index', [
            'jabatan' => Jabatan::with('divisi:id,nama')->orderBy('nama')->get(['id', 'nama', 'divisi_id']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Jobdesk::query()
            ->with('jabatan:id,nama,divisi_id', 'jabatan.divisi:id,nama')
            ->when($search, fn ($q) => $q->where('deskripsi', 'like', "%{$search}%"))
            ->when($request->integer('jabatan_id'), fn ($q, $id) => $q->where('jabatan_id', $id))
            ->latest('id')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(JobdeskRequest $request): JsonResponse
    {
        Jobdesk::create($request->validated());

        return response()->json(['message' => 'Jobdesk berhasil ditambahkan.']);
    }

    public function update(JobdeskRequest $request, Jobdesk $jobdesk): JsonResponse
    {
        $jobdesk->update($request->validated());

        return response()->json(['message' => 'Jobdesk berhasil diperbarui.']);
    }

    public function destroy(Jobdesk $jobdesk): JsonResponse
    {
        $jobdesk->delete();

        return response()->json(['message' => 'Jobdesk berhasil dihapus.']);
    }
}
