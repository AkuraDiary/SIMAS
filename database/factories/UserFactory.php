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
            'tipe_entitas' => 'ADMIN',
            'password' => Hash::make('password'),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'username' => 'admin',
            'email' => 'admin@internal.test',
            'tipe_entitas' => 'ADMIN',
            'password' => Hash::make('admin'),
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
