<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateAccesosUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

#[Middleware('auth')]
class UsuarioController extends Controller
{
    // Muestra la lista de usuarios
    public function index(): View
    {
        $usuarios = User::with('roles')->orderBy('id')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    // Muestra el formulario para crear un nuevo usuario
    public function create(): View
    {
        // Solo roles de la guard 'web': un usuario administrador no puede
        // recibir un rol de otra guarda (p. ej. 'pasante'); Spatie lanzaria
        // GuardDoesNotMatch al asignarlo.
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('usuarios.create', compact('roles'));
    }

    // Almacena un nuevo usuario en la base de datos
    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $rol = $datos['rol'];
        unset($datos['rol']);

        $user = User::create($datos + ['estado' => '1']);
        $user->assignRole($rol);

        return redirect()->route('usuarios.index')
            ->with('confirmacion', 'Usuario creado correctamente');
    }

    // Muestra el detalle completo de un usuario
    public function show(User $user): View
    {
        return view('usuarios.show', compact('user'));
    }

    // Muestra el formulario para editar los datos de un usuario
    public function edit(User $user): View
    {
        return view('usuarios.edit', compact('user'));
    }

    // Actualiza los datos de un usuario existente en la base de datos
    public function update(UpdateUsuarioRequest $request, User $user): RedirectResponse
    {
        $datos = $request->validated();

        if (blank($datos['password'])) {
            unset($datos['password']);
        }

        $user->update($datos);

        return redirect()->route('usuarios.show', $user->id)
            ->with('confirmacion', 'Usuario actualizado correctamente');
    }

    // Muestra la vista de Roles y Permisos de un usuario
    public function accesos(User $user, PermisoService $permisos): View
    {
        // Igual que en create(): solo roles asignables a la guard 'web'.
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();
        $permissionsGrouped = $permisos->agruparPorRecurso();

        $userRoles = $user->roles->pluck('name')->toArray();
        $userPermissionsDirect = $user->permissions->pluck('name')->toArray();
        $permissionsViaRoles = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('usuarios.accesos', compact(
            'user',
            'roles',
            'permissionsGrouped',
            'userRoles',
            'userPermissionsDirect',
            'permissionsViaRoles'
        ));
    }

    // Guarda los roles y permisos directos de un usuario
    public function updateAccesos(UpdateAccesosUsuarioRequest $request, User $user): RedirectResponse
    {
        $datos = $request->validated();

        $user->syncRoles($datos['roles'] ?? []);
        $user->syncPermissions($datos['direct_permissions'] ?? []);

        return redirect()->route('usuarios.accesos', $user->id)
            ->with('confirmacion', 'Roles y permisos actualizados correctamente');
    }

    // Elimina un usuario de la base de datos
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('usuarios.index')
                ->withErrors('No puedes eliminar tu propia cuenta');
        }

        $user->delete();

        return redirect()->route('usuarios.index')
            ->with('confirmacion', 'Usuario eliminado correctamente');
    }
}