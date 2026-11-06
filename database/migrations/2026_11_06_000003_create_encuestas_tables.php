<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $t) {
            $t->id(); $t->string('nombre')->unique(); $t->date('inicio'); $t->date('fin'); $t->boolean('habilitado')->default(false);
        });
        Schema::create('items', function (Blueprint $t) {
            $t->unsignedTinyInteger('numero')->primary(); $t->string('dimension');
        });
        Schema::create('encuestas', function (Blueprint $t) {
            $t->id(); $t->foreignId('establecimiento_id')->constrained('establecimientos')->restrictOnDelete();
            $t->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedSmallInteger('numero'); $t->string('version_instrumento'); $t->date('fecha');
            $t->time('hora_inicio')->nullable(); $t->time('hora_final')->nullable();
            foreach (['condicion', 'edad', 'sexo', 'estudios', 'seguro', 'paciente', 'personal'] as $f) { $t->unsignedTinyInteger($f)->nullable(); }
            $t->string('especialidad')->nullable(); $t->timestamps();
            $t->unique(['establecimiento_id', 'periodo_id', 'numero']);
        });
        Schema::create('respuestas', function (Blueprint $t) {
            $t->id(); $t->foreignId('encuesta_id')->constrained('encuestas')->cascadeOnDelete();
            $t->unsignedTinyInteger('item'); $t->foreign('item')->references('numero')->on('items')->restrictOnDelete();
            $t->unsignedTinyInteger('expectativa')->nullable(); $t->unsignedTinyInteger('percepcion')->nullable();
            $t->unique(['encuesta_id', 'item']);
        });
    }
    public function down(): void
    {
        foreach (['respuestas', 'encuestas', 'items', 'periodos'] as $table) { Schema::dropIfExists($table); }
    }
};
