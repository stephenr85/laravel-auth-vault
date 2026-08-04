<?php

namespace Rushing\AuthVault;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Rushing\AuthVault\Contracts\VaultContextGuard;
use Rushing\AuthVault\Facades\AuthVault as AuthVaultFacade;
use Rushing\AuthVault\Guards\NullVaultContextGuard;

class AuthVaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auth-vault.php', 'auth-vault');

        // Defense-in-depth partition guard (V1). The packaged default is a no-op so
        // single-tenant / un-partitioned consumers are unchanged; a multi-tenant host
        // rebinds this to assert its partition context (e.g. a resolved tenant schema)
        // is established before a conduit_id lookup can resolve.
        $this->app->bindIf(VaultContextGuard::class, NullVaultContextGuard::class);

        $this->app->singleton(AuthVault::class);
    }

    public function boot(): void
    {
        if (class_exists(AliasLoader::class)) {
            AliasLoader::getInstance()->alias('AuthVault', AuthVaultFacade::class);
        }

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
