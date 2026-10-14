<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Tutor extends Model
{
    use HasFactory;

    protected $table = 'tutores';

    protected $fillable = [
        'codigo_docente',
        'nombres',
        'apellidos',
        'correo',
        'carrera_id',
        'estado',
        'foto',
    ];

    // Carrera a la que pertenece el tutor
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    // Pasantes que tiene asignados
    public function pasantes(): HasMany
    {
        return $this->hasMany(Pasante::class);
    }

    // Solo los tutores habilitados para ser asignados
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', '1');
    }
}
