<?php
namespace Database\Factories;

use App\Models\Gestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class GestionFactory extends Factory
{
    protected $model = Gestion::class;

    public function definition()
    {
        /* return [
            'gestion' => $this->faker->date(),
            'monto' => $this->faker->randomFloat(2, 100, 10000),
        ]; */
    }
}
