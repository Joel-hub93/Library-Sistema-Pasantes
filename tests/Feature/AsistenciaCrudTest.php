<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AsistenciaCrudTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConPermisos(array $permisos): User
    {
        $rol = Role::firstOrCreate(['name' => 'test', 'guard_name' => 'web']);
        $rol->syncPermissions(
            collect($permisos)
                ->map(fn (string $n) => Permission::firstOrCreate([
                    'name' => $n,
                    'guard_name' => 'web',
                ]))
        );

        $user = User::factory()->create();
        $user->assignRole('test');

        return $user->fresh();
    }

    private function pasante(array $atributos = []): Pasante
    {
        return Pasante::factory()->create(array_merge([
            'carrera_id' => Carrera::factory()->create(['horas_practicas' => 480])->id,
            'tutor_id' => Tutor::factory()->create()->id,
            'estado' => '1',
        ], $atributos));
    }

    private function datos(array $overrides = []): array
    {
        return array_merge([
            'pasante_id' => $this->pasante()->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ], $overrides);
    }

    /* ---------------- Permisos ---------------- */

    public function test_el_listado_requiere_autenticacion(): void
    {
        $this->get(route('asistencias.index'))->assertRedirect(route('login'));
    }

    public function test_el_listado_exige_el_permiso_de_ver(): void
    {
        $this->actingAs($this->usuarioConPermisos(['crear-pasantes']));

        $this->get(route('asistencias.index'))->assertForbidden();
    }

    public function test_crear_exige_el_permiso_de_crear(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $this->get(route('asistencias.create'))->assertForbidden();
    }

    public function test_eliminar_exige_el_permiso_de_eliminar(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $asistencia = Asistencia::create($this->datos());

        $this->delete(route('asistencias.destroy', $asistencia))->assertForbidden();

        $this->assertDatabaseHas('asistencias', ['id' => $asistencia->id]);
    }

    /* ---------------- Formularios ---------------- */

    public function test_create_muestra_los_pasantes_activos(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $activo = $this->pasante(['apellidos' => 'Activo', 'nombres' => 'Uno']);
        $inactivo = $this->pasante(['apellidos' => 'Inactivo', 'nombres' => 'Dos', 'estado' => '0']);

        $this->get(route('asistencias.create'))
            ->assertOk()
            ->assertSee($activo->apellidos)
            ->assertDontSee($inactivo->apellidos);
    }

    public function test_create_ofrece_las_tres_jornadas(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->get(route('asistencias.create'))
            ->assertOk()
            ->assertSee('value="matutina"', false)
            ->assertSee('value="vespertina"', false)
            ->assertSee('value="nocturna"', false);
    }

    public function test_index_filtra_por_pasante(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $buscado = $this->pasante(['apellidos' => 'Encontrado', 'nombres' => 'Uno']);
        $otro = $this->pasante(['apellidos' => 'Otro', 'nombres' => 'Dos']);

        $primero = Asistencia::create($this->datos(['pasante_id' => $buscado->id]));
        $segundo = Asistencia::create($this->datos(['pasante_id' => $otro->id, 'fecha' => '2026-10-06']));

        $this->get(route('asistencias.index', ['pasante_id' => $buscado->id]))
            ->assertOk()
            ->assertSee('Encontrado Uno')
            ->assertViewHas('asistencias', fn ($lista) => $lista->count() === 1
                && $lista->first()->id === $primero->id
                && $lista->first()->id !== $segundo->id);
    }

    public function test_index_sin_filtro_trae_todos_los_turnos(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = $this->pasante();

        Asistencia::create($this->datos(['pasante_id' => $pasante->id, 'fecha' => '2026-10-05']));
        Asistencia::create($this->datos(['pasante_id' => $pasante->id, 'fecha' => '2026-10-06']));

        $this->get(route('asistencias.index'))
            ->assertOk()
            ->assertViewHas('asistencias', fn ($lista) => $lista->count() === 2);
    }

    public function test_index_filtra_por_fecha(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = $this->pasante(['apellidos' => 'Unico']);

        $octubre5 = Asistencia::create($this->datos(['pasante_id' => $pasante->id, 'fecha' => '2026-10-05']));
        Asistencia::create($this->datos(['pasante_id' => $pasante->id, 'fecha' => '2026-10-06']));

        $this->get(route('asistencias.index', ['fecha' => '2026-10-06']))
            ->assertOk()
            ->assertSee('06/10/2026')
            ->assertDontSee('05/10/2026')
            ->assertViewHas('asistencias', fn ($lista) => $lista->count() === 1
                && $lista->first()->id !== $octubre5->id);
    }

    public function test_index_marca_en_amarillo_y_muestra_el_apoyo_en_dias_con_doble_turno(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = $this->pasante(['apellidos' => 'Compartido', 'nombres' => 'Dia']);

        // Dos turnos el mismo dia: 5 h + 1 h = 6 h. Con la meta diaria de 4 h,
        // el apoyo del dia es de 2 h (02:00) y se repite en ambas filas.
        Asistencia::create($this->datos([
            'pasante_id' => $pasante->id,
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '13:00',
        ]));

        Asistencia::create($this->datos([
            'pasante_id' => $pasante->id,
            'jornada' => 'vespertina',
            'hora_entrada' => '14:00',
            'hora_salida' => '15:00',
        ]));

        $this->get(route('asistencias.index'))
            ->assertOk()
            ->assertSee('class="fila-dia-compartido"', false)
            ->assertSee('02:00', false)
            ->assertViewHas('apoyoPorDia', fn ($apoyo) => ($apoyo[$pasante->id.'|2026-10-05'] ?? 0) === 120)
            ->assertViewHas('diasCompartidos', fn ($dias) => in_array($pasante->id.'|2026-10-05', $dias));
    }

    public function test_index_no_pinta_los_dias_de_un_solo_turno(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = $this->pasante(['apellidos' => 'Unico', 'nombres' => 'Turno']);

        Asistencia::create($this->datos(['pasante_id' => $pasante->id]));

        $this->get(route('asistencias.index'))
            ->assertOk()
            ->assertDontSee('class="fila-dia-compartido"', false);
    }

    /* ---------------- Registrar ---------------- */

    public function test_store_guarda_el_turno_y_calcula_el_total(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $response = $this->post(route('asistencias.store'), $this->datos());

        $asistencia = Asistencia::first();

        $response->assertRedirect(route('asistencias.show', $asistencia->id));
        $response->assertSessionHas('confirmacion');

        $this->assertSame('2026-10-05', $asistencia->fecha->toDateString());
        $this->assertSame('matutina', $asistencia->jornada);
        $this->assertSame('08:00', $asistencia->hora_entrada);
        $this->assertSame('12:00', $asistencia->hora_salida);
        $this->assertSame('04:00:00', $asistencia->totalHoras());
    }

    public function test_store_admite_una_salida_vacia(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos(['hora_salida' => '']));

        $asistencia = Asistencia::first();

        $this->assertNull($asistencia->hora_salida);
        $this->assertSame('00:00:00', $asistencia->totalHoras());
        $this->assertSame('Salida no registrada', $asistencia->estado());
    }

    public function test_store_admite_cualquier_fecha_incluso_un_sabado(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos(['fecha' => '2026-10-10']));

        $this->assertDatabaseHas('asistencias', ['fecha' => '2026-10-10 00:00:00']);
    }

    public function test_store_rechaza_una_salida_anterior_a_la_entrada(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'hora_entrada' => '12:00',
            'hora_salida' => '08:00',
        ]))->assertSessionHasErrors('hora_salida');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_store_rechaza_una_jornada_inexistente(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos(['jornada' => 'tardia']))
            ->assertSessionHasErrors('jornada');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_store_rechaza_un_turno_duplicado_del_mismo_dia_y_jornada(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $datos = $this->datos();

        $this->post(route('asistencias.store'), $datos);
        $this->post(route('asistencias.store'), $datos)->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_store_permite_otro_turno_el_mismo_dia_en_otra_jornada(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $datos = $this->datos();

        $this->post(route('asistencias.store'), $datos);
        $this->post(route('asistencias.store'), array_merge($datos, [
            'jornada' => 'nocturna',
            'hora_entrada' => '20:00',
            'hora_salida' => '23:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_store_rechaza_un_pasante_inexistente(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos(['pasante_id' => 999]))
            ->assertSessionHasErrors('pasante_id');

        $this->assertDatabaseCount('asistencias', 0);
    }

    /* ---------------- Fotos ---------------- */

    public function test_store_guarda_las_fotos_en_el_disco_privado(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'foto_entrada' => UploadedFile::fake()->image('entrada.jpg'),
            'foto_salida' => UploadedFile::fake()->image('salida.jpg'),
        ]));

        $asistencia = Asistencia::first();

        $this->assertStringStartsWith('asistencias/entrada/', $asistencia->foto_entrada);
        $this->assertStringStartsWith('asistencias/salida/', $asistencia->foto_salida);

        Storage::disk('local')->assertExists($asistencia->foto_entrada);
        Storage::disk('local')->assertExists($asistencia->foto_salida);
    }

    public function test_store_acepta_registros_sin_fotos(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos());

        $asistencia = Asistencia::first();

        $this->assertNull($asistencia->foto_entrada);
        $this->assertNull($asistencia->foto_salida);
    }

    public function test_store_rechaza_un_archivo_que_no_es_imagen(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'foto_entrada' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('foto_entrada');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_la_accion_de_foto_devuelve_la_imagen(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'foto_entrada' => UploadedFile::fake()->image('entrada.jpg'),
        ]));

        $asistencia = Asistencia::first();

        $this->get(route('asistencias.foto', [$asistencia->id, 'entrada']))->assertOk();

        $this->get(route('asistencias.foto', [$asistencia->id, 'salida']))->assertNotFound();
    }

    /* ---------------- Ver ---------------- */

    public function test_show_muestra_el_turno_con_su_total(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = $this->pasante(['apellidos' => 'Perez', 'nombres' => 'Rafael']);
        $asistencia = Asistencia::create($this->datos(['pasante_id' => $pasante->id]));

        $this->get(route('asistencias.show', $asistencia))
            ->assertOk()
            ->assertSee('Perez Rafael')
            ->assertSee('05/10/2026')
            ->assertSee('Matutina')
            ->assertSee('04:00:00');
    }

    public function test_show_avisa_cuando_el_turno_cayo_en_fin_de_semana(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $sabado = Asistencia::create($this->datos(['fecha' => '2026-10-10']));

        $this->get(route('asistencias.show', $sabado))
            ->assertOk()
            ->assertSee('Fin de semana');
    }

    /* ---------------- Editar ---------------- */

    public function test_update_cambia_fecha_y_horas(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $asistencia = Asistencia::create($this->datos());

        $this->put(route('asistencias.update', $asistencia), [
            'pasante_id' => $asistencia->pasante_id,
            'fecha' => '2026-10-07',
            'jornada' => 'vespertina',
            'hora_entrada' => '13:00',
            'hora_salida' => '17:30',
        ])->assertRedirect(route('asistencias.show', $asistencia->id));

        $asistencia->refresh();

        $this->assertSame('2026-10-07', $asistencia->fecha->toDateString());
        $this->assertSame('vespertina', $asistencia->jornada);
        $this->assertSame('13:00', $asistencia->hora_entrada);
        $this->assertSame('17:30', $asistencia->hora_salida);
        $this->assertSame('04:00:00', $asistencia->totalHoras());
    }

    public function test_update_conserva_las_fotos_del_registro(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes', 'editar-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'foto_entrada' => UploadedFile::fake()->image('entrada.jpg'),
            'foto_salida' => UploadedFile::fake()->image('salida.jpg'),
        ]));

        $asistencia = Asistencia::first();

        $rutaEntrada = $asistencia->foto_entrada;
        $rutaSalida = $asistencia->foto_salida;

        $this->put(route('asistencias.update', $asistencia), [
            'pasante_id' => $asistencia->pasante_id,
            'fecha' => '2026-10-07',
            'jornada' => 'matutina',
            'hora_entrada' => '09:00',
            'hora_salida' => '13:00',
        ])->assertSessionHasNoErrors();

        $asistencia->refresh();

        $this->assertSame($rutaEntrada, $asistencia->foto_entrada);
        $this->assertSame($rutaSalida, $asistencia->foto_salida);

        Storage::disk('local')->assertExists($rutaEntrada);
        Storage::disk('local')->assertExists($rutaSalida);
    }

    public function test_update_no_acepta_fotos_nuevas(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $asistencia = Asistencia::create($this->datos());
        $rutaOriginal = $asistencia->foto_entrada;

        $this->put(route('asistencias.update', $asistencia), [
            'pasante_id' => $asistencia->pasante_id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
            'foto_entrada' => UploadedFile::fake()->image('intrusa.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame($rutaOriginal, $asistencia->refresh()->foto_entrada);
    }

    public function test_update_ignora_el_propio_registro_al_validar_el_duplicado(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $asistencia = Asistencia::create($this->datos());

        // Cambiar solo la hora no debe chocar con el propio turno
        $this->put(route('asistencias.update', $asistencia), [
            'pasante_id' => $asistencia->pasante_id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '09:00',
            'hora_salida' => '13:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('09:00', $asistencia->refresh()->hora_entrada);
    }

    public function test_update_rechaza_mover_el_turno_a_una_fecha_ya_ocupada(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $pasante = $this->pasante();

        $ocupado = Asistencia::create($this->datos([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
        ]));

        $otro = Asistencia::create($this->datos([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-06',
        ]));

        $this->put(route('asistencias.update', $otro), [
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ])->assertSessionHasErrors('fecha');

        $this->assertSame('2026-10-06', $otro->refresh()->fecha->toDateString());
        $this->assertSame('2026-10-05', $ocupado->refresh()->fecha->toDateString());
    }

    /* ---------------- Eliminar ---------------- */

    public function test_destroy_borra_el_turno_y_sus_fotos_del_disco(): void
    {
        Storage::fake('local');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes', 'eliminar-pasantes']));

        $this->post(route('asistencias.store'), $this->datos([
            'foto_entrada' => UploadedFile::fake()->image('entrada.jpg'),
            'foto_salida' => UploadedFile::fake()->image('salida.jpg'),
        ]));

        $asistencia = Asistencia::first();

        $rutaEntrada = $asistencia->foto_entrada;
        $rutaSalida = $asistencia->foto_salida;

        $this->delete(route('asistencias.destroy', $asistencia))
            ->assertRedirect(route('asistencias.index'))
            ->assertSessionHas('confirmacion');

        $this->assertDatabaseCount('asistencias', 0);

        Storage::disk('local')->assertMissing($rutaEntrada);
        Storage::disk('local')->assertMissing($rutaSalida);
    }
}
