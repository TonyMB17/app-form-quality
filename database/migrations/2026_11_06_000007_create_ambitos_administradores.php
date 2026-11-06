<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('administrador_establecimiento', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('establecimiento_id')->constrained('establecimientos')->cascadeOnDelete();
            $t->primary(['user_id', 'establecimiento_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('administrador_establecimiento'); }
};
