<?php

namespace Rushing\AuthVault;

use Illuminate\Support\ServiceProvider;

class AuthVaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-vault.php', 'auth-vault');

        $this->app->singleton(AuthVault::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/auth-vault.php' => config_path('auth-vault.php'),
        ], 'auth-vault-config');

        // Publish-only: the vault table is created per tenant schema by the host, which
        // owns *where* the rows physically live (the honeypot-free property is enforced
        // by placement, not policy). The host copies this into its tenant migration set.
        $this->publishes([
            __DIR__.'/../database/migrations/create_vault_secrets_table.php.stub' => $this->publishedMigrationPath(),
        ], 'auth-vault-migrations');
    }

    protected function publishedMigrationPath(): string
    {
        return database_path('migrations/tenant/'.date('Y_m_d_His').'_create_vault_secrets_table.php');
    }
}
