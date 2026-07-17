<?php

namespace Rushing\AuthVault\Exceptions;

use RuntimeException;

/**
 * A refresh could not proceed — no refresh token on the secret, or the provider
 * rejected the grant (revoked token). The host maps this to a needs-reconnect state.
 */
class CannotRefreshException extends RuntimeException {}
