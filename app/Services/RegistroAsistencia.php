<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Pasante;
use App\Models\Reporte;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * Registro de asistencia del pasante (portal con guard 'pasante').
 *
 * Concentra las reglas del autoregistro: una entrada por jornada, la salida
 * solo completa un turno ya abierto, nunca se registra en fin de semana ni
 * despues de que el informe final cerro el periodo. El controlador queda como
 * adaptador HTTP.
 */
class RegistroAsistencia
{
    public function __construct(private readonly FotoPrivada $fotos)
    {
    }

    /**
     * Marca la entrada del dia para una jornada.
     *
     * La foto se guarda recien despues de validar el turno: si el pasante ya
     * habia marcado esta jornada, se avisa y no queda ninguna foto huerfana en
     * el disco.
     */
    public function marcarEntrada(Pasante $pasante, string $jornada, UploadedFile $foto): ResultadoRegistro
    {
        if ($this->turnoDeHoy($pasante, $jornada)) {
            return ResultadoRegistro::error('Ya registraste tu entrada para esta jornada.');
        }

        $pasante->asistencias()->create([
            'fecha' => Carbon::today()->toDateString(),
            'jornada' => $jornada,
            'hora_entrada' => Carbon::now()->format('H:i'),
            'foto_entrada' => $this->fotos->guardar($foto, Asistencia::CARPETA_FOTOS.'/entrada'),
        ]);

        return ResultadoRegistro::ok('Entrada registrada correctamente.');
    }

    /**
     * Completa con la salida el turno que ya tiene entrada.
     *
     * No crea un turno nuevo: escribe la salida en el que ya existe, para que
     * entrada y salida queden en la misma fila.
     */
    public function marcarSalida(Pasante $pasante, string $jornada, UploadedFile $foto): ResultadoRegistro
    {
        $turno = $this->turnoDeHoy($pasante, $jornada);

        if (! $turno) {
            return ResultadoRegistro::error('Primero debes marcar tu entrada de esta jornada.');
        }

        if ($turno->completo()) {
            return ResultadoRegistro::error('Tu salida de esta jornada ya fue registrada.');
        }

        $hora = Carbon::now()->format('H:i');

        if (Asistencia::minutosDeHora($hora) <= Asistencia::minutosDeHora($turno->hora_entrada)) {
            return ResultadoRegistro::error('La salida no puede ser anterior a tu entrada.');
        }

        $turno->update([
            'hora_salida' => $hora,
            'foto_salida' => $this->fotos->guardar($foto, Asistencia::CARPETA_FOTOS.'/salida'),
        ]);

        return ResultadoRegistro::ok('Salida registrada correctamente.');
    }

    /**
     * El turno de hoy del pasante para esa jornada.
     */
    public function turnoDeHoy(Pasante $pasante, string $jornada): ?Asistencia
    {
        return $pasante->asistencias()
            ->whereDate('fecha', Carbon::today()->toDateString())
            ->where('jornada', $jornada)
            ->first();
    }

    /**
     * Si el pasante ya tiene informe final: el periodo de registro quedo
     * cerrado y ya no se aceptan asistencias nuevas ni ediciones.
     */
    public function periodoCerrado(int $pasanteId): bool
    {
        return Reporte::query()->activos()->where('pasante_id', $pasanteId)->exists();
    }

    /**
     * Cuantos dias habiles dejo de registrar el pasante desde que ingreso.
     *
     * El administrador es quien autoriza esos dias a mano, asi que la pantalla
     * publica solo informa la cantidad en vez de inventar una recuperacion.
     */
    public function diasHabilesSinRegistro(Pasante $pasante): int
    {
        $inicio = $pasante->fecha_ingreso?->copy()->startOfDay() ?? Carbon::today();
        $ayer = Carbon::today()->subDay();

        if ($inicio->greaterThan($ayer)) {
            return 0;
        }

        $registrados = $pasante->asistencias()
            ->enRango($inicio, $ayer)
            ->get()
            ->map(fn (Asistencia $turno) => $turno->fecha->toDateString())
            ->all();

        $faltantes = 0;

        for ($dia = $inicio->copy(); $dia->lessThanOrEqualTo($ayer); $dia->addDay()) {
            if (Asistencia::esDiaHabil($dia) && ! in_array($dia->toDateString(), $registrados)) {
                $faltantes++;
            }
        }

        return $faltantes;
    }
}
