<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PasanteCumplimientoTest extends TestCase
{
    use RefreshDatabase;

    private function pasante(int $horas = 480): Pasante
    {
        $carrera = Carrera::factory()->create(['horas_practicas' => $horas]);
        $tutor = Tutor::factory()->create();

        return Pasante::factory()->create([
            'carrera_id' => $carrera->id,
            'tutor_id' => $tutor->id,
            'estado' => '1',
        ]);
    }

    /**
     * Crea un turno completo de 4 horas en una fecha dada.
     */
    private function turnoDeCuatroHoras(Pasante $pasante, string $fecha, string $jornada = 'matutina'): void
    {
        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => $fecha,
            'jornada' => $jornada,
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);
    }

    public function test_un_pasante_sin_registros_esta_en_cero(): void
    {
        $pasante = $this->pasante();

        $this->assertSame(0, $pasante->minutosAcumulados());
        $this->assertSame(0.0, $pasante->horasCumplidas());
        $this->assertSame(0.0, $pasante->porcentajeCumplimiento());
        $this->assertSame('Sin horas registradas', $pasante->estadoCumplimiento());
        $this->assertFalse($pasante->completoPracticas());
    }

    public function test_las_horas_requeridas_vienen_de_la_carrera(): void
    {
        $pasante = $this->pasante(480);

        $this->assertSame(480, $pasante->horasRequeridas());
        $this->assertSame($pasante->carrera->horas_practicas, $pasante->horasRequeridas());
    }

    public function test_un_dia_de_cuatro_horas_suma_al_cumplimiento(): void
    {
        $pasante = $this->pasante();
        $this->turnoDeCuatroHoras($pasante, '2026-10-05');

        $pasante->refresh();

        $this->assertSame(240, $pasante->minutosAcumulados());
        $this->assertSame(4.0, $pasante->horasCumplidas());
        $this->assertSame(476.0, $pasante->horasFaltantes());
        $this->assertSame('En curso', $pasante->estadoCumplimiento());
    }

    public function test_varios_dias_suman_hasta_las_horas_de_la_carrera(): void
    {
        $pasante = $this->pasante(8); // 8 horas = 2 dias completos

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');
        $this->turnoDeCuatroHoras($pasante, '2026-10-06');

        $pasante->refresh();

        $this->assertSame(480, $pasante->minutosAcumulados());
        $this->assertSame(8.0, $pasante->horasCumplidas());
        $this->assertSame(0.0, $pasante->horasFaltantes());
        $this->assertSame(100.0, $pasante->porcentajeCumplimiento());
        $this->assertTrue($pasante->completoPracticas());
        $this->assertSame('Horas de practica completadas', $pasante->estadoCumplimiento());
    }

    public function test_el_porcentaje_es_proporcional_a_las_horas_cumplidas(): void
    {
        $pasante = $this->pasante(8);
        $this->turnoDeCuatroHoras($pasante, '2026-10-05');

        $pasante->refresh();

        $this->assertSame(4.0, $pasante->horasCumplidas());
        $this->assertSame(50.0, $pasante->porcentajeCumplimiento());
    }

    public function test_el_excedente_diario_no_suma_al_cumplimiento(): void
    {
        $pasante = $this->pasante(480);

        // 8 horas en un solo dia: 4h cumplen, 4h son apoyo
        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '16:00',
        ]);

        $pasante->refresh();

        $this->assertSame(240, $pasante->minutosAcumulados(), 'solo 4h avanzan la barra');
        $this->assertSame(240, $pasante->minutosApoyoAcumulados(), 'las otras 4h son apoyo');
        $this->assertSame(4.0, $pasante->horasCumplidas());
        $this->assertSame(4.0, $pasante->horasApoyo());
    }

    public function test_dos_turnos_del_mismo_dia_no_superan_la_meta(): void
    {
        $pasante = $this->pasante(480);

        // 2h matutina + 2h nocturna = 4h en el dia, meta cumplida
        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '10:00',
        ]);
        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'nocturna',
            'hora_entrada' => '20:00',
            'hora_salida' => '22:00',
        ]);

        $pasante->refresh();

        $this->assertSame(240, $pasante->minutosAcumulados());
        $this->assertSame(0, $pasante->minutosApoyoAcumulados());
    }

    public function test_cuatro_turnos_del_mismo_dia_todavia_cumplen_solo_las_cuatro_horas(): void
    {
        $pasante = $this->pasante(480);

        foreach (['matutina', 'vespertina', 'nocturna'] as $jornada) {
            Asistencia::create([
                'pasante_id' => $pasante->id,
                'fecha' => '2026-10-05',
                'jornada' => $jornada,
                'hora_entrada' => '08:00',
                'hora_salida' => '12:00',
            ]);
        }

        $pasante->refresh();

        $this->assertSame(240, $pasante->minutosAcumulados());
        $this->assertSame(480, $pasante->minutosApoyoAcumulados(), '8h de exceso en un solo dia');
    }

    public function test_el_exceso_no_corta_las_horas_faltantes(): void
    {
        $pasante = $this->pasante(480);

        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '20:00',
        ]);

        $pasante->refresh();

        $this->assertSame(4.0, $pasante->horasCumplidas());
        $this->assertSame(476.0, $pasante->horasFaltantes());
    }

    public function test_las_horas_faltantes_nunca_son_negativas(): void
    {
        $pasante = $this->pasante(4);

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');

        $pasante->refresh();

        $this->assertTrue($pasante->completoPracticas());
        $this->assertSame(0.0, $pasante->horasFaltantes());
    }

    public function test_una_carrera_sin_horas_no_rompe_el_calculo(): void
    {
        $carrera = Carrera::factory()->create(['horas_practicas' => 0]);
        $pasante = Pasante::factory()->create([
            'carrera_id' => $carrera->id,
            'tutor_id' => Tutor::factory()->create()->id,
        ]);

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');

        $pasante->refresh();

        $this->assertSame(0, $pasante->horasRequeridas());
        $this->assertSame(0.0, $pasante->porcentajeCumplimiento());
        $this->assertFalse($pasante->completoPracticas());
        $this->assertSame('Sin horas de practica definidas', $pasante->estadoCumplimiento());
    }

    public function test_los_turnos_sin_salida_no_avanzan_el_cumplimiento(): void
    {
        $pasante = $this->pasante();

        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => null,
        ]);

        $pasante->refresh();

        $this->assertSame(0, $pasante->minutosAcumulados());
        $this->assertSame(0.0, $pasante->porcentajeCumplimiento());
    }

    public function test_el_pasante_puede_registrar_despues_de_completar_las_horas(): void
    {
        $pasante = $this->pasante(4);
        $this->turnoDeCuatroHoras($pasante, '2026-10-05');

        $pasante->refresh();
        $this->assertTrue($pasante->completoPracticas());

        // El sistema no bloquea: sigue admitting registros
        $this->turnoDeCuatroHoras($pasante, '2026-10-06');

        $pasante->refresh();
        $this->assertDatabaseCount('asistencias', 2);
        $this->assertSame(480, $pasante->minutosAcumulados());
    }

    public function test_la_relacion_carga_los_turnos_del_pasante(): void
    {
        $pasante = $this->pasante();
        $this->turnoDeCuatroHoras($pasante, '2026-10-05');
        $this->turnoDeCuatroHoras($pasante, '2026-10-06', 'vespertina');

        $this->assertCount(2, $pasante->asistencias()->get());
        $this->assertInstanceOf(Asistencia::class, $pasante->asistencias()->first());
    }

    public function test_el_scope_activos_filtra_por_estado(): void
    {
        $activo = $this->pasante();
        $inactivo = Pasante::factory()->create([
            'carrera_id' => $activo->carrera_id,
            'tutor_id' => $activo->tutor_id,
            'estado' => '0',
        ]);

        $resultado = Pasante::activos()->pluck('id');

        $this->assertTrue($resultado->contains($activo->id));
        $this->assertFalse($resultado->contains($inactivo->id));
    }

    public function test_el_porcentaje_no_supera_el_ciento(): void
    {
        $pasante = $this->pasante(4);

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');
        $this->turnoDeCuatroHoras($pasante, '2026-10-06');

        $pasante->refresh();

        // 8 horas cumplidas sobre 4 requeridas: el porcentaje se detiene en 100
        // y el excedente se reporta aparte, no como 200%.
        $this->assertSame(100.0, $pasante->porcentajeCumplimiento());
        $this->assertSame(8.0, $pasante->horasCumplidas());
        $this->assertTrue($pasante->completoPracticas());
    }

    /* ---------------- Promedio de asistencia ---------------- */

    public function test_el_promedio_de_asistencia_sin_registros_es_cero(): void
    {
        Carbon::setTestNow('2026-10-07 12:00:00');

        $pasante = Pasante::factory()->create([
            'carrera_id' => Carrera::factory()->create()->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'fecha_ingreso' => '2026-10-05',
        ]);

        $this->assertSame(0.0, $pasante->promedioAsistencia());
    }

    public function test_el_promedio_de_asistencia_cuenta_un_dia_sobre_los_habiles(): void
    {
        Carbon::setTestNow('2026-10-07 12:00:00');

        // Lunes 05, martes 06 y miercoles 07: tres dias habiles hasta hoy.
        $pasante = Pasante::factory()->create([
            'carrera_id' => Carrera::factory()->create()->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'fecha_ingreso' => '2026-10-05',
        ]);

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');
        $this->turnoDeCuatroHoras($pasante, '2026-10-06');

        $this->assertSame(66.67, $pasante->fresh()->promedioAsistencia());
    }

    public function test_el_promedio_de_asistencia_es_cien_al_asistir_todos_los_dias_habiles(): void
    {
        Carbon::setTestNow('2026-10-07 12:00:00');

        $pasante = Pasante::factory()->create([
            'carrera_id' => Carrera::factory()->create()->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'fecha_ingreso' => '2026-10-05',
        ]);

        $this->turnoDeCuatroHoras($pasante, '2026-10-05');
        $this->turnoDeCuatroHoras($pasante, '2026-10-06');
        $this->turnoDeCuatroHoras($pasante, '2026-10-07');

        $this->assertSame(100.0, $pasante->fresh()->promedioAsistencia());
    }

    public function test_el_fin_de_semana_no_baja_el_promedio_de_asistencia(): void
    {
        // Viernes 09 al lunes 12: solo cuentan viernes y lunes.
        Carbon::setTestNow('2026-10-12 12:00:00');

        $pasante = Pasante::factory()->create([
            'carrera_id' => Carrera::factory()->create()->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'fecha_ingreso' => '2026-10-09',
        ]);

        $this->turnoDeCuatroHoras($pasante, '2026-10-09');

        $this->assertSame(50.0, $pasante->fresh()->promedioAsistencia());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
