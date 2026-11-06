<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('survey_settings', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->boolean('published')->default(true);
            $t->dateTime('closes_at')->nullable();
            $t->timestamps();
        });
        DB::table('survey_settings')->insert(['id' => 1, 'published' => true]);
    }
    public function down(): void { Schema::dropIfExists('survey_settings'); }
};
