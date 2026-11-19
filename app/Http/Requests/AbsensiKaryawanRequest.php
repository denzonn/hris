<?php

namespace App\Http\Requests;

use App\Models\Absensi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbsensiKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware otoritas di route
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('aktif', true)],
            'tanggal' => [
                'required', 'date_format:Y-m-d', 'before_or_equal:today',
                Rule::unique('absensi')->where('user_id', $this->input('user_id'))->ignore($this->route('absensi')),
            ],
            // hadir/telat ditentukan server dari jam masuk
            'status' => ['required', Rule::in(Absensi::STATUS)],
            'jam_masuk' => ['required_if:status,hadir,telat', 'nullable', 'date_format:H:i'],
            'jam_pulang' => ['nullable', 'date_format:H:i', 'after:jam_masuk'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'pegawai', 'jam_masuk' => 'jam masuk', 'jam_pulang' => 'jam pulang'];
    }

    public function messages(): array
    {
        return [
            'tanggal.unique' => 'Pegawai ini sudah punya data absensi di tanggal tersebut.',
            'jam_masuk.required_if' => 'Jam masuk wajib diisi untuk status hadir.',
        ];
    }
}
