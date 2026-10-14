<?php

namespace App\Http\Controllers;

use App\Services\PermisoService;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Listado de permisos agrupados por recurso.
     *
     * Reutiliza PermisoService (la misma fuente que las pantallas de roles y de
     * accesos de usuario) para que la agrupacion y el filtro por guard 'web'
     * sean exactamente los mismos en todo el sistema, sin una segunda copia del
     * algoritmo que se pueda desincronizar.
     */
    public function index(PermisoService $permisos): View
    {
        return view('permisos.index', [
            'permissionsGrouped' => $permisos->agruparPorRecurso(),
        ]);
    }
}
