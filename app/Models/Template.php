<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Template extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'kategori_id',
        'entry_point_unit_id',
        'nama_template',
        'deskripsi',
        'tipe_surat',
        'aksesibilitas',
        'field_variables',
        'approval_path',
        'template_file_path',
        'content_html',
        'render_engine',
        'is_ttd_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_variables' => 'array',
            'approval_path' => 'array',
            'is_ttd_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('template_file')
            ->useDisk('private');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(TemplateKategori::class, 'kategori_id');
    }

    public function entryPointUnit(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'entry_point_unit_id');
    }

    public function unitAkses(): BelongsToMany
    {
        return $this->belongsToMany(
            UnitKerja::class,
            'template_unit_akses',
            'template_id',
            'unit_kerja_id'
        );
    }

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
