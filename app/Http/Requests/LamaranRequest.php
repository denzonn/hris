<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Form lamaran publik (tanpa login). */
class LamaranRequest extends FormRequest
{
    public const PENDIDIKAN = ['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'Lainnya'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('pelamar')->where('lowongan_id', $this->route('lowongan')->id)],
            'no_hp' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'pendidikan_terakhir' => ['required', Rule::in(self::PENDIDIKAN)],
            'pesan' => ['nullable', 'string', 'max:2000'],
            'cv' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['no_hp' => 'no. HP', 'pendidikan_terakhir' => 'pendidikan terakhir', 'cv' => 'CV'];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Email ini sudah melamar di lowongan ini.'];
    }
}
