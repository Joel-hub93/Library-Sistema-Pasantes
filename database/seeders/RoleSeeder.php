<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $subadmin = Role::firstOrCreate(['name' => 'subadmin', 'guard_name' => 'web']);

        $allPermissions = Permission::pluck('name')->toArray();
        $admin->syncPermissions($allPermissions);

        $subadminPermissions = [
            'ver-pasantes',
            'crear-pasantes',
            'editar-pasantes',
            'ver-carreras',
            'ver-tutores',
            'ver-reporte-asistencias',
        ];

        $subadmin->syncPermissions($subadminPermissions);
    }
}