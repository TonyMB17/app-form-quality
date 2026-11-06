<?php

namespace Database\Seeders;

use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('La cuenta de prueba solo se crea en local o testing.');
        }
        $eess = Establecimiento::where('codigo_renipress', '00002630')->firstOrFail();
        $user = User::firstOrNew(['dni' => '00000001']);
        if ($user->exists) {
            return;
        }
        $user->name = 'Usuario de Prueba Curahuasi';
        $user->password = 'Curahuasi-Prueba-2026!';
        $user->role = 'digitador';
        $user->establecimiento()->associate($eess);
        $user->save();
    }
}
