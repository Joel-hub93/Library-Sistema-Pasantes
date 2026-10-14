<?php

namespace Database\Seeders;

use App\Services\AccesoPasante;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Asegura el rol 'pasante' (guard 'pasante') y sus permisos en un despliegue
 * limpio.
 *
 * Reutiliza App\Services\AccesoPasante para no duplicar la lista de permisos:
 * config/permisos.php sigue siendo la unica fuente de verdad. Es idempotente,
 * asi que volver a sembrar no duplica roles ni permisos.
 */
class PasanteRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(AccesoPasante::class)->prepararRol();

        // Sin esto Spatie seguiria sirviendo la lista de permisos cacheada.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

