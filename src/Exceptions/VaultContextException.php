<?php

namespace Rushing\AuthVault\Exceptions;

use RuntimeException;
use Rushing\AuthVault\Contracts\VaultContextGuard;

/**
 * Thrown by a {@see VaultContextGuard} when a vault access
 * would resolve a secret without an established owner-partition context — the residual
 * cross-partition read gap the guard exists to close.
 */
class VaultContextException extends RuntimeException
{
    public static function noContext(string $conduitId, string $operation): self
    {
        return new self(sprintf(
            'Refusing vault %s for conduit [%s]: no owner-partition context is established '
            .'(the lookup would resolve against an unscoped partition).',
            $operation,
            $conduitId,
        ));
    }
}
