<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

/**
 * Package marker for every exception this theme throws.
 *
 * A caller that does not know an individual condition can still catch the
 * theme's failures without catching anything from another package.
 */
interface ThemeException extends \Throwable
{
}
