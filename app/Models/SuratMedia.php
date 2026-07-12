<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SuratMedia extends Pivot
{
    protected $table = 'surat_media';

    public $timestamps = false;

    protected $fillable = [
        'surat_id',
        'media_id',
        'konteks',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
