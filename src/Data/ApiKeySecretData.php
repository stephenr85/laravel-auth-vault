<?php

namespace Rushing\AuthVault\Data;

/**
 * A pasted, static API key — no refresh cycle. Stored `scheme=api-key`.
 */
class ApiKeySecretData extends SecretData
{
    public function __construct(
        public string $key,
    ) {}
}
