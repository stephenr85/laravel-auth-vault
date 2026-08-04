<?php

namespace Rushing\AuthVault\Facades;

use Illuminate\Support\Facades\Facade;
use Rushing\AuthVault\AuthVault as AuthVaultService;

/**
 * Ergonomic front door to the secret vault.
 *
 * @method static \Rushing\AuthVault\Models\VaultSecret put(string $conduitId, string $scheme, \Rushing\AuthVault\Data\SecretData $payload, ?\Illuminate\Support\Carbon $expiresAt = null, ?string $providerAccountId = null, ?string $keyId = null)
 * @method static ?\Rushing\AuthVault\Models\VaultSecret active(string $conduitId, string $scheme)
 * @method static \Rushing\AuthVault\Models\VaultSecret refresh(\Rushing\AuthVault\Models\VaultSecret $secret, \Rushing\AuthVault\Data\SecretData $payload, ?\Illuminate\Support\Carbon $expiresAt = null)
 * @method static \Rushing\AuthVault\Models\VaultSecret refreshOAuth(\Rushing\AuthVault\Models\VaultSecret $secret, \Rushing\AuthVault\Contracts\OAuthTokenRefresher $refresher)
 * @method static \Illuminate\Support\Collection dueForRefresh(?int $skewSeconds = null)
 *
 * @see AuthVaultService
 */
class AuthVault extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthVaultService::class;
    }
}
