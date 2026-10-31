<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\FormSchemaService;
use App\Services\PlaceholderService;
use App\Services\UnikLetterFormatService;
use Database\Seeders\UeqMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Milestone 1 (M1) - Adversarial Template Parsing & Rendering Challenge Test Suite
 *
 * Empirically stress-tests and challenges the 3 official Universitas Kadiri (UNIK)
 * templates seeded by UeqMasterDataSeeder across:
 * - PlaceholderService
 * - UnikLetterFormatService
 * - FormSchemaService
 */
class AdversarialTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    private PlaceholderService $placeholderService;
    private UnikLetterFormatService $unikFormatService;
    private FormSchemaService $formSchemaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholderService = app(PlaceholderService::class);
        $this->unikFormatService = app(UnikLetterFormatService::class);
        $this->formSchemaService = app(FormSchemaService::class);

        // Create base admin user (id: 1) required by seeder
        User::create([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'password' => Hash::make('password'),
            'tipe_entitas' => 'ADMIN',
            'is_active' => true,
        ]);

        // Seed complete UNIK master data
        $this->seed(UeqMasterDataSeeder::class);
    }

    /**
     * Helper to invoke private balanceHtmlFragment method on PlaceholderService
     */
    private function invokeBalanceHtmlFragment(string $fragment): string
    {
        $method = new ReflectionMethod(PlaceholderService::class, 'balanceHtmlFragment');
        $method->setAccessible(true);
        return $method->invoke($this->placeholderService, $fragment);
    }

    /**
     * Test 1: Verify all 3 UNIK templates are seeded with expected metadata,
     * render_engine HTML, active status, and non-empty content_html.
     */
    public function test_all_three_unik_templates_exist_with_proper_metadata(): void
    {
        $expectedTemplates = [
            'Surat Pemberitahuan Sterilisasi Ruang & Kegiatan Operasional',
            'Surat Pemberitahuan Penyesuaian Status Keaktifan Dosen (SISTER)',
            'Surat Pemberitahuan Libur & Cuti Bersama',
        ];

        foreach ($expectedTemplates as $tplName) {
            $tpl = Template::where('nama_template', $tplName)->first();
            $this->assertNotNull($tpl, "Template [{$tplName}] must exist in database");
            $this->assertTrue($tpl->is_active, "Template [{$tplName}] must be active");
            $this->assertTrue($tpl->is_ttd_enabled, "Template [{$tplName}] must have TTD enabled");
            $this->assertEquals('HTML', $tpl->render_engine, "Template [{$tplName}] must use HTML render engine");
            $this->assertEquals('INTERNAL', $tpl->tipe_surat);
            $this->assertNotEmpty($tpl->field_variables, "Template [{$tplName}] must have field_variables");
            $this->assertNotEmpty($tpl->content_html, "Template [{$tplName}] must have content_html");

            // Verify 3-column header table presence
            $this->assertStringContainsString('surat-header-table', $tpl->content_html);
            $this->assertStringContainsString("font-family: 'Times New Roman'", $tpl->content_html);
            $this->assertStringContainsString('START:SURAT_ISI', $tpl->content_html);
            $this->assertStringContainsString('END:SURAT_ISI', $tpl->content_html);
        }
    }

    /**
     * Test 2: Template 1 (Sterilisasi Lab OSCE) rendering challenge
     * - Tests single recipient, multi-recipient, custom replace, custom append, empty recipient
     * - Tests 3-column table header (borderless, Times New Roman 11pt)
     * - Tests body typography (Times New Roman 12pt)
     * - Tests clean replacement of all dynamic placeholders
     * - Verifies NO unparsed {{ ... }} remains
     */
    public function test_template_1_sterilisasi_rendering_challenge(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Sterilisasi Ruang & Kegiatan Operasional')->firstOrFail();

        $dataPayload = [
            'nomor_surat' => '029/BAKU/I/2026',
            'tanggal_surat' => '25 Januari 2026',
            'perihal' => 'Pemberitahuan Sterilisasi Ruang Ujian OSCE',
            'lampiran_text' => '1 Berkas',
            'rujukan_permohonan' => 'surat permohonan Dekan FIK No. 066/FIK UNIK/I/2026 tanggal 24 Januari 2026',
            'nama_kegiatan' => 'Ujian Kompetensi OSCE Apoteker',
            'ruang_terdampak' => 'Sekretariat Fakultas Pertanian dan Ruang Biro Bantuan Hukum (BBH)',
            'jadwal_sterilisasi' => 'tanggal 27 sampai dengan 29 Januari 2026',
        ];

        // 2a. Single recipient test
        $fakultasTeknik = UnitKerja::where('singkatan', 'FT')->firstOrFail();
        $payloadSingle = array_merge($dataPayload, [
            'unit_tujuan_ids' => [$fakultasTeknik->id],
        ]);
        $renderedSingle = $this->placeholderService->renderHtml($tpl, $payloadSingle);

        // Header table assertions
        $this->assertStringContainsString('<table class="surat-header-table"', $renderedSingle);
        $this->assertStringContainsString('border-collapse: collapse;', $renderedSingle);
        $this->assertStringNotContainsString('border="1"', $renderedSingle);
        $this->assertStringContainsString('029/BAKU/I/2026', $renderedSingle);
        $this->assertStringContainsString('Kediri, 25 Januari 2026', $renderedSingle);
        $this->assertStringContainsString('1 Berkas', $renderedSingle);
        $this->assertStringContainsString('Pemberitahuan Sterilisasi Ruang Ujian OSCE', $renderedSingle);

        // Body typography assertions
        $this->assertStringContainsString('class="surat-isi-body"', $renderedSingle);
        $this->assertStringContainsString("font-family: 'Times New Roman'", $renderedSingle);
        $this->assertStringContainsString('font-size: 12pt;', $renderedSingle);

        // Dynamic placeholders assertions
        $this->assertStringContainsString('surat permohonan Dekan FIK No. 066/FIK UNIK/I/2026 tanggal 24 Januari 2026', $renderedSingle);
        $this->assertStringContainsString('Ujian Kompetensi OSCE Apoteker', $renderedSingle);
        $this->assertStringContainsString('Sekretariat Fakultas Pertanian dan Ruang Biro Bantuan Hukum (BBH)', $renderedSingle);
        $this->assertStringContainsString('tanggal 27 sampai dengan 29 Januari 2026', $renderedSingle);

        // Recipient single assertion: single recipient must render formatted title directly without <ol>
        $this->assertStringContainsString('Dekan Fakultas Teknik', $renderedSingle);
        $this->assertStringNotContainsString('<ol style="margin: 0; padding-left: 20px;', $renderedSingle);

        // Zero unparsed curly braces assertion
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $renderedSingle, 'Output must not contain unparsed curly braces');

        // 2b. Multi-recipient test
        $fakultasFik = UnitKerja::where('singkatan', 'FIK')->firstOrFail();
        $baku = UnitKerja::where('singkatan', 'BAKU')->firstOrFail();
        $payloadMulti = array_merge($dataPayload, [
            'unit_tujuan_ids' => [$fakultasTeknik->id, $fakultasFik->id, $baku->id],
        ]);
        $renderedMulti = $this->placeholderService->renderHtml($tpl, $payloadMulti);

        $this->assertStringContainsString('<ol style="margin: 0; padding-left: 20px; font-weight: bold;">', $renderedMulti);
        $this->assertStringContainsString('Dekan Fakultas Teknik', $renderedMulti);
        $this->assertStringContainsString('Dekan Fakultas Ilmu Kesehatan', $renderedMulti);
        $this->assertStringContainsString('Kepala Biro Administrasi Keuangan dan Umum', $renderedMulti);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $renderedMulti);

        // 2c. Custom text replace mode
        $payloadCustomReplace = array_merge($dataPayload, [
            'unit_tujuan_ids' => [$fakultasTeknik->id],
            'kepada_custom' => 'Seluruh Dekan & Kepala Biro di Lingkungan Universitas Kadiri',
            'kepada_custom_mode' => 'replace',
        ]);
        $renderedCustomReplace = $this->placeholderService->renderHtml($tpl, $payloadCustomReplace);
        $this->assertStringContainsString('Seluruh Dekan &amp; Kepala Biro di Lingkungan Universitas Kadiri', $renderedCustomReplace);
        $this->assertStringNotContainsString('Dekan Fakultas Teknik', $renderedCustomReplace);

        // 2d. Custom text append mode
        $payloadCustomAppend = array_merge($dataPayload, [
            'unit_tujuan_ids' => [$fakultasTeknik->id],
            'kepada_custom' => 'Ketua Satgas Fasilitas Kampus',
            'kepada_custom_mode' => 'append',
        ]);
        $renderedCustomAppend = $this->placeholderService->renderHtml($tpl, $payloadCustomAppend);
        $this->assertStringContainsString('<ol style="margin: 0; padding-left: 20px; font-weight: bold;">', $renderedCustomAppend);
        $this->assertStringContainsString('Dekan Fakultas Teknik', $renderedCustomAppend);
        $this->assertStringContainsString('Ketua Satgas Fasilitas Kampus', $renderedCustomAppend);

        // 2e. Empty recipient mode
        $payloadEmpty = array_merge($dataPayload, [
            'unit_tujuan_ids' => [],
            'kepada_custom' => null,
        ]);
        $renderedEmpty = $this->placeholderService->renderHtml($tpl, $payloadEmpty);
        $this->assertStringContainsString('(Pilih Unit Tujuan pada Formulir)', $renderedEmpty);
    }

    /**
     * Test 3: Template 2 (Pemutakhiran SISTER Dosen) rendering challenge
     * - Tests clean replacement of nama_dosen_terkait and kategori_penyesuaian
     * - Tests typography (Times New Roman 12pt) and 3-column table
     * - Tests multi-recipient and custom replace
     * - Verifies NO unparsed {{ ... }} remains
     */
    public function test_template_2_sister_rendering_challenge(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Penyesuaian Status Keaktifan Dosen (SISTER)')->firstOrFail();

        $dataPayload = [
            'nomor_surat' => '102/REK/II/2026',
            'tanggal_surat' => '10 Februari 2026',
            'perihal' => 'Pemberitahuan Penyesuaian Status Keaktifan Dosen pada SISTER',
            'lampiran_text' => '-',
            'nama_dosen_terkait' => '1. Dr. Ahmad Fauzi, M.Kom.<br>2. Siti Nurhaliza, S.T., M.T.',
            'kategori_penyesuaian' => 'pengelolaan status keaktifan dosen pada Sistem Informasi Sumber Daya Terintegrasi (SISTER)',
            'kepada_custom' => 'Seluruh Dekan Fakultas dan Direktur Program Pascasarjana',
            'kepada_custom_mode' => 'replace',
            'tembusan_custom' => "1. Rektor Universitas Kadiri (sebagai laporan)\n2. Arsip BAAK",
        ];

        $rendered = $this->placeholderService->renderHtml($tpl, $dataPayload);

        // Header assertions
        $this->assertStringContainsString('<table class="surat-header-table"', $rendered);
        $this->assertStringContainsString('102/REK/II/2026', $rendered);
        $this->assertStringContainsString('Kediri, 10 Februari 2026', $rendered);
        $this->assertStringContainsString('Pemberitahuan Penyesuaian Status Keaktifan Dosen pada SISTER', $rendered);

        // Typography assertions
        $this->assertStringContainsString('class="surat-isi-body"', $rendered);
        $this->assertStringContainsString("font-family: 'Times New Roman'", $rendered);
        $this->assertStringContainsString('font-size: 12pt;', $rendered);

        // Body content replacement
        $this->assertStringContainsString('pengelolaan status keaktifan dosen pada Sistem Informasi Sumber Daya Terintegrasi (SISTER)', $rendered);
        $this->assertStringContainsString('1. Dr. Ahmad Fauzi, M.Kom.<br>2. Siti Nurhaliza, S.T., M.T.', $rendered);
        $this->assertStringContainsString('Cuti;', $rendered);
        $this->assertStringContainsString('Tugas di Instansi Lain;', $rendered);

        // Tembusan assertions
        $this->assertStringContainsString('Tembusan diberikan Kepada Yth :', $rendered);
        $this->assertStringContainsString('1. Rektor Universitas Kadiri (sebagai laporan)', $rendered);
        $this->assertStringContainsString('2. Arsip BAAK', $rendered);

        // Zero unparsed curly braces
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $rendered);
    }

    /**
     * Test 4: Template 3 (Hari Libur Nasional & Cuti Bersama 2026) rendering challenge
     * - Tests clean replacement of dasar_keputusan, dalam_rangka, tanggal_pelaksanaan, keterangan_layanan
     * - Tests typography (Times New Roman 12pt) and 3-column table
     * - Verifies NO unparsed {{ ... }} remains
     */
    public function test_template_3_libur_rendering_challenge(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Libur & Cuti Bersama')->firstOrFail();

        $dataPayload = [
            'nomor_surat' => '155/REK/III/2026',
            'tanggal_surat' => '15 Maret 2026',
            'perihal' => 'Pemberitahuan Libur Nasional dan Cuti Bersama Hari Raya Idul Fitri 1447 H',
            'lampiran_text' => '1 Berkas',
            'dasar_keputusan' => 'Surat Keputusan Bersama Tiga Menteri Nomor 1497 Tahun 2025',
            'dalam_rangka' => 'Hari Raya Idul Fitri 1447 Hijriah',
            'tanggal_pelaksanaan' => '18 Maret 2026 sampai dengan 26 Maret 2026',
            'keterangan_layanan' => 'Layanan akademik dan administrasi akan beroperasi normal kembali pada hari Jumat, 27 Maret 2026',
            'kepada_custom' => 'Seluruh Sivitas Akademika Universitas Kadiri',
            'kepada_custom_mode' => 'replace',
        ];

        $rendered = $this->placeholderService->renderHtml($tpl, $dataPayload);

        // Header assertions
        $this->assertStringContainsString('<table class="surat-header-table"', $rendered);
        $this->assertStringContainsString('155/REK/III/2026', $rendered);
        $this->assertStringContainsString('Kediri, 15 Maret 2026', $rendered);
        $this->assertStringContainsString('Pemberitahuan Libur Nasional dan Cuti Bersama Hari Raya Idul Fitri 1447 H', $rendered);

        // Typography assertions
        $this->assertStringContainsString('class="surat-isi-body"', $rendered);
        $this->assertStringContainsString("font-family: 'Times New Roman'", $rendered);
        $this->assertStringContainsString('font-size: 12pt;', $rendered);

        // Dynamic content assertions
        $this->assertStringContainsString('Surat Keputusan Bersama Tiga Menteri Nomor 1497 Tahun 2025', $rendered);
        $this->assertStringContainsString('Hari Raya Idul Fitri 1447 Hijriah', $rendered);
        $this->assertStringContainsString('18 Maret 2026 sampai dengan 26 Maret 2026', $rendered);
        $this->assertStringContainsString('Layanan akademik dan administrasi akan beroperasi normal kembali pada hari Jumat, 27 Maret 2026', $rendered);

        // Zero unparsed curly braces
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $rendered);
    }

    /**
     * Test 5: Reserved system keywords challenge
     * - Ensures extractPlaceholders NEVER extracts reserved system keywords:
     *   nomor_surat, tanggal_surat, lampiran, perihal, kepada, tembusan, qr_code, ttd_approver_1
     * - Ensures generateFilamentSchema NEVER creates input fields for reserved system keywords
     */
    public function test_reserved_keywords_are_never_exposed_as_user_form_inputs(): void
    {
        $reservedKeywords = [
            'nomor_surat',
            'tanggal_surat',
            'tanggal_terbit',
            'lampiran',
            'perihal',
            'kepada',
            'kepada_yth',
            'tembusan',
            'qr_code',
            'ttd_approver_1',
            'ttd_approver_2',
            'ttd_dekan',
        ];

        // 5a. Test against each template's raw content_html
        $templates = Template::all();
        $this->assertCount(3, $templates);

        foreach ($templates as $tpl) {
            $extracted = $this->formSchemaService->extractPlaceholders($tpl->content_html);
            $extractedKeys = array_column($extracted, 'key');

            foreach ($reservedKeywords as $reservedKey) {
                $this->assertNotContains(
                    $reservedKey,
                    $extractedKeys,
                    "Reserved keyword [{$reservedKey}] must NOT be extracted as dynamic placeholder for template [{$tpl->nama_template}]"
                );
            }
        }

        // 5b. Adversarial injection into generateFilamentSchema:
        // What if an attacker or misconfigured template passes reserved keywords in field_variables?
        $adversarialFieldVars = [
            ['key' => 'nomor_surat', 'label' => 'Nomor Surat', 'type' => 'text'],
            ['key' => 'tanggal_surat', 'label' => 'Tanggal Surat', 'type' => 'date'],
            ['key' => 'lampiran', 'label' => 'Lampiran', 'type' => 'text'],
            ['key' => 'perihal', 'label' => 'Perihal', 'type' => 'text'],
            ['key' => 'kepada', 'label' => 'Kepada', 'type' => 'text'],
            ['key' => 'tembusan', 'label' => 'Tembusan', 'type' => 'text'],
            ['key' => 'qr_code', 'label' => 'QR Code', 'type' => 'text'],
            ['key' => 'ttd_approver_1', 'label' => 'TTD Approver 1', 'type' => 'text'],
            ['key' => 'valid_custom_var', 'label' => 'Valid Custom Var', 'type' => 'text'],
        ];

        $generatedSchema = $this->formSchemaService->generateFilamentSchema($adversarialFieldVars);

        // Only valid_custom_var should be converted to a Filament component
        $this->assertCount(1, $generatedSchema, 'Only non-reserved custom variables must generate form inputs');
        $this->assertEquals('content.valid_custom_var', $generatedSchema[0]->getName());
    }

    /**
     * Test 6: DOM balance check (PlaceholderService::balanceHtmlFragment)
     * - Validates balanceHtmlFragment on all 3 rendered templates
     * - Stress-tests balanceHtmlFragment with adversarial unclosed/mismatched HTML tags
     */
    public function test_dom_balance_check_and_fragment_balancing(): void
    {
        // 6a. Balance check on rendered output of all 3 templates
        $templates = Template::all();
        foreach ($templates as $tpl) {
            $dummyData = [];
            foreach ($tpl->field_variables as $field) {
                $dummyData[$field['key']] = "Contoh {$field['key']}";
            }
            $dummyData['kepada_custom'] = 'Unit Kerja Terkait';
            $rendered = $this->placeholderService->renderHtml($tpl, $dummyData);

            $balanced = $this->invokeBalanceHtmlFragment($rendered);
            $this->assertNotEmpty($balanced);

            // Verify no unclosed tags remain by checking stack closure
            $this->assertStringContainsString('</table>', $balanced);
            $this->assertStringContainsString('</div>', $balanced);
        }

        // 6b. Adversarial unclosed tags
        $brokenFragment1 = '<div class="outer"><p>Paragraf belum ditutup <b>teks bold';
        $fixedFragment1 = $this->invokeBalanceHtmlFragment($brokenFragment1);
        $this->assertEquals('<div class="outer"><p>Paragraf belum ditutup <b>teks bold</b></p></div>', $fixedFragment1);

        // 6c. Adversarial orphan closing tags
        $brokenFragment2 = '</div></span><p>Teks normal</p>';
        $fixedFragment2 = $this->invokeBalanceHtmlFragment($brokenFragment2);
        $this->assertEquals('<p>Teks normal</p>', $fixedFragment2);

        // 6d. Void tags (img, br, hr) must not receive closing tags
        $voidFragment = '<p>Line 1<br><img src="test.png"><hr>Line 2</p>';
        $fixedVoid = $this->invokeBalanceHtmlFragment($voidFragment);
        $this->assertStringNotContainsString('</br>', $fixedVoid);
        $this->assertStringNotContainsString('</img>', $fixedVoid);
        $this->assertStringNotContainsString('</hr>', $fixedVoid);
        $this->assertEquals($voidFragment, $fixedVoid);
    }

    /**
     * Test 7: Adversarial injection of special characters (quotes, ampersands, tags)
     * - Tests values containing quotes, ampersands, and angle brackets
     */
    public function test_adversarial_special_character_injection(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Libur & Cuti Bersama')->firstOrFail();

        $adversarialData = [
            'dasar_keputusan' => 'SK Bersama No. 100/SK/2026 & SK No. 200/SK/2026',
            'dalam_rangka' => 'Agenda Libur "Idul Fitri" & \'Nyepi\' Tahun 2026',
            'tanggal_pelaksanaan' => '18 s.d. 26 Maret 2026 (WIB)',
            'keterangan_layanan' => 'Layanan dibuka kembali hari kerja berikutnya & normal',
            'kepada_custom' => 'Pimpinan Unit & Staff',
        ];

        $rendered = $this->placeholderService->renderHtml($tpl, $adversarialData);

        // Verify exact strings are preserved cleanly
        $this->assertStringContainsString('SK Bersama No. 100/SK/2026 & SK No. 200/SK/2026', $rendered);
        $this->assertStringContainsString('Agenda Libur "Idul Fitri" & \'Nyepi\' Tahun 2026', $rendered);
        $this->assertStringContainsString('18 s.d. 26 Maret 2026 (WIB)', $rendered);
        $this->assertStringContainsString('Layanan dibuka kembali hari kerja berikutnya & normal', $rendered);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $rendered);
    }

    /**
     * Test 8: Adversarial missing or empty placeholder data
     * - Verifies system cleans up missing placeholders and displays graceful badge/fallback
     * - Verifies no raw {{ key }} leaks into final HTML output
     */
    public function test_adversarial_missing_or_empty_placeholders(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Sterilisasi Ruang & Kegiatan Operasional')->firstOrFail();

        // Pass completely empty data
        $rendered = $this->placeholderService->renderHtml($tpl, []);

        // Raw {{ ... }} must not remain; un-filled non-ttd placeholders receive red badge [key]
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $rendered);
        $this->assertStringContainsString('[rujukan_permohonan]', $rendered);
        $this->assertStringContainsString('[nama_kegiatan]', $rendered);
        $this->assertStringContainsString('[ruang_terdampak]', $rendered);
        $this->assertStringContainsString('[jadwal_sterilisasi]', $rendered);
    }

    /**
     * Test 9: Adversarial PDF rendering mode ($forPdf = true)
     * - In PDF mode, unplaced/un-filled ttd_ placeholders must be stripped to empty string
     * - Page breaks must use <div class="page-break"></div>
     */
    public function test_adversarial_pdf_rendering_mode(): void
    {
        $tpl = Template::where('nama_template', 'Surat Pemberitahuan Libur & Cuti Bersama')->firstOrFail();

        $dataPayload = [
            'dasar_keputusan' => 'SK Tiga Menteri 2025',
            'dalam_rangka' => 'Libur Nasional 2026',
            'tanggal_pelaksanaan' => '20-25 Mei 2026',
            'keterangan_layanan' => 'Masuk kembali 26 Mei 2026',
            'kepada_custom' => 'Seluruh Pegawai',
        ];

        $renderedPdf = $this->placeholderService->renderHtml($tpl, $dataPayload, null, true);

        // In PDF mode, ttd_approver_1 should NOT produce a draggable div; it is stripped or rendered cleanly
        $this->assertStringNotContainsString('draggable-signature', $renderedPdf);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', $renderedPdf);
        $this->assertStringContainsString('.docx-preview-wrapper', $renderedPdf);
    }

    /**
     * Test 10: Bug Discovery Stress Test 1 - PlaceholderService line 308 variable typo ($templat vs $template)
     *
     * Empirical Challenger Audit:
     * Line 308 of app/Services/PlaceholderService.php reads:
     *   foreach ($templat?->field_variables ?? [] as $field)
     * Note the variable name is $templat instead of $template.
     * When a template has a field_variable with 'type' => 'signature' (applicant signature),
     * this loop is supposed to process $data[$key . '_draw'] or $data[$key . '_upload'].
     * Because $templat is undefined, null-safe operator ?-> evaluates to null,
     * and applicant signatures are SILENTLY IGNORED.
     */
    public function test_adversarial_audit_signature_field_variable_processing(): void
    {
        $customTemplate = new Template([
            'nama_template' => 'Template Uji TTD Pemohon',
            'content_html' => '<p>Tanda Tangan Pemohon: {{ ttd_pemohon }}</p>',
            'render_engine' => 'HTML',
            'field_variables' => [
                [
                    'key' => 'ttd_pemohon',
                    'label' => 'Tanda Tangan Pemohon',
                    'type' => 'signature',
                ],
            ],
        ]);

        $rendered = $this->placeholderService->renderHtml($customTemplate, [
            'ttd_pemohon_method' => 'draw',
            'ttd_pemohon_draw' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        ]);

        // If line 308 has $templat instead of $template, ttd_pemohon signature image will NOT be rendered.
        $isSignatureHandled = str_contains($rendered, 'data:image/png;base64');

        // Defect Resolved: When $template is used, $isSignatureHandled is TRUE.
        $this->assertTrue(
            $isSignatureHandled,
            'Defect Resolved: PlaceholderService line 308 evaluates $template->field_variables, rendering applicant signatures.'
        );
    }

    /**
     * Test 11: Bug Discovery Stress Test 2 - Regex Backreference Vulnerability on Backslash-Digit Inputs
     *
     * Empirical Challenger Audit:
     * Line 302 of app/Services/PlaceholderService.php reads:
     *   $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', (string) $value), $html);
     * While str_replace('$', '\$', ...) protects normal dollar amounts ($50),
     * it does NOT escape backslashes (\). Consequently, in PHP preg_replace,
     * any string containing a backslash followed by digits (e.g., '\1') is parsed
     * as a regex backreference #1. Since group 1 does not exist, it evaluates
     * to an empty string, silently deleting the content from user input!
     */
    public function test_adversarial_audit_dollar_and_backslash_replacement(): void
    {
        $customTemplate = new Template([
            'nama_template' => 'Template Uji Karakter Dollar dan Backslash',
            'content_html' => '<p>Biaya: {{ biaya }} | Catatan: {{ catatan }}</p>',
            'render_engine' => 'HTML',
        ]);

        $rendered = $this->placeholderService->renderHtml($customTemplate, [
            'biaya' => 'Biaya kompensasi $50 per peserta',
            'catatan' => 'Poin \\1 referensi',
        ]);

        // 1. Normal dollar amounts ($50) are preserved by str_replace('$', '\$', ...)
        $this->assertStringContainsString('Biaya kompensasi $50 per peserta', $rendered);

        // 2. Backslash-digit sequences (\1) are preserved verbatim by preg_replace_callback
        $this->assertStringContainsString(
            '\\1',
            $rendered,
            'Defect Resolved: Line 302 preg_replace_callback preserves backslash followed by digits (\\1) verbatim.'
        );
    }
}
