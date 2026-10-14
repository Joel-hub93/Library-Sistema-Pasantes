<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * El pasante tambien es usuario del sistema.
 *
 * Entra con su cedula como usuario y como contrasena (cifrada). La guard
 * 'pasante' es INDEPENDIENTE de la guard 'web' de los administradores, asi que
 * las dos identidades nunca se mezclan en una misma sesion: ese era el origen
 * de los fallos de la vieja identificacion por cedula en la sesion.
 */
class Pasante extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    // Guard de Spatie y de Laravel a la que pertenece el pasante
    protected $guard_name = 'pasante';

    // Campos que se pueden guardar masivamente
    protected $fillable = [
        'cedula',
        'nombres',
        'apellidos',
        'email',
        'carrera_id',
        'telefono',
        'fecha_ingreso',
        'tutor_id',
        'estado',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        // La contrasena se guarda cifrada y el cast la cifra al asignarla:
        // al crear o renovar el acceso solo se asigna la cedula en texto plano
        'password' => 'hashed',
    ];

    /**
     * La cedula es la contrasena por defecto del pasante.
     *
     * Se usa al crear la cuenta y cuando el administrador le renueva el acceso.
     * El cast 'hashed' la cifra al guardarse.
     */
    public function asignarContrasenaCedula(): void
    {
        $this->password = $this->cedula;
        $this->save();
    }

    // Carrera que cursa el pasante
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    // Tutor academico asignado al pasante
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    // Turnos de asistencia del pasante
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    // Informes finales generados a este pasante (historial completo)
    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    // Solo los pasantes habilitados
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', '1');
    }

    /**
     * El informe activo del pasante, si ya se genero.
     *
     * Es la unica fila que puede haber por pasante con estado '1': la que
     * cierra el periodo de registro de asistencias.
     */
    public function reporteActivo(): ?Reporte
    {
        return $this->reportes()
            ->activos()
            ->orderByDesc('fecha_generacion')
            ->first();
    }

    /**
     * Si el pasante ya tiene su informe final generado.
     *
     * Mientras sea false el pasante puede seguir marcando asistencias, aunque
     * ya tenga las horas cumplidas; una vez true el periodo quedo cerrado.
     */
    public function informeGenerado(): bool
    {
        return $this->reportes()->activos()->exists();
    }

    /**
     * Horas de practica que debe cumplir el pasante.
     *
     * Provienen de la carrera, no se escriben aca: cambiar el valor de una
     * carrera afecta a todos sus pasantes.
     */
    public function horasRequeridas(): int
    {
        return (int) ($this->carrera->horas_practicas ?? 0);
    }

    /**
     * Minutos de cumplimiento agrupados por dia.
     *
     * La meta diaria se aplica sobre el total del dia, no sobre cada turno por
     * separado: un pasante puede marcar dos veces, pero ambas juntas solo
     * aportan hasta la meta. El excedente se guarda aparte.
     *
     * @return array<string, array{cumplimiento: int, apoyo: int}>
     */
    private function minutosPorDia(): array
    {
        $meta = Asistencia::minutosMetaDiarios();

        $porDia = [];

        $this->asistencias
            ->groupBy(fn (Asistencia $turno) => $turno->fecha->toDateString())
            ->each(function ($turnos) use (&$porDia, $meta) {
                $registrados = $turnos->sum(fn (Asistencia $turno) => $turno->minutosRegistrados());

                $porDia[$turnos->first()->fecha->toDateString()] = [
                    'cumplimiento' => min($registrados, $meta),
                    'apoyo' => max(0, $registrados - $meta),
                ];
            });

        return $porDia;
    }

    /**
     * Minutos que el pasante lleva cumplidos hacia sus horas de practica.
     */
    public function minutosAcumulados(): int
    {
        return array_sum(array_column($this->minutosPorDia(), 'cumplimiento'));
    }

    /**
     * Minutos que el pasante ha apoyado al area por encima de la meta diaria.
     *
     * No suman a minutosAcumulados(): solo se reportan.
     */
    public function minutosApoyoAcumulados(): int
    {
        return array_sum(array_column($this->minutosPorDia(), 'apoyo'));
    }

    /**
     * Horas de practica cumplidas, redondeadas a dos decimales.
     */
    public function horasCumplidas(): float
    {
        return round($this->minutosAcumulados() / 60, 2);
    }

    /**
     * Horas de apoyo al area, redondeadas a dos decimales.
     */
    public function horasApoyo(): float
    {
        return round($this->minutosApoyoAcumulados() / 60, 2);
    }

    /**
     * Horas de practica que le faltan por cumplir.
     *
     * Nunca es negativo: si ya completo la meta, devuelve 0.
     */
    public function horasFaltantes(): float
    {
        $faltan = $this->horasRequeridas() - $this->horasCumplidas();

        return $faltan > 0 ? round($faltan, 2) : 0.0;
    }

    /**
     * Porcentaje de las horas de practica que ya cumplio.
     *
     * Se limita a 100 aunque el pasante siga registrando: el excedente se
     * reporta aparte en horasApoyo(), no como porcentaje sobrepasado. Devuelve
     * 0 si la carrera no tiene horas de practica definidas.
     */
    public function porcentajeCumplimiento(): float
    {
        $requeridas = $this->horasRequeridas();

        if ($requeridas <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->horasCumplidas() / $requeridas) * 100, 2));
    }

    /**
     * Promedio de asistencia: dias con al menos un turno registrado sobre los
     * dias habiles transcurridos desde la fecha de ingreso hasta hoy.
     *
     * Solo cuentan dias habiles (mismo criterio que la bitacora) para que un
     * fin de semana sin registrar no baje el promedio. Devuelve 0 mientras no
     * haya dias habiles transcurridos.
     */
    public function promedioAsistencia(): float
    {
        $hasta = Carbon::today();
        $desde = $this->fecha_ingreso?->copy()->startOfDay() ?? $hasta->copy();

        if ($desde->greaterThan($hasta)) {
            $desde = $hasta->copy();
        }

        $habiles = 0;

        for ($dia = $desde->copy(); $dia->lessThanOrEqualTo($hasta); $dia->addDay()) {
            if (Asistencia::esDiaHabil($dia)) {
                $habiles++;
            }
        }

        if ($habiles === 0) {
            return 0.0;
        }

        $asistidos = $this->asistencias
            ->filter(fn (Asistencia $turno) => $turno->fecha->betweenIncluded($desde, $hasta)
                && Asistencia::esDiaHabil($turno->fecha))
            ->groupBy(fn (Asistencia $turno) => $turno->fecha->toDateString())
            ->count();

        return round(($asistidos / $habiles) * 100, 2);
    }

    /**
     * Si el pasante ya completo sus horas de practica.
     *
     * Solo habilita el boton de generar el informe: el pasante puede seguir
     * registrando hasta que el administrador lo genere (informeGenerado()).
     */
    public function completoPracticas(): bool
    {
        return $this->horasRequeridas() > 0
            && $this->minutosAcumulados() >= $this->horasRequeridas() * 60;
    }

    /**
     * Estado del cumplimiento para mostrarlo en la interfaz.
     */
    public function estadoCumplimiento(): string
    {
        $requeridas = $this->horasRequeridas();

        if ($requeridas <= 0) {
            return 'Sin horas de practica definidas';
        }

        if ($this->completoPracticas()) {
            return 'Horas de practica completadas';
        }

        if ($this->minutosAcumulados() === 0) {
            return 'Sin horas registradas';
        }

        return 'En curso';
    }
}
