<?php

namespace Tests\Feature;

use App\Models\Pasante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PantallasPublicasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pasante con acceso listo: su contrasena es su misma cedula, igual que
     * deja el comando pasantes:sincronizar-acceso.
     */
    private function pasante(array $overrides = []): Pasante
    {
        $pasante = Pasante::factory()->create(array_merge([
            'cedula' => '1755523952',
            'nombres' => 'Rafael Enrique',
            'apellidos' => 'Perez Hidalgo',
            'estado' => '1',
        ], $overrides));

        $pasante->password = $pasante->cedula;
        $pasante->save();

        return $pasante;
    }

    /* ---------------- Login de administradores ---------------- */

    public function test_login_carga_sin_autenticacion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sistema de Control de Asistencia')
            ->assertSee('Acceso para pasantes')
            ->assertSee('Olvidaste tu contraseña');
    }

    public function test_login_usa_el_esqueleto_adminlte(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dist/css/adminlte.min.css', false)
            ->assertSee('dist/js/adminlte.min.js', false)
            ->assertSee('login-card-body', false);
    }

    public function test_login_lleva_al_acceso_de_pasantes(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('pasantes.login'))
            ->assertSee('Acceso para pasantes');
    }

    public function test_login_no_trae_los_botones_de_facebook_ni_google(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Sign in using Facebook')
            ->assertDontSee('Sign in using Google');
    }

    public function test_login_no_debe_cargar_el_scaffold_bootstrap_5(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('@vite', false)
            ->assertDontSee('resources/sass/app.scss', false);
    }

    public function test_recuperar_contrasena_carga(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Restablecer contraseña');
    }

    /* ---------------- Login del pasante ---------------- */

    public function test_el_login_del_pasante_carga_sin_autenticacion(): void
    {
        $this->get(route('pasantes.login'))
            ->assertOk()
            ->assertSee('Acceso para pasantes')
            ->assertSee('Tu contraseña es tu mismo número de cédula.')
            ->assertSee(route('login'), false);
    }

    public function test_cedula_valida_inicia_sesion_y_lleva_a_registrar(): void
    {
        $pasante = $this->pasante();

        $this->post(route('pasantes.login.enviar'), [
            'cedula' => $pasante->cedula,
            'password' => $pasante->cedula,
        ])->assertRedirect(route('asistencias.registrar'));

        $this->assertAuthenticatedAs($pasante, 'pasante');
    }

    public function test_contrasena_incorrecta_vuelve_con_error(): void
    {
        $pasante = $this->pasante();

        $response = $this->from(route('pasantes.login'))
            ->post(route('pasantes.login.enviar'), [
                'cedula' => $pasante->cedula,
                'password' => 'otra-cosa',
            ]);

        $response->assertRedirect(route('pasantes.login'));
        $response->assertSessionHasErrors('cedula');

        $this->assertGuest('pasante');
    }

    public function test_pasante_inactivo_no_puede_iniciar_sesion(): void
    {
        $pasante = $this->pasante(['estado' => '0']);

        $this->from(route('pasantes.login'))
            ->post(route('pasantes.login.enviar'), [
                'cedula' => $pasante->cedula,
                'password' => $pasante->cedula,
            ])
            ->assertSessionHasErrors('cedula');

        $this->assertGuest('pasante');
    }

    public function test_la_cedula_es_obligatoria(): void
    {
        $this->from(route('pasantes.login'))
            ->post(route('pasantes.login.enviar'), ['password' => 'algo'])
            ->assertSessionHasErrors('cedula');
    }

    public function test_la_contrasena_es_obligatoria(): void
    {
        $this->from(route('pasantes.login'))
            ->post(route('pasantes.login.enviar'), ['cedula' => '1755523952'])
            ->assertSessionHasErrors('password');
    }

    public function test_la_contrasena_se_guarda_cifrada(): void
    {
        $pasante = $this->pasante();

        $this->post(route('pasantes.login.enviar'), [
            'cedula' => $pasante->cedula,
            'password' => $pasante->cedula,
        ])->assertRedirect(route('asistencias.registrar'));

        $this->assertNotSame($pasante->cedula, $pasante->fresh()->password);
        $this->assertTrue(Hash::check($pasante->cedula, $pasante->fresh()->password));
    }

    public function test_un_pasante_autenticado_no_ve_el_login(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante')
            ->get(route('pasantes.login'))
            ->assertRedirect(route('asistencias.registrar'));
    }

    /* ---------------- Pantallas de asistencia ---------------- */

    public function test_las_pantallas_de_asistencia_piden_login(): void
    {
        $this->get(route('asistencias.registrar'))->assertRedirect(route('login'));
        $this->get(route('asistencias.registros'))->assertRedirect(route('login'));
    }

    public function test_registrar_muestra_el_pasante_autenticado(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante')
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('Rafael Enrique')
            ->assertSee('Asistencia');
    }

    public function test_registros_muestra_el_pasante_autenticado(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante')
            ->get(route('asistencias.registros'))
            ->assertOk()
            ->assertSee('Rafael Enrique');
    }

    public function test_el_pasante_entra_sin_cuenta_de_administrador(): void
    {
        $pasante = $this->pasante();

        $this->assertGuest('web');

        $this->post(route('pasantes.login.enviar'), [
            'cedula' => $pasante->cedula,
            'password' => $pasante->cedula,
        ])->assertRedirect(route('asistencias.registrar'));

        // Sigue siendo invitado en la guard web de los administradores
        $this->assertGuest('web');
        $this->assertAuthenticated('pasante');
    }
}
