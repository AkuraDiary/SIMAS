<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriArsip extends Model
{
    protected $fillable = [
        'unit_kerja_id',
        'nama',
    ];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
