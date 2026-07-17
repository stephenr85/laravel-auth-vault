<?php

namespace Rushing\AuthVault\Data;

/**
 * A bring-your-own LLM provider key (OpenAI/Anthropic/etc.) — its own scheme, never
 * confused with a vendor token. Stored `scheme=llm-key`.
 */
class LlmKeySecretData extends SecretData
{
    public function __construct(
        public string $provider,
        public string $key,
    ) {}
}
