<?php

use Rushing\AuthVault\AuthVault as AuthVaultService;
use Rushing\AuthVault\Contracts\VaultContextGuard;
use Rushing\AuthVault\Facades\AuthVault;
use Rushing\AuthVault\Models\VaultSecret;

it('resolves the AuthVault singleton as its facade accessor', function () {
    expect(AuthVault::getFacadeRoot())
        ->toBeInstanceOf(AuthVaultService::class)
        ->toBe(app(AuthVaultService::class));
});

it('is bound as a singleton (safe for a facade)', function () {
    expect(app(AuthVaultService::class))->toBe(app(AuthVaultService::class));
});

it('is fakeable via a container swap', function () {
    $sentinel = new VaultSecret;

    $fake = new class(app(VaultContextGuard::class)) extends AuthVaultService
    {
        public ?VaultSecret $sentinel = null;

        public function active(string $conduitId, string $scheme): ?VaultSecret
        {
            return $this->sentinel;
        }
    };
    $fake->sentinel = $sentinel;

    AuthVault::swap($fake);

    expect(AuthVault::getFacadeRoot())->toBe($fake)
        ->and(AuthVault::active('conduit-1', 'api-key'))->toBe($sentinel);
});
