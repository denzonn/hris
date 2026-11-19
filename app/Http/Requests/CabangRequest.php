<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CabangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return ['radius_meter' => 'radius'];
    }
}
