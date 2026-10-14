<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AsistenciaCalculoTest extends TestCase
{
    use RefreshDatabase;

    private function pasante(): Pasante
    {
        $carrera = Carrera::factory()->create(['horas_practicas' => 480]);
        $tutor = Tutor::factory()->create();

        return Pasante::factory()->create([
            'carrera_id' => $carrera->id,
            'tutor_id' => $tutor->id,
            'estado' => '1',
        ]);
    }

    private function turno(
        Pasante $pasante,
        string $entrada,
        ?string $salida,
        string $jornada = 'matutina'
    ): Asistencia {
        return Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => $jornada,
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
        ]);
    }

    public function test_registro_de_cuatro_horas_cumple_la_meta_sin_apoyo(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '12:00');

        $this->assertSame(240, $turno->minutosRegistrados());
        $this->assertSame(240, $turno->minutosCumplimiento());
        $this->assertSame(0, $turno->minutosApoyo());
        $this->assertSame('Cumplido', $turno->estado());
    }

    public function test_el_excedente_no_suma_al_cumplimiento(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '14:00');

        $this->assertSame(360, $turno->minutosRegistrados());
        $this->assertSame(240, $turno->minutosCumplimiento(), 'el cumplimiento se detiene en 240');
        $this->assertSame(120, $turno->minutosApoyo());
        $this->assertSame('Cumplido con apoyo', $turno->estado());
    }

    public function test_dos_turnos_del_mismo_dia_suman_dentro_de_la_meta(): void
    {
        $pasante = $this->pasante();

        // Caso del usuario: 1h en la manana y 3h en la noche completan las 4h
        $manana = $this->turno($pasante, '08:00', '09:00');
        $noche = $this->turno($pasante, '20:00', '23:00', jornada: 'nocturna');

        $this->assertSame(60, $manana->minutosCumplimiento());
        $this->assertSame(180, $noche->minutosCumplimiento());
        $this->assertSame(0, $manana->minutosApoyo());
        $this->assertSame(0, $noche->minutosApoyo());
        $this->assertSame(240, $manana->minutosCumplimiento() + $noche->minutosCumplimiento());
    }

    public function test_un_turno_parcial_reporta_cumplimiento_parcial(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '10:00');

        $this->assertSame(120, $turno->minutosRegistrados());
        $this->assertSame(120, $turno->minutosCumplimiento());
        $this->assertSame(0, $turno->minutosApoyo());
        $this->assertSame('Cumplimiento parcial', $turno->estado());
    }

    public function test_sin_salida_no_cuenta_minutos(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', null);

        $this->assertSame(0, $turno->minutosRegistrados());
        $this->assertFalse($turno->completo());
        $this->assertSame('Salida no registrada', $turno->estado());
    }

    public function test_el_excedente_diario_tampoco_cuenta_como_cumplimiento(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '16:00');

        // 8 horas reales: 240 min cuentan y 240 quedan como apoyo.
        $this->assertSame(480, $turno->minutosRegistrados());
        $this->assertSame(240, $turno->minutosCumplimiento());
        $this->assertSame(240, $turno->minutosApoyo());
        $this->assertSame('Cumplido con apoyo', $turno->estado());
    }

    public function test_la_meta_diaria_viene_de_la_configuracion(): void
    {
        $this->assertSame(240, config('asistencias.minutos_meta_diarios'));
        $this->assertSame(240, Asistencia::minutosMetaDiarios());
    }

    public function test_la_base_de_datos_impide_dos_turnos_del_mismo_dia_y_jornada(): void
    {
        $pasante = $this->pasante();
        $this->turno($pasante, '08:00', '12:00');

        $this->expectException(QueryException::class);

        $this->turno($pasante, '14:00', '18:00');
    }

    public function test_la_base_de_datos_permite_otra_jornada_del_mismo_dia(): void
    {
        $pasante = $this->pasante();
        $this->turno($pasante, '08:00', '12:00');

        $noche = $this->turno($pasante, '20:00', '23:00', jornada: 'nocturna');

        $this->assertSame('nocturna', $noche->jornada);
        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_la_base_de_datos_rechaza_una_jornada_inexistente(): void
    {
        $pasante = $this->pasante();

        $this->expectException(QueryException::class);

        $this->turno($pasante, '08:00', '12:00', jornada: 'tardia');
    }

    public function test_borrar_un_pasante_con_asistencias_esta_bloqueado(): void
    {
        $pasante = $this->pasante();
        $this->turno($pasante, '08:00', '12:00');

        $this->expectException(QueryException::class);

        $pasante->delete();
    }

    /* ---------------- Formato de las horas para la vista ---------------- */

    public function test_el_total_de_horas_se_formatea_como_horas_minutos_segundos(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '12:00');

        // Con ceros a la izquierda, igual que las columnas TIME de MySQL
        $this->assertSame('04:00:00', $turno->totalHoras());
    }

    public function test_el_total_de_horas_usa_ceros_a_la_izquierda(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', '09:05');

        $this->assertSame('01:05:00', $turno->totalHoras());
    }

    public function test_sin_salida_el_total_de_horas_es_cero(): void
    {
        $turno = $this->turno($this->pasante(), '08:00', null);

        $this->assertSame('00:00:00', $turno->totalHoras());
    }

    public function test_el_total_de_horas_esta_topado_en_la_meta_diaria(): void
    {
        // 08:00 a 12:10 son 250 minutos: el total se corta en la meta (04:00)
        // y los 10 minutos sobrantes se reportan aparte como apoyo.
        $turno = $this->turno($this->pasante(), '08:00', '12:10');

        $this->assertSame(250, $turno->minutosRegistrados());
        $this->assertSame('04:00:00', $turno->totalHoras());
        $this->assertSame(10, $turno->minutosApoyo());
    }

    public function test_calcula_los_minutos_con_horas_en_formato_mysql(): void
    {
        // MySQL devuelve las columnas TIME como 'H:i:s', no como 'H:i'.
        $turno = new Asistencia([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
        ]);

        $this->assertSame(480, $turno->minutosRegistrados());
        $this->assertSame(240, $turno->minutosCumplimiento());
        $this->assertSame(240, $turno->minutosApoyo());
        $this->assertSame('04:00:00', $turno->totalHoras());
    }

    public function test_las_horas_se_leen_siempre_en_formato_corto(): void
    {
        // Sin normalizar, una fila recien creada muestra '08:00' y la misma fila
        // recargada de MySQL muestra '08:00:00'.
        $turno = new Asistencia([
            'hora_entrada' => '08:00:00',
            'hora_salida' => '16:00:00',
        ]);

        $this->assertSame('08:00', $turno->hora_entrada);
        $this->assertSame('16:00', $turno->hora_salida);
        $this->assertNull(Asistencia::normalizarHora(null));
        $this->assertNull((new Asistencia)->hora_salida);
    }

    public function test_los_minutos_de_hora_aceptan_ambos_formatos(): void
    {
        $this->assertSame(480, Asistencia::minutosDeHora('08:00'));
        $this->assertSame(480, Asistencia::minutosDeHora('08:00:00'));
        $this->assertSame(485, Asistencia::minutosDeHora('08:05:00'));
        $this->assertSame(960, Asistencia::minutosDeHora('16:00'));
        $this->assertSame(0, Asistencia::minutosDeHora(null));
        $this->assertSame(0, Asistencia::minutosDeHora(''));
    }

    public function test_una_hora_de_salida_anterior_no_genera_minutos_negativos(): void
    {
        $turno = new Asistencia([
            'hora_entrada' => '16:00',
            'hora_salida' => '08:00',
        ]);

        $this->assertSame(0, $turno->minutosRegistrados());
        $this->assertSame('00:00:00', $turno->totalHoras());
    }

    /* ---------------- Dias habiles ---------------- */

    public function test_los_dias_de_semana_son_habiles(): void
    {
        // 05 al 09 de octubre de 2026: lunes a viernes
        foreach (['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09'] as $fecha) {
            $this->assertTrue(Asistencia::esDiaHabil($fecha), "{$fecha} deberia ser habil");
        }
    }

    public function test_el_sabado_y_el_domingo_no_son_habiles(): void
    {
        $this->assertFalse(Asistencia::esDiaHabil('2026-10-10')); // sabado
        $this->assertFalse(Asistencia::esDiaHabil('2026-10-11')); // domingo
    }

    public function test_la_comprobacion_de_dia_habil_acepta_un_objeto_carbon(): void
    {
        $this->assertTrue(Asistencia::esDiaHabil(Carbon::parse('2026-10-05 14:30:00')));
        $this->assertFalse(Asistencia::esDiaHabil(Carbon::parse('2026-10-10 08:00:00')));
    }
}
