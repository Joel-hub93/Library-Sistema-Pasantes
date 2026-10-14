<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarreraRequest;
use App\Http\Requests\UpdateCarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarreraController extends Controller
{
    // Muestra la lista de carreras
    public function index(): View
    {
        $carreras = Carrera::all();

        return view('carreras.index', ['carreras' => $carreras]);
    }

    // Muestra el formulario para crear una nueva carrera
    public function create(): View
    {
        return view('carreras.create');
    }

    // Muestra el detalle completo de una carrera
    public function show(Carrera $carrera): View
    {
        return view('carreras.show', compact('carrera'));
    }

    // Guarda la nueva carrera en la base de datos
    public function store(StoreCarreraRequest $request): RedirectResponse
    {
        Carrera::create($request->validated() + ['estado' => '1']);

        return redirect()->route('carreras.index')
            ->with('confirmacion', 'Carrera registrada correctamente');
    }

    // Muestra el formulario para editar una carrera existente
    public function edit(Carrera $carrera): View
    {
        return view('carreras.edit', compact('carrera'));
    }

    // Actualiza los datos de una carrera en la base de datos
    public function update(UpdateCarreraRequest $request, Carrera $carrera): RedirectResponse
    {
        $carrera->update($request->validated());

        return redirect()->route('carreras.show', $carrera->id)
            ->with('confirmacion', 'Carrera actualizada correctamente');
    }

    // Elimina una carrera de la base de datos
    public function destroy(Carrera $carrera): RedirectResponse
    {
        // Las FK de pasantes y tutores usan restrictOnDelete, se avisa antes de fallar
        if ($carrera->pasantes()->exists()) {
            return redirect()->route('carreras.index')
                ->with('error', 'No se puede eliminar la carrera porque tiene pasantes inscritos.');
        }

        if ($carrera->tutores()->exists()) {
            return redirect()->route('carreras.index')
                ->with('error', 'No se puede eliminar la carrera porque tiene tutores asignados.');
        }

        $carrera->delete();

        return redirect()->route('carreras.index')
            ->with('confirmacion', 'Carrera eliminada correctamente');
    }
}
