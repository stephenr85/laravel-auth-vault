<?php

namespace Rushing\AuthVault\Data;

use Rushing\AuthVault\Casts\VaultSecretCast;
use Spatie\LaravelData\Data;

/**
 * Base for a per-scheme secret payload. The concrete shape (API key, OAuth token,
 * OAuth client credential, LLM key) is what gets JSON-encoded and encrypted into the
 * `payload` column by {@see VaultSecretCast}. The scheme=>DTO
 * map lives in `config('auth-vault.schemes')` so a host can register its own shapes.
 */
abstract class SecretData extends Data {}
