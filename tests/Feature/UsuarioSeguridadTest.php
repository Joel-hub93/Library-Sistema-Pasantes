<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsuarioSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISOS_USUARIOS = [
        'ver-usuarios',
        'crear-usuarios',
        'editar-usuarios',
        'eliminar-usuarios',
        'asignar-permisos-a-usuario',
    ];

    private function crearRol(string $nombre, array $permisos = []): Role
    {
        $rol = Role::create(['name' => $nombre, 'guard_name' => 'web']);

        $modelos = collect($permisos)->map(function (string $nombre): Permission {
            return Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        });

        $rol->syncPermissions($modelos);

        return $rol;
    }

    private function admin(): User
    {
        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole($this->crearRol('admin', self::PERMISOS_USUARIOS));

        return $usuario->fresh();
    }

    private function subadmin(): User
    {
        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole($this->crearRol('subadmin', ['ver-pasantes']));

        return $usuario->fresh();
    }

    /** ---------------- Guardas de ruta ---------------- */

    public function test_subadmin_no_puede_ver_la_lista_de_usuarios(): void
    {
        $this->actingAs($this->subadmin())
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_subadmin_no_puede_abrir_el_formulario_de_crear_usuario(): void
    {
        $this->actingAs($this->subadmin())
            ->get(route('usuarios.create'))
            ->assertForbidden();
    }

    public function test_subadmin_no_puede_crear_usuarios(): void
    {
        $this->actingAs($this->subadmin())
            ->post(route('usuarios.store'), [
                'name'     => 'Intruso',
                'email'    => 'intruso@example.com',
                'password' => 'secreto123',
                'password_confirmation' => 'secreto123',
                'rol'      => 'subadmin',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_subadmin_no_puede_editar_ni_eliminar_usuarios(): void
    {
        $objetivo = User::factory()->create(['estado' => '1']);
        $atacante = $this->subadmin();

        $this->actingAs($atacante)
            ->get(route('usuarios.edit', $objetivo->id))
            ->assertForbidden();

        $this->actingAs($atacante)
            ->put(route('usuarios.update', $objetivo->id), [
                'name'     => 'Secuestrado',
                'email'    => 'secuestrado@example.com',
                'estado'   => '1',
            ])
            ->assertForbidden();

        $this->actingAs($atacante)
            ->delete(route('usuarios.destroy', $objetivo->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id'    => $objetivo->id,
            'email' => $objetivo->email,
        ]);
    }

    public function test_usuario_sin_rol_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_visitante_es_redirigido_al_login(): void
    {
        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_admin_si_puede_acceder_a_todas_las_rutas_de_usuarios(): void
    {
        $admin = $this->admin();
        $otro  = User::factory()->create(['estado' => '1']);

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($admin)->get(route('usuarios.create'))->assertOk();
        $this->actingAs($admin)->get(route('usuarios.show', $otro->id))->assertOk();
        $this->actingAs($admin)->get(route('usuarios.edit', $otro->id))->assertOk();
    }

    public function test_permiso_individual_habilita_solo_su_accion(): void
    {
        $this->crearRol('solo-crear', ['crear-usuarios']);

        $usuario = User::factory()->create(['estado' => '1']);
        $usuario->assignRole('solo-crear');

        $this->actingAs($usuario)->get(route('usuarios.create'))->assertOk();
        $this->actingAs($usuario)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('usuarios.edit', $usuario->id))->assertForbidden();
    }

    /** ---------------- Bug de permisos heredados ---------------- */

    public function test_guardar_accesos_no_convierte_heredados_en_directos(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes', 'crear-pasantes', 'editar-pasantes']);
        Permission::firstOrCreate(['name' => 'editar-carreras', 'guard_name' => 'web']);

        $objetivo = User::factory()->create(['estado' => '1']);
        $objetivo->assignRole('subadmin');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'roles'              => ['subadmin'],
                'direct_permissions' => ['editar-carreras'],
            ])
            ->assertRedirect(route('usuarios.accesos', $objetivo->id));

        $fresco = $objetivo->fresh();

        $this->assertEquals(
            ['editar-carreras'],
            $fresco->permissions->pluck('name')->all(),
            'Solo el permiso marcado debe quedar como directo.'
        );

        $this->assertCount(3, $fresco->getPermissionsViaRoles());
    }

    public function test_quitar_rol_no_conserva_los_permisos_que_venian_de_ese_rol(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes', 'crear-pasantes']);
        Permission::firstOrCreate(['name' => 'editar-carreras', 'guard_name' => 'web']);

        $objetivo = User::factory()->create(['estado' => '1']);
        $objetivo->assignRole('subadmin');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'roles'              => [],
                'direct_permissions' => [],
            ])
            ->assertRedirect();

        $fresco = $objetivo->fresh();

        $this->assertCount(0, $fresco->permissions);
        $this->assertCount(0, $fresco->getPermissionsViaRoles());
    }

    /** ---------------- Anti-bloqueo ---------------- */

    public function test_admin_no_puede_quitarse_a_si_mismo_el_rol_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $admin->id), [
                'roles'              => [],
                'direct_permissions' => [],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_admin_puede_cambiarse_otros_permisos_mantieniendo_admin(): void
    {
        $admin = $this->admin();
        Permission::firstOrCreate(['name' => 'ver-carreras', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $admin->id), [
                'roles'              => ['admin'],
                'direct_permissions' => ['ver-carreras'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    /** ---------------- Validacion de arrays ---------------- */

    public function test_no_permite_mas_de_dos_roles(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes']);
        $this->crearRol('coordinador', ['ver-tutores']);
        $this->crearRol('bibliotecario', ['ver-carreras']);

        $admin = $this->admin();
        $objetivo = User::factory()->create(['estado' => '1']);

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'roles' => ['admin', 'subadmin', 'coordinador'],
            ])
            ->assertSessionHasErrors('roles');
    }

    public function test_no_permite_roles_repetidos(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes']);

        $admin = $this->admin();
        $objetivo = User::factory()->create(['estado' => '1']);

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'roles' => ['subadmin', 'subadmin'],
            ])
            ->assertSessionHasErrors(['roles.0', 'roles.1']);
    }

    public function test_rechaza_un_rol_inexistente(): void
    {
        $admin = $this->admin();
        $objetivo = User::factory()->create(['estado' => '1']);

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'roles' => ['rol-inventado'],
            ])
            ->assertSessionHasErrors('roles.0');
    }

    public function test_rechaza_un_permiso_inexistente(): void
    {
        $admin = $this->admin();
        $objetivo = User::factory()->create(['estado' => '1']);

        $this->actingAs($admin)
            ->put(route('usuarios.accesos.update', $objetivo->id), [
                'direct_permissions' => ['permiso-inventado'],
            ])
            ->assertSessionHasErrors('direct_permissions.0');
    }

    /** ---------------- Vista de accesos ---------------- */

    public function test_la_vista_de_accesos_solo_muestra_la_llave_a_quien_asigna_permisos(): void
    {
        $this->crearRol('subadmin', ['ver-pasantes']);

        $conPermiso = User::factory()->create(['estado' => '1']);
        $conPermiso->assignRole($this->crearRol('gestor', ['ver-usuarios', 'asignar-permisos-a-usuario']));

        $objetivo = User::factory()->create(['estado' => '1']);

        $this->actingAs($conPermiso)
            ->get(route('usuarios.accesos', $objetivo->id))
            ->assertOk()
            ->assertSee(route('usuarios.accesos', $objetivo->id), false);
    }
}