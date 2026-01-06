<?php

namespace Database\Factories;

use App\Models\Colis;
use Illuminate\Database\Eloquent\Factories\Factory;

class ColisFactory extends Factory
{
    protected $model = Colis::class;

    public function definition(): array
    {
        return [
            'id_colis' => $this->faker->unique()->randomNumber(6),
            'tracking_order' => 'TRK-' . $this->faker->unique()->randomNumber(8),
            'ref_order' => 'CMD-' . $this->faker->unique()->randomNumber(6),
            'id_stats' => 10,
            'id_partenaire' => 1,
        ];
    }
}
