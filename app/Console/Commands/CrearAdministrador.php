<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CrearAdministrador extends Command
{
    protected $signature = 'usuarios:crear-administrador {--todos : Autorizar todos los establecimientos del catálogo actual} {--eess=* : Códigos RENIPRESS autorizados}';
    protected $description = 'Crea una cuenta administradora nueva con contraseña oculta y ámbito explícito';

    public function handle(): int
    {
        $codes = array_values(array_unique($this->option('eess')));
        if ((bool) $this->option('todos') === (count($codes) > 0)) {
            $this->error('Elige --todos o uno o más --eess, sin combinarlos.');
            return self::FAILURE;
        }
        $ids = DB::table('establecimientos')->when(!$this->option('todos'), fn ($q) => $q->whereIn('codigo_renipress', $codes))->pluck('id');
        if ($ids->isEmpty() || (!$this->option('todos') && $ids->count() !== count($codes))) {
            $this->error('Importa el catálogo y verifica los códigos indicados.');
            return self::FAILURE;
        }
        $data = ['name'=>$this->ask('Nombre completo'), 'dni'=>$this->ask('DNI'), 'password'=>$this->secret('Contraseña (mínimo 12 caracteres)'), 'password_confirmation'=>$this->secret('Repite la contraseña')];
        $validator = Validator::make($data, ['name'=>'required|string|max:255', 'dni'=>'required|digits:8|unique:users,dni', 'password'=>'required|string|min:12|max:255|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) $this->error($message);
            return self::FAILURE;
        }
        $user = new User(['name' => $data['name'], 'dni' => $data['dni'], 'password' => $data['password']]);
        $user->role = 'administrador';
        $user->save();
        foreach ($ids as $id) {
            DB::table('administrador_establecimiento')->insert(['user_id' => $user->id, 'establecimiento_id' => $id]);
        }
        $this->info('Administrador creado con '.$ids->count().' establecimientos autorizados.');
        return self::SUCCESS;
    }
}
