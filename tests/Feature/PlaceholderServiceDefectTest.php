<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Services\PlaceholderService;
use Tests\TestCase;

class PlaceholderServiceDefectTest extends TestCase
{
    protected PlaceholderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlaceholderService::class);
    }

    /**
     * Test Defect 1: Applicant signature variable parsing in field_variables
     * Ensures $template->field_variables is properly evaluated and applicant signature is rendered.
     */
    public function test_applicant_signature_in_field_variables_renders_correctly(): void
    {
        $template = new Template([
            'nama_template' => 'Surat Pengajuan Mahasiswa',
            'render_engine' => 'HTML',
            'content_html' => '<p>Yang bertanda tangan:</p><p>{{ nama }}</p><p>TTD:</p><p>{{ ttd_pemohon }}</p>',
            'field_variables' => [
                ['key' => 'nama', 'label' => 'Nama', 'type' => 'text'],
                ['key' => 'ttd_pemohon', 'label' => 'TTD Pemohon', 'type' => 'signature', 'signature_type' => 'primary']
            ]
        ]);

        $payload = [
            'nama' => 'Ahmad Dahlan',
            'ttd_pemohon_method' => 'draw',
            'ttd_pemohon_draw' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            'ttd_pemohon_width' => 140,
            'nomor_surat' => '001/MHS/2026',
            'perihal' => 'Pengajuan'
        ];

        $rendered = $this->service->renderHtml($template, $payload);

        // Must render the actual drawn signature image
        $this->assertStringContainsString('data:image/png;base64', $rendered, 'Drawn signature image must be present in rendered HTML.');
        $this->assertStringContainsString('draggable-signature', $rendered, 'Draggable signature wrapper must be present.');
        $this->assertStringNotContainsString('{{ ttd_pemohon }}', $rendered, 'Placeholder must be replaced.');
    }

    /**
     * Test Defect 2: String inputs containing regex backreference patterns (\1, $100)
     * Ensures preg_replace_callback preserves backslashes and currency symbols verbatim.
     */
    public function test_user_inputs_with_backreferences_and_dollars_are_preserved_verbatim(): void
    {
        $template = new Template([
            'nama_template' => 'Surat Pengantar Arsip & Keuangan',
            'render_engine' => 'HTML',
            'content_html' => '<p>ID: {{ kode_berkas }}</p><p>Biaya: {{ nominal }}</p><p>Path: {{ path }}</p>',
            'field_variables' => [
                ['key' => 'kode_berkas', 'label' => 'Kode Berkas', 'type' => 'text'],
                ['key' => 'nominal', 'label' => 'Nominal', 'type' => 'text'],
                ['key' => 'path', 'label' => 'Path', 'type' => 'text'],
            ]
        ]);

        $payload = [
            'kode_berkas' => 'BERKAS-\1-REG',
            'nominal' => 'USD $100 / IDR $500',
            'path' => 'C:\1_folder\sub\2_data',
            'nomor_surat' => '002/KUG/2026',
            'perihal' => 'Pengantar'
        ];

        $rendered = $this->service->renderHtml($template, $payload);

        $this->assertStringContainsString('BERKAS-\1-REG', $rendered, '\1 must not be stripped as capture group.');
        $this->assertStringContainsString('USD $100 / IDR $500', $rendered, '$100 and $500 must not be corrupted.');
        $this->assertStringContainsString('C:\1_folder\sub\2_data', $rendered, 'Backslashes must be preserved.');
    }

    /**
     * Test Defect 2 (Repeater): Repeater loops with backreferences and dollar signs
     */
    public function test_repeater_loops_preserve_backreferences_and_dollar_symbols(): void
    {
        $template = new Template([
            'nama_template' => 'Surat Rincian Biaya Repeater',
            'render_engine' => 'HTML',
            'content_html' => '<div>[loop:items]<p>{{ item_code }} - {{ price }}</p>[/loop:items]</div>',
            'field_variables' => [
                [
                    'key' => 'items',
                    'label' => 'Items',
                    'type' => 'repeater',
                    'repeater_fields' => [
                        ['key' => 'item_code', 'label' => 'Item Code'],
                        ['key' => 'price', 'label' => 'Price'],
                    ]
                ]
            ]
        ]);

        $payload = [
            'items' => [
                ['item_code' => 'ITM-\1', 'price' => '$25.00'],
                ['item_code' => 'ITM-\2', 'price' => '$50.00'],
            ],
            'nomor_surat' => '003/RPT/2026',
            'perihal' => 'Rincian'
        ];

        $rendered = $this->service->renderHtml($template, $payload);

        $this->assertStringContainsString('ITM-\1 - $25.00', $rendered);
        $this->assertStringContainsString('ITM-\2 - $50.00', $rendered);
    }
}
