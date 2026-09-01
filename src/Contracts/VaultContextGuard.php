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
 * context established **resolves against the default partition** (e.g. Postgres `public`),
 * and a caller who knows a `conduit_id` then resolves whatever lives there.
 *
 * That gap is **host-conditional**, and the condition is where the table was published. It
 * is live at a host that puts an un-namespaced `vault_secrets` table in the default partition —
 * publishing this package's own migration stub centrally does exactly that. It is NOT live
 * at a host that publishes the table per-partition only: there, an uncontexted read finds no
 * relation at all and raises `SQLSTATE[42P01]` (measured 2026-08-31 at
 * `~/Herd/splicewire-app` — the table is in every tenant schema and in none of `public`).
 * Note also that there is no *fall-through* in this direction: on Postgres a `search_path`
 * of `"<tenant_schema>,public"` falls through outward, tenant → public, never inward.
 * So the guard is a fail-closed precondition whose value is conditional on host config —
 * which is the argument for keeping it, not for removing it.
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
