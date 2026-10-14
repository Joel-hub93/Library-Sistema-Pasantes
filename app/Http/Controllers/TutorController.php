<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTutorRequest;
use App\Http\Requests\UpdateTutorRequest;
use App\Models\Carrera;
use App\Models\Tutor;
use App\Services\FotoPrivada;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TutorController extends Controller
{
    public function __construct(private readonly FotoPrivada $fotos)
    {
    }

    // Muestra la lista de tutores
    public function index(): View
    {
        $tutores = Tutor::with('carrera')->get();

        return view('tutores.index', ['tutores' => $tutores]);
    }

    // Muestra el formulario para crear un nuevo tutor
    public function create(): View
    {
        return view('tutores.create', ['carreras' => Carrera::activos()->orderBy('nombre')->get()]);
    }

    // Muestra el detalle completo de un tutor
    public function show(Tutor $tutor): View
    {
        $tutor->load('carrera');

        return view('tutores.show', compact('tutor'));
    }

    // Guarda el nuevo tutor en la base de datos
    public function store(StoreTutorRequest $request): RedirectResponse
    {
        // Se aparta 'foto' porque validada() devuelve el UploadedFile, no la ruta
        $datos = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            $datos['foto'] = $this->fotos->guardar($request->file('foto'), 'tutores');
        }

        Tutor::create($datos + ['estado' => '1']);

        return redirect()->route('tutores.index')
            ->with('confirmacion', 'Tutor registrado correctamente');
    }

    // Muestra el formulario para editar un tutor existente
    public function edit(Tutor $tutor): View
    {
        $tutor->load('carrera');

        // Si la carrera asignada ya no esta activa se agrega al catalogo, de lo
        // contrario el select no tendria el valor actual y el formulario
        // reasignaria otra al guardar
        $carreras = Carrera::activos()->orderBy('nombre')->get();

        if ($tutor->carrera && ! $carreras->contains($tutor->carrera->id)) {
            $carreras->push($tutor->carrera);
        }

        return view('tutores.edit', [
            'tutor' => $tutor,
            'carreras' => $carreras->sortBy('nombre')->values(),
        ]);
    }

    // Actualiza los datos de un tutor en la base de datos
    public function update(UpdateTutorRequest $request, Tutor $tutor): RedirectResponse
    {
        $datos = $request->safe()->except('foto');

        // Si se sube una foto nueva se reemplaza la anterior; si no, se conserva
        if ($request->hasFile('foto')) {
            $datos['foto'] = $this->fotos->reemplazar($tutor->foto, $request->file('foto'), 'tutores');
        }

        $tutor->update($datos);

        return redirect()->route('tutores.show', $tutor->id)
            ->with('confirmacion', 'Tutor actualizado correctamente');
    }

    // Elimina un tutor y su foto del disco
    public function destroy(Tutor $tutor): RedirectResponse
    {
        // La FK de pasantes usa restrictOnDelete, se avisa antes de fallar
        if ($tutor->pasantes()->exists()) {
            return redirect()->route('tutores.index')
                ->with('error', 'No se puede eliminar el tutor porque tiene pasantes asignados.');
        }

        $this->fotos->borrar($tutor->foto);
        $tutor->delete();

        return redirect()->route('tutores.index')
            ->with('confirmacion', 'Tutor eliminado correctamente');
    }

    /**
     * Muestra la foto del tutor desde el disco privado.
     *
     * El archivo vive fuera de public/, asi que solo se sirve por esta ruta
     * protegida y quien lo pida directamente recibe 404.
     */
    public function foto(Tutor $tutor): StreamedResponse
    {
        return $this->fotos->servir($tutor->foto);
    }
}
