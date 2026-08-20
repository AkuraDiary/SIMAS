<?php

namespace Database\Factories;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'tipe_entitas' => 'STAF',
            'password' => Hash::make('password'),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'tipe_entitas' => 'ADMIN',
            'password' => Hash::make('password'),
        ]);
    }


    public function createMahasiswa(): static
    {
        return $this->state(fn () => [
            'username' => 'mahasiswa',
            'email' => 'mahasiswa@internal.test',
            'tipe_entitas' => 'MAHASISWA',
            'password' => Hash::make('password'),
        ]);
    }


    public function createPegawai(): static
    {
        return $this->state(fn () => [
            'username' => 'pegawai',
            'email' => 'pegawai@internal.test',
            'tipe_entitas' => 'STAF',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
