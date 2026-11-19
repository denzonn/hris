<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JabatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tunjangan_jabatan' => $this->input('tunjangan_jabatan') ?: 0]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'divisi_id' => ['required', 'integer', 'exists:divisi,id'],
            'tunjangan_jabatan' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    public function attributes(): array
    {
        return ['divisi_id' => 'divisi', 'tunjangan_jabatan' => 'tunjangan jabatan'];
    }
}
