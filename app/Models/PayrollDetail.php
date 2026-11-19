<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    protected $table = 'payroll_detail';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        $uang = array_fill_keys([
            'gaji_pokok', 'tunjangan_jabatan', 'total_tunjangan', 'upah_lembur',
            'potongan_telat', 'potongan_alpha', 'total_potongan', 'gaji_bersih',
        ], 'decimal:2');

        return $uang + [
            'payroll_id' => 'integer',
            'user_id' => 'integer',
            'hari_kerja' => 'integer',
            'hari_hadir' => 'integer',
            'menit_telat' => 'integer',
            'jumlah_alpha' => 'integer',
            'menit_lembur' => 'integer',
            'rincian' => 'array',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
