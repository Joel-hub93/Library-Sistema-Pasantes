<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Archivos privados de la app (fotos de pasantes, tutores y asistencias).
 *
 * Viven en el disco 'local' (storage/app/private), nunca en public/: son
 * rostros de personas reales y solo se sirven por rutas protegidas.
 *
 * Centralizar aqui el disco y la politica de privacidad evita que cada
 * controlador la reimplemente (o se olvide de aplicarla).
 */
class FotoPrivada
{
    /**
     * Disco privado por defecto. Si algun dia cambia (p.ej. a S3), se cambia
     * aqui y no en cada controlador.
     */
    private const DISCO = 'local';

    /**
     * Guarda la foto en la carpeta indicada y devuelve su ruta relativa.
     */
    public function guardar(UploadedFile $foto, string $carpeta): string
    {
        return $foto->store($carpeta, self::DISCO);
    }

    /**
     * Borra la foto del disco si hay una ruta registrada.
     */
    public function borrar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    /**
     * Reemplaza la foto anterior por la nueva y devuelve la ruta nueva.
     *
     * Es el caso de la edicion: se sube una foto distinta y hay que borrar la
     * vieja para no dejar archivos huerfanos.
     */
    public function reemplazar(?string $anterior, UploadedFile $nueva, string $carpeta): string
    {
        $this->borrar($anterior);

        return $this->guardar($nueva, $carpeta);
    }

    /**
     * Devuelve la foto como respuesta, o 404 si no hay ruta o el archivo no
     * existe. Nunca expone rutas ni errores del disco.
     */
    public function servir(?string $ruta): StreamedResponse
    {
        abort_if(! $ruta, 404);
        abort_unless(Storage::disk(self::DISCO)->exists($ruta), 404);

        return Storage::disk(self::DISCO)->response($ruta);
    }
}
