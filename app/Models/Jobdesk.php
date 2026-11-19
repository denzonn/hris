<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jobdesk extends Model
{
    protected $table = 'jobdesk';

    protected $fillable = ['jabatan_id', 'deskripsi'];

    protected function casts(): array
    {
        return ['jabatan_id' => 'integer'];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
