<?php

namespace Tests\Unit;

use App\Models\UnitKerja;
use App\Services\UnikLetterFormatService;
use PHPUnit\Framework\TestCase;

class UnikLetterFormatServiceTest extends TestCase
{
    private UnikLetterFormatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UnikLetterFormatService();
    }

    public function test_format_unit_recipient_name(): void
    {
        $unitFakultas = new UnitKerja(['nama_unit' => 'Fakultas Hukum']);
        $this->assertEquals('Dekan Fakultas Hukum', $this->service->formatUnitRecipientName($unitFakultas));

        $unitLembaga = new UnitKerja(['nama_unit' => 'LP3M']);
        $this->assertEquals('Direktur LP3M', $this->service->formatUnitRecipientName($unitLembaga));

        $unitBiro = new UnitKerja(['nama_unit' => 'BAKU']);
        $this->assertEquals('Kepala BAKU', $this->service->formatUnitRecipientName($unitBiro));

        $unitExplicit = new UnitKerja(['nama_unit' => 'Kepala Perpustakaan']);
        $this->assertEquals('Kepala Perpustakaan', $this->service->formatUnitRecipientName($unitExplicit));
    }

    public function test_render_kepada_yth_with_custom_text_replace(): void
    {
        $html = $this->service->renderKepadaYth(
            [],
            'Seluruh Tenaga Pendidik & Tenaga Kependidikan',
            'replace'
        );

        $this->assertStringContainsString('Seluruh Tenaga Pendidik &amp; Tenaga Kependidikan', $html);
        $this->assertStringContainsString('UNIVERSITAS KADIRI', $html);
        $this->assertStringContainsString('Tempat', $html);
    }

    public function test_render_header_structure(): void
    {
        $html = $this->service->renderHeader([
            'nomor_surat' => '029/BAKU/I/2023',
            'lampiran' => '1 Berkas',
            'perihal' => 'Pemberitahuan Sterilisasi Area',
            'tanggal_surat' => '2023-01-25',
        ]);

        $this->assertStringContainsString('029/BAKU/I/2023', $html);
        $this->assertStringContainsString('1 Berkas', $html);
        $this->assertStringContainsString('Pemberitahuan Sterilisasi Area', $html);
        $this->assertStringContainsString('Kediri, 25 Januari 2023', $html);
    }

    public function test_update_header_in_html_preserves_user_body(): void
    {
        $initialBoilerplate = $this->service->generateBoilerplate([
            'perihal' => 'Perihal Awal',
            'isi_surat' => '<p>Ini naskah rahasia yang tidak boleh hilang saat header diupdate.</p>',
        ]);

        $this->assertStringContainsString('Perihal Awal', $initialBoilerplate);
        $this->assertStringContainsString('Ini naskah rahasia yang tidak boleh hilang saat header diupdate.', $initialBoilerplate);

        $updatedHtml = $this->service->updateHeaderInHtml($initialBoilerplate, [
            'perihal' => 'Perihal Baru yang Direvisi',
            'kepada_custom' => 'Pimpinan Unit Terkait',
            'kepada_custom_mode' => 'replace',
        ]);

        // Perihal baru harus ada
        $this->assertStringContainsString('Perihal Baru yang Direvisi', $updatedHtml);
        $this->assertStringNotContainsString('Perihal Awal', $updatedHtml);
        // Teks naskah user WAJIB tetap utuh
        $this->assertStringContainsString('Ini naskah rahasia yang tidak boleh hilang saat header diupdate.', $updatedHtml);
    }
}
