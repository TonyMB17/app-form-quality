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

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $eessAutorizado;

    private int $eessNoAutorizado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed([UsuarioPruebaSeeder::class, InstrumentoSeeder::class]);

        $this->admin = User::sole();
        $this->admin->role = 'administrador';
        $this->admin->save();
        $this->eessAutorizado = (int) $this->admin->establecimiento_id;
        $this->eessNoAutorizado = (int) DB::table('establecimientos')->where('id', '!=', $this->eessAutorizado)->value('id');
        DB::table('administrador_establecimiento')->insert([
            'user_id' => $this->admin->id,
            'establecimiento_id' => $this->eessAutorizado,
        ]);
        $this->actingAs($this->admin);
    }

    public function test_admin_can_create_and_update_digitadores_without_changing_existing_password_when_blank(): void
    {
        $this->get('/administracion/usuarios')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Administracion/Usuarios/Lista', false)
            ->has('establecimientos', 1));

        $this->post('/administracion/usuarios', [
            'dni' => '12345678',
            'name' => 'Digitador de prueba',
            'establecimiento_id' => $this->eessAutorizado,
            'password' => 'Clave-inicial-123',
        ])->assertRedirect('/administracion/usuarios');

        $digitador = User::where('dni', '12345678')->firstOrFail();
        $this->assertSame('digitador', $digitador->role);
        $this->assertTrue(Hash::check('Clave-inicial-123', $digitador->password));

        $this->put('/administracion/usuarios/'.$digitador->id, [
            'dni' => '12345678',
            'name' => 'Nombre actualizado',
            'establecimiento_id' => $this->eessAutorizado,
            'password' => '',
        ])->assertRedirect('/administracion/usuarios');

        $digitador->refresh();
        $this->assertSame('Nombre actualizado', $digitador->name);
        $this->assertTrue(Hash::check('Clave-inicial-123', $digitador->password));
    }

    public function test_admin_cannot_assign_or_manage_users_outside_its_authorized_scope(): void
    {
        $this->post('/administracion/usuarios', [
            'dni' => '12345678',
            'name' => 'Digitador fuera de ámbito',
            'establecimiento_id' => $this->eessNoAutorizado,
            'password' => 'Clave-inicial-123',
        ])->assertSessionHasErrors('establecimiento_id');

        $outside = User::create([
            'dni' => '87654321',
            'name' => 'Digitador ajeno',
            'password' => 'Clave-inicial-123',
        ]);
        $outside->role = 'digitador';
        $outside->establecimiento_id = $this->eessNoAutorizado;
        $outside->save();

        $this->get('/administracion/usuarios/'.$outside->id.'/editar')->assertNotFound();
        $this->put('/administracion/usuarios/'.$outside->id, [
            'dni' => '87654321',
            'name' => 'No autorizado',
            'establecimiento_id' => $this->eessAutorizado,
            'password' => '',
        ])->assertNotFound();
    }

    public function test_admin_accounts_can_be_edited_and_password_changes_require_the_confirming_admin_password(): void
    {
        $otherAdmin = new User;
        $otherAdmin->dni = '22334455';
        $otherAdmin->name = 'Administrador secundario';
        $otherAdmin->password = 'Clave-admin-123';
        $otherAdmin->role = 'administrador';
        $otherAdmin->save();

        $this->get('/administracion/usuarios')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('administradores', 2));
        $this->get('/administracion/usuarios/'.$otherAdmin->id.'/editar')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Administracion/Usuarios/Formulario', false)
            ->where('usuario.role', 'administrador'));

        $this->put('/administracion/usuarios/'.$otherAdmin->id, [
            'name' => 'No autorizado',
            'current_password' => 'incorrecta',
            'password' => 'Nueva-clave-123',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame('Administrador secundario', $otherAdmin->fresh()->name);
        $this->assertTrue(Hash::check('Clave-admin-123', $otherAdmin->fresh()->password));

        $this->put('/administracion/usuarios/'.$otherAdmin->id, [
            'name' => 'Administrador actualizado',
            'current_password' => 'Curahuasi-Prueba-2026!',
            'password' => 'Nueva-clave-123',
        ])->assertRedirect('/administracion/usuarios');
        $otherAdmin->refresh();
        $this->assertSame('Administrador actualizado', $otherAdmin->name);
        $this->assertTrue(Hash::check('Nueva-clave-123', $otherAdmin->password));

        $digitador = new User;
        $digitador->dni = '11112222';
        $digitador->name = 'Digitador autorizado';
        $digitador->password = 'Clave-inicial-123';
        $digitador->role = 'digitador';
        $digitador->establecimiento_id = $this->eessAutorizado;
        $digitador->save();
        $this->actingAs($digitador)->get('/administracion/usuarios')->assertForbidden();
    }
}
