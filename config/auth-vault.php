<?php

use Rushing\AuthVault\Data\ApiKeySecretData;
use Rushing\AuthVault\Data\LlmKeySecretData;
use Rushing\AuthVault\Data\OAuthClientSecretData;
use Rushing\AuthVault\Data\OAuthSecretData;

return [
    /*
     * Table the vault secrets live in. In a multi-tenant host this table is created
     * per tenant schema — the vault stores; where the rows physically live is the
     * host's concern.
     */
    'table_names' => [
        'vault_secrets' => 'vault_secrets',
    ],

    /*
     * Scheme => secret-DTO map. Each vaulted secret declares a scheme; the DTO defines
     * the plaintext shape stored (encrypted) inside `payload`. Hosts add schemes here
     * (e.g. a vendor-specific token shape) without touching the package.
     */
    'schemes' => [
        'api-key' => ApiKeySecretData::class,
        'oauth' => OAuthSecretData::class,
        'oauth-client' => OAuthClientSecretData::class,
        'llm-key' => LlmKeySecretData::class,
    ],

    /*
     * Envelope-encryption seam. `null` = the framework `encrypted` cast over APP_KEY.
     * A future KMS/envelope scheme resolves a data key by `key_id`; leaving this null
     * keeps every row on the default APP_KEY cast, and `key_id` makes that migration
     * additive. Seamed, not built.
     */
    'default_key_id' => null,

    /*
     * How close to `expires_at` (seconds) an OAuth secret is considered "due" for the
     * refresh sweep. ~5 minutes of skew.
     */
    'refresh_skew_seconds' => 300,
];
