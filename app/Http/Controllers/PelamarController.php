<?php

namespace App\Http\Controllers;

use App\Http\Requests\PelamarStatusRequest;
use App\Models\Lowongan;
use App\Models\Pelamar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PelamarController extends Controller
{
    public function index(): View
    {
        return view('pages.pelamar.index', ['lowongan' => Lowongan::latest()->get(['id', 'judul'])]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Pelamar::query()
            ->with(['lowongan:id,judul', 'user:id,name'])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('nama', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->integer('lowongan_id'), fn ($q, $id) => $q->where('lowongan_id', $id))
            ->when(in_array($request->input('status'), Pelamar::STATUS, true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function status(PelamarStatusRequest $request, Pelamar $pelamar): JsonResponse
    {
        abort_if($pelamar->user_id && $request->validated('status') !== 'diterima', 422, 'Pelamar ini sudah menjadi pegawai.');

        $pelamar->update($request->validated());

        return response()->json(['message' => "Status {$pelamar->nama} diubah menjadi {$pelamar->status}."]);
    }

    public function cv(Pelamar $pelamar): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($pelamar->cv), 404);

        return Storage::disk('local')->response($pelamar->cv, 'CV '.$pelamar->nama.'.pdf');
    }

    public function destroy(Pelamar $pelamar): JsonResponse
    {
        abort_if((bool) $pelamar->user_id, 422, 'Pelamar yang sudah menjadi pegawai tidak bisa dihapus.');

        Storage::disk('local')->delete($pelamar->cv);
        $pelamar->delete();

        return response()->json(['message' => 'Data pelamar berhasil dihapus.']);
    }
}
