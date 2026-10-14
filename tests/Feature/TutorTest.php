<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TutorTest extends TestCase
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
            'codigo_docente' => 'DOC-1000',
            'nombres'        => 'Ana',
            'apellidos'      => 'Lopez',
            'correo'         => 'ana@example.com',
        ], $overrides);
    }

    public function test_store_registra_el_tutor_con_su_carrera(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-tutores', 'crear-tutores']));

        $carrera = Carrera::factory()->create();

        $this->post(route('tutores.store'), $this->datos(['carrera_id' => $carrera->id]))
            ->assertRedirect(route('tutores.index'));

        $this->assertDatabaseHas('tutores', [
            'codigo_docente' => 'DOC-1000',
            'carrera_id'     => $carrera->id,
        ]);
    }

    public function test_store_exige_una_carrera_valida(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-tutores', 'crear-tutores']));

        $this->post(route('tutores.store'), $this->datos([
            'carrera_id' => 999,
        ]))->assertSessionHasErrors('carrera_id');

        $this->assertDatabaseMissing('tutores', ['codigo_docente' => 'DOC-1000']);
    }

    public function test_create_muestra_las_carreras_activas(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-tutores', 'crear-tutores']));

        $carrera = Carrera::factory()->create(['nombre' => 'Derecho', 'estado' => '1']);
        $inactiva = Carrera::factory()->create(['nombre' => 'Inactiva', 'estado' => '0']);

        $this->get(route('tutores.create'))
            ->assertOk()
            ->assertSee($carrera->nombre)
            ->assertDontSee($inactiva->nombre);
    }

    public function test_edit_conserva_la_carrera_aunque_estea_inactiva(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-tutores', 'editar-tutores']));

        $carrera = Carrera::factory()->create(['nombre' => 'Inactiva', 'estado' => '0']);
        $tutor = Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->get(route('tutores.edit', $tutor))
            ->assertOk()
            ->assertSee('Inactiva');
    }

    public function test_index_muestra_el_nombre_de_la_carrera(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-tutores']));

        $carrera = Carrera::factory()->create(['nombre' => 'Derecho']);
        Tutor::factory()->create(['carrera_id' => $carrera->id, 'codigo_docente' => 'JOAL']);

        $this->get(route('tutores.index'))
            ->assertOk()
            ->assertSee('JOAL')
            ->assertSee('Derecho');
    }

    public function test_no_se_puede_eliminar_una_carrera_con_tutores(): void
    {
        $this->actingAs($this->usuarioConPermisos(['eliminar-carreras', 'ver-carreras']));

        $carrera = Carrera::factory()->create();
        Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->delete(route('carreras.destroy', $carrera))
            ->assertRedirect(route('carreras.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('carreras', ['id' => $carrera->id]);
    }
}
