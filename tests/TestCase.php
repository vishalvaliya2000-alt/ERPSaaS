<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // Enforce safe test database connection for testing
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'saas_erp_test');

        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");
        $prohibitedDatabases = [
            'saas_erp',
        ];

        if ($connection === 'mysql' && in_array(strtolower($database), $prohibitedDatabases, true)) {
            throw new RuntimeException(
                "SAFETY ERROR: Tests are attempting to use a production ERP database [Connection: {$connection}, Database: {$database}]. Tests must only run against saas_erp_test or an isolated test database."
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $prohibitedDatabases = [
            'saas_erp',
        ];

        if ($connection === 'mysql' && in_array(strtolower($database), $prohibitedDatabases, true)) {
            throw new RuntimeException(
                "SAFETY ERROR: Tests are attempting to use a production ERP database [Connection: {$connection}, Database: {$database}]. Tests must only run against saas_erp_test or an isolated test database."
            );
        }
    }
}
