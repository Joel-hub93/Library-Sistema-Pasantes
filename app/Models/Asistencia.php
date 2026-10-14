<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un turno de asistencia: pasante + fecha + jornada.
 *
 * La entrada y la salida viven en la misma fila, por eso `hora_salida` en null
 * significa "marco entrada pero todavia no marca salida".
 *
 * `jornada` es un enum de MySQL (`matutina`, `vespertina`, `nocturna`), por lo
 * que la base de datos ya garantiza que solo se guarden esos tres valores.
 */
class Asistencia extends Model
{
    use HasFactory;

    /**
     * Carpeta del disco privado donde viven las fotos de las asistencias.
     *
     * Vive en el modelo para que el portal del pasante, la gestion del
     * administrador y los servicios compartan una sola definicion en vez de
     * repetir el literal en cada lugar.
     */
    public const CARPETA_FOTOS = 'asistencias';

    protected $fillable = [
        'pasante_id',
        'fecha',
        'jornada',
        'hora_entrada',
        'hora_salida',
        'foto_entrada',
        'foto_salida',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Pasante al que pertenece el turno.
     */
    public function pasante(): BelongsTo
    {
        return $this->belongsTo(Pasante::class);
    }

    /**
     * Turnos dentro de un rango de fechas.
     *
     * Usa whereDate porque la columna tiene cast 'date': Eloquent la guarda como
     * 'Y-m-d H:i:s' y una comparacion de texto no encontraria los registros.
     */
    public function scopeEnRango(Builder $query, Carbon $desde, Carbon $hasta): Builder
    {
        return $query
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString());
    }

    /**
     * MySQL devuelve las columnas TIME como 'H:i:s' mientras el controlador
     * escribe 'H:i'. Sin esto, la misma fila mostraria '08:00' o '16:00:00'
     * segun se acabe de crear o se recargue de la base de datos.
     */
    protected function horaEntrada(): Attribute
    {
        return Attribute::get(fn (?string $hora) => self::normalizarHora($hora));
    }

    protected function horaSalida(): Attribute
    {
        return Attribute::get(fn (?string $hora) => self::normalizarHora($hora));
    }

    public static function normalizarHora(?string $hora): ?string
    {
        return $hora === null || $hora === '' ? null : substr($hora, 0, 5);
    }

    /**
     * Minutos como 'H:mm' sin tope de 24 horas.
     *
     * gmdate('H:i') reinicia a 0 al llegar a las 24 horas, asi que un total
     * acumulado de un periodo (p. ej. 480 h) se veria como '12:00' o '00:00'.
     * Para los totales que suman varios dias se necesita este formato.
     */
    public static function formatoHoras(int $minutos): string
    {
        $horas = intdiv($minutos, 60);

        return sprintf('%s:%02d', str_pad((string) $horas, 2, '0', STR_PAD_LEFT), $minutos % 60);
    }

    /**
     * Minutos que un pasante debe cumplir por dia (4 horas).
     */
    public static function minutosMetaDiarios(): int
    {
        return (int) config('asistencias.minutos_meta_diarios', 240);
    }

    /**
     * Convierte una hora a minutos desde medianoche.
     *
     * Se lee solo la parte 'H:i' porque MySQL devuelve 'H:i:s' y el controlador
     * escribe 'H:i'. Se trabaja con enteros en lugar de comparar cadenas, que
     * darian resultados incorrectos entre '08:00' y '08:00:00'.
     */
    public static function minutosDeHora(?string $hora): int
    {
        if ($hora === null || $hora === '') {
            return 0;
        }

        [$h, $m] = array_pad(explode(':', $hora), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    /**
     * Si una fecha es dia habil para registrar asistencia.
     *
     * El pasante no registra sabado ni domingo. El administrador si puede
     * cargar esas fechas, por eso el filtro vive aca y no en las vistas.
     */
    public static function esDiaHabil(Carbon|string $fecha): bool
    {
        return ! Carbon::parse($fecha)->startOfDay()->isWeekend();
    }

    /**
     * Si la fecha del turno cae en sabado o domingo.
     *
     * El administrador si puede registrar esos dias, por eso la vista solo lo
     * muestra como aviso y no lo bloquea.
     */
    public function esFinDeSemana(): bool
    {
        return ! self::esDiaHabil($this->fecha);
    }

    /**
     * Total de horas del turno en formato 'H:i:s' (por ejemplo '04:00:00').
     *
     * No se guarda en la tabla: se calcula restando la salida a la entrada, de
     * modo que nunca queda desactualizado si el administrador edita las horas.
     * Se topa en la meta diaria (04:00): el excedente se muestra aparte como
     * horas de apoyo. Devuelve '00:00:00' si el turno aun no tiene salida.
     */
    public function totalHoras(): string
    {
        return $this->aHorasMinSegundos(min($this->minutosRegistrados(), self::minutosMetaDiarios()));
    }

    /**
     * Formatea minutos como 'H:i:s'.
     */
    private function aHorasMinSegundos(int $minutos): string
    {
        return gmdate('H:i:s', $minutos * 60);
    }

    /**
     * Minutos reales del turno, sin aplicar la meta diaria.
     *
     * Es la diferencia entre entrada y salida. Devuelve 0 si la salida aun no
     * se registro.
     */
    public function minutosRegistrados(): int
    {
        if (! $this->hora_salida) {
            return 0;
        }

        return max(
            0,
            self::minutosDeHora($this->hora_salida) - self::minutosDeHora($this->hora_entrada)
        );
    }

    /**
     * Minutos de este turno que cuentan para las horas de practica.
     *
     * Un turno nunca aporta mas de la meta diaria, sin importar si el pasante
     * se quedo de mas. El excedente se separa en minutosApoyo().
     */
    public function minutosCumplimiento(): int
    {
        return min($this->minutosRegistrados(), self::minutosMetaDiarios());
    }

    /**
     * Minutos que el pasante apoya al area por encima de la meta diaria.
     *
     * No suman al cumplimiento de las horas de practica, solo se reportan.
     */
    public function minutosApoyo(): int
    {
        return max(0, $this->minutosRegistrados() - self::minutosMetaDiarios());
    }

    /**
     * Estado del turno para mostrarlo en la interfaz.
     */
    public function estado(): string
    {
        if (! $this->hora_salida) {
            return 'Salida no registrada';
        }

        if ($this->minutosCumplimiento() >= self::minutosMetaDiarios()) {
            return $this->minutosApoyo() > 0 ? 'Cumplido con apoyo' : 'Cumplido';
        }

        return 'Cumplimiento parcial';
    }

    /**
     * El turno ya tiene salida registrada.
     */
    public function completo(): bool
    {
        return $this->hora_salida !== null;
    }

    /**
     * Ruta de la foto del tipo pedido ('entrada' o 'salida').
     *
     * Devuelve null para cualquier otro tipo. Las dos pantallas de fotos (la
     * del administrador y la del portal del pasante) comparten esta seleccion
     * y delegan en FotoPrivada, que responde 404 cuando la ruta es null.
     */
    public function rutaFoto(string $tipo): ?string
    {
        return match ($tipo) {
            'entrada' => $this->foto_entrada,
            'salida' => $this->foto_salida,
            default => null,
        };
    }
}
