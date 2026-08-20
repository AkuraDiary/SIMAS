<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArsipSurat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'surat_id',
        'unit_kerja_id',
        'kategori_arsip_id',
        'tanggal_arsip',
        'catatan',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function kategoriArsip(): BelongsTo
    {
        return $this->belongsTo(KategoriArsip::class);
    }
}
