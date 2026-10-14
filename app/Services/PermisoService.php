<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermisoService
{
    /**
     * Agrupa los permisos por recurso para las matrices de las vistas.
     *
     * El recurso se toma del nombre del permiso, p.ej. "ver-carreras" -> "carreras",
     * "crear-pasantes" -> "pasantes". Si el permiso no tiene guion,
     * se usa el nombre completo como recurso.
     *
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    public function agruparPorRecurso(): array
    {
        $agrupados = [];

        // Solo permisos de la guard 'web': esta matriz alimenta las pantallas de
        // roles y accesos de los usuarios administradores.
        foreach (Permission::where('guard_name', 'web')->orderBy('name')->get() as $permission) {
            $partes = explode('-', $permission->name);
            $recurso = $partes[1] ?? $partes[0] ?? 'general';

            $agrupados[$recurso][] = $permission;
        }

        ksort($agrupados);

        return $agrupados;
    }
}