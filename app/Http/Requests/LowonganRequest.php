<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['aktif' => $this->boolean('aktif')]);
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'divisi_id' => ['nullable', 'integer', 'exists:divisi,id'],
            'jabatan_id' => ['nullable', 'integer', Rule::exists('jabatan', 'id')
                ->when($this->input('divisi_id'), fn ($r) => $r->where('divisi_id', $this->input('divisi_id')))],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'kualifikasi' => ['required', 'string', 'max:5000'],
            'tanggal_tutup' => ['nullable', 'date_format:Y-m-d'],
            'aktif' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['divisi_id' => 'divisi', 'jabatan_id' => 'jabatan', 'tanggal_tutup' => 'tanggal tutup'];
    }
}
