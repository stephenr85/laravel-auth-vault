<?php

namespace Rushing\AuthVault\Contracts;

use Rushing\AuthVault\Data\OAuthSecretData;
use Rushing\AuthVault\Refresh\GenericOAuthTokenRefresher;
use Rushing\AuthVault\Refresh\RefreshedToken;

/**
 * Scheme-level OAuth token-refresh primitive. Given the current OAuth secret (carrying
 * the refresh token), return a fresh access token + expiry. The provider-specific token
 * endpoint is the implementor's concern; {@see GenericOAuthTokenRefresher}
 * covers the standard OAuth2 `refresh_token` grant.
 */
interface OAuthTokenRefresher
{
    public function refresh(OAuthSecretData $current): RefreshedToken;
}
