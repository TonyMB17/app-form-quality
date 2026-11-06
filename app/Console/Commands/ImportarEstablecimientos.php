<?php

namespace App\Console\Commands;

use App\Models\Establecimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use RuntimeException;
use Throwable;

class ImportarEstablecimientos extends Command
{
    protected $signature = 'establecimientos:importar {archivo : Ruta al listado XLS} {--dry-run : Validar sin guardar}';

    protected $description = 'Importa el catálogo por código RENIPRESS sin eliminar establecimientos existentes';

    public const COLUMNAS = [
        'Código Único' => 'codigo_renipress',
        'Nombre del establecimiento' => 'nombre',
        'Clasificación' => 'clasificacion',
        'Tipo' => 'tipo',
        'Departamento' => 'departamento',
        'Provincia' => 'provincia',
        'Distrito' => 'distrito',
        'Red' => 'red',
        'Microrred' => 'microred',
        'Categoria' => 'categoria',
        'NORTE' => 'norte',
        'ESTE' => 'este',
        'COTA' => 'cota',
    ];

    public function handle(): int
    {
        $book = null;
        try {
            $path = $this->argument('archivo');
            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException('No se puede leer el archivo indicado.');
            }
            // Conservar formatos: el código puede ser texto o tener una máscara de ceros.
            $book = (new Xls)->load($path);
            $sheet = $book->getSheetByName('Listado de Establecimientos');
            if (! $sheet) {
                throw new RuntimeException('Falta la hoja Listado de Establecimientos.');
            }
            $headers = array_map(fn ($v) => trim((string) $v), $sheet->rangeToArray('A1:M1', null, false, true)[0]);
            if ($headers !== array_keys(self::COLUMNAS)) {
                throw new RuntimeException('Los encabezados A1:M1 no corresponden al formato esperado.');
            }
            $records = [];
            $seen = [];
            $rules = array_fill_keys(array_values(self::COLUMNAS), ['required', 'string', 'max:255']);
            $rules['codigo_renipress'] = ['required', 'regex:/^[0-9]{8}$/'];
            foreach (['norte', 'este', 'cota'] as $field) {
                $rules[$field] = ['nullable', 'regex:/^-?\d{1,10}(\.\d{1,8})?$/'];
            }
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $values = $sheet->rangeToArray("A{$row}:M{$row}", null, false, true)[0];
                $values = array_map(fn ($v) => trim((string) $v), $values);
                if (count(array_filter($values, fn ($v) => $v !== '')) === 0) {
                    continue;
                }
                $record = array_combine(array_values(self::COLUMNAS), $values);
                foreach (['norte', 'este', 'cota'] as $field) {
                    $record[$field] = $record[$field] === '' ? null : $record[$field];
                }
                $validator = Validator::make($record, $rules);
                if ($validator->fails()) {
                    throw new RuntimeException("Fila {$row}: ".implode(' ', $validator->errors()->all()));
                }
                $code = $record['codigo_renipress'];
                if (isset($seen[$code])) {
                    throw new RuntimeException("Código duplicado {$code} en filas {$seen[$code]} y {$row}.");
                }
                $seen[$code] = $row;
                $records[] = $record;
            }
            if ($records === []) {
                throw new RuntimeException('El archivo no contiene establecimientos.');
            }
            if ($this->option('dry-run')) {
                $this->info(count($records).' establecimientos válidos. No se guardaron cambios.');
                return self::SUCCESS;
            }
            $counts = DB::transaction(function () use ($records) {
                $counts = ['creados' => 0, 'actualizados' => 0, 'sin cambios' => 0];
                foreach ($records as $record) {
                    $model = Establecimiento::firstOrNew(['codigo_renipress' => $record['codigo_renipress']]);
                    $model->fill($record);
                    $status = ! $model->exists ? 'creados' : ($model->isDirty() ? 'actualizados' : 'sin cambios');
                    if ($model->isDirty()) {
                        $model->save();
                    }
                    $counts[$status]++;
                }
                return $counts;
            });
            foreach ($counts as $label => $count) {
                $this->line("{$label}: {$count}");
            }
            return self::SUCCESS;
        } catch (Throwable $error) {
            // Los errores de conexión pueden incluir parámetros de la conexión local.
            $this->error($error instanceof \Illuminate\Database\QueryException
                ? 'Error de base de datos. No se guardaron cambios en el catálogo. Verifica la conexión y las migraciones.'
                : $error->getMessage());
            return self::FAILURE;
        } finally {
            $book?->disconnectWorksheets();
        }
    }
}
