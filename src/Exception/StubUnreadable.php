<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * A stub file or directory could not be read during generation.
 */
final class StubUnreadable extends \RuntimeException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function at(string $path): self
    {
        return new self(sprintf('Could not read stub path: %s', $path));
    }
}
