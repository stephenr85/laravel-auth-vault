<?php

namespace Rushing\AuthVault\Contracts;

use Rushing\AuthVault\Exceptions\VaultContextException;

/**
 * Defense-in-depth backstop for the vault's placement-based isolation.
 *
 * The vault stores each secret in its owner's physical partition (per ADR-0084, a
 * per-tenant Postgres schema) and relies on that placement — not a policy column — for
 * cross-tenant isolation. That is correct-by-construction *as long as* every read runs
 * inside a resolved owner context. The one residual gap (V1): a read that runs with NO
 * context established falls through to the default partition (e.g. Postgres `public`),
 * and a caller who knows a `conduit_id` then resolves whatever lives there.
 *
 * This guard closes that gap without adding an ownership column (which ADR-0084 rejects).
 * A host binds an implementation that asserts a partition context is actually established
 * before a `conduit_id` lookup resolves; the packaged default is a no-op so single-tenant
 * / un-partitioned consumers keep today's behavior. The vault calls {@see assert()} on
 * every read/write that resolves a secret by `conduit_id`.
 */
interface VaultContextGuard
{
    /**
     * Assert a partition context is established for a secret access. Implementations
     * throw {@see VaultContextException} when the current context would let a lookup
     * resolve against an unscoped/foreign partition.
     *
     * @param  string  $conduitId  the owning subject being resolved
     * @param  string  $operation  'read' | 'write' — the access kind, for audit/telemetry
     *
     * @throws VaultContextException when no safe partition context is established
     */
    public function assert(string $conduitId, string $operation): void;
}
