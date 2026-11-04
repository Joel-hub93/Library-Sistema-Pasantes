<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Feriado;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use App\Services\BitacoraAsistencia;
use App\Services\CalendarioLaboral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Gestion de feriados y su efecto en el resto del sistema.
 *
 * Un feriado no es solo una fila en una tabla: bloquea el marcado del pasante,
 * impide que el administrador cree asistencias y desaparece de bitacoras y
 * totales, igual que un fin de semana.
 */
class FeriadoTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConPermisos(array $permisos): User
    {
        $rol = Role::firstOrCreate(['name' => 'test', 'guard_name' => 'web']);
        $rol->syncPermissions(
            collect($permisos)
                ->map(fn (string $nombre) => Permission::firstOrCreate([
                    'name' => $nombre,
                    'guard_name' => 'web',
                ]))
        );

        $usuario = User::factory()->create();
        $usuario->assignRole('test');

        return $usuario->fresh();
    }

    private function pasante(array $atributos = []): Pasante
    {
        return Pasante::factory()->create(array_merge([
            'cedula' => fake()->unique()->numerify('##########'),
            'carrera_id' => Carrera::factory()->create(['horas_practicas' => 480])->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'estado' => '1',
        ], $atributos));
    }

    private function turno(Pasante $pasante, string $fecha, string $entrada = '08:00', string $salida = '12:00'): Asistencia
    {
        return Asistencia::create([
            'pasante_id' => $pasante->id,
            'fecha' => $fecha,
            'jornada' => 'matutina',
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
        ]);
    }

    /* ---------------- Pantalla y permisos ---------------- */

    public function test_el_listado_requiere_autenticacion(): void
    {
        $this->get(route('feriados.index'))->assertRedirect(route('login'));
    }

    public function test_el_listado_exige_el_permiso_de_ver(): void
    {
        $this->actingAs($this->usuarioConPermisos(['crear-pasantes']));

        $this->get(route('feriados.index'))->assertForbidden();
    }

    public function test_quien_puede_ver_pero_no_crear_no_entra_al_formulario(): void
    {
        $usuario = $this->usuarioConPermisos(['ver-feriados']);

        $this->actingAs($usuario)->get(route('feriados.index'))->assertOk();

        $this->actingAs($usuario)
            ->post(route('feriados.store'), ['fecha' => '2026-10-05'])
            ->assertForbidden();
    }

    /* ---------------- Alta y baja ---------------- */

    public function test_se_puede_registrar_un_feriado_sin_nombre(): void
    {
        $this->actingAs($this->usuarioConPermisos(['crear-feriados']));

        $this->post(route('feriados.store'), ['fecha' => '2026-10-05'])
            ->assertRedirect(route('feriados.index'))
            ->assertSessionHas('confirmacion');

        $this->assertDatabaseHas('feriados', [
            'fecha' => '2026-10-05 00:00:00',
            'nombre' => null,
        ]);
    }

    public function test_no_se_permite_registrar_dos_veces_la_misma_fecha(): void
    {
        $this->actingAs($this->usuarioConPermisos(['crear-feriados']));

        Feriado::create(['fecha' => '2026-10-05']);

        $this->post(route('feriados.store'), ['fecha' => '2026-10-05'])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('feriados', 1);
    }

    public function test_se_puede_eliminar_un_feriado(): void
    {
        $this->actingAs($this->usuarioConPermisos(['eliminar-feriados']));

        $feriado = Feriado::create(['fecha' => '2026-10-05']);

        $this->delete(route('feriados.destroy', $feriado))
            ->assertRedirect(route('feriados.index'));

        $this->assertDatabaseMissing('feriados', ['id' => $feriado->id]);
    }

    /* ---------------- Bloqueo de asistencias ---------------- */

    public function test_el_administrador_no_puede_crear_asistencia_en_feriado(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        Feriado::create(['fecha' => '2026-10-05', 'nombre' => 'Feriado de prueba']);

        $this->post(route('asistencias.store'), [
            'pasante_id' => $this->pasante()->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ])->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_el_administrador_puede_corregir_un_registro_que_quedo_en_feriado(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $pasante = $this->pasante();
        $turno = $this->turno($pasante, '2026-10-05');

        // El feriado se declaro despues del registro original.
        Feriado::create(['fecha' => '2026-10-05']);

        $this->put(route('asistencias.update', $turno), [
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '09:00',
            'hora_salida' => '13:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('09:00', $turno->refresh()->hora_entrada);
    }

    public function test_el_pasante_no_puede_marcar_en_feriado(): void
    {
        Storage::fake('local');

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));

        Feriado::create(['fecha' => '2026-10-05', 'nombre' => 'Feriado de prueba']);

        $this->actingAs($this->pasante(), 'pasante')
            ->from(route('asistencias.registrar'))
            ->post(route('asistencias.marcar'), [
                'tipo' => 'entrada',
                'jornada' => 'matutina',
                'foto' => UploadedFile::fake()->image('entrada.jpg'),
            ])
            ->assertRedirect(route('asistencias.registrar'))
            ->assertSessionHas(
                'error',
                'Hoy es feriado (Feriado de prueba): no se registran asistencias.'
            );

        $this->assertDatabaseCount('asistencias', 0);

        Carbon::setTestNow();
    }

    public function test_la_pantalla_del_pasante_avisa_que_hoy_es_feriado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));

        Feriado::create(['fecha' => '2026-10-05', 'nombre' => 'Feriado de prueba']);

        $this->actingAs($this->pasante(), 'pasante')
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('Hoy es feriado: Feriado de prueba');

        Carbon::setTestNow();
    }

    /* ---------------- Reportes y horas ---------------- */

    public function test_la_bitacora_oculta_los_feriados_y_no_los_suma(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Feriado::create(['fecha' => '2026-10-07']);

        // Registro historico cargado a mano: simula un turno anterior al feriado.
        $this->turno($pasante, '2026-10-07');

        $bitacora = new BitacoraAsistencia(new CalendarioLaboral);

        $fechas = collect($bitacora->dias(
            $pasante,
            Carbon::parse('2026-10-05'),
            Carbon::parse('2026-10-09')
        ))->map(fn (array $fila) => $fila['fecha']->toDateString())->all();

        $this->assertSame(
            ['2026-10-05', '2026-10-06', '2026-10-08', '2026-10-09'],
            $fechas
        );

        $totales = $bitacora->totales(
            $pasante,
            Carbon::parse('2026-10-05'),
            Carbon::parse('2026-10-09')
        );

        $this->assertSame(0.0, $totales['cumplimiento']);
    }

    public function test_las_horas_de_un_feriado_no_cuentan_para_el_cumplimiento(): void
    {
        $pasante = $this->pasante();

        Feriado::create(['fecha' => '2026-10-07']);

        $this->turno($pasante, '2026-10-07');

        $pasante->load('asistencias');

        $this->assertSame(0, $pasante->minutosAcumulados());
        $this->assertSame(0.0, $pasante->horasCumplidas());
    }

    public function test_el_feriado_no_baja_el_promedio_de_asistencia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 18:00:00'));

        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Feriado::create(['fecha' => '2026-10-07']);

        foreach (['2026-10-05', '2026-10-06', '2026-10-08', '2026-10-09'] as $fecha) {
            $this->turno($pasante, $fecha);
        }

        $pasante->load('asistencias');

        $this->assertSame(100.0, $pasante->promedioAsistencia());

        Carbon::setTestNow();
    }
}
