<?php

namespace Tests\Feature;

use App\Models\Feriado;
use App\Services\CalendarioLaboral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Reglas del calendario laboral: fines de semana y feriados.
 *
 * Es la unica fuente de verdad para decidir si un dia es habil, por eso se
 * prueba aislada del resto del sistema.
 */
class CalendarioLaboralTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_dias_de_semana_son_habiles(): void
    {
        $calendario = new CalendarioLaboral;

        // 05 al 09 de octubre de 2026: lunes a viernes
        foreach (['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09'] as $fecha) {
            $this->assertTrue($calendario->esHabil($fecha), "{$fecha} deberia ser habil");
        }
    }

    public function test_el_sabado_y_el_domingo_no_son_habiles(): void
    {
        $calendario = new CalendarioLaboral;

        $this->assertFalse($calendario->esHabil('2026-10-10')); // sabado
        $this->assertFalse($calendario->esHabil('2026-10-11')); // domingo
    }

    public function test_acepta_un_objeto_carbon(): void
    {
        $calendario = new CalendarioLaboral;

        $this->assertTrue($calendario->esHabil(Carbon::parse('2026-10-05 14:30:00')));
        $this->assertFalse($calendario->esHabil(Carbon::parse('2026-10-10 08:00:00')));
    }

    public function test_un_feriado_no_es_habil_y_expone_su_nombre(): void
    {
        Feriado::create([
            'fecha' => '2026-10-05',
            'nombre' => 'Feriado de prueba',
        ]);

        $calendario = new CalendarioLaboral;

        $this->assertTrue($calendario->esFeriado('2026-10-05'));
        $this->assertFalse($calendario->esHabil('2026-10-05'));
        $this->assertSame('Feriado de prueba', $calendario->feriado('2026-10-05')?->nombre);
        $this->assertNull($calendario->feriado('2026-10-06'));
    }

    public function test_un_feriado_sin_nombre_tambien_bloquea_el_dia(): void
    {
        Feriado::create(['fecha' => '2026-10-06']);

        $calendario = new CalendarioLaboral;

        $this->assertTrue($calendario->esFeriado('2026-10-06'));
        $this->assertNull($calendario->feriado('2026-10-06')?->nombre);
        $this->assertFalse($calendario->esHabil('2026-10-06'));
    }

    public function test_limpiar_descarta_la_cache_por_anio(): void
    {
        $calendario = new CalendarioLaboral;

        // Primera consulta: el anio 2026 queda cacheado vacio.
        $this->assertFalse($calendario->esFeriado('2026-12-25'));

        Feriado::create([
            'fecha' => '2026-12-25',
            'nombre' => 'Navidad',
        ]);

        // Sin limpiar, se sigue viendo la cache anterior.
        $this->assertFalse($calendario->esFeriado('2026-12-25'));

        $calendario->limpiar();

        $this->assertTrue($calendario->esFeriado('2026-12-25'));
        $this->assertFalse($calendario->esHabil('2026-12-25'));
    }
}
