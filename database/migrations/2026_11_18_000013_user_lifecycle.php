<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('auth_version')->default(0);
        });
        Schema::create('pending_photo_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_photo_deletions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['activo', 'auth_version']));
    }
};
