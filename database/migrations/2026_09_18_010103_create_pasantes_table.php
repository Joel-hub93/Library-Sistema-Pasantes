<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El pasante es a la vez registro academico e identidad autenticable.
     *
     * Entra al portal con su cedula como usuario y como contrasena inicial
     * (cifrada con el cast 'hashed' del modelo). La columna password viaja en
     * blanco hasta que se sincroniza por primera vez: la cedula nunca se guarda
     * en texto plano.
     *
     * carrera_id y tutor_id son claves foraneas reales (antes existia una
     * columna de texto libre 'carrera'). restrictOnDelete impide borrar una
     * carrera o un tutor que todavia tengan pasantes asignados.
     */
    public function up(): void
    {
        Schema::create('pasantes', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 10)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 100)->unique();

            $table->foreignId('carrera_id')
                ->constrained('carreras')
                ->restrictOnDelete();

            $table->string('telefono', 15);
            $table->text('foto')->nullable();
            $table->date('fecha_ingreso');

            $table->foreignId('tutor_id')
                ->constrained('tutores')
                ->restrictOnDelete();

            $table->string('estado', 5);
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasantes');
    }
};
