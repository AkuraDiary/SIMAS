<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriArsip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_kerja_id',
        'nama',
    ];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
