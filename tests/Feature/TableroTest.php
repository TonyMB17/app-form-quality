<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TableroTest extends TestCase
{
    use RefreshDatabase;
    private int $periodo;
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed([UsuarioPruebaSeeder::class, InstrumentoSeeder::class]);
        $this->actingAs(User::sole());
        $this->periodo = DB::table('periodos')->value('id');
    }
    private function survey(int $eess, int $numero): int
    {
        $id = DB::table('encuestas')->insertGetId(['establecimiento_id' => $eess, 'periodo_id' => $this->periodo, 'user_id' => User::sole()->id, 'numero' => $numero, 'fecha' => '2026-11-06', 'version_instrumento' => 'abancay-2026']);
        foreach (range(1,22) as $i) {
            DB::table('respuestas')->insert(['encuesta_id' => $id, 'item' => $i, 'expectativa' => $i === 4 ? 'No aplica' : ($i >= 5 ? null : '4'), 'percepcion' => $i === 1 ? '7' : ($i === 2 ? '4' : '1')]);
        }
        return $id;
    }
    public function test_counts_gaps_and_filtered_list_share_the_same_scope(): void
    {
        $own = User::sole()->establecimiento_id;
        $this->survey($own, 1);
        $this->survey($own, 2);
        $this->survey(DB::table('establecimientos')->where('id', '!=', $own)->value('id'), 1);
        $this->get('/tablero')->assertForbidden();
        $this->artisan('piloto:administrador', ['--eess' => [DB::table('establecimientos')->where('id', $own)->value('codigo_renipress')]])->assertSuccessful();
        $this->actingAs(User::where('role', 'administrador')->firstOrFail());
        $this->get('/tablero')->assertInertia(fn (AssertableInertia $p) => $p->component('Tablero', false)
            ->where('resumen.encuestas', 2)->where('resumen.establecimientos', 1)->where('resumen.validos', 6)->where('resumen.satisfechos', 4)->where('resumen.excluidos', 38)
            ->has('datos_generales.cuentas', 1)->where('datos_generales.cuentas.0.encuestas', 2)->where('datos_generales.microredes.0.total', 2)->where('datos_generales.distribuciones.sexo.0.total', 2)->has('comparacion', 1)->where('comparacion.0.encuestas', 2)->has('comparacion.0.items', 22)->where('comparacion.0.items.0.validos', 2)->where('comparacion.0.items.0.satisfechos', 2)->where('comparacion.0.items.2.satisfechos', 0)->where('comparacion.0.items.3.validos', 0)
            ->where('encuestas.total', 2)->has('catalogo', 1)->where('items.0.satisfechos', 2)->where('items.1.satisfechos', 2)->where('items.2.satisfechos', 0)
            ->where('items.3.no_aplica', 2)->where('items.4.incompletos', 2)->where('dimensiones.0.validos', 6)->where('dimensiones.1.validos', 0));
        $this->get('/tablero?version=servqual-provisional-v1')->assertInertia(fn (AssertableInertia $p) => $p->where('resumen.encuestas', 0)->where('resumen.validos', 0)->where('encuestas.total', 0)->has('comparacion', 1)->where('comparacion.0.encuestas', 0)->where('comparacion.0.items.0.validos', 0));
        $other = DB::table('establecimientos')->where('id', '!=', $own)->value('id');
        $this->get('/tablero?eess='.$other)->assertSessionHasErrors('eess');
    }
    public function test_admin_must_have_explicit_scope_and_detail_uses_it(): void
    {
        $user = User::sole();
        $allowed = $user->establecimiento_id;
        $id = $this->survey($allowed, 1);
        $other = DB::table('establecimientos')->where('id', '!=', $allowed)->value('id');
        $outside = $this->survey($other, 1);
        $this->get('/tablero')->assertForbidden();
        $user->role = 'administrador'; $user->save();
        $this->actingAs($user)->get('/tablero')->assertForbidden();
        DB::table('administrador_establecimiento')->insert(['user_id' => $user->id, 'establecimiento_id' => $allowed]);
        $this->get('/tablero')->assertInertia(fn (AssertableInertia $p) => $p->where('resumen.encuestas', 1)->has('catalogo', 1)->has('comparacion', 1)->where('comparacion.0.id', $allowed));
        $this->get('/encuestas')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Lista', false)->where('es_admin', true)->where('eess', null)->where('encuestas.total', 1));
        $this->get('/encuestas/'.$id)->assertOk();
        $this->get('/encuestas/'.$outside)->assertNotFound();
        $this->get('/encuestas/nueva')->assertForbidden();
        $this->get('/encuestas/'.$id.'/editar')->assertForbidden();
        $this->post('/salir');
        $this->get('/tablero')->assertRedirect('/ingresar');
    }
}
