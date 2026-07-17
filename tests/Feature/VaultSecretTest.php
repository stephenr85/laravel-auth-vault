<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Rushing\AuthVault\AuthVault;
use Rushing\AuthVault\Data\ApiKeySecretData;
use Rushing\AuthVault\Data\OAuthSecretData;
use Rushing\AuthVault\Models\VaultSecret;
use Rushing\AuthVault\Tests\Support\StubOAuthTokenRefresher;

beforeEach(function () {
    $this->vault = app(AuthVault::class);
    $this->conduit = 'conduit-1';
});

it('stores an api-key secret and hands it back as the active secret', function () {
    $secret = $this->vault->put($this->conduit, 'api-key', new ApiKeySecretData('sk-123'));

    $active = $this->vault->active($this->conduit, 'api-key');

    expect($active)->not->toBeNull()
        ->and($active->is($secret))->toBeTrue()
        ->and($active->payload)->toBeInstanceOf(ApiKeySecretData::class)
        ->and($active->payload->key)->toBe('sk-123')
        ->and($active->expires_at)->toBeNull();
});

it('encrypts the payload at rest and decrypts it on read', function () {
    $this->vault->put($this->conduit, 'api-key', new ApiKeySecretData('super-secret'));

    // The raw stored column must not contain the plaintext.
    $raw = DB::table('vault_secrets')->value('payload');
    expect($raw)->not->toContain('super-secret');

    // But the cast round-trips it.
    expect($this->vault->active($this->conduit, 'api-key')->payload->key)->toBe('super-secret');
});

it('rotation appends a new active row and supersedes the old one', function () {
    $first = $this->vault->put($this->conduit, 'api-key', new ApiKeySecretData('old'));
    $second = $this->vault->put($this->conduit, 'api-key', new ApiKeySecretData('new'));

    expect(VaultSecret::count())->toBe(2)
        ->and($first->fresh()->superseded_at)->not->toBeNull()
        ->and($first->fresh()->isActive())->toBeFalse()
        ->and($second->fresh()->isActive())->toBeTrue();

    // Invariant: exactly one active row per (conduit, scheme).
    expect(VaultSecret::active()->forScheme('api-key')->where('conduit_id', $this->conduit)->count())->toBe(1);
    expect($this->vault->active($this->conduit, 'api-key')->payload->key)->toBe('new');
});

it('refresh mutates the active oauth row in place — no new history row', function () {
    $secret = $this->vault->put(
        $this->conduit,
        'oauth',
        new OAuthSecretData(accessToken: 'a1', refreshToken: 'r1', scopes: ['drive.readonly']),
        expiresAt: Carbon::now()->addMinutes(1),
    );

    $refreshed = $this->vault->refresh(
        $secret,
        new OAuthSecretData(accessToken: 'a2', refreshToken: 'r1', scopes: ['drive.readonly']),
        Carbon::now()->addHour(),
    );

    // Same row id, no append.
    expect(VaultSecret::count())->toBe(1)
        ->and($refreshed->id)->toBe($secret->id)
        ->and($refreshed->fresh()->payload->accessToken)->toBe('a2')
        ->and($refreshed->fresh()->payload->refreshToken)->toBe('r1');
});

it('refreshOAuth applies a scheme-level refresher in place, keeping the refresh token', function () {
    $secret = $this->vault->put(
        $this->conduit,
        'oauth',
        new OAuthSecretData(accessToken: 'a1', refreshToken: 'r-keep', scopes: []),
        expiresAt: Carbon::now()->addMinutes(1),
    );

    $newExpiry = Carbon::now()->addHour();
    $result = $this->vault->refreshOAuth($secret, new StubOAuthTokenRefresher('a-fresh', $newExpiry));

    expect(VaultSecret::count())->toBe(1)
        ->and($result->id)->toBe($secret->id)
        ->and($result->fresh()->payload->accessToken)->toBe('a-fresh')
        ->and($result->fresh()->payload->refreshToken)->toBe('r-keep')
        ->and($result->fresh()->expires_at->toIso8601String())->toBe($newExpiry->toIso8601String());
});

it('lists active oauth secrets due for refresh by the plaintext expires_at', function () {
    // Due (expiring inside the skew window).
    $this->vault->put('c-due', 'oauth', new OAuthSecretData('a', 'r'), expiresAt: Carbon::now()->addSeconds(60));
    // Not due (far future).
    $this->vault->put('c-fresh', 'oauth', new OAuthSecretData('a', 'r'), expiresAt: Carbon::now()->addDays(1));
    // Api-key is never swept.
    $this->vault->put('c-key', 'api-key', new ApiKeySecretData('k'));

    $due = $this->vault->dueForRefresh(300);

    expect($due)->toHaveCount(1)
        ->and($due->first()->conduit_id)->toBe('c-due');
});
