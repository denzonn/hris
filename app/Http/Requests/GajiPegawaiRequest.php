<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GajiPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    protected function prepareForValidation(): void
    {
        // input rupiah berformat "1.500.000" → angka
        $this->merge(['komponen' => collect($this->input('komponen', []))
            ->map(fn ($v) => (int) preg_replace('/\D/', '', (string) $v))->all()]);
    }

    public function rules(): array
    {
        return [
            'komponen' => ['array'],
            'komponen.*' => ['integer', 'min:0', 'max:9999999999999'],
        ];
    }

    public function attributes(): array
    {
        return ['komponen.*' => 'nominal'];
    }
}
