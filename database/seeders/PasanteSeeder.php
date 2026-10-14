<?php

namespace Database\Seeders;

use App\Models\Pasante;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

class PasanteSeeder extends Seeder
{
    public function run(): void
    {
        // Cada pasante se asigna a un tutor y a la carrera de ese tutor, para
        // que los datos de ejemplo sean coherentes con el filtro de tutores por
        // carrera del formulario.
        $tutores = Tutor::activos()->get();

        for ($i = 0; $i < 10; $i++) {
            $tutor = $tutores->random();

            Pasante::factory()->create([
                'carrera_id' => $tutor->carrera_id,
                'tutor_id'   => $tutor->id,
            ]);
        }
    }
}
