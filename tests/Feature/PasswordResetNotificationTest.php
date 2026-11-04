<?php

namespace Tests\Feature;

use App\Models\Pasante;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Correo de restablecimiento de contraseña para administradores.
 *
 * El panel usa el broker "users" (guard web), por lo que el enlace solo debe
 * llegar a administradores y con el texto en español.
 */
class PasswordResetNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_recibe_un_correo_en_espanol_personalizado(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
            'estado' => '1',
        ]);

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $admin->email,
        ]);

        Notification::assertSentTo(
            $admin,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($admin): bool {
                $correo = $notification->toMail($admin);

                $this->assertSame('Restablece tu contraseña', $correo->subject);
                $this->assertStringContainsString('Ana Torres', (string) $correo->greeting);
                $this->assertSame('Restablecer contraseña', $correo->actionText);
                $this->assertStringContainsString('password/reset/', (string) $correo->actionUrl);
                $this->assertStringContainsString(urlencode($admin->email), (string) $correo->actionUrl);
                $this->assertStringContainsString('caducará', implode(' ', array_merge($correo->introLines, $correo->outroLines)));

                return true;
            }
        );
    }

    public function test_un_pasante_no_puede_restablecer_la_contrasena_del_panel(): void
    {
        Notification::fake();

        $pasante = Pasante::factory()->create(['email' => 'pasante@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $pasante->email])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }
}
