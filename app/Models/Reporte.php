<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Informe final de horas de practica de un pasante.
 *
 * La fila solo guarda la referencia al PDF (disco privado), quien lo genero y
 * cuando. Las horas, los registros y el periodo se leen de `asistencias` y
 * `pasantes` al momento de armar el documento: no se copian columnas que ya
 * existen en otras tablas.
 *
 * Un pasante tiene como maximo UN informe con estado '1'. Esa fila activa es
 * la que cierra el periodo: mientras no exista, el pasante sigue registrando
 * asistencias aunque ya tenga las horas cumplidas.
 */
class Reporte extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = '1';
    public const ESTADO_ANULADO = '0';

    protected $fillable = [
        'pasante_id',
        'ruta_pdf',
        'generado_por',
        'fecha_generacion',
        'estado',
    ];

    protected $casts = [
        'fecha_generacion' => 'date',
    ];

    /**
     * Pasante al que pertenece el informe.
     */
    public function pasante(): BelongsTo
    {
        return $this->belongsTo(Pasante::class);
    }

    /**
     * Usuario administrador que genero el informe.
     */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /**
     * Solo los informes activos (los que cierran el periodo).
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Si el informe cierra el periodo del pasante.
     */
    public function activo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }
}
