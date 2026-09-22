<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * A stub layer directory the generator needs is not on disk.
 */
final class StubLayerMissing extends \RuntimeException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forLayer(string $name, string $path): self
    {
        return new self(sprintf('Stub layer "%s" is missing at %s', $name, $path));
    }
}
