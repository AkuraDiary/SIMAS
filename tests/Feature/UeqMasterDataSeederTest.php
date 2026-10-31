<?php

namespace Tests\Feature;

use App\Models\FormatNomorSurat;
use App\Models\Jabatan;
use App\Models\JenisUnit;
use App\Models\Template;
use App\Models\TemplateKategori;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserPegawai;
use App\Models\UserPegawaiJabatan;
use Database\Seeders\UeqMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UeqMasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base admin user (id: 1)
        User::create([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'password' => Hash::make('password'),
            'tipe_entitas' => 'ADMIN',
            'is_active' => true,
        ]);
    }

    public function test_ueq_master_data_seeder_populates_all_entities_correctly(): void
    {
        $this->seed(UeqMasterDataSeeder::class);

        // 1. Jenis Units
        $this->assertEquals(7, JenisUnit::count());
        $expectedJenis = ['Rektorat', 'Biro', 'Lembaga', 'UPT', 'Bagian', 'Fakultas', 'Prodi'];
        foreach ($expectedJenis as $jenis) {
            $this->assertDatabaseHas('jenis_units', ['nama_jenis' => $jenis]);
        }

        // 2. Unit Kerjas & Hierarchy
        $this->assertEquals(55, UnitKerja::count());
        $this->assertGreaterThanOrEqual(40, UnitKerja::count());

        $rootUnit = UnitKerja::where('singkatan', 'REK')->first();
        $this->assertNotNull($rootUnit);
        $this->assertNull($rootUnit->parent_id);
        $this->assertEquals('Rektorat', $rootUnit->nama_unit);
        $this->assertEquals('TERBATAS_DISPOSISI', $rootUnit->getKebijakanSuratMasuk());

        // Check child units
        $baku = UnitKerja::where('singkatan', 'BAKU')->first();
        $this->assertNotNull($baku);
        $this->assertEquals($rootUnit->id, $baku->parent_id);

        $bagKeu = UnitKerja::where('singkatan', 'BAG-KEU')->first();
        $this->assertNotNull($bagKeu);
        $this->assertEquals($baku->id, $bagKeu->parent_id);

        $ft = UnitKerja::where('singkatan', 'FT')->first();
        $this->assertNotNull($ft);
        $this->assertEquals($rootUnit->id, $ft->parent_id);

        $prodiTs = UnitKerja::where('singkatan', 'PRODI-TS')->first();
        $this->assertNotNull($prodiTs);
        $this->assertEquals($ft->id, $prodiTs->parent_id);

        // 3. Jabatans
        $this->assertGreaterThanOrEqual(60, Jabatan::count());
        $this->assertDatabaseHas('jabatans', [
            'unit_kerja_id' => $rootUnit->id,
            'nama_jabatan' => 'Rektor',
            'level_jabatan' => 1,
        ]);

        // 4. Users (1 admin + 14 UEQ = 15)
        $this->assertEquals(15, User::count());
        $this->assertEquals(14, User::where('id', '!=', 1)->count());
        $this->assertEquals(14, UserPegawai::count());
        $this->assertEquals(14, UserPegawaiJabatan::count());

        // Verify key UEQ accounts
        $accounts = [
            'rektor' => ['nip' => '1985 03 002', 'unit' => 'REK', 'disposisi' => true],
            'warek2' => ['nip' => '198212006', 'unit' => 'REK', 'disposisi' => true],
            'staff.rektorat' => ['nip' => '199408012', 'unit' => 'REK', 'disposisi' => false],
            'kepala.baku' => ['nip' => '198705003', 'unit' => 'BAKU', 'disposisi' => true],
            'staff.baku' => ['nip' => '199511008', 'unit' => 'BAKU', 'disposisi' => false],
            'dekan.ft' => ['nip' => '198104001', 'unit' => 'FT', 'disposisi' => true],
            'staff.ft' => ['nip' => '199201015', 'unit' => 'FT', 'disposisi' => false],
            'dekan.fik' => ['nip' => '198607004', 'unit' => 'FIK', 'disposisi' => true],
            'staff.fik' => ['nip' => '199603019', 'unit' => 'FIK', 'disposisi' => false],
        ];

        foreach ($accounts as $username => $meta) {
            $user = User::where('username', $username)->first();
            $this->assertNotNull($user, "User {$username} should exist");
            $this->assertTrue($user->is_active, "User {$username} should be active");
            $this->assertEquals('STAF', $user->tipe_entitas);
            $this->assertTrue(Hash::check('password123', $user->password), "User {$username} password should be password123");

            $pegawai = UserPegawai::where('user_id', $user->id)->first();
            $this->assertNotNull($pegawai);
            $this->assertEquals($meta['nip'], $pegawai->nip);

            $assignment = UserPegawaiJabatan::where('user_pegawai_id', $pegawai->id)->first();
            $this->assertNotNull($assignment);
            $this->assertEquals($meta['disposisi'], $assignment->can_disposisi);
            $this->assertEquals('AKTIF', $assignment->status_jabatan);
        }

        // 5. Format Nomor Surat (5 formats)
        $this->assertEquals(5, FormatNomorSurat::count());
        $expectedFormats = [
            'Format Surat Rektorat (BAKU)',
            'Format Surat Rektorat (Sekretariat)',
            'Format Surat Fak. Ilmu Kesehatan',
            'Format Surat Fakultas Teknik',
            'Format Surat BAAK',
        ];
        foreach ($expectedFormats as $fmt) {
            $formatRecord = FormatNomorSurat::where('nama_format', $fmt)->first();
            $this->assertNotNull($formatRecord, "Format {$fmt} should exist");
            $this->assertEquals(3, $formatRecord->padding_digit);
            $this->assertEquals(0, $formatRecord->nomor_urut_terakhir);
            $this->assertEquals(2026, $formatRecord->tahun);
            $this->assertEquals('INTERNAL', $formatRecord->tipe_surat);
            $this->assertTrue($formatRecord->is_active);
        }

        // 6. Templates & Categories
        $this->assertEquals(2, TemplateKategori::count());
        $this->assertEquals(3, Template::count());

        $expectedTemplates = [
            'Surat Pemberitahuan Sterilisasi Ruang & Kegiatan Operasional',
            'Surat Pemberitahuan Penyesuaian Status Keaktifan Dosen (SISTER)',
            'Surat Pemberitahuan Libur & Cuti Bersama',
        ];
        foreach ($expectedTemplates as $tplName) {
            $tpl = Template::where('nama_template', $tplName)->first();
            $this->assertNotNull($tpl, "Template {$tplName} should exist");
            $this->assertTrue($tpl->is_active);
            $this->assertTrue($tpl->is_ttd_enabled);
            $this->assertEquals('HTML', $tpl->render_engine);
            $this->assertNotEmpty($tpl->field_variables);
            $this->assertStringContainsString('surat-header-table', $tpl->content_html);
            $this->assertStringContainsString('START:SURAT_ISI', $tpl->content_html);
            $this->assertStringContainsString('END:SURAT_ISI', $tpl->content_html);
        }
    }

    public function test_ueq_master_data_seeder_is_idempotent(): void
    {
        // First run
        $this->seed(UeqMasterDataSeeder::class);

        $initialUserCount = User::count();
        $initialUnitCount = UnitKerja::count();
        $initialFormatCount = FormatNomorSurat::count();
        $initialTemplateCount = Template::count();

        // Second run
        $this->seed(UeqMasterDataSeeder::class);

        $this->assertEquals($initialUserCount, User::count(), 'User count should remain identical on second seed');
        $this->assertEquals($initialUnitCount, UnitKerja::count(), 'Unit count should remain identical on second seed');
        $this->assertEquals($initialFormatCount, FormatNomorSurat::count(), 'Format count should remain identical on second seed');
        $this->assertEquals($initialTemplateCount, Template::count(), 'Template count should remain identical on second seed');
    }
}
