<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->seed(UsuarioPruebaSeeder::class);
    }

    public function test_single_demo_user_has_only_requested_profile_and_hashed_password(): void
    {
        $this->seed(UsuarioPruebaSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $user = User::sole();
        $this->assertNull($user->email);
        $this->assertSame('00000001', $user->dni);
        $this->assertSame('00002630', $user->establecimiento->codigo_renipress);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Curahuasi-Prueba-2026!', $user->password));
    }

    public function test_login_profile_and_logout_ignore_client_establishment(): void
    {
        $this->get('/mi-establecimiento')->assertRedirect('/ingresar');
        $this->post('/ingresar', ['dni' => '00000001', 'password' => 'Curahuasi-Prueba-2026!', 'establecimiento_id' => 1, 'role' => 'administrador'])->assertRedirect('/mi-establecimiento');
        $this->assertAuthenticatedAs(User::sole());
        $this->get('/mi-establecimiento?establecimiento_id=1')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MiEstablecimiento', false)
            ->where('perfil.dni', '00000001')
            ->where('perfil.establecimiento.codigo', '00002630')
            ->missing('perfil.password'));
        $this->assertSame('digitador', User::sole()->role);
        $this->post('/salir')->assertRedirect('/ingresar');
        $this->assertGuest();
        $this->get('/mi-establecimiento')->assertRedirect('/ingresar');
    }

    public function test_wrong_password_is_rejected_and_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/ingresar', ['dni' => '00000001', 'password' => 'incorrecta'])->assertSessionHasErrors(['dni' => 'DNI o contraseña incorrectos.']);
        }
        $this->post('/ingresar', ['dni' => '00000001', 'password' => 'Curahuasi-Prueba-2026!'])->assertSessionHasErrors('dni');
        $this->assertGuest();
    }

    public function test_user_without_assignment_or_wrong_role_cannot_access_digitador_profile(): void
    {
        $user = User::sole();
        $user->role = 'administrador';
        $user->save();
        $this->actingAs($user)->get('/mi-establecimiento')->assertForbidden();
        $user->role = 'digitador';
        $user->establecimiento_id = null;
        $user->save();
        $this->actingAs($user->fresh())->get('/mi-establecimiento')->assertForbidden();
    }
}
