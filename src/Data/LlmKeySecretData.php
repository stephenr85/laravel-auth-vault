<?php

namespace Rushing\AuthVault\Data;

use Spatie\LaravelData\Attributes\MapName;

/**
 * A bring-your-own LLM provider key (OpenAI/Anthropic/etc.) — its own scheme, never
 * confused with a vendor token. Stored `scheme=llm-key`.
 *
 * `base_url` is optional: an OpenAI-compatible custom endpoint (OpenRouter, a proxy,
 * a self-hosted gateway) rides the same generic kind by naming its URL here; a native
 * provider leaves it null and inherits the shipped `config('prism.providers.*.url')`.
 */
class LlmKeySecretData extends SecretData
{
    public function __construct(
        public string $provider,
        public string $key,
        #[MapName('base_url')]
        public ?string $baseUrl = null,
    ) {}
}
