<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->string('expectativa', 16)->nullable()->change();
            $table->string('percepcion', 16)->nullable()->change();
        });
        foreach (['expectativa', 'percepcion'] as $field) {
            DB::table('respuestas')->where($field, '99')->update([$field => 'No aplica']);
        }
    }

    public function down(): void
    {
        foreach (['expectativa', 'percepcion'] as $field) {
            DB::table('respuestas')->where($field, 'No aplica')->update([$field => '99']);
        }
        Schema::table('respuestas', function (Blueprint $table) {
            $table->unsignedTinyInteger('expectativa')->nullable()->change();
            $table->unsignedTinyInteger('percepcion')->nullable()->change();
        });
    }
};
