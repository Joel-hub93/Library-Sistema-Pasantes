<?php

namespace Database\Factories;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarreraFactory extends Factory
{
    protected $model = Carrera::class;

    public function definition(): array
    {
        return [
            'codigo_carrera' => fake()->unique()->numerify('CAR-###'),
            'nombre' => fake()->randomElement([
                'Ingeniería en Sistemas',
                'Medicina General',
                'Derecho',
                'Arquitectura',
                'Economía',
                'Psicología',
                'Administración de Empresas',
                'Enfermería',
                'Contabilidad',
                'Diseño Gráfico',
            ]),
            'horas_practicas' => fake()->numberBetween(120, 640),
            'estado' => (string) fake()->randomElement([1, 0]),
        ];
    }
}