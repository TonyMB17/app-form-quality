<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministradorPruebaTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_requires_valid_scope_and_can_login_without_duplicate_accounts(): void
    {
        $this->artisan('establecimientos:importar', ['archivo' => base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->artisan('piloto:administrador')->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $codes = DB::table('establecimientos')->pluck('codigo_renipress')->all();
        $this->artisan('piloto:administrador', ['--eess' => $codes])->assertSuccessful();
        $this->artisan('piloto:administrador', ['--eess' => $codes])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('administrador_establecimiento', 91);
        $this->assertSame('administrador', User::sole()->role);
        $this->post('/ingresar', ['dni' => '00000002', 'password' => 'Abancay-Admin-Prueba-2026!'])->assertRedirect('/tablero');
        $this->get('/encuestas/nueva')->assertForbidden();
        $this->post('/encuestas', [])->assertForbidden();
    }
}
