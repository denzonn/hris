<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    protected function prepareForValidation(): void
    {
        $rupiah = fn ($v) => (int) preg_replace('/\D/', '', (string) $v);

        $this->merge([
            'tanggal_cutoff' => $this->input('tanggal_cutoff') ?: null,
            'potongan_per_menit_telat' => $rupiah($this->input('potongan_per_menit_telat')),
            'potongan_per_alpha' => $rupiah($this->input('potongan_per_alpha')),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal_cutoff' => ['nullable', 'integer', 'between:1,28'],
            'potongan_per_menit_telat' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'potongan_per_alpha' => ['required', 'integer', 'min:0', 'max:9999999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal_cutoff' => 'tanggal cut-off',
            'potongan_per_menit_telat' => 'potongan per menit telat',
            'potongan_per_alpha' => 'potongan per alpha',
        ];
    }
}
