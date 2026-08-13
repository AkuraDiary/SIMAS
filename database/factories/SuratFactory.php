<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Surat>
 */
class SuratFactory extends Factory
{
    protected $model = Surat::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_agenda' => fake()->unique()->numerify('AG-###'),
            'nomor_surat' => fake()->unique()->numerify('SR-###/X/2026'),
            'perihal' => fake()->sentence(4),
            'isi_surat' => fake()->paragraph(),
            'tanggal_buat' => now()->subDays(rand(1, 10)),
            'tanggal_kirim' => now()->subDays(rand(0, 5)),
            'status_surat' => fake()->randomElement([
                'TERKIRIM',
                'DIPROSES',
                'SELESAI',
            ]),
            'unit_pengirim_id' => UnitKerja::inRandomOrder()->first()->id,
            'user_pembuat_id' => User::where('peran', 'stafunit')->inRandomOrder()->first()->id,
        ];
    }
}
