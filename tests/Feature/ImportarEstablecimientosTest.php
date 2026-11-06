<?php

namespace Tests\Feature;

use App\Models\Establecimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use Tests\TestCase;

class ImportarEstablecimientosTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_codes_nulls_and_is_repeatable(): void
    {
        $args = ['archivo' => base_path('Listado de Establecimientos.xls')];
        $this->artisan('establecimientos:importar', $args + ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('establecimientos', 0);
        $this->artisan('establecimientos:importar', $args)->expectsOutput('creados: 91')->assertSuccessful();
        $this->assertDatabaseCount('establecimientos', 91);
        $this->assertDatabaseHas('establecimientos', ['codigo_renipress' => '00002666', 'nombre' => 'CHACOCHE']);
        $this->assertSame(41, Establecimiento::whereNull('cota')->count());
        $this->assertSame('-13.94178231', Establecimiento::where('codigo_renipress', '00002666')->firstOrFail()->norte);
        $this->artisan('establecimientos:importar', $args)->expectsOutput('sin cambios: 91')->assertSuccessful();
        $this->assertDatabaseCount('establecimientos', 91);
    }

    public function test_duplicate_codes_reject_the_entire_import(): void
    {
        $book = (new Xls)->load(base_path('Listado de Establecimientos.xls'));
        $book->getSheet(0)->setCellValue('A92', '00002666');
        $file = tempnam(sys_get_temp_dir(), 'catalogo-test-');
        try {
            (new XlsWriter($book))->save($file);
            $this->artisan('establecimientos:importar', ['archivo' => $file])->assertFailed();
            $this->assertDatabaseCount('establecimientos', 0);
        } finally {
            $book->disconnectWorksheets();
            unlink($file);
        }
    }

    public function test_invalid_data_does_not_partially_update_the_catalog(): void
    {
        $book = (new Xls)->load(base_path('Listado de Establecimientos.xls'));
        $book->getSheet(0)->setCellValue('B92', '');
        $file = tempnam(sys_get_temp_dir(), 'catalogo-test-');
        try {
            (new XlsWriter($book))->save($file);
            $this->artisan('establecimientos:importar', ['archivo' => $file])->assertFailed();
            $this->assertDatabaseCount('establecimientos', 0);
        } finally {
            $book->disconnectWorksheets();
            unlink($file);
        }
    }
}
