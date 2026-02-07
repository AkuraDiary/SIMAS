<?php

namespace Database\Factories;

use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitKerja>
 */
class UnitKerjaFactory extends Factory
{

    protected $model = UnitKerja::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_unit' => fake()->unique()->jobTitle(),
            'jenis_unit' => fake()->randomElement(['fakultas', 'non-fakultas']),
            'status_unit' => 'aktif',
        ];
    }
}
