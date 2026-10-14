<?php

namespace Tests\Feature;

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

class FotoPrivacidadTest extends TestCase
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

    public function test_la_foto_del_pasante_se_guarda_en_el_disco_privado(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes', 'crear-pasantes']));

        $carrera = Carrera::factory()->create();
        $tutor = Tutor::factory()->create(['carrera_id' => $carrera->id]);

        $this->post(route('pasantes.store'), [
            'cedula' => '1755523952',
            'nombres' => 'Rafael Enrique',
            'apellidos' => 'Perez Hidalgo',
            'email' => 'rafael@example.com',
            'telefono' => '0985865448',
            'fecha_ingreso' => '2026-08-13',
            'carrera_id' => $carrera->id,
            'tutor_id' => $tutor->id,
            'foto' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect(route('pasantes.index'));

        $foto = Pasante::query()->sole()->foto;

        $this->assertStringStartsWith('pasantes/', $foto);
        Storage::disk('local')->assertExists($foto);
        Storage::disk('public')->assertMissing($foto);
    }

    public function test_la_foto_del_pasante_se_sirve_por_la_ruta_protegida(): void
    {
        Storage::fake('local');

        $pasante = Pasante::factory()->create(['foto' => 'pasantes/perfil.jpg']);
        Storage::disk('local')->put($pasante->foto, 'contenido');

        $this->actingAs($this->usuarioConPermisos(['ver-pasantes']))
            ->get(route('pasantes.foto', $pasante))
            ->assertOk();
    }

    public function test_la_foto_del_pasante_sin_permiso_recibe_403(): void
    {
        Storage::fake('local');

        $pasante = Pasante::factory()->create(['foto' => 'pasantes/perfil.jpg']);
        Storage::disk('local')->put($pasante->foto, 'contenido');

        $this->actingAs($this->usuarioConPermisos([]))
            ->get(route('pasantes.foto', $pasante))
            ->assertForbidden();
    }

    public function test_la_foto_del_tutor_se_guarda_en_el_disco_privado(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->usuarioConPermisos(['ver-tutores', 'crear-tutores']));

        $this->post(route('tutores.store'), [
            'codigo_docente' => 'DOC-0001',
            'nombres' => 'Ana',
            'apellidos' => 'Lopez',
            'correo' => 'ana@example.com',
            'carrera_id' => Carrera::factory()->create()->id,
            'foto' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect(route('tutores.index'));

        $foto = Tutor::query()->sole()->foto;

        $this->assertStringStartsWith('tutores/', $foto);
        Storage::disk('local')->assertExists($foto);
        Storage::disk('public')->assertMissing($foto);
    }

    public function test_la_foto_del_tutor_se_sirve_por_la_ruta_protegida(): void
    {
        Storage::fake('local');

        $tutor = Tutor::factory()->create(['foto' => 'tutores/perfil.jpg']);
        Storage::disk('local')->put($tutor->foto, 'contenido');

        $this->actingAs($this->usuarioConPermisos(['ver-tutores']))
            ->get(route('tutores.foto', $tutor))
            ->assertOk();
    }

    public function test_la_foto_del_tutor_sin_permiso_recibe_403(): void
    {
        Storage::fake('local');

        $tutor = Tutor::factory()->create(['foto' => 'tutores/perfil.jpg']);
        Storage::disk('local')->put($tutor->foto, 'contenido');

        $this->actingAs($this->usuarioConPermisos([]))
            ->get(route('tutores.foto', $tutor))
            ->assertForbidden();
    }
}
