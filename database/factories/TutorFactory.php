<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

class TutorFactory extends Factory
{
    protected $model = Tutor::class;

    public function definition(): array
    {
        return [
            'codigo_docente' => fake()->unique()->numerify('DOC-####'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'carrera_id' => Carrera::factory(),
            'estado' => (string) fake()->randomElement([1, 0]),
            'foto' => 'fotos/' . fake()->uuid() . '.jpg',
        ];
    }
}