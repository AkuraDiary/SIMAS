<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\Template;
use App\Models\UnitKerja;
use App\Models\User;
use Database\Seeders\DatabaseCleanupSeeder;
use Database\Seeders\UeqMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseCleanupSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin account
        User::create([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'password' => Hash::make('password'),
            'tipe_entitas' => 'ADMIN',
            'is_active' => true,
        ]);
    }

    public function test_cleanup_seeder_purges_all_tables_and_preserves_admin(): void
    {
        // 1. Seed master data to create records
        $this->seed(UeqMasterDataSeeder::class);

        $this->assertEquals(15, User::count());
        $this->assertEquals(55, UnitKerja::count());
        $this->assertEquals(3, Template::count());

        // 2. Run DatabaseCleanupSeeder
        $this->seed(DatabaseCleanupSeeder::class);

        // 3. Assert post-cleanup state
        $this->assertEquals(1, User::count(), 'Only admin should remain in users table');
        $admin = User::find(1);
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->username);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->assertEquals(0, UnitKerja::count(), 'Unit kerjas should be 0');
        $this->assertEquals(0, Template::count(), 'Templates should be 0');
        $this->assertEquals(0, DB::table('format_nomor_surats')->count(), 'Format nomor surats should be 0');
        $this->assertEquals(0, DB::table('jabatans')->count(), 'Jabatans should be 0');
        $this->assertEquals(0, DB::table('user_pegawai')->count(), 'User pegawai should be 0');
        $this->assertEquals(0, DB::table('user_pegawai_jabatans')->count(), 'User pegawai jabatans should be 0');
    }
}
