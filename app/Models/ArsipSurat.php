<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArsipSurat extends Model
{
    public function kategoriArsip(): BelongsTo
    {
        return $this->belongsTo(KategoriArsip::class);
    }
}
