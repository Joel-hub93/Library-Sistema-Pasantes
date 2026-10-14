<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles' => $roles,
            'rolesSistema' => config('permisos.roles_sistema'),
        ]);
    }

    public function create(PermisoService $permisos): View
    {
        return view('roles.create', [
            'permissionsGrouped' => $permisos->agruparPorRecurso(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $role = Role::create(['name' => $datos['name']]);

        $role->syncPermissions($datos['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('confirmacion', 'Rol creado correctamente');
    }

    public function edit(Role $role, PermisoService $permisos): View
    {
        $this->abortSiNoEsWeb($role);

        return view('roles.edit', [
            'role'                => $role,
            'permissionsGrouped' => $permisos->agruparPorRecurso(),
            'rolePermissions'     => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->abortSiNoEsWeb($role);

        $datos = $request->validated();

        $role->update(['name' => $datos['name']]);
        $role->syncPermissions($datos['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('confirmacion', 'Rol actualizado correctamente');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->abortSiNoEsWeb($role);

        if (in_array($role->name, config('permisos.roles_sistema'), true)) {
            return redirect()->route('roles.index')
                ->with('error', 'El rol "' . ucfirst($role->name) . '" es del sistema y no se puede eliminar');
        }

        $usuariosAsignados = $role->users()->count();

        if ($usuariosAsignados > 0) {
            $usuarios = $usuariosAsignados === 1
                ? '1 usuario tiene'
                : $usuariosAsignados . ' usuarios tienen';

            return redirect()->route('roles.index')
                ->with('error', 'No se puede eliminar "' . ucfirst($role->name) . '": ' . $usuarios . ' este rol asignado');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('confirmacion', 'Rol eliminado correctamente');
    }

    /**
     * La gestion de roles de esta pantalla es solo para la guard 'web'.
     *
     * Los roles de otras guardas (p. ej. 'pasante', que usa el portal de
     * pasantes) no deben poder editarse ni eliminarse desde aqui, ni siquiera
     * accediendo por URL directa al id.
     */
    private function abortSiNoEsWeb(Role $role): void
    {
        abort_unless($role->guard_name === 'web', 404);
    }
}