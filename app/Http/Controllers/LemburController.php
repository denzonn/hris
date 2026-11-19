<?php

namespace App\Http\Controllers;

use App\Http\Requests\LemburRequest;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin/HR: perintah lembur (langsung disetujui) + setujui/tolak pengajuan.
 * Karyawan: lihat & ajukan lembur sendiri (pending), ubah/hapus selama masih pending.
 */
class LemburController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.lembur.index', [
            'isKaryawan' => $this->isKaryawan($request),
            'pegawai' => $this->isKaryawan($request) ? collect() : User::where('aktif', true)
                ->where('otoritas_id', '!=', 1)->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();
        $bulan = $request->string('bulan')->value(); // format Y-m

        $data = Lembur::query()
            ->with(['user:id,name,nik', 'penyetuju:id,name'])
            ->when($this->isKaryawan($request), fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")))
            ->when(in_array($request->input('status'), Lembur::STATUS, true), fn ($q) => $q->where('status', $request->input('status')))
            ->when(preg_match('/^\d{4}-\d{2}$/', $bulan), fn ($q) => $q
                ->whereYear('tanggal', substr($bulan, 0, 4))->whereMonth('tanggal', substr($bulan, 5, 2)))
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai')
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(LemburRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($this->isKaryawan($request)) {
            $data += ['user_id' => $request->user()->id, 'status' => 'pending'];
        } else {
            $data += ['status' => 'disetujui', 'disetujui_oleh' => $request->user()->id];
        }

        Lembur::create($data);

        return response()->json(['message' => $this->isKaryawan($request)
            ? 'Pengajuan lembur berhasil dikirim.'
            : 'Perintah lembur berhasil ditambahkan.']);
    }

    public function update(LemburRequest $request, Lembur $lembur): JsonResponse
    {
        $this->cekMilikPending($request, $lembur);

        $lembur->update($request->validated());

        return response()->json(['message' => 'Lembur berhasil diperbarui.']);
    }

    public function destroy(Request $request, Lembur $lembur): JsonResponse
    {
        $this->cekMilikPending($request, $lembur);

        $lembur->delete();

        return response()->json(['message' => 'Lembur berhasil dihapus.']);
    }

    public function status(Request $request, Lembur $lembur): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['disetujui', 'ditolak'])]]);

        $lembur->update($data + ['disetujui_oleh' => $request->user()->id]);

        return response()->json(['message' => "Lembur berhasil {$data['status']}."]);
    }

    private function isKaryawan(Request $request): bool
    {
        return $request->user()->otoritas_id === 3;
    }

    private function cekMilikPending(Request $request, Lembur $lembur): void
    {
        if (! $this->isKaryawan($request)) {
            return;
        }

        abort_unless($lembur->user_id === $request->user()->id, 403);
        abort_if($lembur->status !== 'pending', 422, 'Lembur yang sudah diproses tidak bisa diubah.');
    }
}
