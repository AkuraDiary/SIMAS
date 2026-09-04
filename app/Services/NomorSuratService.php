<?php

namespace App\Services;

use App\Models\FormatNomorSurat;
use App\Models\NomorSuratLog;
use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\UnitKerja;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NomorSuratService
{
    /**
     * Cari format nomor surat yang paling sesuai berdasarkan unit, tipe surat, dan tahun.
     * Prioritas:
     * 1. Unit + Tipe Spesifik
     * 2. Unit + Tipe ALL
     * 3. Global (Pusat) + Tipe Spesifik
     * 4. Global (Pusat) + Tipe ALL
     * 5. Fallback ke format aktif terbaru tahun sebelumnya
     */
    public function resolveFormat(?int $unitId, string $tipeSurat = 'ALL', ?int $tahun = null): ?FormatNomorSurat
    {
        $tahun = $tahun ?? (int) date('Y');

        if ($unitId) {
            // 1. Unit + Tipe Spesifik
            $format = FormatNomorSurat::where('unit_kerja_id', $unitId)
                ->where('tipe_surat', $tipeSurat)
                ->where('tahun', $tahun)
                ->where('is_active', true)
                ->first();

            if ($format) return $format;

            // 2. Unit + Tipe ALL
            if ($tipeSurat !== 'ALL') {
                $format = FormatNomorSurat::where('unit_kerja_id', $unitId)
                    ->where('tipe_surat', 'ALL')
                    ->where('tahun', $tahun)
                    ->where('is_active', true)
                    ->first();

                if ($format) return $format;
            }
        }

        // 3. Global + Tipe Spesifik
        $format = FormatNomorSurat::whereNull('unit_kerja_id')
            ->where('tipe_surat', $tipeSurat)
            ->where('tahun', $tahun)
            ->where('is_active', true)
            ->first();

        if ($format) return $format;

        // 4. Global + Tipe ALL
        if ($tipeSurat !== 'ALL') {
            $format = FormatNomorSurat::whereNull('unit_kerja_id')
                ->where('tipe_surat', 'ALL')
                ->where('tahun', $tahun)
                ->where('is_active', true)
                ->first();

            if ($format) return $format;
        }

        // 5. Fallback: ambil format aktif apapun milik unit atau global
        return FormatNomorSurat::where(function ($q) use ($unitId) {
                if ($unitId) {
                    $q->where('unit_kerja_id', $unitId)->orWhereNull('unit_kerja_id');
                } else {
                    $q->whereNull('unit_kerja_id');
                }
            })
            ->where('is_active', true)
            ->orderBy('tahun', 'desc')
            ->first();
    }

    /**
     * Dapatkan daftar format yang relevan untuk unit dan tipe surat tertentu.
     */
    public function getAvailableFormats(?int $unitId, ?string $tipeSurat = null): array
    {
        $query = FormatNomorSurat::where('is_active', true)
            ->where(function ($q) use ($unitId) {
                if ($unitId) {
                    $q->where('unit_kerja_id', $unitId)
                      ->orWhereNull('unit_kerja_id');
                } else {
                    $q->whereNull('unit_kerja_id');
                }
            });

        if ($tipeSurat) {
            $query->where(function ($q) use ($tipeSurat) {
                $q->where('tipe_surat', $tipeSurat)
                  ->orWhere('tipe_surat', 'ALL');
            });
        }

        return $query->get()->mapWithKeys(function ($f) {
            $scope = $f->unit_kerja_id ? ($f->unitKerja?->nama_unit ?? 'Unit') : 'Pusat/Global';
            $tipe = $f->tipe_surat === 'ALL' ? 'Semua Tipe' : $f->tipe_surat;
            return [$f->id => "[{$scope} - {$tipe}] {$f->nama_format} ({$f->format_penomoran})"];
        })->toArray();
    }

    public const STANDARD_TAGS = ['NOMOR', 'KODE_UNIT', 'BULAN_ROMAWI', 'BULAN_ANGKA', 'TAHUN', 'TIPE'];

    /**
     * Ekstrak tag kustom dari pola format penomoran yang bukan merupakan STANDARD_TAGS.
     * Contoh: "{NOMOR}/{KODE_KLASIFIKASI}/{KODE_UNIT}/{TAHUN}" -> ['KODE_KLASIFIKASI']
     */
    public function extractCustomTags(string $formatPattern): array
    {
        preg_match_all('/\{([A-Za-z0-9_]+)\}/', $formatPattern, $matches);
        if (empty($matches[1])) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $matches[1],
            fn ($tag) => !in_array($tag, self::STANDARD_TAGS, true)
        )));
    }

    /**
     * Mengubah angka bulan ke bentuk Romawi
     */
    public function toRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Format angka nomor urut dengan padding digit
     */
    public function formatNomorUrut(int $nomorUrut, int $padding = 3): string
    {
        return str_pad((string) $nomorUrut, max(1, $padding), '0', STR_PAD_LEFT);
    }

    /**
     * Cek apakah tanggal yang dipilih merupakan backdate (kurang dari hari ini).
     */
    public function isDateBackdate(Carbon|string|null $tanggal): bool
    {
        if (!$tanggal) return false;

        $tgl = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);
        return $tgl->startOfDay()->lessThan(Carbon::today());
    }

    /**
     * Render template nomor surat menjadi string lengkap.
     */
    public function renderTemplate(
        FormatNomorSurat $format,
        string $nomorPart,
        Carbon $tanggalSurat,
        ?UnitKerja $unit = null,
        ?string $tipeSurat = null,
        array $customTags = []
    ): string {
        $kodeUnit = $unit?->singkatan ?? 'UN';
        $bulanRomawi = $this->toRomanMonth((int) $tanggalSurat->format('n'));
        $bulanAngka = $tanggalSurat->format('m');
        $tahun = $tanggalSurat->format('Y');

        $tipeCode = match ($tipeSurat) {
            'INTERNAL' => 'ND',
            'PENGAJUAN' => 'PGN',
            'TERBITAN' => 'SK',
            'EKSTERNAL' => 'EKS',
            default => 'SRT',
        };

        $result = $format->format_penomoran;
        $result = str_replace('{NOMOR}', $nomorPart, $result);
        $result = str_replace('{KODE_UNIT}', $kodeUnit, $result);
        $result = str_replace('{BULAN_ROMAWI}', $bulanRomawi, $result);
        $result = str_replace('{BULAN_ANGKA}', $bulanAngka, $result);
        $result = str_replace('{TAHUN}', $tahun, $result);
        $result = str_replace('{TIPE}', $tipeCode, $result);

        // Flatten custom tags (support nested 'nomor_surat_tags' or root-level tags, case-insensitive)
        $flatTags = [];
        if (isset($customTags['nomor_surat_tags']) && is_array($customTags['nomor_surat_tags'])) {
            foreach ($customTags['nomor_surat_tags'] as $k => $v) {
                $flatTags[strtoupper($k)] = $v;
            }
        }
        foreach ($customTags as $k => $v) {
            if (is_scalar($v)) {
                $flatTags[strtoupper($k)] = (string) $v;
            }
        }

        // Replace any remaining {TAG} from flatTags
        preg_match_all('/\{([A-Za-z0-9_]+)\}/', $result, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $customTag) {
                $upperTag = strtoupper($customTag);
                if (array_key_exists($upperTag, $flatTags) && $flatTags[$upperTag] !== '') {
                    $result = str_replace('{' . $customTag . '}', $flatTags[$upperTag], $result);
                }
            }
        }

        return $result;
    }

    /**
     * Generate preview untuk form modal penomoran.
     */
    public function previewNomor(
        FormatNomorSurat $format,
        Carbon|string|null $tanggalSurat = null,
        ?string $customNomorPart = null,
        ?UnitKerja $unit = null,
        ?string $tipeSurat = null,
        array $customTags = []
    ): string {
        $tgl = $tanggalSurat ? ($tanggalSurat instanceof Carbon ? $tanggalSurat : Carbon::parse($tanggalSurat)) : Carbon::now();
        $nomorPart = $customNomorPart ?? $this->formatNomorUrut($format->nomor_urut_terakhir + 1, $format->padding_digit ?? 3);

        return $this->renderTemplate($format, $nomorPart, $tgl, $unit, $tipeSurat, $customTags);
    }

    /**
     * Menetapkan nomor surat secara resmi, menyimpan log, dan mengupdate record surat.
     */
    public function assignNomorSurat(Surat $surat, FormatNomorSurat $format, array $params): string
    {
        return DB::transaction(function () use ($surat, $format, $params) {
            $tglSurat = isset($params['tanggal_surat'])
                ? ($params['tanggal_surat'] instanceof Carbon ? $params['tanggal_surat'] : Carbon::parse($params['tanggal_surat']))
                : Carbon::now();

            $isManual = (bool) ($params['is_manual'] ?? false);
            $incrementCounter = (bool) ($params['increment_counter'] ?? (!$isManual));
            $nomorLengkap = trim($params['nomor_surat_preview'] ?? $params['nomor_lengkap'] ?? '');
            $alasanBackdate = $params['alasan_backdate'] ?? null;
            $userId = $params['user_id'] ?? auth()->id();

            $isBackdate = $this->isDateBackdate($tglSurat);

            // Tentukan nomor_urut yang dicatat di log
            $nomorUrut = (int) ($params['nomor_urut'] ?? ($format->nomor_urut_terakhir + 1));

            if ($incrementCounter) {
                // Lock row to prevent race conditions on simultaneous letter numbering
                $lockedFormat = FormatNomorSurat::where('id', $format->id)->lockForUpdate()->first();
                $lockedFormat->increment('nomor_urut_terakhir');
                $nomorUrut = $lockedFormat->nomor_urut_terakhir;
            }

            // Simpan log penomoran
            NomorSuratLog::create([
                'surat_id' => $surat->id,
                'format_nomor_id' => $format->id,
                'nomor_urut' => $nomorUrut,
                'nomor_lengkap' => $nomorLengkap,
                'is_backdate' => $isBackdate,
                'is_manual' => $isManual,
                'alasan_backdate' => $alasanBackdate,
                'user_id' => $userId,
                'tanggal_ditetapkan' => $tglSurat->toDateString(),
                'created_at' => Carbon::now(),
            ]);

            // Update surat
            $surat->nomor_surat = $nomorLengkap;
            if (!empty($params['custom_tags']) && is_array($params['custom_tags'])) {
                $content = $surat->content ?? [];
                if (!is_array($content)) {
                    $content = json_decode($content, true) ?? [];
                }
                $content['nomor_surat_tags'] = array_merge($content['nomor_surat_tags'] ?? [], $params['custom_tags']);
                $surat->content = $content;
            }
            if (!$surat->tanggal_kirim) {
                $surat->tanggal_kirim = $tglSurat;
            }
            $surat->save();

            // Catat ke timeline / riwayat
            $catatanTimeline = "Nomor surat ditetapkan: {$nomorLengkap}";
            if ($isBackdate) {
                $catatanTimeline .= " (Backdate: {$tglSurat->translatedFormat('d F Y')})";
                if ($alasanBackdate) {
                    $catatanTimeline .= " - Alasan: {$alasanBackdate}";
                }
            }
            if ($isManual) {
                $catatanTimeline .= " [Kustomisasi Manual/Sisipan]";
            }

            SuratRiwayat::create([
                'surat_id' => $surat->id,
                'unit_asal_id' => $surat->unit_pengirim_id,
                'unit_tujuan_id' => $surat->unit_pengirim_id,
                'user_aktor_id' => $userId,
                'status' => 'DIPERBARUI',
                'catatan' => $catatanTimeline,
                'actioned_at' => Carbon::now(),
            ]);

            return $nomorLengkap;
        });
    }
}
