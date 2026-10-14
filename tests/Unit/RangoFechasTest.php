<?php

namespace Tests\Unit;

use App\Services\RangoFechas;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Reglas de normalizacion de rangos de fechas de los documentos.
 */
class RangoFechasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sin_datos_usa_los_ultimos_treinta_dias(): void
    {
        $rango = RangoFechas::deInput(null, null);

        $this->assertSame('2026-09-16', $rango->desde->toDateString());
        $this->assertSame('2026-10-15', $rango->hasta->toDateString());
    }

    public function test_el_fin_nunca_pasa_de_hoy(): void
    {
        $rango = RangoFechas::deInput('2026-10-01', '2026-12-31');

        $this->assertSame('2026-10-01', $rango->desde->toDateString());
        $this->assertSame('2026-10-15', $rango->hasta->toDateString());
    }

    public function test_el_inicio_no_supera_al_fin(): void
    {
        $rango = RangoFechas::deInput('2026-10-20', '2026-10-10');

        // El inicio se empuja hacia atras hasta quedar ordenado con el fin.
        $this->assertSame('2026-10-10', $rango->desde->toDateString());
        $this->assertSame('2026-10-10', $rango->hasta->toDateString());
    }

    public function test_el_rango_se_limita_a_un_an(): void
    {
        $rango = RangoFechas::deInput('2020-01-01', '2026-10-15');

        $this->assertSame('2025-10-15', $rango->desde->toDateString());
        $this->assertSame('2026-10-15', $rango->hasta->toDateString());
    }

    public function test_el_inicio_por_defecto_puede_venir_del_pasante(): void
    {
        $rango = RangoFechas::deInput(null, null, Carbon::parse('2026-01-10'));

        $this->assertSame('2026-01-10', $rango->desde->toDateString());
        $this->assertSame('2026-10-15', $rango->hasta->toDateString());
    }

    public function test_el_inicio_por_defecto_tambien_respeta_el_tope_de_un_an(): void
    {
        $rango = RangoFechas::deInput(null, null, Carbon::parse('2020-01-01'));

        $this->assertSame('2025-10-15', $rango->desde->toDateString());
        $this->assertSame('2026-10-15', $rango->hasta->toDateString());
    }
}
