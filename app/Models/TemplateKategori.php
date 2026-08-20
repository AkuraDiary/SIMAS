<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateKategori extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'kategori_id');
    }
}
