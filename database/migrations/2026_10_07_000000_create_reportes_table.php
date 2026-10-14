<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de informes finales generados por el administracion.
     *
     * Solo guarda la referencia al documento: los datos del informe (horas,
     * registros, periodo) se leen de `asistencias` y `pasantes` al momento de
     * armar el PDF, y quedan congelados dentro del archivo guardado en el
     * disco privado.
     *
     * La fila con estado '1' es la que bloquea el registro de nuevas
     * asistencias del pasante: no existe un informe activo => puede seguir
     * marcando; ya existe => el periodo quedo cerrado.
     */
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();

            // restrictOnDelete: borrar un pasante no debe dejar un PDF huerfano
            // sin dueno; primero se anula o se limpia el archivo.
            $table->foreignId('pasante_id')
                ->constrained('pasantes')
                ->restrictOnDelete();

            // Ruta dentro del disco privado (storage/app/private), nunca en
            // /storage publico: el informe contiene datos personales.
            $table->text('ruta_pdf');

            // Quien lo genero. restrictOnDelete para no perder la trazabilidad
            // del documento si se elimina el usuario.
            $table->foreignId('generado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('fecha_generacion');

            // '1' = activo (bloquea el pasante), '0' = anulado (reabre el
            // registro). Mismo patron de estado que el resto del proyecto.
            $table->string('estado', 1)->default('1');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
