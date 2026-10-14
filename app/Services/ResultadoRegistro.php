<?php

namespace App\Services;

/**
 * Resultado de un marcado de asistencia del pasante.
 *
 * El servicio decide el desenlace (con su mensaje) y el controlador se limita
 * a traducirlo a una redireccion con exito o con error. Asi la regla de
 * negocio no depende de HTTP y puede probarse sola.
 */
final class ResultadoRegistro
{
    private function __construct(
        public readonly bool $exitoso,
        public readonly string $mensaje,
    ) {
    }

    public static function ok(string $mensaje): self
    {
        return new self(true, $mensaje);
    }

    public static function error(string $mensaje): self
    {
        return new self(false, $mensaje);
    }
}
