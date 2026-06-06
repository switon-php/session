<?php

declare(strict_types=1);

namespace Switon\Session;

/**
 * Base exception for session component failures.
 *
 * Use when session-specific failures need one package-level exception root.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\SessionInterface
 * @see \Switon\Http\Exception
 */
class Exception extends \Switon\Http\Exception
{
}
