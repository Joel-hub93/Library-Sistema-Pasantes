<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_docente')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('correo')->nullable();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->string('estado')->default('1');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutores');
    }
};
