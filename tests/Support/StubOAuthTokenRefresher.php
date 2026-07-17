<?php

namespace Rushing\AuthVault\Tests\Support;

use Illuminate\Support\Carbon;
use Rushing\AuthVault\Contracts\OAuthTokenRefresher;
use Rushing\AuthVault\Data\OAuthSecretData;
use Rushing\AuthVault\Refresh\RefreshedToken;

/**
 * A same-grant refresh: hands back a fresh access token, keeps the refresh token.
 */
class StubOAuthTokenRefresher implements OAuthTokenRefresher
{
    public function __construct(
        public string $newAccessToken = 'fresh-access-token',
        public ?Carbon $expiresAt = null,
    ) {}

    public function refresh(OAuthSecretData $current): RefreshedToken
    {
        return new RefreshedToken(
            new OAuthSecretData(
                accessToken: $this->newAccessToken,
                refreshToken: $current->refreshToken,
                scopes: $current->scopes,
            ),
            $this->expiresAt,
        );
    }
}
