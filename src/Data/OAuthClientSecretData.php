<?php

namespace Rushing\AuthVault\Data;

/**
 * A registered OAuth *client* credential (the app's own client_id/secret) — distinct
 * from the per-user token. A secret, so it lives in the vault, never in config. Stored
 * `scheme=oauth-client`.
 */
class OAuthClientSecretData extends SecretData
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {}
}
