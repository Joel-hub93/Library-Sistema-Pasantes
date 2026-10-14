<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasanteTest extends TestCase
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

    private function datos(array $overrides = []): array
    {
        return array_merge([
            'cedula'        => '1755523952',
            'nombres'       => 'Rafael Enrique',
            'apellidos'     => 'Perez Hidalgo',
            'email'         => 'rafael@example.com',
            'telefono'      => '0985865448',
            'fecha_ingreso' => '2026-08-13',
        ], $overrides);
    }

    public function test_create_muestra_las_carreras_activas_con_sus_horas(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $carrera = Carrera::factory()->create(['nombre' => 'Derecho', 'horas_practicas' => 360, 'estado' => '1']);
        $inactiva = Carrera::factory()->create(['nombre' => 'Inactiva', 'estado' => '0']);

        $this->get(route('pasantes.create'))
            ->assertOk()
            ->assertSee($carrera->nombre)
            ->assertSee('data-horas="360"', false)
            ->assertDontSee($inactiva->nombre);
    }

    public function test_el_formulario_incluye_la_carrera_de_cada_tutor_para_filtrar(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        Carrera::factory()->create(['estado' => '1']);
        $tutor = Tutor::factory()->create(['estado' => '1']);

        $this->get(route('pasantes.create'))
            ->assertOk()
            ->assertSee('data-carrera="'.$tutor->carrera_id.'"', false);
    }

    public function test_store_registra_el_pasante_con_carrera_y_tutor(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->post(route('pasantes.store'), $this->datos([
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
        ]))->assertRedirect(route('pasantes.index'));

        $this->assertDatabaseHas('pasantes', [
            'cedula'     => '1755523952',
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
            'estado'     => '1',
        ]);

        $this->assertSame('2026-08-13', Pasante::first()->fecha_ingreso->format('Y-m-d'));
    }

    public function test_store_exige_carrera_y_tutor_validos(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('pasantes.store'), $this->datos([
            'carrera_id' => 999,
            'tutor_id'   => 999,
        ]))->assertSessionHasErrors(['carrera_id', 'tutor_id']);

        $this->assertDatabaseCount('pasantes', 0);
    }

    public function test_store_rechaza_un_tutor_de_otra_carrera(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $carrera = Carrera::factory()->create();
        $otraCarrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $otraCarrera->id]);

        $this->post(route('pasantes.store'), $this->datos([
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
        ]))->assertSessionHasErrors('tutor_id');

        $this->assertDatabaseCount('pasantes', 0);
    }

    public function test_store_ignora_la_columna_carrera_de_texto(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->post(route('pasantes.store'), $this->datos([
            'carrera'    => 'CARRERA INVENTADA',
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
        ]))->assertRedirect(route('pasantes.index'));

        $this->assertDatabaseMissing('pasantes', ['carrera' => 'CARRERA INVENTADA']);
    }

    public function test_index_muestra_el_codigo_de_carrera_y_tutor(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $carrera = Carrera::factory()->create([
            'codigo_carrera' => 'TSDS',
            'nombre' => 'Tecnologia Superior en Desarrollo de Software',
            'horas_practicas' => 480,
        ]);
        $tutor = Tutor::factory()->create([
            'codigo_docente' => 'JOAL',
            'nombres' => 'Joel',
            'apellidos' => 'Aldas',
        ]);

        Pasante::factory()->create(['carrera_id' => $carrera->id, 'tutor_id' => $tutor->id]);

        $this->get(route('pasantes.index'))
            ->assertOk()
            ->assertSee('<td title="Tecnologia Superior en Desarrollo de Software">TSDS</td>', false)
            ->assertSee('<td title="Joel Aldas">JOAL</td>', false)
            ->assertSee('480');
    }

    public function test_show_muestra_las_horas_de_la_carrera(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $pasante = Pasante::factory()->create([
            'carrera_id' => Carrera::factory()->create(['horas_practicas' => 480])->id,
        ]);

        $this->get(route('pasantes.show', $pasante))
            ->assertOk()
            ->assertSee('480');
    }

    public function test_edit_conserva_el_tutor_aunque_estea_inactivo(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $tutor = Tutor::factory()->create(['estado' => '0']);
        $pasante = Pasante::factory()->create(['tutor_id' => $tutor->id]);

        $this->get(route('pasantes.edit', $pasante))
            ->assertOk()
            ->assertSee($tutor->codigo_docente);
    }

    public function test_update_cambia_carrera_y_tutor(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $pasante = Pasante::factory()->create();
        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->put(route('pasantes.update', $pasante), $this->datos([
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
            'estado'     => '0',
        ]))->assertRedirect(route('pasantes.show', $pasante->id));

        $this->assertDatabaseHas('pasantes', [
            'id'         => $pasante->id,
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
            'estado'     => '0',
        ]);
    }

    public function test_update_rechaza_un_tutor_de_otra_carrera(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'editar-pasantes']));

        $pasante = Pasante::factory()->create();
        $carrera = Carrera::factory()->create();
        $otraCarrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $otraCarrera->id]);

        $this->put(route('pasantes.update', $pasante), $this->datos([
            'carrera_id' => $carrera->id,
            'tutor_id'   => $tutor->id,
            'estado'     => '1',
        ]))->assertSessionHasErrors('tutor_id');

        $this->assertDatabaseMissing('pasantes', [
            'id'       => $pasante->id,
            'tutor_id' => $tutor->id,
        ]);
    }

    public function test_no_se_puede_eliminar_un_tutor_con_pasantes(): void
    {
        $this->actingAs($this->usuarioConPermisos(['eliminar-tutores', 'ver-tutores']));

        $tutor = Tutor::factory()->create();
        Pasante::factory()->create(['tutor_id' => $tutor->id]);

        $this->delete(route('tutores.destroy', $tutor))
            ->assertRedirect(route('tutores.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tutores', ['id' => $tutor->id]);
    }

    public function test_no_se_puede_eliminar_una_carrera_con_pasantes(): void
    {
        $this->actingAs($this->usuarioConPermisos(['eliminar-carreras', 'ver-carreras']));

        $carrera = Carrera::factory()->create();
        Pasante::factory()->create(['carrera_id' => $carrera->id]);

        $this->delete(route('carreras.destroy', $carrera))
            ->assertRedirect(route('carreras.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('carreras', ['id' => $carrera->id]);
    }

    public function test_el_formulario_requiere_autenticacion(): void
    {
        $this->get(route('pasantes.create'))->assertRedirect(route('login'));
    }
}