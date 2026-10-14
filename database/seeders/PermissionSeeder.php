<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ver-roles',
            'crear-roles',
            'editar-roles',
            'eliminar-roles',
            'ver-permisos',
            'asignar-permisos-a-usuario',
            'ver-usuarios',
            'crear-usuarios',
            'editar-usuarios',
            'eliminar-usuarios',
            'ver-pasantes',
            'crear-pasantes',
            'editar-pasantes',
            'eliminar-pasantes',
            'ver-carreras',
            'crear-carreras',
            'editar-carreras',
            'eliminar-carreras',
            'ver-tutores',
            'crear-tutores',
            'editar-tutores',
            'eliminar-tutores',
            'ver-reporte-asistencias',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}