<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Cabang;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Otoritas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Master Pegawai (tabel users). Tidak ada hard delete, pakai toggle kolom `aktif`. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.user.index', [
            'cabang' => Cabang::orderBy('nama')->get(['id', 'nama']),
            'divisi' => Divisi::orderBy('nama')->get(['id', 'nama']),
            'jabatan' => Jabatan::orderBy('nama')->get(['id', 'nama', 'divisi_id']),
            'otoritas' => Otoritas::when($request->user()->otoritas_id !== 1, fn ($q) => $q->where('id', '!=', 1))
                ->orderBy('id')->get(['id', 'nama']),
            'statusPtkp' => UserRequest::STATUS_PTKP,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = User::query()
            ->with('otoritas:id,nama', 'cabang:id,nama', 'divisi:id,nama', 'jabatan:id,nama')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")))
            ->when($request->integer('cabang_id'), fn ($q, $id) => $q->where('cabang_id', $id))
            ->when($request->integer('divisi_id'), fn ($q, $id) => $q->where('divisi_id', $id))
            ->when($request->filled('aktif'), fn ($q) => $q->where('aktif', $request->boolean('aktif')))
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('pegawai', 'public');
        }

        User::create($data);

        return response()->json(['message' => 'Pegawai berhasil ditambahkan.']);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $request->safe()->except(['foto', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('pegawai', 'public');
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
        }

        $user->update($data);

        return response()->json(['message' => 'Pegawai berhasil diperbarui.']);
    }

    public function toggleAktif(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->otoritas_id !== 1 && $user->otoritas_id === 1, 403);

        if ($user->is($request->user())) {
            return response()->json(['message' => 'Anda tidak bisa menonaktifkan akun sendiri.'], 422);
        }

        $user->update(['aktif' => ! $user->aktif]);

        return response()->json(['message' => $user->aktif ? 'Pegawai berhasil diaktifkan.' : 'Pegawai berhasil dinonaktifkan.']);
    }
}
