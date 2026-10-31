<?php

namespace App\Services;

use App\Models\UnitKerja;
use Carbon\Carbon;

class UnikLetterFormatService
{
    /**
     * Format nama penerima resmi berdasarkan nama unit kerja atau pimpinan unit.
     */
    public function formatUnitRecipientName(UnitKerja $unit): string
    {
        $namaUnit = trim($unit->nama_unit);

        // Jika sudah mengandung kata jabatan (Dekan, Kepala, Direktur, Ketua)
        if (preg_match('/^(Dekan|Kepala|Direktur|Ketua|Koordinator)\b/i', $namaUnit)) {
            return $namaUnit;
        }

        // Jika unit adalah Fakultas
        if (preg_match('/^Fakultas\b/i', $namaUnit)) {
            return 'Dekan ' . $namaUnit;
        }

        // Jika unit adalah Lembaga (misal LP3M)
        if (preg_match('/^(Lembaga|LP3M|LPPM)\b/i', $namaUnit)) {
            return 'Direktur ' . $namaUnit;
        }

        // Default untuk Biro, Bagian, Pusat, Unit, Perpustakaan, dsb.
        return 'Kepala ' . $namaUnit;
    }

    /**
     * Susun blok Kepada Yth berdasarkan daftar unit dan teks kustom.
     */
    public function renderKepadaYth(array $unitIds, ?string $customText = null, string $customMode = 'replace'): string
    {
        $customText = trim((string) $customText);
        $units = !empty($unitIds)
            ? UnitKerja::whereIn('id', $unitIds)->get()->sortBy(function ($u) use ($unitIds) {
                return array_search($u->id, $unitIds);
            })
            : collect();

        $items = [];
        foreach ($units as $unit) {
            $items[] = $this->formatUnitRecipientName($unit);
        }

        $bodyHtml = '';

        if (!empty($customText) && $customMode === 'replace') {
            // Mode Menimpa: Tampilkan teks kustom (misal untuk penerima kolektif)
            $bodyHtml = '<p style="margin: 0; font-weight: bold;">' . e($customText) . '</p>';
        } else {
            // Jika ada teks tambahan di mode append
            if (!empty($customText) && $customMode === 'append') {
                $items[] = $customText;
            }

            if (count($items) === 0) {
                $bodyHtml = '<p style="margin: 0; color: #64748b;">(Pilih Unit Tujuan pada Formulir)</p>';
            } elseif (count($items) === 1) {
                $bodyHtml = '<p style="margin: 0; font-weight: bold;">' . e($items[0]) . '</p>';
            } else {
                $listHtml = '<ol style="margin: 0; padding-left: 20px; font-weight: bold;">';
                foreach ($items as $item) {
                    $listHtml .= '<li style="margin-bottom: 2px;">' . e($item) . '</li>';
                }
                $listHtml .= '</ol>';
                $bodyHtml = $listHtml;
            }
        }

        return "<!-- START:SURAT_KEPADA -->\n" .
            "<div class=\"surat-kepada-block\" style=\"margin-bottom: 20px; font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.4;\">\n" .
            "  <p style=\"margin: 0 0 4px 0;\">Kepada Yth. :</p>\n" .
            "  <div style=\"margin-left: 24px;\">\n" .
            "    " . $bodyHtml . "\n" .
            "    <p style=\"margin: 4px 0 0 0;\"><strong>UNIVERSITAS KADIRI</strong><br>Di &ndash;<br>&nbsp;&nbsp;&nbsp;&nbsp;<u>Tempat</u></p>\n" .
            "  </div>\n" .
            "</div>\n" .
            "<!-- END:SURAT_KEPADA -->";
    }

    /**
     * Susun blok metadata kepala surat (Nomor, Tanggal, Lampiran, Perihal).
     */
    public function renderHeader(array $params): string
    {
        $nomor = !empty($params['nomor_surat']) ? e($params['nomor_surat']) : '{{ nomor_surat }}';
        $lampiran = !empty($params['lampiran']) ? e($params['lampiran']) : '-';
        $perihal = !empty($params['perihal']) ? e($params['perihal']) : 'Pemberitahuan';
        $tanggal = !empty($params['tanggal_surat'])
            ? Carbon::parse($params['tanggal_surat'])->locale('id')->translatedFormat('d F Y')
            : Carbon::now()->locale('id')->translatedFormat('d F Y');
        $kota = !empty($params['kota']) ? e($params['kota']) : 'Kediri';

        return "<!-- START:SURAT_HEADER -->\n" .
            "<table class=\"surat-header-table\" style=\"width: 100%; border-collapse: collapse; margin-bottom: 18px; font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.4;\">\n" .
            "  <tr>\n" .
            "    <td style=\"width: 15%; vertical-align: top;\">Nomor</td>\n" .
            "    <td style=\"width: 3%; vertical-align: top;\">:</td>\n" .
            "    <td style=\"width: 42%; vertical-align: top;\">{$nomor}</td>\n" .
            "    <td style=\"width: 40%; text-align: right; vertical-align: top;\">{$kota}, {$tanggal}</td>\n" .
            "  </tr>\n" .
            "  <tr>\n" .
            "    <td style=\"vertical-align: top;\">Lampiran</td>\n" .
            "    <td style=\"vertical-align: top;\">:</td>\n" .
            "    <td colspan=\"2\" style=\"vertical-align: top;\">{$lampiran}</td>\n" .
            "  </tr>\n" .
            "  <tr>\n" .
            "    <td style=\"vertical-align: top;\">Perihal</td>\n" .
            "    <td style=\"vertical-align: top;\">:</td>\n" .
            "    <td colspan=\"2\" style=\"vertical-align: top;\"><strong>{$perihal}</strong></td>\n" .
            "  </tr>\n" .
            "</table>\n" .
            "<!-- END:SURAT_HEADER -->";
    }

    /**
     * Susun blok tembusan jika terdapat unit tembusan atau teks tembusan kustom.
     */
    public function renderTembusan(array $tembusanUnitIds = [], ?string $customTembusan = null): string
    {
        $customTembusan = trim((string) $customTembusan);
        $units = !empty($tembusanUnitIds)
            ? UnitKerja::whereIn('id', $tembusanUnitIds)->get()
            : collect();

        $items = [];
        foreach ($units as $unit) {
            $items[] = $this->formatUnitRecipientName($unit);
        }

        if (!empty($customTembusan)) {
            $customLines = preg_split('/[\r\n]+/', $customTembusan);
            foreach ($customLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $items[] = $line;
                }
            }
        }

        if (empty($items)) {
            return "<!-- START:SURAT_TEMBUSAN -->\n<!-- END:SURAT_TEMBUSAN -->";
        }

        $listHtml = '<ol style="margin: 0; padding-left: 20px;">';
        foreach ($items as $item) {
            $listHtml .= '<li>' . e($item) . '</li>';
        }
        $listHtml .= '</ol>';

        return "<!-- START:SURAT_TEMBUSAN -->\n" .
            "<div class=\"surat-tembusan-block\" style=\"margin-top: 28px; font-family: 'Times New Roman', Times, serif; font-size: 10pt; line-height: 1.3;\">\n" .
            "  <p style=\"margin: 0 0 4px 0;\"><u>Tembusan diberikan Kepada Yth :</u></p>\n" .
            "  " . $listHtml . "\n" .
            "</div>\n" .
            "<!-- END:SURAT_TEMBUSAN -->";
    }

    /**
     * Bangun kerangka boilerplate HTML lengkap untuk surat baru dari awal (Scratch).
     */
    public function generateBoilerplate(array $params): string
    {
        $headerHtml = $this->renderHeader($params);
        $kepadaHtml = $this->renderKepadaYth(
            $params['unit_tujuan_ids'] ?? [],
            $params['kepada_custom'] ?? null,
            $params['kepada_custom_mode'] ?? 'replace'
        );

        $tembusanHtml = $this->renderTembusan(
            $params['unit_tembusan_ids'] ?? [],
            $params['tembusan_custom'] ?? null
        );

        $isiSurat = !empty($params['isi_surat'])
            ? $params['isi_surat']
            : "<p style=\"text-align: justify; text-indent: 30px; margin-bottom: 12px;\">Dengan hormat,</p>\n" .
              "<p style=\"text-align: justify; text-indent: 30px; margin-bottom: 12px;\">Sehubungan dengan ... (tuliskan isi/tujuan surat di sini) ...</p>\n" .
              "<p style=\"text-align: justify; text-indent: 30px; margin-bottom: 12px;\">Demikian surat pemberitahuan ini disampaikan untuk dapat ditindaklanjuti sebagaimana mestinya. Atas perhatian dan kerja samanya, kami sampaikan terima kasih.</p>";

        return $headerHtml . "\n" .
            $kepadaHtml . "\n" .
            "<!-- START:SURAT_ISI -->\n" .
            "<div class=\"surat-isi-body\" style=\"font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5;\">\n" .
            $isiSurat . "\n" .
            "</div>\n" .
            "<!-- END:SURAT_ISI -->\n" .
            $tembusanHtml;
    }

    /**
     * Perbarui hanya blok header dan Kepada Yth di dalam HTML TinyMCE yang sudah ada
     * tanpa menghapus teks naskah yang sudah diketik oleh pengguna.
     */
    public function updateHeaderInHtml(string $currentHtml, array $newParams): string
    {
        $newHeader = $this->renderHeader($newParams);
        $newKepada = $this->renderKepadaYth(
            $newParams['unit_tujuan_ids'] ?? [],
            $newParams['kepada_custom'] ?? null,
            $newParams['kepada_custom_mode'] ?? 'replace'
        );
        $newTembusan = $this->renderTembusan(
            $newParams['unit_tembusan_ids'] ?? [],
            $newParams['tembusan_custom'] ?? null
        );

        $hasHeaderMarker = str_contains($currentHtml, '<!-- START:SURAT_HEADER -->');
        $hasKepadaMarker = str_contains($currentHtml, '<!-- START:SURAT_KEPADA -->');
        $hasTembusanMarker = str_contains($currentHtml, '<!-- START:SURAT_TEMBUSAN -->');

        // Jika dokumen sudah memiliki marker terstruktur
        if ($hasHeaderMarker && $hasKepadaMarker) {
            $updated = preg_replace(
                '/<!-- START:SURAT_HEADER -->.*?<!-- END:SURAT_HEADER -->/s',
                $newHeader,
                $currentHtml
            );
            $updated = preg_replace(
                '/<!-- START:SURAT_KEPADA -->.*?<!-- END:SURAT_KEPADA -->/s',
                $newKepada,
                $updated
            );

            if ($hasTembusanMarker) {
                $updated = preg_replace(
                    '/<!-- START:SURAT_TEMBUSAN -->.*?<!-- END:SURAT_TEMBUSAN -->/s',
                    $newTembusan,
                    $updated
                );
            } elseif (!empty($newTembusan)) {
                $updated .= "\n" . $newTembusan;
            }

            return $updated;
        }

        // Fallback: Jika belum ada marker, bungkus teks eksisting sebagai isi naskah
        return $newHeader . "\n" . $newKepada . "\n" .
            "<!-- START:SURAT_ISI -->\n" .
            "<div class=\"surat-isi-body\" style=\"font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5;\">\n" .
            $currentHtml . "\n" .
            "</div>\n" .
            "<!-- END:SURAT_ISI -->\n" .
            $newTembusan;
    }
}
