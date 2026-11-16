<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_setup_requires_scope_and_hashes_password(): void
    {
        $this->artisan('establecimientos:importar', ['archivo'=>base_path('Listado de Establecimientos.xls')])->assertSuccessful();
        $this->artisan('usuarios:crear-administrador')->assertFailed();
        $this->artisan('usuarios:crear-administrador', ['--todos'=>true])
            ->expectsQuestion('Nombre completo', 'Administración de prueba')
            ->expectsQuestion('DNI', '12345678')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'Ficticia-solo-test-123')
            ->expectsQuestion('Repite la contraseña', 'Ficticia-solo-test-123')->assertSuccessful();
        $user = User::sole();
        $this->assertSame('administrador', $user->role);
        $this->assertTrue(Hash::check('Ficticia-solo-test-123', $user->password));
        $this->assertDatabaseCount('administrador_establecimiento', 91);
    }

    public function test_production_seeding_does_not_create_demo_accounts_or_open_periods(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->artisan('db:seed', ['--force'=>true])->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('periodos', 0);
        $this->assertDatabaseCount('items', 22);
    }
}
