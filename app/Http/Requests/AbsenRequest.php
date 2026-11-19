<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbsenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // absen untuk diri sendiri
    }

    public function rules(): array
    {
        // pengaturan null → controller yang menolak dengan pesan jelas
        $pengaturan = $this->user()->cabang?->pengaturanAbsensi;
        $wajib = fn (bool $ya) => $ya ? 'required' : 'nullable';

        return [
            'foto' => [$wajib((bool) $pengaturan?->wajib_foto), 'image', 'max:5120'],
            'latitude' => [$wajib((bool) $pengaturan?->wajib_lokasi), 'numeric', 'between:-90,90'],
            'longitude' => [$wajib((bool) $pengaturan?->wajib_lokasi), 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Foto wajib diambil.',
            'latitude.required' => 'Lokasi wajib diaktifkan.',
            'longitude.required' => 'Lokasi wajib diaktifkan.',
        ];
    }
}
