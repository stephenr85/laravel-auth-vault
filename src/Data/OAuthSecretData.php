<?php

namespace Rushing\AuthVault\Data;

/**
 * An OAuth authorization-code grant's tokens. The `refreshToken` is the grant identity
 * — a refresh that returns a fresh `accessToken` but the same `refreshToken` is the
 * in-place carve-out; a new `refreshToken` is a rotation (append). Stored `scheme=oauth`.
 */
class OAuthSecretData extends SecretData
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public array $scopes = [],
    ) {}
}
