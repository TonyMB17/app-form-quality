<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('encuestas', 'version_instrumento')) {
            DB::table('encuestas')->where('version_instrumento', 'servqual-provisional-v1')->whereIn('seguro', [2, 3])
                ->update(['seguro' => DB::raw('CASE seguro WHEN 2 THEN 3 WHEN 3 THEN 2 END')]);

            Schema::table('encuestas', function (Blueprint $table) {
                $table->dropColumn('version_instrumento');
            });
        }
    }

    public function down(): void
    {
        Schema::table('encuestas', function (Blueprint $table) {
            $table->string('version_instrumento')->default('encuesta-unica');
        });
    }
};
