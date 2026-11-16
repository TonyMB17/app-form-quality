<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('encuestas',fn(Blueprint $t)=>$t->string('foto_path')->nullable()); }
 public function down(): void { Schema::table('encuestas',fn(Blueprint $t)=>$t->dropColumn('foto_path')); }
};
