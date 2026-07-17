<?php

namespace Rushing\AuthVault\Refresh;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Rushing\AuthVault\Contracts\OAuthTokenRefresher;
use Rushing\AuthVault\Data\OAuthSecretData;
use Rushing\AuthVault\Exceptions\CannotRefreshException;

/**
 * The standard OAuth2 `refresh_token` grant (RFC 6749 §6). Refresh responses commonly
 * omit `refresh_token` — a same-grant refresh — so the current refresh token is
 * carried forward, which is exactly what keeps this an in-place mutation, not a
 * rotation. Provider quirks (Google's `access_type=offline`) are the host's concern at
 * connect time; refresh itself is uniform.
 */
class GenericOAuthTokenRefresher implements OAuthTokenRefresher
{
    public function __construct(
        protected string $tokenUrl,
        protected string $clientId,
        protected string $clientSecret,
    ) {}

    public function refresh(OAuthSecretData $current): RefreshedToken
    {
        if ($current->refreshToken === null || $current->refreshToken === '') {
            throw new CannotRefreshException('The OAuth secret has no refresh token.');
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $current->refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->failed()) {
            throw new CannotRefreshException(
                "OAuth refresh grant rejected (HTTP {$response->status()}).",
            );
        }

        $body = $response->json();

        $expiresAt = isset($body['expires_in'])
            ? Carbon::now()->addSeconds((int) $body['expires_in'])
            : null;

        return new RefreshedToken(
            new OAuthSecretData(
                accessToken: $body['access_token'],
                refreshToken: $body['refresh_token'] ?? $current->refreshToken,
                scopes: isset($body['scope']) ? explode(' ', $body['scope']) : $current->scopes,
            ),
            $expiresAt,
        );
    }
}
