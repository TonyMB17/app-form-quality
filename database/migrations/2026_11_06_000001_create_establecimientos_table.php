<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_renipress', 8)->unique();
            foreach (['nombre', 'clasificacion', 'tipo', 'departamento', 'provincia', 'distrito', 'red', 'microred', 'categoria'] as $field) {
                $table->string($field);
            }
            $table->decimal('norte', 18, 8)->nullable();
            $table->decimal('este', 18, 8)->nullable();
            $table->decimal('cota', 18, 8)->nullable();
            $table->timestamps();
            $table->index(['red', 'microred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
