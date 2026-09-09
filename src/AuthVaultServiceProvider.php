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
        $directory = database_path('migrations/tenant');
        $suffix = '_create_vault_secrets_table.php';

        // Resolve onto a copy the host already has, so re-publishing is idempotent. Without this the
        // path below mints a FRESH timestamp on every publish, and `publishes()` runs at boot, so a
        // host that already tracks this migration silently gets a SECOND file creating the same table
        // — which is what a clean `composer setup` was producing. spatie/laravel-package-tools does
        // this same same-stem lookup in `generateMigrationName()`; this package hand-rolls its
        // destination and so has to do it too.
        //
        // Earliest match wins, so the answer is stable once a host has (as some do) accumulated more
        // than one copy. Returning an existing path also means `vendor:publish --force` OVERWRITES
        // that file rather than adding another — deliberate, and the same bargain package-tools makes,
        // but it is why --force must not be pointed at a host-authored variant of this table.
        $existing = glob($directory.'/*'.$suffix);

        if ($existing !== false && $existing !== []) {
            sort($existing);

            return $existing[0];
        }

        return $directory.'/'.date('Y_m_d_His').$suffix;
    }
}
