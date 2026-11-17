<?php
namespace Tests\Feature;
use PDO;
use Tests\TestCase;
class DatabaseOptionsTest extends TestCase
{
    public function test_mysql_options_preserve_pdo_numeric_keys(): void
    {
        if (!extension_loaded('pdo_mysql')) $this->markTestSkipped('Requires PDO MySQL.');
        foreach (['mysql','mariadb'] as $driver) {
            $options = config("database.connections.$driver.options");
            $this->assertSame(1, $options[PDO::ATTR_AUTOCOMMIT]);
            $this->assertSame('SET autocommit=1', $options[PDO::MYSQL_ATTR_INIT_COMMAND]);
            $this->assertArrayNotHasKey(PDO::ATTR_PREFETCH, $options);
        }
    }
}
