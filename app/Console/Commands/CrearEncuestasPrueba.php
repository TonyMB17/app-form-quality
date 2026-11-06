<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SurveyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrearEncuestasPrueba extends Command
{
    protected $signature = 'piloto:encuestas {--cantidad=10 : Cantidad de cuentas y encuestas ficticias (1-10)}';

    protected $description = 'Crea cuentas digitadoras y encuestas ficticias en local/testing';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Disponible únicamente en local/testing.');

            return self::FAILURE;
        }

        $cantidad = filter_var($this->option('cantidad'), FILTER_VALIDATE_INT);
        if ($cantidad === false || $cantidad < 1 || $cantidad > 10) {
            $this->error('La cantidad debe ser un entero entre 1 y 10.');

            return self::FAILURE;
        }

        try {
            $cuentas = DB::transaction(function () use ($cantidad) {
                $settings = SurveyAccess::settings(true);
                if (! $settings || ! SurveyAccess::open($settings)) {
                    throw new \RuntimeException('La encuesta debe estar publicada y dentro del plazo.');
                }

                $today = now('America/Lima')->toDateString();
                $periodo = DB::table('periodos')->where('habilitado', true)
                    ->where('inicio', '<=', $today)->where('fin', '>=', $today)
                    ->lockForUpdate()->first();
                if (! $periodo) {
                    throw new \RuntimeException('No hay un periodo habilitado vigente.');
                }
                if (DB::table('items')->count() !== 22) {
                    throw new \RuntimeException('El instrumento debe tener sus 22 ítems cargados.');
                }

                $cuentasExistentes = User::whereBetween('dni', ['99000001', '99000010'])->get()->keyBy('dni');
                $eessExistentes = [];
                foreach ($cuentasExistentes as $cuenta) {
                    if ($cuenta->role !== 'digitador' || ! $cuenta->establecimiento_id) {
                        throw new \RuntimeException("El DNI sintético {$cuenta->dni} ya está ocupado.");
                    }
                    $eessExistentes[$cuenta->dni] = $cuenta->establecimiento_id;
                }

                $asignados = DB::table('users')->where('role', 'digitador')
                    ->whereNotNull('establecimiento_id')->pluck('establecimiento_id')->all();
                $disponibles = DB::table('establecimientos')->whereNotIn('id', $asignados)
                    ->orderBy('codigo_renipress')->get(['id', 'codigo_renipress'])->keyBy('id');
                $seleccionados = array_values($eessExistentes);
                $resultado = [];
                $inicio = CarbonImmutable::now('America/Lima');

                for ($i = 1; $i <= $cantidad; $i++) {
                    $dni = (string) (99000000 + $i);
                    $user = $cuentasExistentes->get($dni);
                    if (! $user) {
                        $eess = $disponibles->first(fn ($establecimiento) => ! in_array($establecimiento->id, $seleccionados, true));
                        if (! $eess) {
                            throw new \RuntimeException('No hay suficientes establecimientos sin cuenta digitadora.');
                        }

                        $user = new User;
                        $user->dni = $dni;
                        $user->name = 'Usuario de Prueba '.$eess->codigo_renipress;
                        $user->password = $dni;
                        $user->role = 'digitador';
                        $user->establecimiento_id = $eess->id;
                        $user->save();
                        $seleccionados[] = $eess->id;
                        $eessExistentes[$dni] = $eess->id;
                    } else {
                        $eess = $disponibles->get($user->establecimiento_id)
                            ?? DB::table('establecimientos')->where('id', $user->establecimiento_id)->first(['id', 'codigo_renipress']);
                    }

                    DB::table('establecimientos')->where('id', $user->establecimiento_id)->lockForUpdate()->first();
                    $survey = DB::table('encuestas')->where('user_id', $user->id)->first();
                    if (! $survey) {
                        $numero = 1 + (int) DB::table('encuestas')->where('establecimiento_id', $user->establecimiento_id)
                            ->where('periodo_id', $periodo->id)->max('numero');
                        $encuestaId = DB::table('encuestas')->insertGetId([
                            'establecimiento_id' => $user->establecimiento_id,
                            'periodo_id' => $periodo->id,
                            'user_id' => $user->id,
                            'numero' => $numero,
                            'envio_id' => (string) Str::uuid(),
                            'version_instrumento' => 'abancay-2026',
                            'fecha' => $inicio->toDateString(),
                            'hora_inicio' => $inicio->format('H:i:s'),
                            'hora_final' => $inicio->format('H:i:s'),
                            'condicion' => 1 + (($i - 1) % 2),
                            'edad' => 20 + (($i * 3) % 45),
                            'sexo' => 1 + (($i - 1) % 2),
                            'estudios' => 1 + (($i - 1) % 5),
                            'seguro' => 1 + (($i - 1) % 3),
                            'paciente' => 1 + (($i - 1) % 2),
                            'personal' => 1 + (($i - 1) % 5),
                            'especialidad' => 'DATOS FICTICIOS DE PRUEBA - NO USAR EN RESULTADOS',
                            'created_at' => $inicio,
                            'updated_at' => $inicio,
                        ]);

                        foreach (range(1, 22) as $item) {
                            DB::table('respuestas')->insert([
                                'encuesta_id' => $encuestaId,
                                'item' => $item,
                                'expectativa' => (string) (5 + (($item + $i) % 3)),
                                'percepcion' => (string) (4 + (($item + $i) % 4)),
                            ]);
                        }
                    }

                    $resultado[] = [
                        'dni' => $dni,
                        'codigo_renipress' => $eess->codigo_renipress,
                        'numero' => $survey->numero ?? $numero,
                    ];
                }

                return $resultado;
            });
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Cuentas y encuestas de prueba listas (la contraseña de cada cuenta es su DNI):');
        foreach ($cuentas as $cuenta) {
            $this->line("DNI: {$cuenta['dni']} | EESS: {$cuenta['codigo_renipress']} | Encuesta: #{$cuenta['numero']}");
        }

        return self::SUCCESS;
    }
}
