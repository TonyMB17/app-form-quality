<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\EncuestaItemsSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MetasTest extends TestCase
{
    use RefreshDatabase;

    private User $digitador;

    private User $admin;

    private int $eess;

    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-16 10:00:00', 'America/Lima'));
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed([UsuarioPruebaSeeder::class, EncuestaItemsSeeder::class]);
        $this->digitador = User::sole();
        $this->eess = $this->digitador->establecimiento_id;
        $this->other = DB::table('establecimientos')->where('id', '!=', $this->eess)->value('id');
        $this->admin = new User(['name' => 'Administrador', 'dni' => '87654321', 'password' => 'Test-only-123']);
        $this->admin->role = 'administrador';
        $this->admin->save();
        DB::table('administrador_establecimiento')->insert(['user_id' => $this->admin->id, 'establecimiento_id' => $this->eess]);
    }

    public function test_defaults_permissions_and_updates_are_scoped(): void
    {
        $this->assertSame(91, DB::table('establecimientos')->where('meta_encuestas', 5)->count());
        $this->get('/administracion/metas')->assertRedirect('/ingresar');
        $this->actingAs($this->digitador)->get('/administracion/metas')->assertForbidden();
        $this->put('/administracion/metas/'.$this->eess, ['meta_encuestas' => 9])->assertForbidden();
        $this->actingAs($this->admin)->get('/administracion/metas')->assertInertia(fn (Assert $p) => $p->component('Administracion/Metas')->has('establecimientos', 1)->where('establecimientos.0.id', $this->eess)->where('establecimientos.0.meta_encuestas', 5));
        $this->put('/administracion/metas/'.$this->other, ['meta_encuestas' => 9])->assertNotFound();
        foreach ([0, -1, 1.5, 'abc', null, 100001] as $invalid) {
            $this->put('/administracion/metas/'.$this->eess, ['meta_encuestas' => $invalid])->assertSessionHasErrors('meta_encuestas');
        }
        $this->put('/administracion/metas/'.$this->eess, ['meta_encuestas' => 8])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('establecimientos', ['id' => $this->eess, 'meta_encuestas' => 8]);
        $this->assertDatabaseHas('establecimientos', ['id' => $this->other, 'meta_encuestas' => 5]);
        $this->actingAs($this->digitador)->get('/mi-establecimiento')->assertInertia(fn (Assert $p) => $p->where('meta.objetivo', 8)->where('meta.realizadas', 0));
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->assertDatabaseHas('establecimientos', ['id' => $this->eess, 'meta_encuestas' => 8]);
    }

    public function test_progress_is_individual_and_scoped_to_period_and_establishment(): void
    {
        $period = DB::table('periodos')->value('id');
        $old = DB::table('periodos')->insertGetId(['nombre' => 'Anterior', 'inicio' => '2025-01-01', 'fin' => '2025-01-15', 'estado' => 'cerrado', 'habilitado' => false]);
        $peer = new User(['name' => 'Segundo digitador', 'dni' => '87654322', 'password' => 'Test-only-123']);
        $peer->role = 'digitador';
        $peer->establecimiento_id = $this->eess;
        $peer->save();
        foreach ([[$this->digitador->id, $this->eess, $period], [$this->digitador->id, $this->eess, $old], [$this->digitador->id, $this->other, $period], [$peer->id, $this->eess, $period]] as $i => $row) {
            DB::table('encuestas')->insert(['user_id' => $row[0], 'establecimiento_id' => $row[1], 'periodo_id' => $row[2], 'numero' => $i + 1, 'fecha' => '2026-11-16']);
        }
        $this->actingAs($this->digitador)->get('/mi-establecimiento')->assertInertia(fn (Assert $p) => $p->where('meta.realizadas', 1)->where('meta.objetivo', 5));
        $this->actingAs($this->admin)->get('/administracion/metas?periodo='.$period)->assertInertia(fn (Assert $p) => $p->has('establecimientos.0.digitadores', 2)->where('establecimientos.0.digitadores.0.realizadas', 1)->where('establecimientos.0.digitadores.1.realizadas', 1));
        $this->get('/administracion/metas?periodo='.$old)->assertInertia(fn (Assert $p) => $p->where('establecimientos.0.digitadores.0.realizadas', 0)->where('establecimientos.0.digitadores.1.realizadas', 1));
        DB::table('periodos')->update(['estado' => 'cerrado']);
        $this->actingAs($this->digitador)->get('/mi-establecimiento')->assertInertia(fn (Assert $p) => $p->where('periodo',null)->where('meta.realizadas',0));
    }
}
