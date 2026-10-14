<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Siembra la base de datos.
     *
     * Primero la configuracion base (permisos y roles del sistema, incluido el
     * rol del portal de pasantes), necesaria en cualquier entorno. El usuario
     * administrador NO se siembra: se crea con "php artisan admin:crear" para no
     * dejar credenciales en el repositorio. Los datos de ejemplo (carreras,
     * tutores y pasantes) solo se cargan fuera de produccion.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            PasanteRoleSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call([
                CarreraSeeder::class,
                TutorSeeder::class,
                PasanteSeeder::class,
            ]);
        }
    }
}
