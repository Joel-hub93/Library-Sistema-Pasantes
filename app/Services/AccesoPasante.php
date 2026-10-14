<?php

namespace App\Services;

use App\Models\Pasante;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Acceso del portal de pasantes (guard 'pasante').
 *
 * Centraliza el rol y los permisos del pasante: los crea si faltan (entorno
 * recien clonado o antes de correr el seeder) y los asigna. Antes esta logica
 * estaba escrita dos veces: en el alta/edicion de pasantes y en el comando de
 * sincronizacion.
 */
class AccesoPasante
{
    /** Rol del portal de pasantes (guard propia, ajena a los roles 'web'). */
    public const ROL = 'pasante';

    /** Guard de los pasantes. */
    public const GUARD = 'pasante';

    /**
     * Crea el rol 'pasante' con sus permisos si todavia no existen.
     */
    public function prepararRol(): void
    {
        foreach ($this->permisos() as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => self::GUARD]);
        }

        Role::firstOrCreate(['name' => self::ROL, 'guard_name' => self::GUARD])
            ->syncPermissions($this->permisos());
    }

    /**
     * Asegura que un pasante tenga el rol, creandolo antes si hace falta.
     *
     * Se usa en el alta y la edicion: el rol nunca falta y el alta no falla.
     */
    public function asignar(Pasante $pasante): void
    {
        $this->prepararRol();

        $this->otorgar($pasante);
    }

    /**
     * Recorre todos los pasantes: genera las contrasenas que faltan y asegura
     * el rol de cada uno.
     *
     * @return int  Cantidad de contrasenas generadas.
     */
    public function sincronizarTodos(): int
    {
        $this->prepararRol();

        $generadas = 0;

        foreach (Pasante::all() as $pasante) {
            if (blank($pasante->password)) {
                $pasante->asignarContrasenaCedula();
                $generadas++;
            }

            $this->otorgar($pasante);
        }

        // Sin esto Spatie seguiria sirviendo la lista de permisos cacheada.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $generadas;
    }

    /**
     * Permisos de la guard 'pasante' (config/permisos.php).
     *
     * @return array<int, string>
     */
    private function permisos(): array
    {
        return config('permisos.permisos_pasante');
    }

    private function otorgar(Pasante $pasante): void
    {
        if (! $pasante->hasRole(self::ROL)) {
            $pasante->assignRole(self::ROL);
        }
    }
}
