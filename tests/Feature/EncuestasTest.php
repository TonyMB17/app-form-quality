<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EncuestaItemsSeeder;
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
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 10:15:00', 'America/Lima'));
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed([UsuarioPruebaSeeder::class, EncuestaItemsSeeder::class]);
        $this->actingAs(User::sole());
    }
    private function payload(int $numero = 1): array
    {
        return ['foto' => \Illuminate\Http\UploadedFile::fake()->image('encuesta.jpg'), 'condicion' => 1, 'edad' => 25, 'sexo' => 1, 'estudios' => 3, 'seguro' => 2, 'paciente' => 1, 'personal' => 1, 'especialidad' => 'Consulta de prueba', 'inicio_token' => $this->get('/encuestas/nueva')->viewData('page')['props']['inicio_token'],
            'respuestas' => array_map(fn ($n) => ['item' => $n, 'expectativa' => $n === 1 ? 'No aplica' : 'Sin respuesta', 'percepcion' => $n === 1 ? 7 : 'Sin respuesta'], range(1,22))];
    }
    public function test_user_can_submit_multiple_surveys_and_edit_older_owned_records(): void
    {
        $payload = $this->payload();
        $secondPayload = $this->payload();
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->post('/encuestas', $secondPayload)->assertRedirect();
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->assertSame([1, 2], DB::table('encuestas')->orderBy('numero')->pluck('numero')->all());
        $this->assertDatabaseCount('encuestas', 2);
        $this->assertDatabaseCount('respuestas', 44);
        $this->assertDatabaseHas('respuestas', ['item' => 1, 'expectativa' => 'No aplica', 'percepcion' => 'No aplica']);
        $this->assertDatabaseHas('respuestas', ['item' => 2, 'expectativa' => 'Sin respuesta', 'percepcion' => 'Sin respuesta']);
        $this->get('/encuestas')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Lista', false)->where('encuestas.total', 2));
        $this->get('/encuestas?numero=1&fecha=2026-11-06')->assertInertia(fn (AssertableInertia $p) => $p->where('encuestas.total', 1)->where('encuestas.data.0.numero', 1));
        $this->get('/encuestas?numero=999')->assertInertia(fn (AssertableInertia $p) => $p->where('encuestas.total', 0));
        $id = DB::table('encuestas')->value('id');
        $this->get('/encuestas/'.$id.'/editar')->assertInertia(fn (AssertableInertia $p) => $p->component('Encuestas/Editar', false));
        $answers = $payload['respuestas'];
        $answers[1]['expectativa'] = '5';
        $answers[1]['percepcion'] = '6';
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertRedirect('/encuestas/'.$id);
        $this->assertDatabaseCount('encuestas', 2);
        $this->assertDatabaseHas('respuestas', ['encuesta_id' => $id, 'item' => 2, 'expectativa' => '5', 'percepcion' => '6']);
        $this->assertDatabaseHas('encuesta_cambios', ['encuesta_id'=>$id,'user_id'=>User::sole()->id]);
        $this->get('/encuestas/'.$id)->assertOk();
    }

    public function test_perception_follows_unrated_expectation_on_create_and_edit(): void
    {
        $payload = $this->payload();
        unset($payload['respuestas'][0]['percepcion']);
        $payload['respuestas'][1]['percepcion'] = '7';
        $this->post('/encuestas', $payload)->assertSessionHasNoErrors();
        $id = DB::table('encuestas')->value('id');
        $this->assertDatabaseHas('respuestas', ['encuesta_id'=>$id, 'item'=>1, 'percepcion'=>'No aplica']);
        $this->assertDatabaseHas('respuestas', ['encuesta_id'=>$id, 'item'=>2, 'percepcion'=>'Sin respuesta']);
        $answers = $payload['respuestas'];
        $answers[0] = ['item'=>1, 'expectativa'=>'6', 'percepcion'=>''];
        $this->put('/encuestas/'.$id, ['respuestas'=>$answers])->assertSessionHasErrors('respuestas.0.percepcion');
        $answers[0]['percepcion'] = '5';
        $this->put('/encuestas/'.$id, ['respuestas'=>$answers])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('respuestas', ['encuesta_id'=>$id, 'item'=>1, 'expectativa'=>'6', 'percepcion'=>'5']);
        $answers[0]['expectativa'] = 'Sin respuesta';
        $this->put('/encuestas/'.$id, ['respuestas'=>$answers])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('respuestas', ['encuesta_id'=>$id, 'item'=>1, 'percepcion'=>'Sin respuesta']);
    }

    public function test_capture_respects_start_end_and_draft_state(): void
    {
        $payload = $this->payload();
        DB::table('periodos')->update(['abre_at'=>'2026-11-06 10:16:00','cierra_at'=>'2026-11-06 10:17:00']);
        $this->get('/encuestas/nueva')->assertInertia(fn (AssertableInertia $p)=>$p->where('inicio_token',null));
        $this->post('/encuestas',$payload)->assertSessionHasErrors('acceso');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 10:16:00','America/Lima'));
        $this->post('/encuestas',$payload)->assertSessionHasNoErrors();
        $next = $this->payload();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 10:17:00','America/Lima'));
        $this->post('/encuestas',$next)->assertSessionHasErrors('acceso');
        DB::table('periodos')->update(['estado'=>'borrador','cierra_at'=>'2026-11-07 00:00:00']);
        $this->post('/encuestas',$next)->assertSessionHasErrors('acceso');
        $this->assertDatabaseCount('encuestas',1);
        $this->get('/administracion/encuesta')->assertForbidden();
    }

    public function test_photo_is_required_private_and_not_duplicated_on_retry(): void
    {
        $data = $this->payload(); $photo = $data['foto']; unset($data['foto']);
        $this->post('/encuestas',$data)->assertSessionHasErrors('foto');
        $this->assertDatabaseCount('encuestas',0);
        $data['foto'] = \Illuminate\Http\UploadedFile::fake()->create('fake.jpg',100,'text/plain');
        $this->post('/encuestas',$data)->assertSessionHasErrors('foto');
        $data['foto'] = $photo;
        $this->post('/encuestas',$data)->assertSessionHasNoErrors();
        $this->post('/encuestas',$data)->assertSessionHasNoErrors();
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('local')->allFiles('encuestas-fotos'));
        $record = DB::table('encuestas')->first();
        $this->get('/encuestas/'.$record->id.'/foto')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        DB::table('encuestas')->where('id',$record->id)->update(['establecimiento_id'=>DB::table('establecimientos')->where('id','!=',$record->establecimiento_id)->value('id')]);
        $this->get('/encuestas/'.$record->id.'/foto')->assertNotFound();
        $this->post('/salir');
        $this->get('/encuestas/'.$record->id.'/foto')->assertRedirect('/ingresar');
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

        DB::table('periodos')->update(['estado' => 'cerrado']);
        $this->put('/encuestas/'.$id, ['respuestas' => $answers])->assertSessionHasErrors('acceso');
        $this->assertDatabaseHas('respuestas', ['encuesta_id' => $id, 'item' => 2, 'expectativa' => 'Sin respuesta', 'percepcion' => 'Sin respuesta']);
        $this->get('/encuestas/'.$id.'/editar')->assertRedirect('/encuestas/'.$id);

        DB::table('periodos')->update(['estado' => 'publicado', 'cierra_at' => '2026-11-06 10:16:00']);
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
        $data = ['nombre'=>'Campaña noviembre','estado'=>'publicado','abre_at'=>'2027-01-02T08:00','cierra_at'=>'2027-01-16T08:00'];
        $this->put('/administracion/encuesta',$data)->assertSessionHasNoErrors();
        $id = DB::table('periodos')->where('nombre',$data['nombre'])->value('id');
        $this->assertDatabaseHas('periodos',['id'=>$id,'estado'=>'publicado','cierra_at'=>'2027-01-16 08:00:00']);
        $this->get('/administracion/encuesta')->assertInertia(fn (AssertableInertia $p) => $p->where('periodos.0.estado_visual','Programado'));
        $this->travelTo(\Carbon\Carbon::parse('2027-01-02 08:00:00','America/Lima'));
        $this->get('/administracion/encuesta')->assertInertia(fn (AssertableInertia $p) => $p->where('periodos.0.estado_visual','Abierto'));
        $this->travelTo(\Carbon\Carbon::parse('2027-01-16 08:00:00','America/Lima'));
        $this->get('/administracion/encuesta')->assertInertia(fn (AssertableInertia $p) => $p->where('periodos.0.estado_visual','Finalizado'));
        $this->put('/administracion/encuesta',array_replace($data,['nombre'=>'Solapado']))->assertSessionHasErrors('abre_at');
        $this->put('/administracion/encuesta',array_replace($data,['id'=>$id,'estado'=>'cerrado']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('periodos',['id'=>$id,'estado'=>'cerrado']);
        $digitador = User::where('role', 'digitador')->sole();
        $eess = $digitador->establecimiento_id;
        DB::table('encuestas')->insert(['establecimiento_id'=>$eess,'periodo_id'=>$id,'user_id'=>$digitador->id,'numero'=>1,'fecha'=>'2027-01-03']);
        $this->delete('/administracion/encuesta/'.$id)->assertSessionHasErrors('periodo');
        $this->assertDatabaseHas('periodos',['id'=>$id]);
        $empty = ['nombre'=>'Periodo eliminable','estado'=>'borrador','abre_at'=>'2027-02-01T08:00','cierra_at'=>'2027-02-15T08:00'];
        $this->put('/administracion/encuesta',$empty)->assertSessionHasNoErrors();
        $emptyId = DB::table('periodos')->where('nombre',$empty['nombre'])->value('id');
        $this->delete('/administracion/encuesta/'.$emptyId)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('periodos',['id'=>$emptyId]);
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
        $this->post('/encuestas', $payload)->assertSessionHasErrors('acceso');
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
        $invalid['respuestas'][0]['expectativa'] = '5';
        unset($invalid['respuestas'][0]['percepcion']);
        $this->post('/encuestas', $invalid)->assertSessionHasErrors('respuestas.0.percepcion');
        $this->post('/encuestas', array_replace($payload, ['edad' => null]))->assertSessionHasErrors('edad');
        $this->post('/encuestas', array_replace($payload, ['personal' => 6]))->assertSessionHasErrors('personal_otro');
        $this->assertDatabaseCount('encuestas', 0);
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->get('/tablero')->assertForbidden();
    }
    public function test_single_questionnaire_and_other_professional_are_saved(): void
    {
        $payload = array_replace($this->payload(), ['seguro' => 2, 'personal' => 6, 'personal_otro' => 'Técnico de enfermería']);
        $this->post('/encuestas', $payload)->assertRedirect();
        $this->assertDatabaseHas('encuestas', ['seguro' => 2, 'personal_otro' => 'Técnico de enfermería']);
        $id = DB::table('encuestas')->value('id');
        $this->get('/encuestas/'.$id)->assertInertia(fn (AssertableInertia $p) => $p->where('items.8.percepcion', '¿Cuando usted presentó algún problema o dificultad se resolvió inmediatamente?'));
        $this->assertDatabaseHas('encuestas', ['numero' => 1, 'personal' => 6, 'personal_otro' => 'Técnico de enfermería']);
        $this->assertDatabaseCount('encuestas', 1);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('encuestas', 'version_instrumento'));
    }

    public function test_removing_legacy_versions_normalizes_insurance_values(): void
    {
        \Illuminate\Support\Facades\Schema::table('encuestas', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('version_instrumento')->nullable();
        });
        $digitador = User::where('role', 'digitador')->sole();
        $periodo = DB::table('periodos')->value('id');
        foreach ([
            ['numero' => 1, 'version_instrumento' => 'servqual-provisional-v1', 'seguro' => 2],
            ['numero' => 2, 'version_instrumento' => 'servqual-provisional-v1', 'seguro' => 3],
            ['numero' => 3, 'version_instrumento' => 'abancay-2026', 'seguro' => 2],
        ] as $survey) {
            DB::table('encuestas')->insert($survey + [
                'establecimiento_id' => $digitador->establecimiento_id,
                'periodo_id' => $periodo,
                'user_id' => $digitador->id,
                'fecha' => '2026-11-06',
            ]);
        }

        $migration = require database_path('migrations/2026_11_09_000011_remove_survey_instrument_version.php');
        $migration->up();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('encuestas', 'version_instrumento'));
        $this->assertDatabaseHas('encuestas', ['numero' => 1, 'seguro' => 3]);
        $this->assertDatabaseHas('encuestas', ['numero' => 2, 'seguro' => 2]);
        $this->assertDatabaseHas('encuestas', ['numero' => 3, 'seguro' => 2]);
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
