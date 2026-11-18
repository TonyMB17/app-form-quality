<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $eess = DB::table('establecimientos')->value('id');
        $admin = User::create(['name' => 'Administrador', 'dni' => '11111111', 'password' => 'Prueba-12345']);
        $admin->forceFill(['role' => 'administrador'])->save();
        DB::table('administrador_establecimiento')->insert(['user_id' => $admin->id, 'establecimiento_id' => $eess]);
        $user = User::create(['name' => 'Digitador', 'dni' => '22222222', 'password' => 'Prueba-12345']);
        $user->forceFill(['role' => 'digitador', 'establecimiento_id' => $eess])->save();
        return [$admin, $user];
    }

    public function test_deactivation_blocks_login_and_old_sessions_even_after_reactivation(): void
    {
        [$admin, $user] = $this->accounts();
        $this->actingAs($admin)->patch('/administracion/usuarios/'.$user->id.'/estado', ['activo' => false])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'activo' => false]);
        $this->actingAs($user)->get('/encuestas')->assertRedirect('/ingresar');
        $this->post('/ingresar', ['dni' => $user->dni, 'password' => 'Prueba-12345'])->assertSessionHasErrors('dni');
        $this->actingAs($admin)->patch('/administracion/usuarios/'.$user->id.'/estado', ['activo' => true])->assertRedirect();
        $this->actingAs($user->fresh())->withSession(['auth_version' => 0])->get('/encuestas')->assertRedirect('/ingresar');
        $this->post('/ingresar', ['dni' => $user->dni, 'password' => 'Prueba-12345'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_deletion_requires_both_confirmations_and_removes_owned_data_and_photos(): void
    {
        [$admin, $user] = $this->accounts();
        Storage::fake('local');
        $this->seed(\Database\Seeders\EncuestaItemsSeeder::class);
        $photo = 'encuestas-fotos/prueba.jpg';
        Storage::disk('local')->put($photo, 'test');
        $survey = DB::table('encuestas')->insertGetId(['user_id' => $user->id, 'establecimiento_id' => $user->establecimiento_id, 'periodo_id' => DB::table('periodos')->value('id'), 'numero' => 1, 'fecha' => '2026-11-18', 'foto_path' => $photo]);
        DB::table('respuestas')->insert(['encuesta_id' => $survey, 'item' => 1, 'expectativa' => '7', 'percepcion' => '7']);
        DB::table('encuesta_cambios')->insert(['encuesta_id' => $survey, 'user_id' => $user->id, 'antes' => '[]', 'despues' => '[]', 'created_at' => now()]);
        $this->actingAs($admin)->delete('/administracion/usuarios/'.$user->id)->assertSessionHasErrors();
        $payload = ['confirmado' => true, 'confirmacion' => 'ELIMINAR '.$user->dni, 'encuestas_count' => 0];
        $this->delete('/administracion/usuarios/'.$user->id, $payload)->assertSessionHasErrors('encuestas_count');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        Storage::disk('local')->assertExists($photo);
        $payload['encuestas_count'] = 1;
        $this->delete('/administracion/usuarios/'.$user->id, $payload)->assertRedirect('/administracion/usuarios');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('encuestas', ['id' => $survey]);
        $this->assertDatabaseCount('respuestas', 0);
        $this->assertDatabaseCount('encuesta_cambios', 0);
        $this->assertDatabaseCount('pending_photo_deletions', 0);
        Storage::disk('local')->assertMissing($photo);
    }

    public function test_failed_photo_cleanup_is_retained_and_can_be_retried(): void
    {
        DB::table('pending_photo_deletions')->insert(['path' => 'encuestas-fotos/pending.jpg']);
        Storage::shouldReceive('disk')->with('local')->andReturnSelf();
        Storage::shouldReceive('delete')->with('encuestas-fotos/pending.jpg')->once()->andReturn(false);
        $this->assertSame(1, \App\Support\DeletedSurveyPhotos::cleanup());
        Storage::shouldReceive('delete')->with('encuestas-fotos/pending.jpg')->once()->andReturn(true);
        $this->assertSame(0, \App\Support\DeletedSurveyPhotos::cleanup());
    }

    public function test_historical_surveys_outside_scope_block_deletion(): void
    {
        [$admin, $user] = $this->accounts();
        $this->seed(\Database\Seeders\EncuestaItemsSeeder::class);
        $outside = DB::table('establecimientos')->where('id', '!=', $user->establecimiento_id)->value('id');
        DB::table('encuestas')->insert(['user_id' => $user->id, 'establecimiento_id' => $outside, 'periodo_id' => DB::table('periodos')->value('id'), 'numero' => 1, 'fecha' => '2026-11-18']);
        $this->actingAs($admin)->delete('/administracion/usuarios/'.$user->id, ['confirmado' => true, 'confirmacion' => 'ELIMINAR '.$user->dni, 'encuestas_count' => 1])->assertForbidden();
        $this->assertDatabaseCount('encuestas', 1);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_accounts_outside_scope_and_admin_accounts_are_protected(): void
    {
        [$admin, $user] = $this->accounts();
        $user->forceFill(['establecimiento_id' => DB::table('establecimientos')->where('id', '!=', $user->establecimiento_id)->value('id')])->save();
        foreach ([$user, $admin] as $target) {
            $this->actingAs($admin)->patch('/administracion/usuarios/'.$target->id.'/estado', ['activo' => false])->assertNotFound();
            $this->delete('/administracion/usuarios/'.$target->id, ['confirmado' => true, 'confirmacion' => 'ELIMINAR '.$target->dni, 'encuestas_count' => 0])->assertNotFound();
        }
        $this->actingAs($user)->patch('/administracion/usuarios/'.$admin->id.'/estado', ['activo' => false])->assertForbidden();
    }
}
