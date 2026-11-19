<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HariLiburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d', Rule::unique('hari_libur')->ignore($this->route('hari_libur'))],
            'keterangan' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.'];
    }
}
