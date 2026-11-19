<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobdeskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'jabatan_id' => ['required', 'integer', 'exists:jabatan,id'],
            'deskripsi' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['jabatan_id' => 'jabatan'];
    }
}
