<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Reporte;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Modulo de reportes: listados PDF/Excel, bitacora de asistencias on demand
 * e informe final.
 *
 * El informe se genera una sola vez por pasante al cumplir sus horas; despues
 * de guardarse cierra el periodo de registro (ni el pasante ni el admin pueden
 * crear asistencias nuevas). Es el UNICO reporte que se persiste: los listados
 * y las bitacoras se descargan sin dejar rastro.
 */
class ReporteTest extends TestCase
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
            'cedula' => fake()->unique()->numerify('##########'),
            'nombres' => 'Rafael Enrique',
            'apellidos' => 'Perez Hidalgo',
            'estado' => '1',
            'fecha_ingreso' => '2026-10-05',
            // Meta baja: con 8 horas registradas el pasante ya cumple
            'carrera_id' => Carrera::factory()->create(['horas_practicas' => 8])->id,
            'tutor_id' => Tutor::factory()->create()->id,
        ], $atributos));
    }

    /**
     * Dos turnos de 4h en dias habiles consecutivos = 8 horas de practica,
     * con lo que una carrera de 8 horas queda cumplida.
     */
    private function cumplirHoras(Pasante $pasante, int $horas = 8): void
    {
        $porDia = 4;
        $dias = (int) ceil($horas / $porDia);

        for ($i = 0; $i < $dias; $i++) {
            $fecha = Carbon::parse('2026-10-05')->addDays($i);

            Asistencia::create([
                'pasante_id' => $pasante->id,
                'fecha' => $fecha->toDateString(),
                'jornada' => 'matutina',
                'hora_entrada' => '08:00',
                'hora_salida' => '12:00',
            ]);
        }
    }

    private function generarInforme(Pasante $pasante): Reporte
    {
        $this->cumplirHoras($pasante);

        return $pasante->reporteActivo()
            ?? Reporte::create([
                'pasante_id' => $pasante->id,
                'ruta_pdf' => 'reportes/informe-' . $pasante->id . '.pdf',
                'generado_por' => $this->usuarioConPermisos(['ver-reporte-asistencias'])->id,
                'fecha_generacion' => now(),
                'estado' => Reporte::ESTADO_ACTIVO,
            ]);
    }

    /* ---------------- Permisos ---------------- */

    public function test_el_listado_de_reportes_exige_autenticacion(): void
    {
        $this->get(route('reportes.index'))->assertRedirect(route('login'));
    }

    public function test_el_listado_de_reportes_exige_el_permiso(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $this->get(route('reportes.index'))->assertForbidden();
    }

    public function test_generar_informe_exige_el_permiso(): void
    {
        $pasante = $this->pasante();
        $this->cumplirHoras($pasante);

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $this->post(route('reportes.generar'), ['pasante_id' => $pasante->id])->assertForbidden();
    }

    public function test_el_index_pasa_los_pasantes_en_json_utilizable_a_los_modales(): void
    {
        $this->pasante();
        $admin = $this->usuarioConPermisos(['ver-reporte-asistencias']);

        $html = $this->actingAs($admin)->get(route('reportes.index'))->assertOk()->getContent();

        // Un solo escape (el del motor de Blade): el navegador lo convierte
        // en JSON valido para el select. Si hubiera doble escape (&amp;quot;)
        // JSON.parse fallaria y los modales quedarian vacios.
        $this->assertStringNotContainsString('&amp;quot;', $html);
        $this->assertStringContainsString('data-pasantes="[{&quot;id&quot;:', $html);
    }

    /* ---------------- Listados de los modulos ---------------- */

    public function test_los_listados_descargables_exigen_el_permiso(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $this->get(route('reportes.usuarios.pdf'))->assertForbidden();
        $this->get(route('reportes.carreras.excel'))->assertForbidden();
    }

    public function test_el_admin_descarga_los_listados_como_pdf(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.usuarios.pdf'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('reportes.pasantes.pdf'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('reportes.carreras.pdf'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('reportes.tutores.pdf'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_el_admin_descarga_los_listados_como_excel(): void
    {
        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.usuarios.excel'))->assertOk()->assertDownload();
        $this->get(route('reportes.pasantes.excel'))->assertOk()->assertDownload();
        $this->get(route('reportes.carreras.excel'))->assertOk()->assertDownload();
        $this->get(route('reportes.tutores.excel'))->assertOk()->assertDownload();
    }

    /* ---------------- Bitacora administrativa (on demand) ---------------- */

    public function test_la_bitacora_administrativa_exige_el_permiso(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']));

        $this->get(route('reportes.asistencias.pdf', ['pasante_id' => $pasante->id]))
            ->assertForbidden();
    }

    public function test_la_bitacora_administrativa_descarga_el_pdf_del_pasante(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        // 05, 06 y 07 de octubre de 2026 son dias habiles sin turno alguno:
        // la bitacora debe listarlos, dejando el hueco visible en el PDF
        $this->get(route('reportes.asistencias.pdf', [
            'pasante_id' => $pasante->id,
            'desde' => '2026-10-05',
            'hasta' => '2026-10-07',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }

    public function test_la_bitacora_administrativa_descarga_el_excel_también(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.asistencias.excel', [
            'pasante_id' => $pasante->id,
            'desde' => '2026-10-05',
            'hasta' => '2026-10-07',
        ]))
            ->assertOk()
            ->assertDownload();

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_la_bitacora_rechaza_un_rango_de_fechas_invalido(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.asistencias.pdf', [
            'pasante_id' => $pasante->id,
            'desde' => '2026-10-07',
            'hasta' => '2026-10-01',
        ]))->assertSessionHasErrors(['desde']);
    }

    public function test_la_bitacora_rechaza_una_fecha_inicial_anterior_al_ingreso(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.asistencias.pdf', [
            'pasante_id' => $pasante->id,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-07',
        ]))->assertSessionHasErrors(['desde']);
    }

    /* ---------------- Generacion del informe ---------------- */

    public function test_generar_informe_exige_haber_cumplido_las_horas(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->post(route('reportes.generar'), ['pasante_id' => $pasante->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('reportes', ['pasante_id' => $pasante->id]);
    }

    public function test_la_vista_previa_descarga_el_pdf_sin_guardarlo(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $this->cumplirHoras($pasante);

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->get(route('reportes.informe.preview', ['pasante_id' => $pasante->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseCount('reportes', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_generar_informe_guarda_el_pdf_en_almacenamiento_privado(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $this->cumplirHoras($pasante);

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->post(route('reportes.generar'), ['pasante_id' => $pasante->id])
            ->assertSessionHas('confirmacion');

        $reporte = Reporte::where('pasante_id', $pasante->id)->firstOrFail();

        $this->assertSame(Reporte::ESTADO_ACTIVO, $reporte->estado);
        $this->assertTrue(Storage::disk('local')->exists($reporte->ruta_pdf));
        $this->assertGreaterThan(1000, Storage::disk('local')->size($reporte->ruta_pdf));
    }

    public function test_generar_informe_dos_veces_es_rechazado(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $reporte = $this->generarInforme($pasante);

        $this->actingAs($this->usuarioConPermisos(['ver-reporte-asistencias']));

        $this->post(route('reportes.generar'), ['pasante_id' => $pasante->id])
            ->assertSessionHas('error');

        $this->assertSame(1, Reporte::where('pasante_id', $pasante->id)->count());
    }

    /* ---------------- Cierre del periodo ---------------- */

    public function test_sin_informe_el_pasante_puede_seguir_registrando(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante');

        $this->post(route('asistencias.marcar'), [
            'tipo' => 'entrada',
            'jornada' => 'matutina',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasNoErrors();
    }

    public function test_con_informe_el_pasante_no_puede_marcar_asistencias(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $this->generarInforme($pasante);

        $this->actingAs($pasante, 'pasante');

        $this->post(route('asistencias.marcar'), [
            'tipo' => 'entrada',
            'jornada' => 'matutina',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHas('error');
    }

    public function test_con_informe_el_admin_no_puede_crear_asistencias(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $this->generarInforme($pasante);

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $this->post(route('asistencias.store'), [
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-08',
            'jornada' => 'matutina',
            'hora_entrada' => '08:00',
            'hora_salida' => '12:00',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('asistencias', [
            'pasante_id' => $pasante->id,
            'fecha' => '2026-10-08',
        ]);
    }

    public function test_anular_el_informe_reabre_el_periodo_de_registro(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $reporte = $this->generarInforme($pasante);

        $admin = $this->usuarioConPermisos(['ver-reporte-asistencias']);
        $this->actingAs($admin);

        $this->post(route('reportes.anular', $reporte))->assertSessionHas('confirmacion');

        $this->assertSame(Reporte::ESTADO_ANULADO, $reporte->fresh()->estado);
        $this->assertFalse($pasante->informeGenerado());

        $this->actingAs($pasante, 'pasante');

        $this->post(route('asistencias.marcar'), [
            'tipo' => 'entrada',
            'jornada' => 'matutina',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasNoErrors();
    }

    public function test_anular_un_informe_ya_anulado_avisa(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $reporte = $this->generarInforme($pasante);

        $admin = $this->usuarioConPermisos(['ver-reporte-asistencias']);
        $this->actingAs($admin);

        $this->post(route('reportes.anular', $reporte))->assertSessionHas('confirmacion');
        $this->post(route('reportes.anular', $reporte))->assertSessionHas('error');
    }

    /* ---------------- Descarga del informe ---------------- */

    public function test_el_pasante_sin_informe_recibe_un_404(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante');

        $this->get(route('reportes.mi.informe'))->assertNotFound();
    }

    public function test_el_pasante_descarga_su_informe_como_pdf(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $reporte = $this->generarInforme($pasante);

        Storage::disk('local')->put($reporte->ruta_pdf, 'contenido-de-prueba');

        $this->actingAs($pasante, 'pasante');

        $this->get(route('reportes.mi.informe'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_anular_el_informe_bloquea_la_descarga_del_pasante(): void
    {
        Storage::fake('local');

        $pasante = $this->pasante();
        $reporte = $this->generarInforme($pasante);

        $admin = $this->usuarioConPermisos(['ver-reporte-asistencias']);
        $this->actingAs($admin);

        $this->post(route('reportes.anular', $reporte))->assertSessionHas('confirmacion');

        $this->actingAs($pasante, 'pasante');

        $this->get(route('reportes.mi.informe'))->assertNotFound();
    }

    /* ---------------- Bitacora propia del pasante ---------------- */

    public function test_la_bitacora_del_pasante_es_un_pdf(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante');

        $this->get(route('reportes.mi.bitacora.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }

    public function test_la_bitacora_excel_se_descarga_sin_guardarse(): void
    {
        $pasante = $this->pasante();

        $this->actingAs($pasante, 'pasante');

        $this->get(route('reportes.mi.bitacora.excel'))
            ->assertOk()
            ->assertDownload();

        $this->assertDatabaseCount('reportes', 0);
    }
}