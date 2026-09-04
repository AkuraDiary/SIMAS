<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormatNomorSurat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_kerja_id',
        'tipe_surat',
        'nama_format',
        'format_penomoran',
        'padding_digit',
        'nomor_urut_terakhir',
        'tahun',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'padding_digit' => 'integer',
            'nomor_urut_terakhir' => 'integer',
            'tahun' => 'integer',
        ];
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function nomorSuratLogs(): HasMany
    {
        return $this->hasMany(NomorSuratLog::class, 'format_nomor_id');
    }

    public function generateNomorSurat(Surat $surat, array $options = []): string
    {
        $service = app(\App\Services\NomorSuratService::class);
        $tglSurat = isset($options['tanggal_surat'])
            ? \Carbon\Carbon::parse($options['tanggal_surat'])
            : ($surat->tanggal_kirim ?? now());

        $isManual = (bool) ($options['is_manual'] ?? false);
        $customNomor = $options['nomor_surat_preview'] ?? null;

        if (!$customNomor) {
            $customNomor = $service->previewNomor(
                $this,
                $tglSurat,
                $options['nomor_part'] ?? null,
                $surat->unitPengirim,
                $surat->tipe_surat,
                $surat->content ?? []
            );
        }

        return $service->assignNomorSurat($surat, $this, array_merge([
            'tanggal_surat' => $tglSurat,
            'nomor_surat_preview' => $customNomor,
            'is_manual' => $isManual,
            'increment_counter' => $options['increment_counter'] ?? (!$isManual),
            'alasan_backdate' => $options['alasan_backdate'] ?? null,
            'user_id' => $options['user_id'] ?? auth()->id(),
        ], $options));
    }
}
