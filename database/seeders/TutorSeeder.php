<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    public function run(): void
    {
        // Cada tutor se asigna a una de las carreras ya sembradas, repartiendo
        // los tutores entre ellas (no se crean carreras nuevas).
        $carreras = Carrera::activos()->pluck('id');

        Tutor::factory(5)
            ->sequence(fn ($secuencia) => [
                'carrera_id' => $carreras[$secuencia->index % $carreras->count()],
            ])
            ->create();
    }
}
