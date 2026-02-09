<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposisi extends Model
{
    /** @use HasFactory<\Database\Factories\DisposisiFactory> */
    use HasFactory;

    protected $fillable = [
        'jenis_instruksi',
        'sifat',
        'catatan',
        'tanggal_disposisi',
        'tanggal_update',
        'status_disposisi',
        'surat_id',
        'unit_tujuan_id',
        'unit_pembuat_id',
        'parent_disposisi_id',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_tujuan_id');
    }

    public function unitPembuat(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_pembuat_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'parent_disposisi_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Disposisi::class, 'parent_disposisi_id');
    }

   
}
