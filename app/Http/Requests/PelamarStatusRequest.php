<?php

namespace App\Http\Requests;

use App\Models\Pelamar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PelamarStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Pelamar::STATUS)],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
