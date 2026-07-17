# laravel-auth-vault

A generic, per-owner **credential vault** for Laravel. It stores secrets — API keys and
OAuth tokens — encrypted, append-only, with safe rotation and an OAuth token-refresh
primitive. It knows nothing about connectors, tenancy, or billing: it stores a secret and
refreshes an OAuth token, nothing more.

## What it is

- **One polymorphic table, `vault_secrets`.** A secret belongs to one owning subject
  (`conduit_id` — a back-reference, no FK), has a `scheme` (`api-key`, `oauth`,
  `oauth-client`, `llm-key`, or a host-registered one), and an encrypted `payload`.
- **Append-only.** The table *is* the audit log. `superseded_at IS NULL` marks the single
  active secret per `(conduit_id, scheme)`.
- **Two ways an active secret changes:**
  - **rotation** (identity change: re-auth, api-key swap, new refresh token) →
    `AuthVault::put()` supersedes the active row and appends a new one.
  - **refresh** (same grant, fresh access token) → `AuthVault::refresh()` /
    `refreshOAuth()` mutate the active row **in place** — no history row.
- **Plaintext `expires_at` / `provider_account_id`** so a refresh sweep or a UI can query
  without decrypting.
- **Envelope/KMS seam.** Encryption is the framework `encrypted` cast over `APP_KEY` via a
  dedicated `VaultSecretCast`; `key_id` (null = `APP_KEY`) makes a later KMS migration
  additive. Seamed, not built.

## Usage

```php
use Rushing\AuthVault\AuthVault;
use Rushing\AuthVault\Data\ApiKeySecretData;
use Rushing\AuthVault\Data\OAuthSecretData;

$vault = app(AuthVault::class);

// Store / rotate.
$vault->put($conduitId, 'api-key', new ApiKeySecretData('sk-123'));

// Read the single active secret (decrypts inside the owner's frame).
$secret = $vault->active($conduitId, 'oauth');
$token = $secret->payload->accessToken;

// Refresh an OAuth token in place with the standard OAuth2 grant.
use Rushing\AuthVault\Refresh\GenericOAuthTokenRefresher;

$vault->refreshOAuth($secret, new GenericOAuthTokenRefresher($tokenUrl, $clientId, $clientSecret));

// Sweep: active oauth secrets expiring within the skew window.
foreach ($vault->dueForRefresh() as $due) { /* ... */ }
```

## Install

```bash
composer require rushing/laravel-auth-vault
php artisan vendor:publish --tag=auth-vault-migrations   # copies into database/migrations/tenant
php artisan vendor:publish --tag=auth-vault-config
```

The migration publishes into `database/migrations/tenant` — in a multi-tenant host the
vault table is created per tenant schema so a secret physically lives with its owner (the
honeypot-free property is enforced by *placement*, not policy).

## Test

```bash
composer install
composer test
```
