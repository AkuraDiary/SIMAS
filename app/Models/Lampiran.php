<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Lampiran extends Model
{
    /** @use HasFactory<\Database\Factories\LampiranFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'lampirans';

    protected $fillable = [
        'path_file',
        'surat_id',
    ];

    /**
     * Lampiran milik satu surat
     */
    public function surat()
    {
        return $this->belongsTo(Surat::class);
    }

    /**
     * Helper kecil biar Blade tidak jelek
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path_file);
    }

    /**
     * Ambil nama file dari path
     */
    public function getFilenameAttribute(): string
    {
        return basename($this->path_file);
    }
}
