<?php

use Rushing\AuthVault\Data\LlmKeySecretData;

it('uses camelCase for the PHP property while preserving the snake-case wire key', function () {
    $data = LlmKeySecretData::from([
        'provider' => 'openai-compatible',
        'key' => 'secret',
        'base_url' => 'https://gateway.example.test/v1',
    ]);

    expect($data->baseUrl)->toBe('https://gateway.example.test/v1')
        ->and(property_exists($data, 'base_url'))->toBeFalse()
        ->and($data->toArray())->toHaveKey('base_url', 'https://gateway.example.test/v1');
});
