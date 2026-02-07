<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SuratUnit extends Pivot
{
    /** @use HasFactory<\Database\Factories\SuratUnitFactory> */
    use HasFactory;
    protected $table = 'surat_unit';
    
    protected $fillable = [
        'surat_id',
        'unit_kerja_id',
        'jenis_tujuan',
        'tanggal_terima',
        'status_baca',
    ];

    public $timestamps = false;

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
