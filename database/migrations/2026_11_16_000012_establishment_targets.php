<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('establecimientos', 'meta_encuestas')) {
            Schema::table('establecimientos', fn (Blueprint $table) => $table->unsignedInteger('meta_encuestas')->default(5));
        }
    }

    public function down(): void
    {
        Schema::table('establecimientos', fn (Blueprint $table) => $table->dropColumn('meta_encuestas'));
    }
};
