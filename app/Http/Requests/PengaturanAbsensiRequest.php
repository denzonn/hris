<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    protected function prepareForValidation(): void
    {
        // checkbox yang tidak dicentang tidak terkirim
        $this->merge([
            'wajib_foto' => $this->boolean('wajib_foto'),
            'wajib_lokasi' => $this->boolean('wajib_lokasi'),
            'toleransi_telat_menit' => $this->input('toleransi_telat_menit') ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'after:jam_masuk'],
            'toleransi_telat_menit' => ['required', 'integer', 'min:0', 'max:240'],
            'hari_kerja' => ['required', 'array', 'min:1'],
            'hari_kerja.*' => ['integer', 'between:1,7', 'distinct'],
            'wajib_foto' => ['boolean'],
            'wajib_lokasi' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jam_masuk' => 'jam masuk',
            'jam_pulang' => 'jam pulang',
            'toleransi_telat_menit' => 'toleransi telat',
            'hari_kerja' => 'hari kerja',
        ];
    }
}
