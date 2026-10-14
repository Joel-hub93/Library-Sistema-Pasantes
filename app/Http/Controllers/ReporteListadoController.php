<?php

namespace App\Http\Controllers;

use App\Exports\ListadoExport;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listados simples de modulos (usuarios, pasantes, carreras y tutores).
 *
 * Cada listado se descarga en PDF (horizontal) o Excel con el titulo fusionado
 * en la primera fila. Nunca se guardan: se arman bajo demanda.
 */
class ReporteListadoController extends Controller
{
    public function usuariosPdf(): Response
    {
        $usuarios = User::with('roles')->orderBy('name')->get();

        return Pdf::loadView('reportes.pdf.usuarios', ['usuarios' => $usuarios])
            ->setPaper('letter', 'landscape')
            ->download('listado_usuarios.pdf');
    }

    public function usuariosExcel(): Response
    {
        $usuarios = User::with('roles')->orderBy('name')->get();

        $filas = $usuarios->map(fn (User $user) => [
            $user->id,
            $user->name,
            $user->email,
            $user->roles->pluck('name')->map(fn ($rol) => ucfirst($rol))->implode(', ') ?: 'Sin rol',
            $user->estado == '1' ? 'Activo' : 'Inactivo',
            $user->created_at->format('d/m/Y H:i'),
        ]);

        return $this->descargarListado(
            'Listado de Usuarios',
            ['ID', 'Nombre', 'Correo Electrónico', 'Roles', 'Estado', 'Fecha de Registro'],
            $filas,
            'listado_usuarios.xlsx'
        );
    }

    public function pasantesPdf(): Response
    {
        $pasantes = Pasante::with(['carrera', 'tutor'])->orderBy('apellidos')->orderBy('nombres')->get();

        return Pdf::loadView('reportes.pdf.pasantes', ['pasantes' => $pasantes])
            ->setPaper('letter', 'landscape')
            ->download('listado_pasantes.pdf');
    }

    public function pasantesExcel(): Response
    {
        $pasantes = Pasante::with(['carrera', 'tutor'])->orderBy('apellidos')->orderBy('nombres')->get();

        $filas = $pasantes->map(fn (Pasante $pasante) => [
            $pasante->id,
            $pasante->cedula,
            $pasante->nombres,
            $pasante->apellidos,
            $pasante->carrera?->nombre ?? '—',
            $pasante->tutor ? trim($pasante->tutor->apellidos.' '.$pasante->tutor->nombres) : '—',
            $pasante->horasRequeridas(),
            $pasante->fecha_ingreso?->format('d/m/Y') ?? '—',
            $pasante->estado == '1' ? 'Activo' : 'Inactivo',
        ]);

        return $this->descargarListado(
            'Listado de Pasantes',
            ['ID', 'Cédula', 'Nombres', 'Apellidos', 'Carrera', 'Tutor', 'Horas a realizar', 'Fecha de ingreso', 'Estado'],
            $filas,
            'listado_pasantes.xlsx'
        );
    }

    public function carrerasPdf(): Response
    {
        $carreras = Carrera::orderBy('id')->get();

        return Pdf::loadView('reportes.pdf.carreras', ['carreras' => $carreras])
            ->setPaper('letter', 'landscape')
            ->download('listado_carreras.pdf');
    }

    public function carrerasExcel(): Response
    {
        $carreras = Carrera::orderBy('id')->get();

        $filas = $carreras->map(fn (Carrera $carrera) => [
            $carrera->id,
            $carrera->codigo_carrera,
            $carrera->nombre,
            $carrera->horas_practicas,
            $carrera->estado == '1' ? 'Activa' : 'Inactiva',
        ]);

        return $this->descargarListado(
            'Listado de Carreras',
            ['ID', 'Código carrera', 'Nombre carrera', 'Horas de prácticas', 'Estado'],
            $filas,
            'listado_carreras.xlsx'
        );
    }

    public function tutoresPdf(): Response
    {
        $tutores = Tutor::with('carrera')->orderBy('apellidos')->orderBy('nombres')->get();

        return Pdf::loadView('reportes.pdf.tutores', ['tutores' => $tutores])
            ->setPaper('letter', 'landscape')
            ->download('listado_tutores.pdf');
    }

    public function tutoresExcel(): Response
    {
        $tutores = Tutor::with('carrera')->orderBy('apellidos')->orderBy('nombres')->get();

        $filas = $tutores->map(fn (Tutor $tutor) => [
            $tutor->id,
            $tutor->codigo_docente,
            $tutor->nombres,
            $tutor->apellidos,
            $tutor->carrera?->nombre ?? '—',
            $tutor->estado == '1' ? 'Activo' : 'Inactivo',
        ]);

        return $this->descargarListado(
            'Listado de Tutores',
            ['ID', 'Código docente', 'Nombres', 'Apellidos', 'Carrera asignada', 'Estado'],
            $filas,
            'listado_tutores.xlsx'
        );
    }

    /**
     * Descarga un Excel de listado generico: el titulo va fusionado en la
     * primera fila a lo ancho de todas las columnas. Nunca se guarda.
     */
    private function descargarListado(string $titulo, array $encabezados, Collection $filas, string $nombre): Response
    {
        return Excel::download(new ListadoExport($titulo, $encabezados, $filas), $nombre);
    }
}
