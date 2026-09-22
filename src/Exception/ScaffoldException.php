<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * Package marker for every exception this package throws.
 *
 * A caller that does not know an individual condition can still catch the
 * scaffold's failures without catching anything from another package.
 */
interface ScaffoldException extends \Throwable
{
}
