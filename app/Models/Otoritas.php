<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Otoritas extends Model
{
    protected $table = 'otoritas';

    protected $fillable = ['id', 'nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
