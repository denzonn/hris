<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return ['periode' => ['required', 'date_format:Y-m', 'unique:payroll,periode']];
    }

    public function messages(): array
    {
        return ['periode.unique' => 'Payroll periode ini sudah ada. Buka lalu generate ulang bila masih draft.'];
    }
}
