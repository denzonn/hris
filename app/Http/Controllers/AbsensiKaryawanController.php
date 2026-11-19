<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbsensiKaryawanRequest;
use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Input & koreksi absensi oleh HR (lupa absen, HP rusak). Jejak: diubah_oleh. */
class AbsensiKaryawanController extends Controller
{
    public function index(): View
    {
        return view('pages.absensi_karyawan.index', [
            'cabang' => Cabang::orderBy('nama')->get(['id', 'nama']),
            'pegawai' => User::where('aktif', true)->where('otoritas_id', '!=', 1)->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = Absensi::query()
            ->with(['user:id,name,nik,cabang_id', 'user.cabang:id,nama', 'pengubah:id,name'])
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")))
            ->when($request->integer('cabang_id'), fn ($q, $id) => $q->whereHas('user', fn ($u) => $u->where('cabang_id', $id)))
            ->when(in_array($request->input('status'), Absensi::STATUS, true), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->date('tanggal_mulai'), fn ($q, $t) => $q->whereDate('tanggal', '>=', $t))
            ->when($request->date('tanggal_selesai'), fn ($q, $t) => $q->whereDate('tanggal', '<=', $t))
            ->orderByDesc('tanggal')->orderBy('user_id')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(AbsensiKaryawanRequest $request): JsonResponse
    {
        Payroll::tolakJikaTerkunci($request->validated('tanggal'));
        Absensi::create($this->siapkan($request));

        return response()->json(['message' => 'Absensi berhasil ditambahkan.']);
    }

    public function update(AbsensiKaryawanRequest $request, Absensi $absensi): JsonResponse
    {
        Payroll::tolakJikaTerkunci($absensi->tanggal->toDateString(), $request->validated('tanggal'));
        $absensi->update($this->siapkan($request));

        return response()->json(['message' => 'Absensi berhasil diperbarui.']);
    }

    public function destroy(Absensi $absensi): JsonResponse
    {
        Payroll::tolakJikaTerkunci($absensi->tanggal->toDateString());
        $absensi->delete();

        return response()->json(['message' => 'Absensi berhasil dihapus.']);
    }

    private function siapkan(AbsensiKaryawanRequest $request): array
    {
        $data = $request->validated() + ['diubah_oleh' => $request->user()->id];

        if (! in_array($data['status'], ['hadir', 'telat'], true)) {
            return array_merge($data, ['jam_masuk' => null, 'jam_pulang' => null, 'menit_telat' => 0]);
        }

        // ponytail: cabang tanpa pengaturan → dianggap tidak telat
        $pengaturan = User::find($data['user_id'])->cabang?->pengaturanAbsensi;
        $data['menit_telat'] = $pengaturan?->menitTelat($data['jam_masuk'].':00') ?? 0;
        $data['status'] = $data['menit_telat'] ? 'telat' : 'hadir';

        return $data;
    }
}
