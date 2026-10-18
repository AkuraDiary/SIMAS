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
        'kop_posisi',
        'footer_posisi',
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

    protected function getLayoutMeta(): array
    {
        $raw = $this->attributes['template_file_path'] ?? null;
        if (is_string($raw) && str_starts_with(trim($raw), '{')) {
            return json_decode($raw, true) ?: [];
        }
        return [];
    }
    protected function setLayoutMeta(string $key, mixed $value): void
    {
        $meta = $this->getLayoutMeta();
        $meta[$key] = $value;
        $this->attributes['template_file_path'] = json_encode($meta);
    }
    protected function kopPosisi(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => $this->getLayoutMeta()['kop_posisi'] ?? 'AWAL_SAJA',
            set: function ($value) {
                $meta = $this->getLayoutMeta();
                $meta['kop_posisi'] = $value ?: 'AWAL_SAJA';
                return ['template_file_path' => json_encode($meta)];
            }
        );
    }
    protected function footerPosisi(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => $this->getLayoutMeta()['footer_posisi'] ?? 'AKHIR_SAJA',
            set: function ($value) {
                $meta = $this->getLayoutMeta();
                $meta['footer_posisi'] = $value ?: 'AKHIR_SAJA';
                return ['template_file_path' => json_encode($meta)];
            }
        );
    }
    public function getMediaDataUri(string $collection): ?string
    {
        $media = $this->getFirstMedia($collection);
        if (!$media || !file_exists($media->getPath())) {
            return null;
        }
        $mime = mime_content_type($media->getPath()) ?: $media->mime_type ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($media->getPath()));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('template_file')
            ->useDisk('private');
        $this->addMediaCollection('kop_surat')
            ->singleFile()
            ->useDisk('private');
        $this->addMediaCollection('footer_surat')
            ->singleFile()
            ->useDisk('private');
        $this->addMediaCollection('background_surat')
            ->singleFile()
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
