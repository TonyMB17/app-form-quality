<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('encuestas', function (Blueprint $t) { $t->uuid('envio_id')->nullable()->unique(); $t->unsignedInteger('numero')->change(); }); }
    public function down(): void { Schema::table('encuestas', function (Blueprint $t) { $t->dropUnique(['envio_id']); $t->dropColumn('envio_id'); }); }
};
