<?php

namespace App\Http\Requests;

use App\Models\PengajuanIzin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IzinKaryawanRequest extends FormRequest
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
            'jenis' => ['required', Rule::in(PengajuanIzin::JENIS)],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string', 'max:255'],
            'lampiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'pegawai', 'tanggal_mulai' => 'tanggal mulai', 'tanggal_selesai' => 'tanggal selesai'];
    }
}
