<?php

namespace Rushing\AuthVault;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Rushing\AuthVault\Contracts\OAuthTokenRefresher;
use Rushing\AuthVault\Data\SecretData;
use Rushing\AuthVault\Models\VaultSecret;

/**
 * The vault's façade over the append-only secret store. Named `AuthVault` (never a bare
 * `Vault`) — it stores secrets, hands back the single active one per scheme, and
 * distinguishes the two ways an active secret changes:
 *
 *   - **rotation** (identity change: re-auth, api-key swap, new refresh token) →
 *     {@see put()} supersedes the active row and appends a new one.
 *   - **refresh** (same grant, fresh access token) → {@see refresh()} /
 *     {@see refreshOAuth()} mutate the active row in place, leaving no history row.
 *
 * Invariant it enforces: at most one active row per (conduit_id, scheme).
 */
class AuthVault
{
    /**
     * Store a secret, rotating: supersede the current active row for this
     * (conduit_id, scheme) and append a fresh active one. Use for the first store and
     * for every identity-changing rotation.
     */
    public function put(
        string $conduitId,
        string $scheme,
        SecretData $payload,
        ?Carbon $expiresAt = null,
        ?string $providerAccountId = null,
        ?string $keyId = null,
    ): VaultSecret {
        return DB::transaction(function () use ($conduitId, $scheme, $payload, $expiresAt, $providerAccountId, $keyId) {
            VaultSecret::query()
                ->where('conduit_id', $conduitId)
                ->where('scheme', $scheme)
                ->whereNull('superseded_at')
                ->update(['superseded_at' => now()]);

            return VaultSecret::create([
                'conduit_id' => $conduitId,
                'scheme' => $scheme,
                'payload' => $payload,
                'expires_at' => $expiresAt,
                'provider_account_id' => $providerAccountId,
                'key_id' => $keyId ?? config('auth-vault.default_key_id'),
            ]);
        });
    }

    /**
     * The single active secret for a (conduit_id, scheme), or null.
     */
    public function active(string $conduitId, string $scheme): ?VaultSecret
    {
        return VaultSecret::query()
            ->where('conduit_id', $conduitId)
            ->where('scheme', $scheme)
            ->whereNull('superseded_at')
            ->first();
    }

    /**
     * Carve-out: mutate the active row in place (same grant, fresh access token — no
     * new history row). Rows are otherwise append-only; this is the one sanctioned
     * in-place write.
     */
    public function refresh(VaultSecret $secret, SecretData $payload, ?Carbon $expiresAt = null): VaultSecret
    {
        $secret->payload = $payload;

        if ($expiresAt !== null) {
            $secret->expires_at = $expiresAt;
        }

        $secret->save();

        return $secret;
    }

    /**
     * Scheme-level OAuth refresh primitive: pull the active OAuth payload, ask the
     * refresher for a fresh token, mutate the active row in place. A same-grant refresh
     * keeps the refresh token, so this stays in-place (not a rotation).
     */
    public function refreshOAuth(VaultSecret $secret, OAuthTokenRefresher $refresher): VaultSecret
    {
        $current = $secret->payload;

        $refreshed = $refresher->refresh($current);

        return $this->refresh($secret, $refreshed->secret, $refreshed->expiresAt);
    }

    /**
     * Active OAuth secrets due for refresh — expiring within the skew. Scans the
     * plaintext `expires_at` column (no decrypt). The host's sweep iterates these.
     *
     * @return Collection<int, VaultSecret>
     */
    public function dueForRefresh(?int $skewSeconds = null): Collection
    {
        $skew = $skewSeconds ?? (int) config('auth-vault.refresh_skew_seconds', 300);

        return VaultSecret::query()
            ->where('scheme', 'oauth')
            ->whereNull('superseded_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addSeconds($skew))
            ->get();
    }
}
