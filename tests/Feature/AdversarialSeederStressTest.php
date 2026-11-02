<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\FormatNomorSurat;
use App\Models\Jabatan;
use App\Models\JenisUnit;
use App\Models\NomorSuratLog;
use App\Models\Surat;
use App\Models\SuratKomentar;
use App\Models\SuratRiwayat;
use App\Models\SuratTtd;
use App\Models\Template;
use App\Models\TemplateKategori;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserPegawai;
use App\Models\UserPegawaiJabatan;
use Database\Seeders\DatabaseCleanupSeeder;
use Database\Seeders\UeqMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdversarialSeederStressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Standard setup: admin account (id: 1)
        User::create([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'password' => Hash::make('password'),
            'tipe_entitas' => 'ADMIN',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Consecutive Triple Seeding Challenge:
     * Execute UeqMasterDataSeeder 3 consecutive times in a row.
     * Verify exact record counts, zero constraint violations, zero duplicate/orphaned rows,
     * and exact primary key stability (no unnecessary delete-recreate).
     */
    public function test_consecutive_triple_seeding_is_strictly_idempotent_with_zero_constraint_violations(): void
    {
        // --- Pass 1: Initial Seed ---
        $this->seed(UeqMasterDataSeeder::class);

        $baselineUnits = UnitKerja::count();
        $baselineUsers = User::count();
        $baselineFormats = FormatNomorSurat::count();
        $baselineTemplates = Template::count();
        $baselineJenis = JenisUnit::count();
        $baselineJabatans = Jabatan::count();
        $baselinePegawai = UserPegawai::count();
        $baselinePegawaiJabatan = UserPegawaiJabatan::count();
        $baselineCategories = TemplateKategori::count();

        // Exact expected counts
        $this->assertSame(55, $baselineUnits, 'UnitKerja count must be exactly 55');
        $this->assertSame(15, $baselineUsers, 'User count must be exactly 15 (1 admin + 14 UEQ)');
        $this->assertSame(5, $baselineFormats, 'FormatNomorSurat count must be exactly 5');
        $this->assertSame(3, $baselineTemplates, 'Template count must be exactly 3');
        $this->assertSame(7, $baselineJenis, 'JenisUnit count must be exactly 7');
        $this->assertSame(14, $baselinePegawai, 'UserPegawai count must be exactly 14');
        $this->assertSame(14, $baselinePegawaiJabatan, 'UserPegawaiJabatan count must be exactly 14');
        $this->assertSame(2, $baselineCategories, 'TemplateKategori count must be exactly 2');

        // Capture snapshot of entity IDs to detect primary key drift
        $userSnapshot = User::pluck('id', 'username')->toArray();
        $unitSnapshot = UnitKerja::pluck('id', 'singkatan')->toArray();
        $formatSnapshot = FormatNomorSurat::pluck('id', 'nama_format')->toArray();
        $templateSnapshot = Template::pluck('id', 'nama_template')->toArray();

        // --- Pass 2: Second Consecutive Seed ---
        $this->seed(UeqMasterDataSeeder::class);

        $this->assertSame(55, UnitKerja::count(), 'UnitKerja count must remain exactly 55 after Pass 2');
        $this->assertSame(15, User::count(), 'User count must remain exactly 15 after Pass 2');
        $this->assertSame(5, FormatNomorSurat::count(), 'FormatNomorSurat count must remain exactly 5 after Pass 2');
        $this->assertSame(3, Template::count(), 'Template count must remain exactly 3 after Pass 2');
        $this->assertSame($baselineJenis, JenisUnit::count(), 'JenisUnit count must remain unchanged after Pass 2');
        $this->assertSame($baselineJabatans, Jabatan::count(), 'Jabatan count must remain unchanged after Pass 2');
        $this->assertSame($baselinePegawai, UserPegawai::count(), 'UserPegawai count must remain unchanged after Pass 2');
        $this->assertSame($baselinePegawaiJabatan, UserPegawaiJabatan::count(), 'UserPegawaiJabatan count must remain unchanged after Pass 2');

        // Assert IDs did not drift on Pass 2
        $this->assertEquals($userSnapshot, User::pluck('id', 'username')->toArray(), 'User IDs must not mutate on Pass 2');
        $this->assertEquals($unitSnapshot, UnitKerja::pluck('id', 'singkatan')->toArray(), 'Unit IDs must not mutate on Pass 2');
        $this->assertEquals($formatSnapshot, FormatNomorSurat::pluck('id', 'nama_format')->toArray(), 'Format IDs must not mutate on Pass 2');
        $this->assertEquals($templateSnapshot, Template::pluck('id', 'nama_template')->toArray(), 'Template IDs must not mutate on Pass 2');

        // --- Pass 3: Third Consecutive Seed ---
        $this->seed(UeqMasterDataSeeder::class);

        $this->assertSame(55, UnitKerja::count(), 'UnitKerja count must remain exactly 55 after Pass 3');
        $this->assertSame(15, User::count(), 'User count must remain exactly 15 after Pass 3');
        $this->assertSame(5, FormatNomorSurat::count(), 'FormatNomorSurat count must remain exactly 5 after Pass 3');
        $this->assertSame(3, Template::count(), 'Template count must remain exactly 3 after Pass 3');
        $this->assertSame($baselineJenis, JenisUnit::count(), 'JenisUnit count must remain unchanged after Pass 3');
        $this->assertSame($baselineJabatans, Jabatan::count(), 'Jabatan count must remain unchanged after Pass 3');
        $this->assertSame($baselinePegawai, UserPegawai::count(), 'UserPegawai count must remain unchanged after Pass 3');
        $this->assertSame($baselinePegawaiJabatan, UserPegawaiJabatan::count(), 'UserPegawaiJabatan count must remain unchanged after Pass 3');

        // Assert IDs did not drift on Pass 3
        $this->assertEquals($userSnapshot, User::pluck('id', 'username')->toArray(), 'User IDs must not mutate on Pass 3');
        $this->assertEquals($unitSnapshot, UnitKerja::pluck('id', 'singkatan')->toArray(), 'Unit IDs must not mutate on Pass 3');
        $this->assertEquals($formatSnapshot, FormatNomorSurat::pluck('id', 'nama_format')->toArray(), 'Format IDs must not mutate on Pass 3');
        $this->assertEquals($templateSnapshot, Template::pluck('id', 'nama_template')->toArray(), 'Template IDs must not mutate on Pass 3');

        // --- Deep Relational Integrity & Orphan Check ---
        // 1. Every UnitKerja with parent_id must resolve to an existing UnitKerja
        foreach (UnitKerja::whereNotNull('parent_id')->get() as $u) {
            $this->assertTrue(UnitKerja::where('id', $u->parent_id)->exists(), "Unit {$u->singkatan} parent_id {$u->parent_id} does not exist");
            $this->assertTrue(JenisUnit::where('id', $u->jenis_unit_id)->exists(), "Unit {$u->singkatan} jenis_unit_id does not exist");
        }

        // 2. Every Jabatan must resolve to an existing UnitKerja
        foreach (Jabatan::all() as $j) {
            $this->assertTrue(UnitKerja::where('id', $j->unit_kerja_id)->exists(), "Jabatan {$j->id} has invalid unit_kerja_id");
        }

        // 3. Every UserPegawai must resolve to an existing User
        foreach (UserPegawai::all() as $up) {
            $this->assertTrue(User::where('id', $up->user_id)->exists(), "UserPegawai {$up->nip} has invalid user_id");
        }

        // 4. Every UserPegawaiJabatan must resolve to valid UserPegawai, UnitKerja, and Jabatan
        foreach (UserPegawaiJabatan::all() as $upj) {
            $this->assertTrue(UserPegawai::where('id', $upj->user_pegawai_id)->exists(), "UserPegawaiJabatan {$upj->id} has invalid user_pegawai_id");
            $this->assertTrue(UnitKerja::where('id', $upj->unit_kerja_id)->exists(), "UserPegawaiJabatan {$upj->id} has invalid unit_kerja_id");
            $this->assertTrue(Jabatan::where('id', $upj->jabatan_id)->exists(), "UserPegawaiJabatan {$upj->id} has invalid jabatan_id");
        }

        // 5. Every FormatNomorSurat must resolve to an existing UnitKerja
        foreach (FormatNomorSurat::all() as $fns) {
            $this->assertTrue(UnitKerja::where('id', $fns->unit_kerja_id)->exists(), "FormatNomorSurat {$fns->nama_format} has invalid unit_kerja_id");
        }

        // 6. Every Template must resolve to an existing TemplateKategori and UnitKerja
        foreach (Template::all() as $t) {
            $this->assertTrue(TemplateKategori::where('id', $t->kategori_id)->exists(), "Template {$t->nama_template} has invalid kategori_id");
            $this->assertTrue(UnitKerja::where('id', $t->entry_point_unit_id)->exists(), "Template {$t->nama_template} has invalid entry_point_unit_id");
        }
    }

    /**
     * 2. Cleanup & Re-seeding Cycle Challenge:
     * Seed -> Clean -> Seed -> Clean -> Seed.
     * Verify the database transitions between empty (admin preserved) and fully populated
     * without primary key collisions, foreign key locks, or state corruption.
     */
    public function test_cleanup_and_reseeding_cycle_transitions_cleanly_without_collision(): void
    {
        // Phase 1: Initial Seed
        $this->seed(UeqMasterDataSeeder::class);
        $this->assertSame(15, User::count());
        $this->assertSame(55, UnitKerja::count());
        $this->assertSame(5, FormatNomorSurat::count());
        $this->assertSame(3, Template::count());

        // Phase 2: First Cleanup
        $this->seed(DatabaseCleanupSeeder::class);
        $this->assertSame(1, User::count(), 'Phase 2: Users must be exactly 1 (admin)');
        $this->assertSame(0, UnitKerja::count(), 'Phase 2: UnitKerja must be 0');
        $this->assertSame(0, FormatNomorSurat::count(), 'Phase 2: FormatNomorSurat must be 0');
        $this->assertSame(0, Template::count(), 'Phase 2: Template must be 0');
        $this->assertSame(0, UserPegawai::count(), 'Phase 2: UserPegawai must be 0');
        $this->assertSame(0, UserPegawaiJabatan::count(), 'Phase 2: UserPegawaiJabatan must be 0');
        $this->assertSame(0, Jabatan::count(), 'Phase 2: Jabatan must be 0');

        // Phase 3: Second Seed (Re-seeding)
        $this->seed(UeqMasterDataSeeder::class);
        $this->assertSame(15, User::count(), 'Phase 3: Users must be 15');
        $this->assertSame(55, UnitKerja::count(), 'Phase 3: UnitKerja must be 55');
        $this->assertSame(5, FormatNomorSurat::count(), 'Phase 3: FormatNomorSurat must be 5');
        $this->assertSame(3, Template::count(), 'Phase 3: Template must be 3');
        $this->assertSame(14, UserPegawai::count(), 'Phase 3: UserPegawai must be 14');
        $this->assertSame(14, UserPegawaiJabatan::count(), 'Phase 3: UserPegawaiJabatan must be 14');

        // Phase 4: Second Cleanup
        $this->seed(DatabaseCleanupSeeder::class);
        $this->assertSame(1, User::count(), 'Phase 4: Users must be 1 (admin)');
        $this->assertSame(0, UnitKerja::count(), 'Phase 4: UnitKerja must be 0');
        $this->assertSame(0, FormatNomorSurat::count(), 'Phase 4: FormatNomorSurat must be 0');
        $this->assertSame(0, Template::count(), 'Phase 4: Template must be 0');

        // Phase 5: Third Seed
        $this->seed(UeqMasterDataSeeder::class);
        $this->assertSame(15, User::count(), 'Phase 5: Users must be 15');
        $this->assertSame(55, UnitKerja::count(), 'Phase 5: UnitKerja must be 55');
        $this->assertSame(5, FormatNomorSurat::count(), 'Phase 5: FormatNomorSurat must be 5');
        $this->assertSame(3, Template::count(), 'Phase 5: Template must be 3');
        $this->assertSame(14, UserPegawai::count(), 'Phase 5: UserPegawai must be 14');
        $this->assertSame(14, UserPegawaiJabatan::count(), 'Phase 5: UserPegawaiJabatan must be 14');

        // Verify admin is still intact after 5 transitions
        $admin = User::find(1);
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->username);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    /**
     * 3. Admin Preservation Edge Cases:
     * Check that DatabaseCleanupSeeder never alters users.id = 1's username, email,
     * password hash, settings, or active sessions.
     */
    public function test_admin_preservation_integrity_under_cleanup(): void
    {
        // Custom admin configuration
        $admin = User::find(1);
        $originalHash = $admin->password;
        $admin->email = 'custom.admin@universitas-kadiri.ac.id';
        $admin->settings = ['theme' => 'dark', 'font' => 'sans'];
        $admin->save();

        // Populate master data
        $this->seed(UeqMasterDataSeeder::class);

        // Create sessions: 1 for admin, 1 for staff user, 1 for guest (null user_id)
        DB::table('sessions')->insert([
            [
                'id' => 'sess_admin_test_1',
                'user_id' => 1,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'AdminBrowser/1.0',
                'payload' => base64_encode('admin_session_payload'),
                'last_activity' => time(),
            ],
            [
                'id' => 'sess_staff_test_2',
                'user_id' => User::where('username', 'rektor')->first()->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'StaffBrowser/1.0',
                'payload' => base64_encode('staff_session_payload'),
                'last_activity' => time(),
            ],
            [
                'id' => 'sess_guest_test_3',
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'GuestBrowser/1.0',
                'payload' => base64_encode('guest_session_payload'),
                'last_activity' => time(),
            ],
        ]);

        $this->assertSame(3, DB::table('sessions')->count());

        // Run Cleanup
        $this->seed(DatabaseCleanupSeeder::class);

        // Verify Admin Record
        $cleanedAdmin = User::find(1);
        $this->assertNotNull($cleanedAdmin);
        $this->assertSame(1, $cleanedAdmin->id);
        $this->assertSame('admin', $cleanedAdmin->username);
        $this->assertSame('custom.admin@universitas-kadiri.ac.id', $cleanedAdmin->email);
        $this->assertSame($originalHash, $cleanedAdmin->password, 'Password hash must be bit-for-bit identical (not rehashed)');
        $this->assertSame(['theme' => 'dark', 'font' => 'sans'], $cleanedAdmin->settings);
        $this->assertSame('ADMIN', $cleanedAdmin->tipe_entitas);
        $this->assertTrue($cleanedAdmin->is_active);

        // Verify Sessions: admin session preserved, non-admin sessions purged
        $this->assertTrue(DB::table('sessions')->where('id', 'sess_admin_test_1')->exists(), 'Admin session must be preserved');
        $this->assertFalse(DB::table('sessions')->where('id', 'sess_staff_test_2')->exists(), 'Staff session must be purged');
        $this->assertFalse(DB::table('sessions')->where('id', 'sess_guest_test_3')->exists(), 'Guest session must be purged');
        $this->assertSame(1, DB::table('sessions')->count());
    }

    /**
     * 4. Admin Guard Edge Cases:
     * Check that DatabaseCleanupSeeder immediately halts if admin is missing or corrupted.
     */
    public function test_cleanup_seeder_guards_against_missing_admin(): void
    {
        // Delete admin (id 1)
        DB::table('users')->where('id', 1)->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Admin account (id: 1) missing or invalid!');

        $this->seed(DatabaseCleanupSeeder::class);
    }

    /**
     * 4b. Admin Guard Edge Cases:
     * Check that DatabaseCleanupSeeder immediately halts if users.id = 1 has username != 'admin'.
     */
    public function test_cleanup_seeder_guards_against_corrupted_admin_username(): void
    {
        // Corrupt admin username
        DB::table('users')->where('id', 1)->update(['username' => 'hacker']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Admin account (id: 1) missing or invalid!');

        $this->seed(DatabaseCleanupSeeder::class);
    }

    /**
     * 5. Active Transactional Graph Stress Test:
     * Create active letters, approval paths, comments, dispositions, logs, and signatures,
     * then challenge DatabaseCleanupSeeder to wipe them cleanly without foreign key locks.
     */
    public function test_cleanup_seeder_wipes_complex_relational_graph_with_active_letters(): void
    {
        $this->seed(UeqMasterDataSeeder::class);

        $rektorUser = User::where('username', 'rektor')->first();
        $warekUser = User::where('username', 'warek2')->first();
        $bakuUnit = UnitKerja::where('singkatan', 'BAKU')->first();
        $rekUnit = UnitKerja::where('singkatan', 'REK')->first();
        $template = Template::first();
        $format = FormatNomorSurat::first();
        $rektorPegawaiJabatan = UserPegawaiJabatan::where('unit_kerja_id', $rekUnit->id)->first();
        $jabatan = Jabatan::first();

        // 1. Create active Surat
        $surat = Surat::create([
            'template_id' => $template->id,
            'user_pegawai_jabatan_id' => $rektorPegawaiJabatan->id,
            'unit_pengirim_id' => $rekUnit->id,
            'user_pembuat_id' => $rektorUser->id,
            'perihal' => 'Surat Uji Beban Relasi',
            'nomor_surat' => '001/REK/XI/2026',
            'status_surat' => 'DIPROSES',
            'tipe_surat' => 'INTERNAL',
            'content' => ['perihal' => 'Surat Uji'],
        ]);

        // 2. Create SuratRiwayat
        SuratRiwayat::create([
            'surat_id' => $surat->id,
            'unit_asal_id' => $rekUnit->id,
            'unit_tujuan_id' => $bakuUnit->id,
            'user_aktor_id' => $rektorUser->id,
            'status' => 'DIBUAT',
            'catatan' => 'Pengajuan surat uji',
        ]);

        // 3. Create Disposisi
        Disposisi::create([
            'surat_id' => $surat->id,
            'user_pembuat_id' => $rektorUser->id,
            'unit_tujuan_id' => $bakuUnit->id,
            'user_pegawai_jabatan_id' => $rektorPegawaiJabatan->id,
            'jenis_instruksi' => 'Untuk Ditindaklanjuti',
            'tanggal_disposisi' => now(),
            'status_disposisi' => 'BARU',
            'catatan' => 'Tindak lanjuti segera',
        ]);

        // 4. Create SuratKomentar
        SuratKomentar::create([
            'surat_id' => $surat->id,
            'user_id' => $warekUser->id,
            'unit_kerja_id' => $rekUnit->id,
            'pesan' => 'Mohon disesuaikan format lampiran',
        ]);

        // 5. Create SuratTtd
        SuratTtd::create([
            'surat_id' => $surat->id,
            'user_id' => $rektorUser->id,
            'jabatan_saat_ttd' => 'Rektor',
            'unit_saat_ttd' => 'Rektorat',
            'signed_at' => now(),
            'halaman' => 1,
            'posisi_x' => 100,
            'posisi_y' => 200,
            'is_visible' => true,
        ]);

        // 6. Create NomorSuratLog
        NomorSuratLog::create([
            'format_nomor_id' => $format->id,
            'surat_id' => $surat->id,
            'user_id' => $rektorUser->id,
            'nomor_urut' => 1,
            'nomor_lengkap' => '001/REK/XI/2026',
            'tanggal_ditetapkan' => now()->toDateString(),
        ]);

        // 7. Create Notification
        DB::table('notifications')->insert([
            'id' => '00000000-0000-0000-0000-000000000001',
            'type' => 'App\\Notifications\\SuratNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $rektorUser->id,
            'data' => json_encode(['title' => 'Surat Baru']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify relational data is actively populated
        $this->assertSame(1, Surat::count());
        $this->assertSame(1, SuratRiwayat::count());
        $this->assertSame(1, Disposisi::count());
        $this->assertSame(1, SuratKomentar::count());
        $this->assertSame(1, SuratTtd::count());
        $this->assertSame(1, NomorSuratLog::count());
        $this->assertSame(1, DB::table('notifications')->count());

        // Execute DatabaseCleanupSeeder - must wipe all relational rows safely
        $this->seed(DatabaseCleanupSeeder::class);

        // Assert all transactional tables cleared
        $this->assertSame(0, Surat::count(), 'Surats must be 0');
        $this->assertSame(0, SuratRiwayat::count(), 'SuratRiwayats must be 0');
        $this->assertSame(0, Disposisi::count(), 'Disposisis must be 0');
        $this->assertSame(0, SuratKomentar::count(), 'SuratKomentars must be 0');
        $this->assertSame(0, SuratTtd::count(), 'SuratTtds must be 0');
        $this->assertSame(0, NomorSuratLog::count(), 'NomorSuratLogs must be 0');
        $this->assertSame(0, DB::table('notifications')->count(), 'Notifications must be 0');

        // Assert all master tables cleared
        $this->assertSame(0, UnitKerja::count(), 'UnitKerja must be 0');
        $this->assertSame(0, FormatNomorSurat::count(), 'FormatNomorSurat must be 0');
        $this->assertSame(0, Template::count(), 'Template must be 0');
        $this->assertSame(0, UserPegawai::count(), 'UserPegawai must be 0');
        $this->assertSame(0, UserPegawaiJabatan::count(), 'UserPegawaiJabatan must be 0');
        $this->assertSame(0, Jabatan::count(), 'Jabatan must be 0');

        // Admin strictly preserved
        $this->assertSame(1, User::count(), 'Only admin must remain in users');
        $this->assertNotNull(User::find(1));
    }

    /**
     * 6. Authentication & Persona Integrity Stress Test:
     * Verify that all 14 UEQ accounts authenticate cleanly with password123,
     * have active status, correct entity type, and valid profiles across multiple seeds.
     */
    public function test_all_14_ueq_accounts_maintain_working_auth_and_correct_passwords_across_seeding(): void
    {
        // Execute triple seeding
        $this->seed(UeqMasterDataSeeder::class);
        $this->seed(UeqMasterDataSeeder::class);
        $this->seed(UeqMasterDataSeeder::class);

        $ueqExpected = [
            'rektor' => ['nip' => '1985 03 002', 'disposisi' => true],
            'warek2' => ['nip' => '198212006', 'disposisi' => true],
            'staff.rektorat' => ['nip' => '199408012', 'disposisi' => false],
            'kepala.baku' => ['nip' => '198705003', 'disposisi' => true],
            'staff.baku' => ['nip' => '199511008', 'disposisi' => false],
            'kepala.baak' => ['nip' => '198302005', 'disposisi' => true],
            'kepala.bapsi' => ['nip' => '198909007', 'disposisi' => true],
            'dekan.ft' => ['nip' => '198104001', 'disposisi' => true],
            'staff.ft' => ['nip' => '199201015', 'disposisi' => false],
            'dekan.fik' => ['nip' => '198607004', 'disposisi' => true],
            'staff.fik' => ['nip' => '199603019', 'disposisi' => false],
            'direktur.lppm' => ['nip' => '197812002', 'disposisi' => true],
            'kepala.ppm' => ['nip' => '198406009', 'disposisi' => true],
            'kepala.kerjasama' => ['nip' => '199010011', 'disposisi' => true],
        ];

        foreach ($ueqExpected as $username => $expected) {
            // Test valid login
            $valid = Auth::attempt(['username' => $username, 'password' => 'password123']);
            $this->assertTrue($valid, "Authentication must succeed for {$username} with password123");

            $user = Auth::user();
            $this->assertSame($username, $user->username);
            $this->assertSame('STAF', $user->tipe_entitas);
            $this->assertTrue($user->is_active);

            // Test invalid password rejection
            $invalid = Auth::attempt(['username' => $username, 'password' => 'wrongpassword']);
            $this->assertFalse($invalid, "Authentication must fail for {$username} with invalid password");

            // Profile verification
            $pegawai = UserPegawai::where('user_id', $user->id)->first();
            $this->assertNotNull($pegawai, "UserPegawai record must exist for {$username}");
            $this->assertSame($expected['nip'], $pegawai->nip);

            // Assignment verification
            $assignment = UserPegawaiJabatan::where('user_pegawai_id', $pegawai->id)->first();
            $this->assertNotNull($assignment, "UserPegawaiJabatan record must exist for {$username}");
            $this->assertSame('AKTIF', $assignment->status_jabatan);
            $this->assertSame($expected['disposisi'], (bool) $assignment->can_disposisi);

            Auth::logout();
        }
    }

    /**
     * 7. Soft-Deleted Entities Cleanup Stress Test:
     * Check that soft-deleted entities (users, units) do not linger or cause orphaned records.
     */
    public function test_database_cleanup_handles_soft_deleted_records_safely(): void
    {
        $this->seed(UeqMasterDataSeeder::class);

        // Soft delete a user and a unit
        $userToDelete = User::where('username', 'staff.ft')->first();
        $userToDelete->delete();
        $this->assertTrue($userToDelete->trashed());

        $unitToDelete = UnitKerja::where('singkatan', 'BAG-KEU')->first();
        $unitToDelete->delete();
        $this->assertTrue($unitToDelete->trashed());

        // Run cleanup
        $this->seed(DatabaseCleanupSeeder::class);

        // Verify that even soft-deleted records are purged from the tables
        $this->assertSame(0, UnitKerja::withTrashed()->count(), 'All UnitKerja including soft-deleted must be wiped');
        $this->assertSame(1, User::withTrashed()->count(), 'Only admin must remain in users table');
    }
}
