<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UeqMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UeqFilamentPagesAndAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Base Admin
        User::create([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'password' => Hash::make('password'),
            'tipe_entitas' => 'ADMIN',
            'is_active' => true,
        ]);

        // 2. Populate complete UNIK master data
        $this->seed(UeqMasterDataSeeder::class);
    }

    public function test_all_14_ueq_personas_authenticate_successfully_with_password123(): void
    {
        $ueqUsernames = [
            'rektor',
            'warek2',
            'staff.rektorat',
            'kepala.baku',
            'staff.baku',
            'kepala.baak',
            'kepala.bapsi',
            'dekan.ft',
            'staff.ft',
            'dekan.fik',
            'staff.fik',
            'direktur.lppm',
            'kepala.ppm',
            'kepala.kerjasama',
        ];

        foreach ($ueqUsernames as $username) {
            $attempt = Auth::attempt(['username' => $username, 'password' => 'password123']);
            $this->assertTrue($attempt, "Authentication failed for user: {$username}");

            $user = Auth::user();
            $this->assertEquals($username, $user->username);
            $this->assertEquals('STAF', $user->tipe_entitas);
            $this->assertTrue($user->is_active);

            Auth::logout();
        }
    }

    public function test_admin_authenticates_with_password(): void
    {
        $attempt = Auth::attempt(['username' => 'admin', 'password' => 'password']);
        $this->assertTrue($attempt, 'Admin authentication failed');

        $user = Auth::user();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('ADMIN', $user->tipe_entitas);

        Auth::logout();
    }

    public function test_admin_can_access_filament_master_data_pages(): void
    {
        $admin = User::find(1);

        $urls = [
            '/internal',
            '/internal/manage-organisasi',
            '/internal/user-pegawais',
            '/internal/format-nomor-surats',
            '/internal/template-resource/templates',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($admin)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "Page {$url} returned unexpected HTTP status: " . $response->status()
            );
            if ($response->status() === 200) {
                $response->assertSee('SIMAS');
            }
        }
    }
}
