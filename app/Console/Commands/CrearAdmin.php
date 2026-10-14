<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Crea o promueve un usuario administrador sin dejar credenciales en el repo.
 *
 * Pensado para produccion: pide el correo y la contrasena por consola (esta
 * ultima de forma oculta). Tambien admite el correo, el nombre y la contrasena
 * como argumentos para despliegues automatizados.
 */
class CrearAdmin extends Command
{
    protected $signature = 'admin:crear
                            {email? : Correo del administrador}
                            {--name= : Nombre a mostrar (por omision "Administrador")}
                            {--password= : Contrasena; si se omite se pide de forma oculta}';

    protected $description = 'Crea (o promueve) un usuario con el rol admin de la guard web';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Correo del administrador');
        $nombre = $this->option('name') ?: 'Administrador';
        $password = $this->option('password') ?: $this->secret('Contrasena (minimo 8 caracteres)');

        $validador = Validator::make(
            ['name' => $nombre, 'email' => $email, 'password' => $password],
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $existia = User::where('email', $email)->exists();

        $usuario = User::updateOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'password' => $password, 'estado' => '1'],
        );

        $usuario->assignRole('admin');

        $this->info($existia
            ? "Usuario existente promovido a admin: {$email}"
            : "Usuario admin creado: {$email}");

        return self::SUCCESS;
    }
}
