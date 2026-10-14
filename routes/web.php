<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AsistenciaPortalController;
use App\Http\Controllers\Auth\PasanteLoginController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\PasanteController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReporteBitacoraController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteListadoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->middleware('auth')->name('inicio');

/*
|--------------------------------------------------------------------------
| Asistencias (acceso de pasantes)
|--------------------------------------------------------------------------
|
| Estas pantallas las usa el pasante autenticado con su cuenta propia,
| la guard 'pasante' (cedula + contrasena cifrada). Van FUERA del grupo
| 'auth' de administradores pero exigen la guard de pasante.
|
*/

Route::middleware(['auth:pasante'])->group(function () {
    Route::get('/asistencias/registrar', [AsistenciaPortalController::class, 'registrar'])
        ->name('asistencias.registrar');

    Route::get('/asistencias/registros', [AsistenciaPortalController::class, 'registros'])
        ->name('asistencias.registros');

    // Todos los registros del pasante, desde su fecha de ingreso hasta hoy
    Route::get('/asistencias/historial', [AsistenciaPortalController::class, 'historial'])
        ->name('asistencias.historial');

    // El pasante marca su entrada o su salida desde la misma pantalla. La hora
    // y la fecha las toma el servidor, el cliente solo manda el tipo, la jornada
    // y la foto que se toma con la camara.
    Route::post('/asistencias/marcar', [AsistenciaPortalController::class, 'marcar'])
        ->name('asistencias.marcar');

    // Fotos de los turnos del pasante autenticado. El controlador comprueba que
    // el turno sea del pasante de la sesion.
    Route::get('/asistencias/mis-fotos/{asistencia}/{tipo}', [AsistenciaPortalController::class, 'fotoPublica'])
        ->name('asistencias.foto.publica');

    /*
    | Reportes del pasante
    |
    | La bitacora siempre esta disponible: es su herramienta para ver como
    | va. El informe final solo si la administracion ya lo genero; si no,
    | el controlador responde 404.
    */
    Route::get('/mis-reportes/bitacora/pdf', [ReporteBitacoraController::class, 'miBitacoraPdf'])
        ->name('reportes.mi.bitacora.pdf');

    Route::get('/mis-reportes/bitacora/excel', [ReporteBitacoraController::class, 'miBitacoraExcel'])
        ->name('reportes.mi.bitacora.excel');

    Route::get('/mis-reportes/informe', [ReporteController::class, 'miInforme'])
        ->name('reportes.mi.informe');
});

/*
|--------------------------------------------------------------------------
| Login de pasantes
|--------------------------------------------------------------------------
|
| El pasante entra con su cedula como usuario y como contrasena. La guard
| 'pasante' es independiente de la 'web', asi que no comparte sesion con los
| administradores.
|
*/

Route::get('/pasantes/login', [PasanteLoginController::class, 'showLoginForm'])
    ->name('pasantes.login');

Route::post('/pasantes/login', [PasanteLoginController::class, 'login'])
    ->middleware('throttle:login')
    ->name('pasantes.login.enviar');

Route::post('/pasantes/logout', [PasanteLoginController::class, 'logout'])
    ->middleware('auth:pasante')
    ->name('pasantes.logout');

Route::middleware(['auth'])->group(function () {

    /*
     |--------------------------------------------------------------------------
     | Permisos por accion
     |--------------------------------------------------------------------------
     |
     | Cada modulo se declara como cuatro recursos parciales, uno por accion.
     | ORDEN OBLIGATORIO: create/store ANTES que index/show, de lo contrario
     | GET /pasantes/create cae dentro del comodin /pasantes/{pasante}
     | y responde 404 en vez de mostrar el formulario de creacion.
     |
     */

    // Gestion de Pasantes
    Route::resource('/pasantes', PasanteController::class)
        ->only(['create', 'store'])->middleware('can:crear-pasantes');
    Route::resource('/pasantes', PasanteController::class)
        ->only(['edit', 'update'])->middleware('can:editar-pasantes');
    Route::resource('/pasantes', PasanteController::class)
        ->only(['destroy'])->middleware('can:eliminar-pasantes');
    Route::resource('/pasantes', PasanteController::class)
        ->only(['index', 'show'])->middleware('can:ver-pasantes');

    // Muestra una foto de pasante desde el disco privado
    Route::get('/pasantes/{pasante}/foto', [PasanteController::class, 'foto'])
        ->name('pasantes.foto')->middleware('can:ver-pasantes');

    // Gestion de Carreras
    Route::resource('/carreras', CarreraController::class)
        ->only(['create', 'store'])->middleware('can:crear-carreras');
    Route::resource('/carreras', CarreraController::class)
        ->only(['edit', 'update'])->middleware('can:editar-carreras');
    Route::resource('/carreras', CarreraController::class)
        ->only(['destroy'])->middleware('can:eliminar-carreras');
    Route::resource('/carreras', CarreraController::class)
        ->only(['index', 'show'])->middleware('can:ver-carreras');

    // Gestion de Tutores
    Route::resource('/tutores', TutorController::class)
        ->parameters(['tutores' => 'tutor'])
        ->only(['create', 'store'])->middleware('can:crear-tutores');
    Route::resource('/tutores', TutorController::class)
        ->parameters(['tutores' => 'tutor'])
        ->only(['edit', 'update'])->middleware('can:editar-tutores');
    Route::resource('/tutores', TutorController::class)
        ->parameters(['tutores' => 'tutor'])
        ->only(['destroy'])->middleware('can:eliminar-tutores');
    Route::resource('/tutores', TutorController::class)
        ->parameters(['tutores' => 'tutor'])
        ->only(['index', 'show'])->middleware('can:ver-tutores');

    // Muestra una foto de tutor desde el disco privado
    Route::get('/tutores/{tutor}/foto', [TutorController::class, 'foto'])
        ->name('tutores.foto')->middleware('can:ver-tutores');

    // Gestion de Usuarios
    Route::resource('/usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'user'])
        ->only(['create', 'store'])->middleware('can:crear-usuarios');
    Route::resource('/usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'user'])
        ->only(['edit', 'update'])->middleware('can:editar-usuarios');
    Route::resource('/usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'user'])
        ->only(['destroy'])->middleware('can:eliminar-usuarios');
    Route::resource('/usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'user'])
        ->only(['index', 'show'])->middleware('can:ver-usuarios');

    // Vista de Roles y Permisos de un usuario (boton de la llavesita)
    Route::get('/usuarios/{user}/accesos', [UsuarioController::class, 'accesos'])
        ->name('usuarios.accesos')
        ->middleware('can:asignar-permisos-a-usuario');

    Route::put('/usuarios/{user}/accesos', [UsuarioController::class, 'updateAccesos'])
        ->name('usuarios.accesos.update')
        ->middleware('can:asignar-permisos-a-usuario');

    // Gestion de Asistencias
    Route::resource('/asistencias', AsistenciaController::class)
        ->only(['create', 'store'])->middleware('can:crear-pasantes');
    Route::resource('/asistencias', AsistenciaController::class)
        ->only(['edit', 'update'])->middleware('can:editar-pasantes');
    Route::resource('/asistencias', AsistenciaController::class)
        ->only(['destroy'])->middleware('can:eliminar-pasantes');
    Route::resource('/asistencias', AsistenciaController::class)
        ->only(['index', 'show'])->middleware('can:ver-pasantes');

    // Muestra una foto de asistencia desde el disco privado
    Route::get('/asistencias/{asistencia}/foto/{tipo}', [AsistenciaController::class, 'foto'])
        ->whereIn('tipo', ['entrada', 'salida'])
        ->name('asistencias.foto')->middleware('can:ver-pasantes');

    // Gestion de Roles (no tiene vista show)
    Route::resource('/roles', RoleController::class)
        ->only(['create', 'store'])->middleware('can:crear-roles');
    Route::resource('/roles', RoleController::class)
        ->only(['edit', 'update'])->middleware('can:editar-roles');
    Route::resource('/roles', RoleController::class)
        ->only(['destroy'])->middleware('can:eliminar-roles');
    Route::resource('/roles', RoleController::class)
        ->only(['index'])->middleware('can:ver-roles');

    // Listado de Permisos (solo lectura)
    Route::get('/permisos', [PermissionController::class, 'index'])
        ->name('permisos.index')->middleware('can:ver-permisos');

    /*
     |------------------------------------------------------------------
     | Modulo de Reportes
     |------------------------------------------------------------------
     |
     | El listado de modulos (index) es la unica vista del modulo: cada
     | fila tiene sus botones de descarga. Los listados y bitacoras se
     | arman bajo demanda y NUNCA se guardan. El informe final es el unico
     | que persiste en `reportes` y su fila activa cierra el periodo de
     | registro del pasante.
     |
     | Todas las rutas fijas van ANTES que las de parametro {reporte} para
     | que ninguna caiga dentro del comodin.
     |
     */

    Route::get('/reportes', [ReporteController::class, 'index'])
        ->name('reportes.index')->middleware('can:ver-reporte-asistencias');

    // Listados simples de modulos: se descargan directo, no se guardan.
    Route::get('/reportes/usuarios/pdf', [ReporteListadoController::class, 'usuariosPdf'])
        ->name('reportes.usuarios.pdf')->middleware('can:ver-reporte-asistencias');
    Route::get('/reportes/usuarios/excel', [ReporteListadoController::class, 'usuariosExcel'])
        ->name('reportes.usuarios.excel')->middleware('can:ver-reporte-asistencias');

    Route::get('/reportes/pasantes/pdf', [ReporteListadoController::class, 'pasantesPdf'])
        ->name('reportes.pasantes.pdf')->middleware('can:ver-reporte-asistencias');
    Route::get('/reportes/pasantes/excel', [ReporteListadoController::class, 'pasantesExcel'])
        ->name('reportes.pasantes.excel')->middleware('can:ver-reporte-asistencias');

    Route::get('/reportes/carreras/pdf', [ReporteListadoController::class, 'carrerasPdf'])
        ->name('reportes.carreras.pdf')->middleware('can:ver-reporte-asistencias');
    Route::get('/reportes/carreras/excel', [ReporteListadoController::class, 'carrerasExcel'])
        ->name('reportes.carreras.excel')->middleware('can:ver-reporte-asistencias');

    Route::get('/reportes/tutores/pdf', [ReporteListadoController::class, 'tutoresPdf'])
        ->name('reportes.tutores.pdf')->middleware('can:ver-reporte-asistencias');
    Route::get('/reportes/tutores/excel', [ReporteListadoController::class, 'tutoresExcel'])
        ->name('reportes.tutores.excel')->middleware('can:ver-reporte-asistencias');

    // Asistencias: los botones del index abren modales que apuntan aca.
    // El tipo de la bitacora se resuelve por GET (params pasante + fechas).
    Route::get('/reportes/asistencias/pdf', [ReporteBitacoraController::class, 'asistenciaPdf'])
        ->name('reportes.asistencias.pdf')->middleware('can:ver-reporte-asistencias');
    Route::get('/reportes/asistencias/excel', [ReporteBitacoraController::class, 'asistenciaExcel'])
        ->name('reportes.asistencias.excel')->middleware('can:ver-reporte-asistencias');

    // Informe final: la vista previa no guarda nada; generar si lo guarda y
    // cierra el periodo de registro del pasante.
    Route::get('/reportes/informe/preview', [ReporteController::class, 'vistaPrevia'])
        ->name('reportes.informe.preview')->middleware('can:ver-reporte-asistencias');
    Route::post('/reportes/informe/generar', [ReporteController::class, 'generar'])
        ->name('reportes.generar')->middleware('can:ver-reporte-asistencias');

    Route::get('/reportes/{reporte}/informe', [ReporteController::class, 'descargarInforme'])
        ->name('reportes.informe')->middleware('can:ver-reporte-asistencias');

    Route::post('/reportes/{reporte}/anular', [ReporteController::class, 'anular'])
        ->name('reportes.anular')->middleware('can:ver-reporte-asistencias');
});

Auth::routes(['register' => false]);
