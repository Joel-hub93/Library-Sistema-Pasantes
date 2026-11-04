<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un dia no laborable configurado por el administrador.
 *
 * No tiene tipo ni provincia: cualquier fila bloquea el dia para todos los
 * pasantes. El nombre es opcional y solo sirve para mostrarlo en pantalla.
 */
class Feriado extends Model
{
    protected $fillable = [
        'fecha',
        'nombre',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
