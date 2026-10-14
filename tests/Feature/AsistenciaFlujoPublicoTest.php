<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Marcado de asistencia del pasante con su cuenta propia.
 *
 * El flujo es una sola pantalla: el pasante elige si marca entrada o salida, se
 * toma la foto con la camara y envia el formulario. Aqui se comprueba lo que
 * puede y no puede hacer: una vez por jornada, solo en dia habil, con foto
 * obligatoria y sin ver los turnos de otro pasante.
 */
class AsistenciaFlujoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function pasante(array $atributos = []): Pasante
    {
        return Pasante::factory()->create(array_merge([
            // Cedula unica porque varias pruebas crean mas de un pasante
            'cedula' => fake()->unique()->numerify('##########'),
            'nombres' => 'Rafael Enrique',
            'apellidos' => 'Perez Hidalgo',
            'estado' => '1',
            'carrera_id' => Carrera::factory()->create(['horas_practicas' => 480])->id,
            'tutor_id' => Tutor::factory()->create()->id,
        ], $atributos));
    }

    /**
     * Autentica al pasante con su cuenta propia (guard 'pasante').
     */
    private function identificado(Pasante $pasante): static
    {
        return $this->actingAs($pasante, 'pasante');
    }

    /**
     * Un turno guardado, como lo dejaria el administrador o el propio pasante.
     */
    private function turno(array $atributos = []): Asistencia
    {
        return Asistencia::create(array_merge([
            'pasante_id' => $this->pasante()->id,
            'fecha' => Carbon::today()->toDateString(),
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ], $atributos));
    }

    private function foto(string $nombre = 'foto.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nombre);
    }

    /**
     * Marca como el pasante, tal como lo hace el formulario de la pantalla.
     */
    private function marcar(string $tipo, array $atributos = []): TestResponse
    {
        return $this->post(route('asistencias.marcar'), array_merge([
            'tipo' => $tipo,
            'jornada' => 'matutina',
            'foto' => $this->foto($tipo.'.jpg'),
        ], $atributos));
    }

    /* ---------------- Pantalla unica ---------------- */

    public function test_la_pantalla_es_una_sola_vista_de_marcado(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('¿Qué vas a marcar?', false)
            ->assertSee('Registro diario');
    }

    public function test_la_pantalla_permite_elegir_entrada_o_salida(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('name="tipo"', false)
            ->assertSee('value="entrada"', false)
            ->assertSee('value="salida"', false);
    }

    public function test_la_pantalla_tiene_los_controles_de_la_camara(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('id="camara"', false)
            ->assertSee('id="btnCamara"', false)
            ->assertSee('id="btnTomar"', false)
            ->assertSee('id="btnRepetir"', false)
            ->assertSee('id="btnGuardar"', false);
    }

    public function test_la_pantalla_pide_la_foto_con_la_camara_del_navegador(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('js/marcado.js', false)
            ->assertSee('id="foto"', false)
            ->assertSee('Elegir una foto de la galeria');
    }

    public function test_la_pantalla_muestra_la_hora_del_servidor_con_segundos(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 08:07:42'));

        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('08:07:42')
            ->assertSee('id="reloj"', false);

        Carbon::setTestNow();
    }

    public function test_la_pantalla_avisa_con_el_mismo_patron_de_avisos_del_proyecto(): void
    {
        $this->identificado($this->pasante())
            ->withSession(['confirmacion' => 'Entrada registrada correctamente.'])
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('mostrarToast(', false)
            ->assertSee('¡Asistencia registrada!', false)
            ->assertSee('Entrada registrada correctamente.');
    }

    public function test_la_pantalla_avisa_el_error_de_la_marca_anterior(): void
    {
        $this->identificado($this->pasante())
            ->withSession(['error' => 'Primero debes marcar tu entrada de esta jornada.'])
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('No se pudo registrar', false)
            ->assertSee('Primero debes marcar tu entrada de esta jornada.');
    }

    /* ---------------- Entrada ---------------- */

    public function test_la_entrada_registra_el_turno_con_la_hora_del_servidor(): void
    {
        Storage::fake('local');

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:07:00'));

        $pasante = $this->pasante();

        $this->identificado($pasante)
            ->marcar('entrada')
            ->assertRedirect()
            ->assertSessionHas('confirmacion');

        $turno = Asistencia::sole();

        $this->assertSame($pasante->id, $turno->pasante_id);
        $this->assertSame('2026-10-05', $turno->fecha->toDateString());
        $this->assertSame('matutina', $turno->jornada);
        $this->assertSame('08:07', $turno->hora_entrada);
        $this->assertNull($turno->hora_salida);

        Carbon::setTestNow();
    }

    public function test_la_marca_ignora_la_fecha_que_manda_el_cliente(): void
    {
        Storage::fake('local');

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:07:00'));

        $this->identificado($this->pasante())
            ->marcar('entrada', [
                'fecha' => '2020-01-01',
                'hora_entrada' => '23:59',
            ])
            ->assertRedirect();

        $this->assertSame('2026-10-05', Asistencia::sole()->fecha->toDateString());
        $this->assertSame('08:07', Asistencia::sole()->hora_entrada);

        Carbon::setTestNow();
    }

    public function test_la_entrada_guarda_la_foto_en_el_disco_privado(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante())
            ->marcar('entrada')
            ->assertRedirect();

        $ruta = Asistencia::sole()->foto_entrada;

        $this->assertNotNull($ruta);
        Storage::disk('local')->assertExists($ruta);
    }

    public function test_la_marca_exige_foto(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante())
            ->from(route('asistencias.registrar'))
            ->post(route('asistencias.marcar'), ['tipo' => 'entrada', 'jornada' => 'matutina'])
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_la_marca_exige_una_jornada_valida(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante())
            ->from(route('asistencias.registrar'))
            ->marcar('entrada', ['jornada' => 'madrugada'])
            ->assertSessionHasErrors('jornada');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_la_marca_exige_un_tipo_valido(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante())
            ->from(route('asistencias.registrar'))
            ->marcar('descanso')
            ->assertSessionHasErrors('tipo');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_no_se_puede_marcar_dos_entradas_en_la_misma_jornada(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante());

        $this->marcar('entrada')->assertSessionHas('confirmacion');
        $this->marcar('entrada')->assertSessionHas('error');

        $this->assertSame(1, Asistencia::count());
    }

    public function test_se_puede_marcar_una_entrada_por_jornada(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante());

        foreach (['matutina', 'vespertina'] as $jornada) {
            $this->marcar('entrada', ['jornada' => $jornada])->assertSessionHas('confirmacion');
        }

        $this->assertSame(2, Asistencia::count());
    }

    public function test_una_marca_rechazada_no_deja_fotos_huerfanas(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante());

        $this->marcar('entrada')->assertSessionHas('confirmacion');

        $antes = Storage::disk('local')->allFiles('asistencias');
        $this->marcar('entrada', ['foto' => $this->foto('rechazada.jpg')])->assertSessionHas('error');

        $this->assertSame($antes, Storage::disk('local')->allFiles('asistencias'));
    }

    public function test_otro_pasante_no_ve_el_turno_ya_marcado(): void
    {
        Storage::fake('local');

        $primero = $this->pasante();
        $segundo = $this->pasante();

        $this->identificado($primero)->marcar('entrada');
        $this->identificado($segundo)->marcar('entrada')->assertSessionHas('confirmacion');

        $this->assertSame(2, Asistencia::count());
        $this->assertSame(
            [$primero->id, $segundo->id],
            Asistencia::orderBy('id')->pluck('pasante_id')->all()
        );
    }

    /* ---------------- Dias habiles ---------------- */

    public function test_no_se_registra_en_sabado_ni_en_domingo(): void
    {
        Storage::fake('local');

        foreach (['2026-10-10', '2026-10-11'] as $finDeSemana) {
            Carbon::setTestNow(Carbon::parse("$finDeSemana 09:00:00"));

            $this->identificado($this->pasante())
                ->from(route('asistencias.registrar'))
                ->marcar('entrada')
                ->assertSessionHas('error');

            Carbon::setTestNow();
        }

        $this->assertSame(0, Asistencia::count());
    }

    public function test_la_pantalla_de_marcado_avisa_en_fin_de_semana(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-10 09:00:00'));

        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('No se registran asistencias');

        Carbon::setTestNow();
    }

    /* ---------------- Salida ---------------- */

    public function test_la_salida_completa_el_mismo_turno_de_la_entrada(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));

        $this->identificado($pasante)->marcar('entrada');

        Carbon::setTestNow(Carbon::parse('2026-10-05 12:30:00'));

        $this->marcar('salida')
            ->assertRedirect()
            ->assertSessionHas('confirmacion');

        $turno = Asistencia::sole();

        $this->assertSame('08:00', $turno->hora_entrada);
        $this->assertSame('12:30', $turno->hora_salida);
        $this->assertSame('04:00:00', $turno->totalHoras());
        $this->assertTrue($turno->completo());

        Carbon::setTestNow();
    }

    public function test_no_se_puede_marcar_salida_sin_entrada(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante())
            ->from(route('asistencias.registrar'))
            ->marcar('salida')
            ->assertSessionHas('error');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_no_se_puede_marcar_dos_salidas(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante());

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));
        $this->marcar('entrada');

        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00:00'));
        $this->marcar('salida')->assertSessionHas('confirmacion');
        $this->marcar('salida')->assertSessionHas('error');

        $this->assertSame(1, Asistencia::count());
        $this->assertSame('12:00', Asistencia::sole()->hora_salida);

        Carbon::setTestNow();
    }

    public function test_la_salida_exige_foto(): void
    {
        Storage::fake('local');

        $this->identificado($this->pasante());

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:00:00'));
        $this->marcar('entrada');

        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00:00'));

        $this->from(route('asistencias.registrar'))
            ->post(route('asistencias.marcar'), ['tipo' => 'salida', 'jornada' => 'matutina'])
            ->assertSessionHasErrors('foto');

        $this->assertNull(Asistencia::sole()->hora_salida);

        Carbon::setTestNow();
    }

    public function test_no_se_puede_marcar_salida_en_fin_de_semana(): void
    {
        Storage::fake('local');

        Carbon::setTestNow(Carbon::parse('2026-10-09 08:00:00'));

        $this->identificado($this->pasante())->marcar('entrada');

        Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00'));

        $this->marcar('salida')->assertSessionHas('error');

        $this->assertNull(Asistencia::sole()->hora_salida);

        Carbon::setTestNow();
    }

    /* ---------------- Sesion ---------------- */

    public function test_no_se_puede_marcar_sin_identificarse(): void
    {
        Storage::fake('local');

        // Sin sesion de pasante la ruta pide login: no hay registro ni errores raros
        $this->from(route('login'))
            ->marcar('entrada')
            ->assertRedirect(route('login'));

        $this->assertSame(0, Asistencia::count());
    }

    public function test_salir_cierra_la_sesion_del_pasante_y_vuelve_al_login(): void
    {
        $this->identificado($this->pasante())
            ->post(route('pasantes.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest('pasante');

        $this->get(route('asistencias.registrar'))
            ->assertRedirect(route('login'));
    }

    public function test_la_marca_se_guarda_solo_con_la_sesion_del_pasante(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();

        $this->identificado($pasante)
            ->marcar('entrada')
            ->assertRedirect()
            ->assertSessionHas('confirmacion')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Asistencia::count());
        $this->assertSame($pasante->id, Asistencia::sole()->pasante_id);
    }

    /* ---------------- Layout y pantallas ---------------- */

    public function test_la_pantalla_del_pasante_se_muestra_dentro_del_sistema(): void
    {
        $pasante = $this->pasante();

        $this->identificado($pasante)
            ->get(route('asistencias.registrar'))
            ->assertOk()
            // El sidebar del sistema, no el layout de afuera
            ->assertSee('main-sidebar')
            ->assertSee('Registrar asistencia')
            ->assertSee('Asistencia')
            ->assertSee('Registrar')
            ->assertSee('Registro actual')
            ->assertSee('Historial')
            // Con la identidad del pasante en el panel del sidebar
            ->assertSee('Rafael Enrique')
            ->assertSee($pasante->apellidos);
    }

    public function test_el_menu_del_pasante_no_muestra_los_modulos_del_administrador(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertDontSee('Gestión de Usuarios')
            ->assertDontSee('Gestión de Roles');
    }

    /**
     * El bug que reporto el administrador: en el mismo navegador se identifica
     * como pasante, luego entra como administrador, y el menu del pasante se le
     * dibuja encima del panel.
     */
    public function test_el_menu_del_pasante_no_aparece_si_la_sesion_tambien_tiene_usuario(): void
    {
        $pasante = $this->pasante();
        $usuario = User::factory()->create(['estado' => 1]);

        $this->identificado($pasante)
            ->actingAs($usuario, 'web')
            ->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('MI ASISTENCIA')
            ->assertDontSee('Salir')
            ->assertDontSee('Todos mis registros');
    }

    public function test_entrar_como_administrador_no_mezcla_el_menu_con_el_del_pasante(): void
    {
        $usuario = User::factory()->create([
            'email' => 'admin@istvidanueva.edu.ec',
            'password' => 'secreto123',
            'estado' => 1,
        ]);

        // Primero se autentica como pasante, luego entra como administrador
        $this->identificado($this->pasante())
            ->post(route('login'), [
                'email' => $usuario->email,
                'password' => 'secreto123',
            ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario, 'web');

        // La guard 'web' gana en el layout: nada del menu del pasante
        $this->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('MI ASISTENCIA')
            ->assertDontSee('Salir');
    }

    /**
     * El pasante deja el formulario abierto, su sesion cambia (al entrar como
     * administrador la sesion se regenera) y al marcar el token ya no vale.
     * El navegador recibe la pantalla "Page Expired" con un aviso utilizable.
     *
     * En los tests el CSRF se salta (Laravel lo desactiva en unit tests), asi
     * que el rechazo 419 se verifica con una peticion HTTP real: aqui solo se
     * comprueba que el cliente envia el token nuevo de la plantilla.
     */
    public function test_la_plantilla_de_marcado_incluye_un_token_csrf(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_la_pantalla_muestra_un_cuadro_de_foto_con_su_imagen_por_defecto(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('Avatar-Default-Img.jpg', false)
            ->assertSee('id="instantanea"', false);
    }

    public function test_la_pantalla_bloquea_la_entrada_si_el_turno_de_esa_jornada_ya_la_tiene(): void
    {
        $pasante = $this->pasante();

        $this->turno([
            'pasante_id' => $pasante->id,
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => null,
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.registrar'))
            ->assertOk()
            // La jornada se auto-selecciona y la entrada queda deshabilitada
            ->assertSee('value="matutina" selected', false)
            ->assertSee('value="entrada" class="sr-only" disabled', false)
            ->assertSee('value="salida" class="sr-only" checked', false);
    }

    public function test_la_pantalla_deshabilita_la_salida_si_aun_no_se_ha_marcado_la_entrada(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('value="entrada" class="sr-only" checked', false)
            ->assertSee('value="salida" class="sr-only" disabled', false);
    }

    public function test_el_boton_de_enviar_dice_registrar_y_evita_el_doble_envio(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.registrar'))
            ->assertOk()
            ->assertSee('Registrar')
            ->assertSee('js/marcado.js', false);
    }

    public function test_el_registro_de_hoy_muestra_solo_el_turno_del_dia(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-01']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 18:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-02',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '13:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.registros'))
            ->assertOk()
            ->assertSee('Asistencia Registrada')
            ->assertSee('04:00:00')
            ->assertSee(route('asistencias.historial'))
            // El turno del 02 pertenece al historial, no a la pantalla de hoy
            ->assertDontSee('02/10/2026');

        Carbon::setTestNow();
    }

    public function test_el_registro_de_hoy_registra_su_salida_pendiente(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 13:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => null,
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.registros'))
            ->assertOk()
            ->assertSee('Falta la salida')
            ->assertSee('Te falta registrar la salida');

        Carbon::setTestNow();
    }

    public function test_el_historial_es_una_pantalla_aparte_de_la_de_hoy(): void
    {
        $this->identificado($this->pasante())
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('Historial de Asistencias')
            ->assertSee('tablaHistorial', false);
    }

    public function test_el_registro_de_hoy_pide_login_sin_identificarse(): void
    {
        $this->get(route('asistencias.registros'))
            ->assertRedirect(route('login'));
    }

    public function test_el_historial_pide_login_sin_identificarse(): void
    {
        $this->get(route('asistencias.historial'))
            ->assertRedirect(route('login'));
    }

    /* ---------------- Historial ---------------- */

    public function test_el_historial_muestra_los_turnos_hasta_hoy(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-01']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 18:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('05/10/2026')
            ->assertSee('04:00:00');

        Carbon::setTestNow();
    }

    public function test_el_historial_marca_los_dias_con_varias_jornadas_y_su_apoyo(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 20:00:00'));

        // Dos turnos el mismo dia: 5 h + 1 h = 6 h. Como la meta diaria es de
        // 4 h, el apoyo del dia es de 2 h (02:00) y se repite en ambas filas.
        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '13:00',
        ]);

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'vespertina',
            'hora_entrada' => '14:00',
            'hora_salida' => '15:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('05/10/2026')
            ->assertSee('class="fila-dia-compartido"', false)
            ->assertSee('02:00');

        Carbon::setTestNow();
    }

    public function test_el_historial_no_pinta_los_dias_de_un_solo_turno(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 18:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertDontSee('class="fila-dia-compartido"', false);

        Carbon::setTestNow();
    }

    public function test_el_historial_avisa_de_los_dias_habiles_sin_registro(): void
    {
        // Del lunes 05 al viernes 09 hay cuatro dias habiles y solo se registro el 06
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-09 18:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-06',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('3')
            ->assertSee('días hábiles')
            ->assertSee('Notifica al administrador');

        Carbon::setTestNow();
    }

    public function test_el_historial_no_cuenta_fines_de_semana_como_faltantes(): void
    {
        // Del lunes 05 al domingo 11 solo hay cinco dias habiles, y se registraron todos
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-11 18:00:00'));

        foreach (['05', '06', '07', '08', '09'] as $dia) {
            $this->turno([
                'pasante_id' => $pasante->id,
                'fecha' => "2026-10-$dia",
                'jornada' => 'matutina',
                'hora_entrada' => '08:00',
                'hora_salida' => '12:00',
            ]);
        }

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertDontSee('Notifica al administrador');

        Carbon::setTestNow();
    }

    public function test_el_historial_no_muestra_turnos_de_otro_pasante(): void
    {
        $propio = $this->pasante(['fecha_ingreso' => '2026-10-05']);
        $ajeno = $this->pasante();

        Carbon::setTestNow(Carbon::parse('2026-10-05 18:00:00'));

        $this->turno([
            'pasante_id' => $propio->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->turno([
            'pasante_id' => $ajeno->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '14:00',
            'hora_salida' => '18:00',
        ]);

        $this->identificado($propio)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('08:00')
            ->assertDontSee('14:00');

        Carbon::setTestNow();
    }

    public function test_el_historial_ya_no_muestra_el_avance_de_la_carrera(): void
    {
        $pasante = $this->pasante(['fecha_ingreso' => '2026-10-05']);

        Carbon::setTestNow(Carbon::parse('2026-10-05 18:00:00'));

        $this->turno([
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-05',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.historial'))
            ->assertOk()
            ->assertSee('05/10/2026')
            ->assertDontSee('4.0 / 480.0 h')
            ->assertDontSee('0.83%');

        Carbon::setTestNow();
    }

    /* ---------------- Fotos privadas ---------------- */

    public function test_el_pasante_ve_las_fotos_de_sus_turnos(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();

        $turno = $this->turno([
            'pasante_id' => $pasante->id,
            'foto_entrada' => 'asistencias/entrada/foto.jpg',
        ]);

        Storage::disk('local')->put($turno->foto_entrada, 'contenido');

        // La foto se sirve en streaming, asi que el cuerpo no llega al assert
        $this->identificado($pasante)
            ->get(route('asistencias.foto.publica', [$turno->id, 'entrada']))
            ->assertOk();

        $this->assertSame('contenido', Storage::disk('local')->get($turno->foto_entrada));
    }

    public function test_el_pasante_no_ve_las_fotos_de_otro(): void
    {
        Storage::fake('local');

        $ajeno = $this->pasante();

        $turno = $this->turno([
            'pasante_id' => $ajeno->id,
            'foto_entrada' => 'asistencias/entrada/ajena.jpg',
        ]);

        Storage::disk('local')->put($turno->foto_entrada, 'secreto');

        $this->identificado($this->pasante())
            ->get(route('asistencias.foto.publica', [$turno->id, 'entrada']))
            ->assertNotFound();

        // La sesion del pasante no invade la guard web de los administradores
        $this->assertGuest('web');
    }

    public function test_la_foto_publica_pide_login_sin_identificarse(): void
    {
        Storage::fake('local');

        $turno = $this->turno(['foto_entrada' => 'asistencias/entrada/sin.jpg']);

        Storage::disk('local')->put($turno->foto_entrada, 'contenido');

        $this->get(route('asistencias.foto.publica', [$turno->id, 'entrada']))
            ->assertRedirect(route('login'));
    }

    public function test_la_foto_publica_rechaza_un_tipo_que_no_existe(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();

        $turno = $this->turno([
            'pasante_id' => $pasante->id,
            'foto_entrada' => 'asistencias/entrada/tipo.jpg',
        ]);

        $this->identificado($pasante)
            ->get(route('asistencias.foto.publica', [$turno->id, 'cedula']))
            ->assertNotFound();
    }
}
