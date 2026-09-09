<?php

use Rushing\AuthVault\AuthVaultServiceProvider;

/**
 * This package hand-rolls its publish destination instead of going through
 * spatie/laravel-package-tools, so it also has to hand-roll package-tools'
 * same-stem lookup. Without it `publishedMigrationPath()` minted a fresh
 * timestamp on every call, and since `publishes()` runs at boot, a host that
 * already tracked this migration got a SECOND file creating the same table on
 * every `composer setup`.
 */
function resolveVaultPublishPath(): string
{
    $provider = new AuthVaultServiceProvider(app());
    $method = new ReflectionMethod($provider, 'publishedMigrationPath');
    $method->setAccessible(true);

    return $method->invoke($provider);
}

function tenantMigrationDir(): string
{
    $dir = database_path('migrations/tenant');

    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

it('resolves onto a migration the host already has, rather than minting a second one', function () {
    $existing = tenantMigrationDir().'/2026_08_30_231652_create_vault_secrets_table.php';
    file_put_contents($existing, '<?php // host copy');

    expect(resolveVaultPublishPath())->toBe($existing);

    unlink($existing);
});

it('picks the EARLIEST copy when a host has already accumulated duplicates', function () {
    $dir = tenantMigrationDir();
    $first = $dir.'/2026_08_30_231652_create_vault_secrets_table.php';
    $second = $dir.'/2026_09_09_134635_create_vault_secrets_table.php';
    file_put_contents($first, '<?php // host copy');
    file_put_contents($second, '<?php // the duplicate this bug produced');

    expect(resolveVaultPublishPath())->toBe($first);

    unlink($first);
    unlink($second);
});

it('mints a timestamped name when the host has no copy at all', function () {
    $dir = tenantMigrationDir();

    foreach ((array) glob($dir.'/*_create_vault_secrets_table.php') as $stale) {
        unlink($stale);
    }

    expect(resolveVaultPublishPath())
        ->toMatch('#/\d{4}_\d{2}_\d{2}_\d{6}_create_vault_secrets_table\.php$#');
});
