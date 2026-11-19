<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public const STATUS_PTKP = ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'];

    public function authorize(): bool
    {
        // HR (otoritas 2) tidak boleh mengubah akun Super Admin. Akses modul dijaga middleware di route.
        return ! ($this->user()->otoritas_id !== 1 && $this->route('user')?->otoritas_id === 1);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gaji_pokok' => $this->input('gaji_pokok') ?: 0,
            'jatah_cuti' => $this->input('jatah_cuti') ?? 12,
            'status_kerja' => $this->input('status_kerja') ?: 'tetap',
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            // HR hanya boleh memberi otoritas 2 / 3
            'otoritas_id' => ['required', 'integer', 'exists:otoritas,id',
                Rule::notIn($this->user()->otoritas_id === 1 ? [] : [1])],
            'nik' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user)],
            'cabang_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'divisi_id' => ['nullable', 'integer', 'exists:divisi,id'],
            'jabatan_id' => ['nullable', 'integer', Rule::exists('jabatan', 'id')
                ->when($this->filled('divisi_id'), fn ($rule) => $rule->where('divisi_id', $this->input('divisi_id')))],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk' => ['nullable', 'date'],
            'status_kerja' => ['required', Rule::in(['tetap', 'kontrak', 'magang'])],
            'tanggal_akhir_kontrak' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'gaji_pokok' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'status_ptkp' => ['nullable', Rule::in(self::STATUS_PTKP)],
            'jatah_cuti' => ['required', 'integer', 'min:0', 'max:255'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'otoritas_id' => 'otoritas',
            'cabang_id' => 'cabang',
            'divisi_id' => 'divisi',
            'jabatan_id' => 'jabatan',
            'no_hp' => 'no. HP',
            'gaji_pokok' => 'gaji pokok',
            'no_rekening' => 'no. rekening',
            'status_ptkp' => 'status PTKP',
            'jatah_cuti' => 'jatah cuti',
        ];
    }

    public function messages(): array
    {
        return [
            'otoritas_id.not_in' => 'Anda tidak berhak memberi otoritas Super Admin.',
            'jabatan_id.exists' => 'Jabatan tidak ditemukan pada divisi yang dipilih.',
        ];
    }
}
