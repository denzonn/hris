<?php

namespace App\Http\Controllers;

use App\Http\Requests\GajiPegawaiRequest;
use App\Models\KomponenGaji;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Nominal komponen gaji per pegawai. Gaji pokok tetap diubah di master pegawai. */
class GajiPegawaiController extends Controller
{
    public function index(): View
    {
        return view('pages.gaji_pegawai.index', [
            'komponen' => KomponenGaji::orderBy('tipe')->orderBy('nama')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = User::query()
            ->select('id', 'name', 'nik', 'jabatan_id', 'gaji_pokok')
            ->with(['jabatan:id,nama,tunjangan_jabatan', 'komponenGaji:id,nama,tipe,sifat'])
            ->where('aktif', true)->where('otoritas_id', '!=', 1)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function update(GajiPegawaiRequest $request, User $user): JsonResponse
    {
        abort_if($user->otoritas_id === 1, 403);

        $valid = KomponenGaji::whereKey(array_keys($request->validated('komponen', [])))->pluck('id')->all();
        $sync = collect($request->validated('komponen', []))
            ->only($valid)
            ->filter(fn ($nominal) => $nominal > 0) // 0 = tidak dapat komponen ini
            ->map(fn ($nominal) => ['nominal' => $nominal]);

        $user->komponenGaji()->sync($sync);

        return response()->json(['message' => "Komponen gaji {$user->name} berhasil disimpan."]);
    }
}
