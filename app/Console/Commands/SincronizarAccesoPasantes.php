<?php

namespace App\Console\Commands;

use App\Services\AccesoPasante;
use Illuminate\Console\Command;

/**
 * Crea los permisos y el rol 'pasante' y da acceso a los pasantes actuales.
 *
 * El acceso de cada pasante es su cedula como usuario y como contrasena. La
 * logica vive en App\Services\AccesoPasante, compartida con el alta y la
 * edicion de pasantes, para que el rol y sus permisos no se desincronicen.
 */
class SincronizarAccesoPasantes extends Command
{
    protected $signature = 'pasantes:sincronizar-acceso';

    protected $description = 'Crea el rol y los permisos del pasante y cifra su cedula como contrasena cuando no hay ninguna.';

    public function handle(AccesoPasante $acceso): int
    {
        $generadas = $acceso->sincronizarTodos();

        $this->info("Rol y permisos de 'pasante' listos. $generadas contrasenas generadas.");

        return self::SUCCESS;
    }
}