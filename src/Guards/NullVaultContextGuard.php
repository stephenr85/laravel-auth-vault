<?php

namespace Rushing\AuthVault\Guards;

use Rushing\AuthVault\Contracts\VaultContextGuard;

/**
 * The packaged default: no partition assertion. Single-tenant / un-partitioned consumers
 * (and the package's own test bench) keep today's behavior — the vault stores and reads
 * with no context requirement. A multi-tenant host overrides this binding with a guard
 * that asserts its partition context (e.g. a resolved tenant schema) is established.
 */
class NullVaultContextGuard implements VaultContextGuard
{
    public function assert(string $conduitId, string $operation): void
    {
        // Intentionally permissive — see the class docblock.
    }
}
