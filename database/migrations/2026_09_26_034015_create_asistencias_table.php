<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un turno es la unidad de registro: pasante + fecha + jornada.
     *
     * La unique (pasante, fecha, jornada) es la clave natural del turno: impide
     * dos entradas el mismo dia y hace imposible una "salida sin entrada" a
     * nivel de base de datos, no solo en el servicio. hora_salida NULL significa
     * que aun no se marco la salida.
     *
     * restrictOnDelete: borrar un pasante con historial debe fallar, no destruir
     * en silencio las asistencias ya registradas.
     *
     * Las fotos se guardan como rutas dentro del disco privado de la app, nunca
     * en /storage publico: son rostros de personas reales.
     */
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pasante_id')
                ->constrained('pasantes')
                ->restrictOnDelete();

            $table->date('fecha');

            $table->enum('jornada', ['matutina', 'vespertina', 'nocturna']);

            $table->time('hora_entrada');

            // NULL significa "salida no registrada"
            $table->time('hora_salida')->nullable();

            $table->text('foto_entrada')->nullable();
            $table->text('foto_salida')->nullable();

            $table->timestamps();

            $table->unique(['pasante_id', 'fecha', 'jornada'], 'asistencias_turno_unico');
            $table->index(['fecha', 'jornada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
