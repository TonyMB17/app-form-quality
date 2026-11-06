<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EncuestasTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 10:15:00', 'America/Lima'));
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed([UsuarioPruebaSeeder::class, InstrumentoSeeder::class]);
        $this->actingAs(User::sole());
    }
    private function payload(int $numero = 1): array
    {
        return ['condicion' => 1, 'edad' => 25, 'sexo' => 1, 'estudios' => 3, 'seguro' => 2, 'paciente' => 1, 'personal' => 1, 'especialidad' => 'Consulta de prueba', 'inicio_token' => $this->get('/encuestas/nueva')->viewData('page')['props']['inicio_token'],
            'respuestas' => array_map(fn ($n) => ['item' => $n, 'expectativa' => $n === 1 ? 'No aplica' : 'Sin respuesta', 'percepcion' => $n === 1 ? 7 : 'Sin respuesta'], range(1,22))];
    }
    public function test_user_can_submit_once_and_edit_the_same_survey_while_open(): void
    {
        $payload = $this->payload();
        $secondPayload = $this->payload();
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->post('/encuestas', $secondPayload)->assertSessionHasErrors('acceso');
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->assertSame([1], DB::table('encuestas')->orderBy('numero')->pluck('numero')->all());
        $this->assertDatabaseCount('encuestas', 1);
        $this->assertDatabaseCount('respuestas', 22);
        $this->assertDatabaseHas('respuestas', ['item' => 1, 'expectativa' => 'No aplica', 'percepcion' => 7]);
        $this->assertDatabaseHas('respuestas', ['item' => 2, 'expectativa' => 'Sin respuesta', 'percepcion' => 'Sin respuesta']);
        $this->get('/encuestas')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Lista', false)->where('encuestas.total', 1));
        $this->get('/encuestas?numero=1&fecha=2026-11-06')->assertInertia(fn (AssertableInertia $p) => $p->where('encuestas.total', 1)->where('encuestas.data.0.numero', 1));
        $this->get('/encuestas?numero=999')->assertInertia(fn (AssertableInertia $p) => $p->where('encuestas.total', 0));
        $id = DB::table('encuestas')->value('id');
        $this->get('/encuestas/'.$id.'/editar')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Editar', false));
        $answers = $payload['respuestas'];
        $answers[1]['expectativa'] = '5';
        $answers[1]['percepcion'] = '6';
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertRedirect('/encuestas/'.$id);
        $this->assertDatabaseCount('encuestas', 1);
        $this->assertDatabaseHas('respuestas', ['encuesta_id' => $id, 'item' => 2, 'expectativa' => '5', 'percepcion' => '6']);
        $this->get('/encuestas/'.$id)->assertOk();
    }

    public function test_digitador_can_only_list_and_view_their_own_survey(): void
    {
        $this->post('/encuestas', $this->payload())->assertRedirect();
        $ownId = DB::table('encuestas')->where('user_id', User::sole()->id)->value('id');
        $other = new User;
        $other->name = 'Otro digitador de prueba';
        $other->dni = '99900001';
        $other->password = '99900001';
        $other->role = 'digitador';
        $other->establecimiento_id = User::sole()->establecimiento_id;
        $other->save();
        $foreignId = DB::table('encuestas')->insertGetId([
            'establecimiento_id' => $other->establecimiento_id,
            'periodo_id' => DB::table('periodos')->value('id'),
            'user_id' => $other->id,
            'numero' => 2,
            'envio_id' => (string) \Illuminate\Support\Str::uuid(),
            'version_instrumento' => 'abancay-2026',
            'fecha' => '2026-11-06',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/encuestas')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('es_admin', false)->where('encuestas.total', 1)->where('encuestas.data.0.id', $ownId));
        $this->get('/encuestas/'.$ownId)->assertOk();
        $this->get('/encuestas/'.$foreignId)->assertNotFound();
        $this->get('/tablero')->assertForbidden();
    }

    public function test_survey_edits_are_blocked_when_unpublished_or_at_the_deadline(): void
    {
        $payload = $this->payload();
        $this->post('/encuestas', $payload)->assertRedirect();
        $id = DB::table('encuestas')->value('id');
        $answers = $payload['respuestas'];
        $answers[1]['expectativa'] = '5';
        $answers[1]['percepcion'] = '6';

        DB::table('survey_settings')->where('id', 1)->update(['published' => false]);
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertSessionHasErrors('acceso');
        $this->assertDatabaseHas('respuestas', ['encuesta_id' => $id, 'item' => 2, 'expectativa' => 'Sin respuesta', 'percepcion' => 'Sin respuesta']);
        $this->get('/encuestas/'.$id.'/editar')->assertRedirect('/encuestas/'.$id);

        DB::table('survey_settings')->where('id', 1)->update(['published' => true, 'closes_at' => '2026-11-06 10:16:00']);
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertRedirect('/encuestas/'.$id);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 10:16:00', 'America/Lima'));
        $answers[1]['expectativa'] = '4';
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertSessionHasErrors('acceso');
        $this->assertDatabaseHas('respuestas', ['encuesta_id' => $id, 'item' => 2, 'expectativa' => '5', 'percepcion' => '6']);
    }

    public function test_global_admin_can_change_publication_and_lima_deadline(): void
    {
        $codes = DB::table('establecimientos')->pluck('codigo_renipress')->all();
        $this->artisan('piloto:administrador', ['--eess' => $codes])->assertSuccessful();
        $admin = User::where('role', 'administrador')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/administracion/encuesta')->assertInertia(fn (AssertableInertia $p) => $p->component('SurveySettings', false));
        $this->put('/administracion/encuesta', ['published' => true, 'closes_at' => '2026-11-06T10:16'])
            ->assertRedirect();
        $this->assertDatabaseHas('survey_settings', ['id' => 1, 'published' => true, 'closes_at' => '2026-11-06 10:16:00']);

        $this->put('/administracion/encuesta', ['published' => false, 'closes_at' => null])
            ->assertRedirect();
        $this->assertDatabaseHas('survey_settings', ['id' => 1, 'published' => false, 'closes_at' => null]);
    }

    public function test_demo_command_creates_idempotent_synthetic_users_and_surveys(): void
    {
        $this->artisan('piloto:encuestas', ['--cantidad' => 3])->assertSuccessful();

        $demoUsers = User::whereBetween('dni', ['99000001', '99000003'])->orderBy('dni')->get();
        $this->assertCount(3, $demoUsers);
        foreach ($demoUsers as $user) {
            $this->assertSame('digitador', $user->role);
            $this->assertTrue(Hash::check($user->dni, $user->password));
            $this->assertDatabaseHas('encuestas', [
                'user_id' => $user->id,
                'especialidad' => 'DATOS FICTICIOS DE PRUEBA - NO USAR EN RESULTADOS',
            ]);
            $surveyId = DB::table('encuestas')->where('user_id', $user->id)->value('id');
            $this->assertDatabaseCount('respuestas', 22 * 3);
            $this->assertSame(22, DB::table('respuestas')->where('encuesta_id', $surveyId)->count());
        }
        $this->assertDatabaseCount('encuestas', 3);

        $this->artisan('piloto:encuestas', ['--cantidad' => 3])->assertSuccessful();
        $this->assertDatabaseCount('encuestas', 3);
        $this->assertDatabaseCount('users', 4);
    }

    public function test_invalid_score_identity_override_and_closed_period_are_rejected(): void
    {
        $data = $this->payload(); $data['respuestas'][0]['expectativa'] = 8;
        $this->post('/encuestas', $data)->assertSessionHasErrors('respuestas.0.expectativa');
        $data['respuestas'][0]['expectativa'] = 99;
        $this->post('/encuestas', $data)->assertSessionHasErrors('respuestas.0.expectativa');
        $data['respuestas'][0]['expectativa'] = '99';
        $this->post('/encuestas', $data)->assertSessionHasErrors('respuestas.0.expectativa');
        $this->post('/encuestas', $this->payload() + ['establecimiento_id' => 1])->assertSessionHasErrors('establecimiento_id');
        $payload = $this->payload();
        DB::table('periodos')->update(['habilitado' => false]);
        $this->post('/encuestas', $payload)->assertSessionHasErrors('inicio_token');
        $this->assertDatabaseCount('encuestas', 0);
        $this->assertDatabaseCount('respuestas', 0);
    }
    public function test_other_establishment_surveys_are_not_visible(): void
    {
        $this->post('/encuestas', $this->payload());
        $id = DB::table('encuestas')->value('id');
        DB::table('encuestas')->where('id', $id)->update(['establecimiento_id' => DB::table('establecimientos')->where('codigo_renipress', '00002666')->value('id')]);
        $this->get('/encuestas/'.$id)->assertNotFound();
        $this->get('/encuestas')->assertInertia(fn (AssertableInertia $p) => $p->where('encuestas.total', 0));
    }
    public function test_metadata_is_automatic_and_cannot_be_overridden(): void
    {
        $payload = $this->payload();
        foreach (['numero' => 55, 'periodo_id' => 1, 'fecha' => '2020-01-01', 'hora_inicio' => '01:00', 'hora_final' => '02:00'] as $key => $value) {
            $this->post('/encuestas', $payload + [$key => $value])->assertSessionHasErrors($key);
        }
        $this->travel(7)->minutes();
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->assertDatabaseHas('encuestas', ['numero' => 1, 'fecha' => '2026-11-06', 'hora_inicio' => '10:15', 'hora_final' => '10:22']);
        $this->post('/encuestas', array_replace($payload, ['inicio_token' => 'alterado']))->assertSessionHasErrors('inicio_token');
        $this->assertDatabaseCount('encuestas', 1);
    }
    public function test_explicit_answers_are_required_and_special_options_are_excluded_from_results(): void
    {
        $payload = $this->payload();
        foreach (['', null] as $missing) {
            $invalid = $payload;
            $invalid['respuestas'][0]['expectativa'] = $missing;
            $this->post('/encuestas', $invalid)->assertSessionHasErrors('respuestas.0.expectativa');
        }
        $invalid = $payload;
        unset($invalid['respuestas'][0]['percepcion']);
        $this->post('/encuestas', $invalid)->assertSessionHasErrors('respuestas.0.percepcion');
        $this->post('/encuestas', array_replace($payload, ['edad' => null]))->assertSessionHasErrors('edad');
        $this->post('/encuestas', array_replace($payload, ['personal' => 6]))->assertSessionHasErrors('personal_otro');
        $this->assertDatabaseCount('encuestas', 0);
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->get('/tablero')->assertForbidden();
    }
    public function test_pdf_version_and_other_professional_are_saved_without_reinterpreting_legacy(): void
    {
        $payload = array_replace($this->payload(), ['seguro' => 2, 'personal' => 6, 'personal_otro' => 'Técnico de enfermería']);
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->assertDatabaseHas('encuestas', ['version_instrumento' => 'abancay-2026', 'seguro' => 2, 'personal_otro' => 'Técnico de enfermería']);
        $id = DB::table('encuestas')->value('id');
        $this->get('/encuestas/'.$id)->assertInertia(fn (AssertableInertia $p) => $p->where('items.8.percepcion', '¿Cuando usted presentó algún problema o dificultad se resolvió inmediatamente?'));
        DB::table('encuestas')->where('id', $id)->update(['version_instrumento' => 'servqual-provisional-v1']);
        $this->get('/encuestas/'.$id)->assertInertia(fn (AssertableInertia $p) => $p->where('encuesta.version_instrumento', 'servqual-provisional-v1')->where('encuesta.seguro', 2)->where('items.0.expectativa', null));
        $this->assertDatabaseHas('encuestas', ['numero' => 1, 'personal' => 6, 'personal_otro' => 'Técnico de enfermería']);
        $this->assertDatabaseCount('encuestas', 1);
    }
    public function test_form_has_22_items_and_all_routes_require_login(): void
    {
        $this->get('/encuestas/nueva')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Formulario', false)->has('items', 22)->where('eess.codigo_renipress', '00002630'));
        $payload = $this->payload();
        $this->post('/salir');
        foreach (['/encuestas', '/encuestas/nueva', '/encuestas/1'] as $url) $this->get($url)->assertRedirect('/ingresar');
        $this->post('/encuestas', $payload)->assertRedirect('/ingresar');
    }
}
