<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jabatan extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'nama_jabatan',
        'level_jabatan',
    ];

    public function pegawaiJabatans(): HasMany
    {
        return $this->hasMany(UserPegawaiJabatan::class);
    }
}
