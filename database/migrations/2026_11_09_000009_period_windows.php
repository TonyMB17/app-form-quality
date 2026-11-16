<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('periodos', function(Blueprint $t) { $t->string('estado')->default('borrador'); $t->dateTime('abre_at')->nullable(); $t->dateTime('cierra_at')->nullable(); });
  foreach(DB::table('periodos')->get() as $p) DB::table('periodos')->where('id',$p->id)->update(['estado'=>$p->habilitado ? 'publicado' : 'cerrado','abre_at'=>$p->inicio.' 00:00:00','cierra_at'=>$p->fin.' 23:59:59']);
  Schema::create('encuesta_cambios', function(Blueprint $t) { $t->id(); $t->foreignId('encuesta_id')->constrained('encuestas'); $t->foreignId('user_id')->constrained('users'); $t->json('antes'); $t->json('despues'); $t->timestamp('created_at'); });
 }
 public function down(): void { Schema::dropIfExists('encuesta_cambios'); Schema::table('periodos',fn(Blueprint $t)=>$t->dropColumn(['estado','abre_at','cierra_at'])); }
};
