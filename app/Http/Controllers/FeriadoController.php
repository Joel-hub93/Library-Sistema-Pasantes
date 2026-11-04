<?php

namespace App\Http\Controllers;

use App\Models\Feriado;
use App\Services\CalendarioLaboral;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeriadoController extends Controller
{
    public function __construct(
        private readonly CalendarioLaboral $calendario,
    ) {}

    public function index(): View
    {
        return view('feriados.index', [
            'feriados' => Feriado::query()->orderBy('fecha')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                $this->reglaFechaUnica(),
            ],
            'nombre' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha.required' => 'Debe seleccionar una fecha.',
            'fecha.date_format' => 'La fecha no tiene el formato correcto.',
            'nombre.max' => 'El nombre no debe superar los 120 caracteres.',
        ]);

        Feriado::create($datos);
        $this->calendario->limpiar();

        return redirect()
            ->route('feriados.index')
            ->with('confirmacion', 'Feriado registrado correctamente.');
    }

    /**
     * Evita dos feriados en la misma fecha.
     *
     * No se usa Rule::unique porque la columna tiene cast 'date': la base
     * guarda 'Y-m-d 00:00:00' y la comparacion de texto fallaria. whereDate
     * compara solo la parte de la fecha.
     */
    private function reglaFechaUnica(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (Feriado::query()->whereDate('fecha', $value)->exists()) {
                $fail('Esa fecha ya está registrada como feriado.');
            }
        };
    }

    public function destroy(Feriado $feriado): RedirectResponse
    {
        $feriado->delete();
        $this->calendario->limpiar();

        return redirect()
            ->route('feriados.index')
            ->with('confirmacion', 'Feriado eliminado correctamente.');
    }
}
