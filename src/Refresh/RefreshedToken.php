<?php

namespace Rushing\AuthVault\Refresh;

use Illuminate\Support\Carbon;
use Rushing\AuthVault\Data\OAuthSecretData;

/**
 * The result of an OAuth token refresh: the fresh secret payload plus the new expiry.
 * A same-grant refresh keeps the original `refreshToken` (the carve-out that mutates
 * the active row in place).
 */
class RefreshedToken
{
    public function __construct(
        public OAuthSecretData $secret,
        public ?Carbon $expiresAt = null,
    ) {}
}
