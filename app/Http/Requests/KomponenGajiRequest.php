<?php

namespace App\Http\Requests;

use App\Models\KomponenGaji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KomponenGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('komponen_gaji')->ignore($this->route('komponen_gaji'))],
            'tipe' => ['required', Rule::in(KomponenGaji::TIPE)],
            'sifat' => ['required', Rule::in(KomponenGaji::SIFAT)],
        ];
    }
}
