<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasanteRequest;
use App\Http\Requests\UpdatePasanteRequest;
use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Services\AccesoPasante;
use App\Services\FotoPrivada;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PasanteController extends Controller
{
    public function __construct(
        private readonly FotoPrivada $fotos,
        private readonly AccesoPasante $acceso,
    ) {
    }

    // Catálogos de carreras y tutores habilitados para los formularios
    private function catalogos(): array
    {
        return [
            'carreras' => Carrera::activos()->orderBy('nombre')->get(),
            'tutores' => Tutor::activos()->orderBy('nombres')->get(),
        ];
    }

    // Muestra la lista de pasantes
    public function index(): View
    {
        $pasantes = Pasante::with(['carrera', 'tutor'])->orderBy('id')->get();

        return view('pasantes.index', compact('pasantes'));
    }

    // Muestra el formulario para crear un nuevo pasante
    public function create(): View
    {
        return view('pasantes.create', $this->catalogos());
    }

    // Muestra el detalle completo de un pasante
    public function show(Pasante $pasante): View
    {
        $pasante->load(['carrera', 'tutor']);

        return view('pasantes.show', compact('pasante'));
    }

    // Guarda el nuevo pasante en la base de datos
    public function store(StorePasanteRequest $request): RedirectResponse
    {
        // Se aparta 'foto' porque validada() devuelve el UploadedFile, no la ruta
        $datos = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            $datos['foto'] = $this->fotos->guardar($request->file('foto'), 'pasantes');
        }

        $pasante = Pasante::create($datos + ['estado' => '1']);

        // Su usuario para el sistema es su cedula y su contrasena su misma cedula
        $pasante->asignarContrasenaCedula();
        $this->acceso->asignar($pasante);

        return redirect()->route('pasantes.index')
            ->with('confirmacion', 'Pasante registrado correctamente');
    }

    // Muestra el formulario para editar un pasante existente
    public function edit(Pasante $pasante): View
    {
        $pasante->load(['carrera', 'tutor']);

        $catalogos = $this->catalogos();

        // Si la carrera o el tutor asignados ya no estan activos se agregan al
        // catalogo, de lo contrario el select no tendria el valor actual y el
        // formulario reasignaria otro al guardar
        $catalogos['carreras'] = $this->asegurarEnLista($catalogos['carreras'], $pasante->carrera, 'nombre');
        $catalogos['tutores'] = $this->asegurarEnLista($catalogos['tutores'], $pasante->tutor, 'nombres');

        return view('pasantes.edit', array_merge($catalogos, compact('pasante')));
    }

    // Agrega un registro al catalogo si no esta y lo reordena
    private function asegurarEnLista($lista, $registro, string $orden)
    {
        if ($registro && ! $lista->contains($registro->id)) {
            $lista->push($registro);
        }

        return $lista->sortBy($orden)->values();
    }

    // Actualiza los datos de un pasante en la base de datos
    public function update(UpdatePasanteRequest $request, Pasante $pasante): RedirectResponse
    {
        $datos = $request->safe()->except('foto');

        // Si se sube una foto nueva se reemplaza la anterior; si no, se conserva
        if ($request->hasFile('foto')) {
            $datos['foto'] = $this->fotos->reemplazar($pasante->foto, $request->file('foto'), 'pasantes');
        }

        $pasante->update($datos);

        // Si cambiaron la cedula, es el nuevo usuario y la nueva contrasena
        if ($request->has('cedula') && $pasante->wasChanged('cedula')) {
            $pasante->asignarContrasenaCedula();
        }

        $this->acceso->asignar($pasante);

        return redirect()->route('pasantes.show', $pasante->id)
            ->with('confirmacion', 'Pasante actualizado correctamente');
    }

    // Elimina un pasante de la base de datos y su foto del disco
    public function destroy(Pasante $pasante): RedirectResponse
    {
        $this->fotos->borrar($pasante->foto);

        $pasante->delete();

        return redirect()->route('pasantes.index')
            ->with('confirmacion', 'Pasante eliminado correctamente');
    }

    /**
     * Muestra la foto del pasante desde el disco privado.
     *
     * El archivo vive fuera de public/, asi que solo se sirve por esta ruta
     * protegida y quien lo pida directamente recibe 404.
     */
    public function foto(Pasante $pasante): StreamedResponse
    {
        return $this->fotos->servir($pasante->foto);
    }
}
