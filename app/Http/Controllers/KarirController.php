<?php

namespace App\Http\Controllers;

use App\Http\Requests\LamaranRequest;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/** Halaman karir publik (tanpa login). */
class KarirController extends Controller
{
    public function index(): View
    {
        return view('pages.karir.index', [
            'lowongan' => Lowongan::dibuka()->with(['divisi:id,nama'])->latest()->get(),
        ]);
    }

    public function show(Lowongan $lowongan): View
    {
        $this->cekDibuka($lowongan);

        return view('pages.karir.show', [
            'lowongan' => $lowongan->load('divisi:id,nama', 'jabatan:id,nama'),
            'pendidikan' => LamaranRequest::PENDIDIKAN,
        ]);
    }

    public function store(LamaranRequest $request, Lowongan $lowongan): JsonResponse
    {
        $this->cekDibuka($lowongan);

        $lowongan->pelamar()->create($request->safe()->except('cv') + [
            'cv' => $request->file('cv')->store('cv', 'local'),
        ]);

        return response()->json(['message' => 'Lamaran Anda berhasil dikirim. Kami akan menghubungi Anda bila lolos seleksi.']);
    }

    private function cekDibuka(Lowongan $lowongan): void
    {
        abort_unless(Lowongan::dibuka()->whereKey($lowongan->id)->exists(), 404);
    }
}
