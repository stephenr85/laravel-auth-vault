<?php

namespace Rushing\AuthVault\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rushing\AuthVault\Casts\VaultSecretCast;
use Rushing\AuthVault\Data\SecretData;

/**
 * One vaulted secret. Rows are append-only: `superseded_at IS NULL` marks the single
 * active secret per (conduit_id, scheme); a non-null `superseded_at` is history. The
 * `payload` is an encrypted per-scheme {@see SecretData} DTO; `expires_at` /
 * `provider_account_id` stay plaintext so a sweep / UI can query them without decrypt.
 *
 * @property SecretData|null $payload
 */
class VaultSecret extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'payload' => VaultSecretCast::class,
        'expires_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('auth-vault.table_names.vault_secrets', 'vault_secrets');
    }

    /**
     * @param  Builder<VaultSecret>  $query
     * @return Builder<VaultSecret>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('superseded_at');
    }

    /**
     * @param  Builder<VaultSecret>  $query
     * @return Builder<VaultSecret>
     */
    public function scopeForScheme(Builder $query, string $scheme): Builder
    {
        return $query->where('scheme', $scheme);
    }

    public function isActive(): bool
    {
        return $this->superseded_at === null;
    }

    /**
     * Expired (optionally within a lookahead skew) — the refresh-sweep predicate,
     * mirrored on the plaintext `expires_at` column.
     */
    public function isExpired(int $skewSeconds = 0): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->subSeconds($skewSeconds)->isPast();
    }
}
