<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('dni', 8)->nullable()->unique();
            $table->foreignId('establecimiento_id')->nullable()->constrained('establecimientos')->restrictOnDelete();
            $table->enum('role', ['digitador', 'administrador'])->default('digitador');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('establecimiento_id');
            $table->dropUnique(['dni']);
            $table->dropColumn(['dni', 'role']);
        });
        // Email permanece anulable para no invalidar las cuentas sin correo.
    }
};
