<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Carrera extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_carrera',
        'nombre',
        'horas_practicas',
        'estado',
    ];

    // Pasantes inscritos en la carrera
    public function pasantes(): HasMany
    {
        return $this->hasMany(Pasante::class);
    }

    // Tutores asignados a la carrera
    public function tutores(): HasMany
    {
        return $this->hasMany(Tutor::class);
    }

    // Solo las carreras habilitadas para ser asignadas
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', '1');
    }
}
