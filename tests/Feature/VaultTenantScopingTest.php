<?php

use Rushing\AuthVault\AuthVault;
use Rushing\AuthVault\Contracts\VaultContextGuard;
use Rushing\AuthVault\Data\ApiKeySecretData;
use Rushing\AuthVault\Exceptions\VaultContextException;
use Rushing\AuthVault\Guards\NullVaultContextGuard;
use Rushing\AuthVault\Tests\Support\PartitionScopedContextGuard;

/**
 * V1 hardening (sourced-particles ticket 10). The vault isolates secrets by physical row
 * *placement* — the owner's partition (a per-tenant Postgres schema in the host). That is
 * airtight while every access runs inside a resolved owner context, but has one residual
 * gap: an access with NO context established falls through to the default partition, and a
 * caller who knows a conduit_id resolves whatever lives there. These tests prove the
 * {@see VaultContextGuard} defense-in-depth backstop closes that gap.
 */
function vaultWithGuard(VaultContextGuard $guard): AuthVault
{
    app()->instance(VaultContextGuard::class, $guard);
    app()->forgetInstance(AuthVault::class);

    return app(AuthVault::class);
}

it('demonstrates the V1 gap: with no partition guard a known conduit_id resolves any secret', function () {
    // The un-partitioned default — models the pre-fix behavior. A single physical table,
    // no context requirement: knowing the conduit_id is enough to read the secret.
    $vault = vaultWithGuard(new NullVaultContextGuard);

    $vault->put('conduit-shared', 'api-key', new ApiKeySecretData('sk-victim'));

    expect($vault->active('conduit-shared', 'api-key')->payload->key)->toBe('sk-victim');
});

it('refuses a vault read when no owner-partition context is established (V1 closed)', function () {
    $guard = new PartitionScopedContextGuard;
    $vault = vaultWithGuard($guard);

    // Tenant A stores its secret inside its own partition.
    $guard->enter('tenant-A');
    $vault->put('conduit-A', 'api-key', new ApiKeySecretData('sk-tenant-A'));

    // A caller now tries to resolve tenant A's conduit_id with NO partition entered
    // (the unscoped-`public` fall-through). The guard refuses before the lookup runs.
    $guard->enter(null);

    expect(fn () => $vault->active('conduit-A', 'api-key'))
        ->toThrow(VaultContextException::class);
});

it('refuses a vault write with no partition context established', function () {
    $guard = new PartitionScopedContextGuard;
    $vault = vaultWithGuard($guard);

    $guard->enter(null);

    expect(fn () => $vault->put('conduit-A', 'api-key', new ApiKeySecretData('sk')))
        ->toThrow(VaultContextException::class);
});

it('resolves normally once an owner-partition context is entered', function () {
    $guard = new PartitionScopedContextGuard;
    $vault = vaultWithGuard($guard);

    $guard->enter('tenant-A');
    $vault->put('conduit-A', 'api-key', new ApiKeySecretData('sk-tenant-A'));

    expect($vault->active('conduit-A', 'api-key')->payload->key)->toBe('sk-tenant-A');
});
