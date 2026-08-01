<?php

namespace Rushing\AuthVault\Tests\Support;

use Rushing\AuthVault\Contracts\VaultContextGuard;
use Rushing\AuthVault\Exceptions\VaultContextException;

/**
 * A test double modeling a host's partition guard: it knows which owner partition is
 * currently "entered" (analogous to the tenant schema on the Postgres search_path) and
 * refuses any vault access when no partition is entered. Proves the guard closes the V1
 * gap without the package needing to know what a "tenant" is.
 */
class PartitionScopedContextGuard implements VaultContextGuard
{
    private ?string $enteredPartition = null;

    /** conduit_id => the partition that legitimately owns it. */
    private array $ownership = [];

    public function enter(?string $partition): void
    {
        $this->enteredPartition = $partition;
    }

    public function own(string $conduitId, string $partition): void
    {
        $this->ownership[$conduitId] = $partition;
    }

    public function assert(string $conduitId, string $operation): void
    {
        if ($this->enteredPartition === null) {
            throw VaultContextException::noContext($conduitId, $operation);
        }
    }
}
