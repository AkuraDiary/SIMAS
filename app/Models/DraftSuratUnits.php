<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftSuratUnits extends Model
{
    protected $fillable = [
        'surat_id',
        'unit_kerja_id',
        'jenis_tujuan',
    ];

    public function surat() : BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function unitKerja() : BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }
}

