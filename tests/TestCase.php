<?php

namespace Rushing\AuthVault\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rushing\AuthVault\AuthVaultServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelDataServiceProvider::class,
            AuthVaultServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $migration = require __DIR__.'/../database/migrations/create_vault_secrets_table.php.stub';
        $migration->up();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Crypt needs a key for the VaultSecretCast round-trip.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
