<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisUnit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'nama_jenis',
        'deskripsi',
    ];

    public function unitKerjas(): HasMany
    {
        return $this->hasMany(UnitKerja::class);
    }
}
