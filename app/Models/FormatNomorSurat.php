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
        'nama_format',
        'format_penomoran',
        'nomor_urut_terakhir',
        'tahun',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function generateNomorSurat(Surat $surat): string
    {
        $this->nomor_urut_terakhir++;
        $this->save();

        $nomorUrut = str_pad($this->nomor_urut_terakhir, 3, '0', STR_PAD_LEFT);
        $kodeUnit = $surat->unitPengirim?->singkatan ?? 'UN';

        $romawiBulan = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $bulan = $romawiBulan[date('n')];
        $tahun = date('Y');

        $format = $this->format_penomoran;

        $format = str_replace('{NOMOR}', $nomorUrut, $format);
        $format = str_replace('{KODE_UNIT}', $kodeUnit, $format);
        $format = str_replace('{BULAN_ROMAWI}', $bulan, $format);
        $format = str_replace('{TAHUN}', $tahun, $format);

        $nomorLengkap = $format;

        NomorSuratLog::create([
            'surat_id' => $surat->id,
            'format_nomor_id' => $this->id,
            'nomor_urut' => $this->nomor_urut_terakhir,
            'nomor_lengkap' => $nomorLengkap,
            'tanggal_ditetapkan' => now(),
        ]);

        return $nomorLengkap;
    }
}
