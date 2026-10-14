<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleGestionTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISOS_ROLES = [
        'ver-roles',
        'crear-roles',
        'editar-roles',
        'eliminar-roles',
    ];

    private function admin(): User
    {
        $rol = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rol->syncPermissions(
            collect(self::PERMISOS_ROLES)
                ->map(fn (string $n) => Permission::firstOrCreate(['name' => $n, 'guard_name' => 'web']))
        );

        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole('admin');

        return $usuario->fresh();
    }

    private function permiso(string $nombre): Permission
    {
        return Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
    }

    /** ---------------- Guardas de ruta ---------------- */

    public function test_sin_permiso_no_se_puede_ver_ni_gestionar_roles(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes']);

        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole('subadmin');

        $this->actingAs($usuario)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('roles.create'))->assertForbidden();
        $this->actingAs($usuario)->post(route('roles.store'), ['name' => 'nuevo'])->assertForbidden();
    }

    public function test_sin_permiso_no_se_puede_ver_la_lista_de_permisos(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes']);

        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole('subadmin');

        $this->actingAs($usuario)->get(route('permisos.index'))->assertForbidden();
    }

    /** ---------------- Normalizacion del nombre ---------------- */

    public function test_el_nombre_del_rol_se_normaliza_a_minusculas(): void
    {
        $this->permiso('ver-carreras');

        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'name'        => '  CoordINador  ',
                'permissions' => ['ver-carreras'],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'coordinador']);
    }

    public function test_rechaza_un_nombre_que_solo_difiere_en_mayusculas(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), ['name' => 'ADMIN'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Role::where('name', 'admin')->count());
    }

    public function test_al_editar_se_puede_conservar_el_mismo_nombre(): void
    {
        $rol = Role::create(['name' => 'coordinador', 'guard_name' => 'web']);

        $this->actingAs($this->admin())
            ->put(route('roles.update', $rol->id), ['name' => 'Coordinador'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('roles', ['id' => $rol->id, 'name' => 'coordinador']);
    }

    /** ---------------- Politica de eliminacion ---------------- */

    public function test_no_se_puede_eliminar_el_rol_admin(): void
    {
        $this->admin();

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', Role::findByName('admin')->id))
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    public function test_no_se_puede_eliminar_el_rol_subadmin(): void
    {
        Role::create(['name' => 'subadmin', 'guard_name' => 'web']);

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', Role::findByName('subadmin')->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'subadmin']);
    }

    public function test_no_se_puede_eliminar_un_rol_con_usuarios_asignados(): void
    {
        $this->actingAs($this->admin())->post(route('roles.store'), ['name' => 'coordinador']);

        $rol = Role::findByName('coordinador');
        User::factory()->create(['estado' => '1'])->assignRole('coordinador');

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', $rol->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_se_puede_eliminar_un_rol_sin_usuarios(): void
    {
        $this->actingAs($this->admin())->post(route('roles.store'), ['name' => 'temporal']);

        $rol = Role::findByName('temporal');

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', $rol->id))
            ->assertSessionHas('confirmacion');

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    /** ---------------- Sincronizacion de permisos ---------------- */

    public function test_al_crear_un_rol_solo_quedan_los_permisos_marcados(): void
    {
        $this->permiso('ver-carreras');
        $this->permiso('crear-carreras');

        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'name'        => 'lector',
                'permissions' => ['ver-carreras'],
            ]);

        $rol = Role::findByName('lector');

        $this->assertEquals(['ver-carreras'], $rol->permissions->pluck('name')->all());
    }

    public function test_al_editar_un_rol_se_reemplazan_todos_sus_permisos(): void
    {
        $this->permiso('ver-carreras');
        $this->permiso('crear-carreras');

        $rol = Role::create(['name' => 'lector', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-carreras']);

        $this->actingAs($this->admin())
            ->put(route('roles.update', $rol->id), [
                'name'        => 'lector',
                'permissions' => ['crear-carreras'],
            ]);

        $this->assertEquals(['crear-carreras'], $rol->fresh()->permissions->pluck('name')->all());
    }

    public function test_rechaza_permisos_repetidos(): void
    {
        $this->permiso('ver-carreras');

        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'name'        => 'lector',
                'permissions' => ['ver-carreras', 'ver-carreras'],
            ])
            ->assertSessionHasErrors('permissions.1');
    }

    public function test_rechaza_un_permiso_inexistente(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'name'        => 'lector',
                'permissions' => ['permiso-inventado'],
            ])
            ->assertSessionHasErrors('permissions.0');
    }

    /** ---------------- N+1 ---------------- */

    public function test_el_listado_de_roles_no_hace_una_consulta_por_rol(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('roles.store'), ['name' => 'coordinador']);
        $this->actingAs($admin)->post(route('roles.store'), ['name' => 'bibliotecario']);
        $this->actingAs($admin)->post(route('roles.store'), ['name' => 'secretario']);

        \DB::enableQueryLog();
        \DB::flushQueryLog();

        $this->actingAs($admin)->get(route('roles.index'))->assertOk();

        // Con withCount el conteo viaja como subselect dentro de la consulta
        // principal de roles. El N+1 que corregimos eran N consultas sueltas
        // "select count(*) from users ... where roles.id = ?".
        $conteosSueltos = collect(\DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $q) => str_starts_with($q, 'select count(*)') && str_contains($q, '"users"'));

        $this->assertCount(
            0,
            $conteosSueltos,
            'El listado no debe ejecutar un conteo de usuarios por cada rol.'
        );

        \DB::disableQueryLog();
    }

    private function crearRol(string $nombre, array $permisos = []): Role
    {
        $rol = Role::create(['name' => $nombre, 'guard_name' => 'web']);
        $rol->syncPermissions(collect($permisos)->map(fn (string $n) => $this->permiso($n)));

        return $rol;
    }
}