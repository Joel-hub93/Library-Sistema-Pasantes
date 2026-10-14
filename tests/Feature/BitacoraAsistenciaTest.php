<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Pasante;
use App\Services\BitacoraAsistencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Congela el armado de bitacoras (filas dia a dia y totales del periodo).
 *
 * Es un test de caracterizacion: fija los numeros que ya producia el
 * controlador para poder extraerlos a un servicio sin cambiar el resultado.
 */
class BitacoraAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private BitacoraAsistencia $bitacora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bitacora = new BitacoraAsistencia;
    }

    private function pasante(): Pasante
    {
        return Pasante::factory()->create([
            'fecha_ingreso' => '2026-10-05',
            'estado' => '1',
        ]);
    }

    private function turno(Pasante $pasante, string $fecha, string $entrada, ?string $salida, string $jornada = 'matutina'): void
    {
        Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => $fecha,
            'jornada' => $jornada,
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
        ]);
    }

    public function test_arma_un_renglon_por_dia_habil_del_rango(): void
    {
        $pasante = $this->pasante();

        // 2026-10-05 (lunes): dos turnos, 6 h (4 de practica + 2 de apoyo)
        $this->turno($pasante, '2026-10-05', '08:00', '12:00');
        $this->turno($pasante, '2026-10-05', '14:00', '16:00', 'vespertina');

        // 2026-10-06 (martes): sin registro -> el hueco debe verse
        // 2026-10-07 (miercoles): 4 h 30 min
        $this->turno($pasante, '2026-10-07', '08:00', '12:30');

        $filas = $this->bitacora->dias($pasante, Carbon::parse('2026-10-05'), Carbon::parse('2026-10-07'));

        $this->assertCount(3, $filas);

        $this->assertSame('2026-10-05', $filas[0]['fecha']->toDateString());
        $this->assertSame('Registro completo', $filas[0]['estado']);
        $this->assertSame('06:00', $filas[0]['total']);
        $this->assertSame('04:00', $filas[0]['horas_completas']);
        $this->assertSame('02:00', $filas[0]['tiempo_adicional']);

        $this->assertSame('2026-10-06', $filas[1]['fecha']->toDateString());
        $this->assertSame('No registro asistencia', $filas[1]['estado']);
        $this->assertSame('00:00', $filas[1]['total']);

        $this->assertSame('2026-10-07', $filas[2]['fecha']->toDateString());
        $this->assertSame('04:30', $filas[2]['total']);
        $this->assertSame('00:30', $filas[2]['tiempo_adicional']);
    }

    public function test_un_turno_sin_salida_se_marca_como_pendiente(): void
    {
        $pasante = $this->pasante();
        $this->turno($pasante, '2026-10-05', '08:00', null);

        $filas = $this->bitacora->dias($pasante, Carbon::parse('2026-10-05'), Carbon::parse('2026-10-05'));

        $this->assertCount(1, $filas);
        $this->assertSame('Sin salida registrada', $filas[0]['estado']);
        $this->assertSame('00:00', $filas[0]['total']);
    }

    public function test_los_totales_separan_practica_y_apoyo_y_omiten_fines_de_semana(): void
    {
        $pasante = $this->pasante();

        $this->turno($pasante, '2026-10-05', '08:00', '12:00');
        $this->turno($pasante, '2026-10-05', '14:00', '16:00', 'vespertina');
        $this->turno($pasante, '2026-10-07', '08:00', '12:30');

        // 2026-10-10 (sabado): no debe entrar en los totales
        $this->turno($pasante, '2026-10-10', '08:00', '12:00');

        $totales = $this->bitacora->totales($pasante, Carbon::parse('2026-10-05'), Carbon::parse('2026-10-07'));

        $this->assertSame(8.0, $totales['cumplimiento']);
        $this->assertSame(2.5, $totales['apoyo']);
        $this->assertSame(10.5, $totales['registrado']);
        $this->assertSame('08:00', $totales['cumplimiento_hm']);
        $this->assertSame('02:30', $totales['apoyo_hm']);
        $this->assertSame('10:30', $totales['registrado_hm']);

        // Sin rango tambien ignora el sabado
        $this->assertSame($totales, $this->bitacora->totales($pasante));
    }
}
