<?php

namespace App\Http\Controllers;

use App\Http\Requests\IzinKaryawanRequest;
use App\Models\Absensi;
use App\Models\Payroll;
use App\Models\PengajuanIzin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Izin, sakit, cuti. Karyawan mengajukan (pending) untuk dirinya sendiri; HR/Admin menyetujui.
 * Pengajuan yang dibuat HR langsung disetujui. Disetujui → baris absensi terisi per hari kerja.
 * Hanya pengajuan pending yang bisa diubah/dihapus/diproses.
 */
class IzinKaryawanController extends Controller
{
    public function index(Request $request): View
    {
        $isKaryawan = $this->isKaryawan($request);

        return view('pages.izin_karyawan.index', [
            'isKaryawan' => $isKaryawan,
            'sisaCuti' => $request->user()->sisaCuti(),
            'pegawai' => $isKaryawan ? collect() : User::where('aktif', true)
                ->where('otoritas_id', '!=', 1)->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $data = PengajuanIzin::query()
            ->with(['user:id,name,nik', 'penyetuju:id,name'])
            ->when($this->isKaryawan($request), fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")))
            ->when(in_array($request->input('status'), ['pending', 'disetujui', 'ditolak'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->when(in_array($request->input('jenis'), PengajuanIzin::JENIS, true), fn ($q) => $q->where('jenis', $request->input('jenis')))
            ->latest()
            ->paginate(min($request->integer('per_page', 10), 100));

        return response()->json($data);
    }

    public function store(IzinKaryawanRequest $request): JsonResponse
    {
        $izin = new PengajuanIzin($request->safe()->except('lampiran'));
        $izin->user_id ??= $request->user()->id;
        $this->validasi($izin);

        if ($request->hasFile('lampiran')) {
            $izin->lampiran = $request->file('lampiran')->store('izin', 'local');
        }

        if ($this->isKaryawan($request)) {
            $izin->save();

            return response()->json(['message' => 'Pengajuan berhasil dikirim.']);
        }

        $this->setujui($izin, $request->user());

        return response()->json(['message' => 'Pengajuan berhasil ditambahkan dan disetujui.']);
    }

    public function update(IzinKaryawanRequest $request, PengajuanIzin $izin): JsonResponse
    {
        $this->cekBisaUbah($request, $izin);

        $izin->fill($request->safe()->except('lampiran'));
        $this->validasi($izin);

        if ($request->hasFile('lampiran')) {
            $lama = $izin->lampiran;
            $izin->lampiran = $request->file('lampiran')->store('izin', 'local');
            $lama && Storage::disk('local')->delete($lama);
        }

        $izin->save();

        return response()->json(['message' => 'Pengajuan berhasil diperbarui.']);
    }

    public function destroy(Request $request, PengajuanIzin $izin): JsonResponse
    {
        $this->cekBisaUbah($request, $izin);

        $izin->lampiran && Storage::disk('local')->delete($izin->lampiran);
        $izin->delete();

        return response()->json(['message' => 'Pengajuan berhasil dihapus.']);
    }

    public function status(Request $request, PengajuanIzin $izin): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['disetujui', 'ditolak'])]]);
        abort_if($izin->status !== 'pending', 422, 'Pengajuan ini sudah diproses.');

        if ($data['status'] === 'ditolak') {
            $izin->update(['status' => 'ditolak', 'disetujui_oleh' => $request->user()->id]);
        } else {
            $this->validasi($izin); // saldo cuti bisa berubah sejak diajukan
            $this->setujui($izin, $request->user());
        }

        return response()->json(['message' => "Pengajuan berhasil {$data['status']}."]);
    }

    public function lampiran(Request $request, PengajuanIzin $izin): StreamedResponse
    {
        abort_if($this->isKaryawan($request) && $izin->user_id !== $request->user()->id, 403);
        abort_unless($izin->lampiran && Storage::disk('local')->exists($izin->lampiran), 404);

        return Storage::disk('local')->response($izin->lampiran);
    }

    private function validasi(PengajuanIzin $izin): void
    {
        $tanggal = $izin->tanggalEfektif();
        abort_if($tanggal === [], 422, 'Rentang tanggal tidak berisi hari kerja.');
        Payroll::tolakJikaTerkunci(...$tanggal);

        $bentrok = PengajuanIzin::where('user_id', $izin->user_id)
            ->whereIn('status', ['pending', 'disetujui'])
            ->when($izin->exists, fn ($q) => $q->whereKeyNot($izin->id))
            ->whereDate('tanggal_mulai', '<=', $izin->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $izin->tanggal_mulai)
            ->exists();
        abort_if($bentrok, 422, 'Sudah ada pengajuan lain di rentang tanggal ini.');

        if ($izin->jenis === 'cuti') {
            // ponytail: cuti lintas tahun dihitung dari saldo tahun tanggal_mulai
            $sisa = $izin->user->sisaCuti($izin->tanggal_mulai->year);
            abort_if(count($tanggal) > $sisa, 422, sprintf('Sisa cuti tidak cukup (butuh %d hari, sisa %d hari).', count($tanggal), $sisa));
        }
    }

    /** Isi absensi per hari kerja. Absen hadir/telat atau status lain yang sudah ada tidak ditimpa, kecuali alpha. */
    private function setujui(PengajuanIzin $izin, User $penyetuju): void
    {
        DB::transaction(function () use ($izin, $penyetuju) {
            $izin->fill(['status' => 'disetujui', 'disetujui_oleh' => $penyetuju->id])->save();

            $ada = Absensi::where('user_id', $izin->user_id)
                ->whereIn('tanggal', $izin->tanggalEfektif())
                ->get()->keyBy(fn ($a) => $a->tanggal->toDateString());

            foreach ($izin->tanggalEfektif() as $tanggal) {
                $row = $ada->get($tanggal);
                if ($row && $row->status !== 'alpha') {
                    continue;
                }

                Absensi::updateOrCreate(['user_id' => $izin->user_id, 'tanggal' => $tanggal], [
                    'status' => $izin->jenis,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'menit_telat' => 0,
                    'keterangan' => Str::limit(ucfirst($izin->jenis).': '.$izin->alasan, 250),
                    'diubah_oleh' => $penyetuju->id,
                ]);
            }
        });
    }

    private function isKaryawan(Request $request): bool
    {
        return $request->user()->otoritas_id === 3;
    }

    private function cekBisaUbah(Request $request, PengajuanIzin $izin): void
    {
        abort_if($this->isKaryawan($request) && $izin->user_id !== $request->user()->id, 403);
        abort_if($izin->status !== 'pending', 422, 'Pengajuan yang sudah diproses tidak bisa diubah.');
    }
}
