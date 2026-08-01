<?php

namespace Rushing\AuthVault\Events;

use Rushing\AuthVault\AuthVault;

/**
 * Fired whenever {@see AuthVault::active()} resolves (or fails to
 * resolve) an active secret by conduit_id. The read-audit hook: a host listens and writes
 * an audit-log row, so every secret read is attributable — cheap to leave unlistened.
 * Carries no plaintext (only the owning subject, scheme, and hit/miss).
 */
class VaultSecretResolved
{
    public function __construct(
        public string $conduitId,
        public string $scheme,
        public bool $found,
    ) {}
}
