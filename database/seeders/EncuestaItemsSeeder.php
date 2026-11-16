<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EncuestaItemsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 22) as $n) {
            $dimension = match (true) { $n <= 5 => 'Fiabilidad', $n <= 9 => 'Capacidad de respuesta', $n <= 13 => 'Seguridad', $n <= 18 => 'Empatía', default => 'Aspectos tangibles' };
            DB::table('items')->updateOrInsert(['numero' => $n], ['dimension' => $dimension]);
        }
        if (app()->environment(['local', 'testing'])) {
            DB::table('periodos')->insertOrIgnore(['nombre' => 'Piloto técnico 2026', 'inicio' => '2026-01-01', 'fin' => '2026-12-31', 'habilitado' => true, 'estado' => 'publicado', 'abre_at' => '2026-01-01 00:00:00', 'cierra_at' => '2027-01-01 00:00:00']);
        }
    }
}
