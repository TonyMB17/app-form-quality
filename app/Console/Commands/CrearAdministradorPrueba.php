<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrearAdministradorPrueba extends Command
{
    protected $signature = 'piloto:administrador {--eess=* : Códigos RENIPRESS autorizados}';
    protected $description = 'Crea la cuenta administradora ficticia local con ámbito explícito';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Disponible únicamente en local/testing.'); return self::FAILURE;
        }
        $codes = array_values(array_unique($this->option('eess')));
        $ids = DB::table('establecimientos')->whereIn('codigo_renipress', $codes)->pluck('id');
        if (!$codes || $ids->count() !== count($codes)) {
            $this->error('Indica códigos existentes mediante --eess.'); return self::FAILURE;
        }
        $existing = User::where('dni', '00000002')->first();
        if ($existing && $existing->role !== 'administrador') {
            $this->error('El DNI de prueba está ocupado por otra cuenta.'); return self::FAILURE;
        }
        DB::transaction(function () use ($ids) {
            $user = User::firstOrNew(['dni' => '00000002']);
            if (!$user->exists) {
                $user->name = 'Administrador de Prueba';
                $user->password = 'Abancay-Admin-Prueba-2026!';
                $user->role = 'administrador';
                $user->save();
            }
            DB::table('administrador_establecimiento')->where('user_id', $user->id)->delete();
            foreach ($ids as $id) DB::table('administrador_establecimiento')->insert(['user_id' => $user->id, 'establecimiento_id' => $id]);
        });
        $this->info('Administrador de prueba configurado con '.$ids->count().' EESS autorizados.');
        return self::SUCCESS;
    }
}
