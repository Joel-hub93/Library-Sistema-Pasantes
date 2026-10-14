<?php

namespace Database\Factories;

use App\Models\Pasante;
use App\Models\Carrera;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pasante>
 */
class PasanteFactory extends Factory
{
    protected $model = Pasante::class;

    /**
     * Reutiliza las carreras y tutores que ya existen en la base de datos en
     * lugar de crear uno nuevo por cada pasante. Si la tabla esta vacia se crea
     * uno, para que la factory tambien funcione sola en un entorno limpio.
     */
    public function configure()
    {
        return $this->recycle(Carrera::activos()->get())
            ->recycle(Tutor::activos()->get());
    }

    public function definition(): array
    {
        return [
            'cedula' => fake()->unique()->numerify('##########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'carrera_id' => Carrera::factory()->state(['estado' => '1']),
            'telefono' => fake()->numerify('09########'),
            'foto' => 'fotos/' . fake()->uuid() . '.jpg',
            'fecha_ingreso' => fake()->date(),
            'tutor_id' => Tutor::factory()->state(['estado' => '1']),
            'estado' => (string) fake()->randomElement([1, 0]),
        ];
    }
}
