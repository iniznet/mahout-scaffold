<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

/**
 * The render boundary was reached before the composition root booted.
 *
 * functions.php calls Bootstrap::run() before any template loads, so this is
 * an invariant break, not an expected absence.
 */
final class NotBooted extends \LogicException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function beforeRender(): self
    {
        return new self('The render boundary was reached before Bootstrap::run() booted the kernel.');
    }
}
