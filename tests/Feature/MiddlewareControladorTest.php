<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El middleware de los controladores se declara con el atributo #[Middleware].
 * Estos tests fallan si alguien vuelve a un constructor vacio o borra el atributo.
 */
class MiddlewareControladorTest extends TestCase
{
    use RefreshDatabase;

    /** ---------------- Comportamiento real ---------------- */
    public function test_dashboard_exige_autenticacion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_usuarios_exige_autenticacion(): void
    {
        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_logout_exige_autenticacion(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_confirmar_password_exige_autenticacion(): void
    {
        $this->get(route('password.confirm'))->assertRedirect(route('login'));
    }

    public function test_login_se_cierra_para_quien_ya_esta_autenticado(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect();
    }

    public function test_usuarios_sigue_siendo_accesible_para_un_admin(): void
    {
        $admin = User::factory()->create();
        Permission::firstOrCreate(['name' => 'ver-usuarios', 'guard_name' => 'web']);

        $rol = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $rol->syncPermissions([Permission::findByName('ver-usuarios')]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
    }

    /** ---------------- El atributo esta presente ---------------- */
    public static function controladoresConMiddleware(): array
    {
        return [
            [UsuarioController::class, 'auth'],
            [AdminController::class, 'auth'],
            [ConfirmPasswordController::class, 'auth'],
            [LoginController::class, 'guest:web'],
        ];
    }

    #[DataProvider('controladoresConMiddleware')]
    public function test_el_controlador_declara_su_middleware_con_atributo(string $clase, string $esperado): void
    {
        $atributos = collect((new ReflectionClass($clase))->getAttributes(Middleware::class))
            ->map(fn ($a) => $a->newInstance()->middleware)
            ->all();

        $this->assertContains(
            $esperado,
            $atributos,
            "{$clase} debe declarar #[Middleware('{$esperado}')]."
        );

        $this->assertFalse(
            method_exists($clase, '__construct'),
            "{$clase} ya no debe usar el constructor para middleware."
        );
    }

    public function test_login_solo_pide_guest_excepto_en_logout(): void
    {
        $instancia = collect((new ReflectionClass(LoginController::class))->getAttributes(Middleware::class))
            ->map(fn ($a) => $a->newInstance())
            ->first(fn ($a) => str_starts_with($a->middleware, 'guest'));

        $this->assertNotNull($instancia);
        $this->assertSame(['logout'], $instancia->except);
    }
}
