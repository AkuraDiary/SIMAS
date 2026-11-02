<?php

namespace Tests\Unit;

use App\Models\Template;
use App\Services\FormSchemaService;
use App\Services\PlaceholderService;
use Tests\TestCase;

class PlaceholderServiceTest extends TestCase
{
    private PlaceholderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlaceholderService();
    }

    public function test_reserved_keywords_are_filtered_in_form_schema(): void
    {
        $html = '<p>{{ nomor_surat }}</p><p>{{ perihal }}</p><p>{{ kepada }}</p><p>{{ tembusan }}</p><p>{{ nama_mahasiswa }}</p>';
        $formService = new FormSchemaService();
        $fields = $formService->extractPlaceholders($html);

        // Hanya variabel kustom 'nama_mahasiswa' yang menjadi field input dinamis
        $keys = array_column($fields, 'key');
        $this->assertContains('nama_mahasiswa', $keys);
        $this->assertNotContains('nomor_surat', $keys);
        $this->assertNotContains('perihal', $keys);
        $this->assertNotContains('kepada', $keys);
        $this->assertNotContains('tembusan', $keys);
    }

    public function test_render_html_injects_reserved_unik_placeholders(): void
    {
        $template = new Template([
            'content_html' => '<h3>Perihal: {{ perihal }}</h3><p>Lampiran: {{ lampiran }}</p>{{ kepada }}<p>Isi: {{ pesan }}</p>{{ tembusan }}',
        ]);

        $rendered = $this->service->renderHtml($template, [
            'perihal' => 'Pemberitahuan Kegiatan Akademik',
            'lampiran_text' => '1 Bendel',
            'kepada_custom' => 'Seluruh Dekan Fakultas',
            'pesan' => 'Diharapkan hadir tepat waktu.',
            'tembusan_custom' => 'Arsip Universitas',
        ]);

        $this->assertStringContainsString('Pemberitahuan Kegiatan Akademik', $rendered);
        $this->assertStringContainsString('1 Bendel', $rendered);
        $this->assertStringContainsString('Seluruh Dekan Fakultas', $rendered);
        $this->assertStringContainsString('UNIVERSITAS KADIRI', $rendered);
        $this->assertStringContainsString('Diharapkan hadir tepat waktu.', $rendered);
        $this->assertStringContainsString('Tembusan diberikan Kepada Yth :', $rendered);
        $this->assertStringContainsString('Arsip Universitas', $rendered);
    }

    public function test_render_html_defaults_lampiran_to_dash_when_empty(): void
    {
        $template = new Template([
            'content_html' => '<p>Lampiran: {{ lampiran }}</p>',
        ]);

        $rendered = $this->service->renderHtml($template, []);

        $this->assertStringContainsString('Lampiran: -', $rendered);
    }
}
