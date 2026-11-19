<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LemburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses & kepemilikan dicek di controller
    }

    public function rules(): array
    {
        return [
            // karyawan hanya untuk dirinya sendiri → user_id diisi controller
            'user_id' => $this->user()->otoritas_id === 3
                ? ['exclude']
                : ['required', 'integer', Rule::exists('users', 'id')->where('aktif', true)],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            // ponytail: lembur lewat tengah malam belum didukung, pecah jadi 2 baris kalau perlu
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'pegawai', 'jam_mulai' => 'jam mulai', 'jam_selesai' => 'jam selesai'];
    }
}
