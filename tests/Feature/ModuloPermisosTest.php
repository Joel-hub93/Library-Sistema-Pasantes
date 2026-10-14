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

class ModuloPermisosTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISOS_SUBADMIN = [
        'ver-pasantes',
        'crear-pasantes',
        'editar-pasantes',
        'ver-carreras',
        'ver-tutores',
        'ver-reporte-asistencias',
    ];

    private function crearRol(string $nombre, array $permisos): Role
    {
        $rol = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        $rol->syncPermissions(
            collect($permisos)->map(fn (string $n) => Permission::firstOrCreate(['name' => $n, 'guard_name' => 'web']))
        );

        return $rol;
    }

    private function usuarioSinRol(): User
    {
        return User::factory()->create(['estado' => '1']);
    }

    private function usuarioConRolSubadmin(): User
    {
        $this->crearRol('subadmin', self::PERMISOS_SUBADMIN);
        $usuario = $this->usuarioSinRol();
        $usuario->assignRole('subadmin');

        return $usuario->fresh();
    }

    private function usuarioConRolAdmin(): User
    {
        $this->crearRol('admin', ['ver-pasantes', 'ver-carreras', 'ver-tutores']);
        $usuario = $this->usuarioSinRol();
        $usuario->assignRole('admin');

        return $usuario->fresh();
    }

    public function test_subadmin_puede_crear_pasantes_pero_no_eliminarlos(): void
    {
        $subadmin = $this->usuarioConRolSubadmin();
        $pasante = Pasante::factory()->create();

        $this->actingAs($subadmin)
            ->get(route('pasantes.create'))
            ->assertOk();

        $this->actingAs($subadmin)
            ->delete(route('pasantes.destroy', $pasante))
            ->assertForbidden();

        $this->assertDatabaseHas('pasantes', ['id' => $pasante->id]);
    }

    public function test_subadmin_puede_ver_carreras_pero_no_eliminarlas(): void
    {
        $subadmin = $this->usuarioConRolSubadmin();
        $carrera = Carrera::factory()->create();

        $this->actingAs($subadmin)
            ->get(route('carreras.index'))
            ->assertOk();

        $this->actingAs($subadmin)
            ->delete(route('carreras.destroy', $carrera))
            ->assertForbidden();

        $this->assertDatabaseHas('carreras', ['id' => $carrera->id]);
    }

    public function test_subadmin_puede_ver_tutores_pero_no_eliminarlos(): void
    {
        $subadmin = $this->usuarioConRolSubadmin();
        $tutor = Tutor::factory()->create();

        $this->actingAs($subadmin)
            ->get(route('tutores.index'))
            ->assertOk();

        $this->actingAs($subadmin)
            ->delete(route('tutores.destroy', $tutor))
            ->assertForbidden();

        $this->assertDatabaseHas('tutores', ['id' => $tutor->id]);
    }

    public function test_subadmin_no_puede_editar_pasantes_que_permiso_no_tiene(): void
    {
        $subadmin = $this->usuarioSinRol();
        $this->crearRol('subadmin', ['ver-pasantes']);
        $subadmin->assignRole('subadmin');
        $subadmin = $subadmin->fresh();
        $pasante = Pasante::factory()->create();

        $this->actingAs($subadmin)
            ->get(route('pasantes.edit', $pasante))
            ->assertForbidden();

        $this->actingAs($subadmin)
            ->put(route('pasantes.update', $pasante), [
                'cedula' => '1755523952',
            ])
            ->assertForbidden();
    }

    public function test_admin_tiene_acceso_completo_a_los_tres_modulos(): void
    {
        $admin = $this->usuarioConRolAdmin();
        $pasante = Pasante::factory()->create();
        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create();

        $this->actingAs($admin)->get(route('pasantes.index'))->assertOk();
        $this->actingAs($admin)->get(route('pasantes.show', $pasante))->assertOk();
        $this->actingAs($admin)->get(route('carreras.index'))->assertOk();
        $this->actingAs($admin)->get(route('carreras.show', $carrera))->assertOk();
        $this->actingAs($admin)->get(route('tutores.index'))->assertOk();
        $this->actingAs($admin)->get(route('tutores.show', $tutor))->assertOk();
    }

    public function test_usuario_sin_rol_no_puede_acceder_a_los_modulos(): void
    {
        $user = $this->usuarioSinRol();
        $pasante = Pasante::factory()->create();
        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create();

        $this->actingAs($user)->get(route('pasantes.index'))->assertForbidden();
        $this->actingAs($user)->get(route('pasantes.create'))->assertForbidden();
        $this->actingAs($user)->delete(route('pasantes.destroy', $pasante))->assertForbidden();

        $this->actingAs($user)->get(route('carreras.index'))->assertForbidden();
        $this->actingAs($user)->get(route('carreras.create'))->assertForbidden();
        $this->actingAs($user)->delete(route('carreras.destroy', $carrera))->assertForbidden();

        $this->actingAs($user)->get(route('tutores.index'))->assertForbidden();
        $this->actingAs($user)->get(route('tutores.create'))->assertForbidden();
        $this->actingAs($user)->delete(route('tutores.destroy', $tutor))->assertForbidden();
    }
}