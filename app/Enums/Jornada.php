<?php

namespace App\Enums;

/**
 * Jornadas de asistencia.
 *
 * Reemplaza el mapa que antes vivia en config/asistencias.php: los valores y
 * sus etiquetas quedan en un solo sitio tipado. La columna `jornada` de la
 * tabla asistencias es un enum de MySQL con estos mismos valores, asi que el
 * enum solo aporta la lista para validar y para pintar los selects.
 */
enum Jornada: string
{
    case Matutina = 'matutina';
    case Vespertina = 'vespertina';
    case Nocturna = 'nocturna';

    /**
     * Etiqueta legible para la interfaz.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Matutina => 'Matutina',
            self::Vespertina => 'Vespertina',
            self::Nocturna => 'Nocturna',
        };
    }

    /**
     * Valores del enum, para las reglas de validacion (Rule::in).
     *
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $jornada) => $jornada->value, self::cases());
    }

    /**
     * Mapa valor => etiqueta, para pintar los selects de los formularios.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $jornada) {
            $opciones[$jornada->value] = $jornada->etiqueta();
        }

        return $opciones;
    }
}
