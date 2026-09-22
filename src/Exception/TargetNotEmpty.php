<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * The target directory exists and is not empty.
 *
 * This is a refusal, not a failure: the generator decides it before any file
 * is written, and a caller that treats it as a failure would retry the same
 * refusal forever.
 */
final class TargetNotEmpty extends \RuntimeException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function at(string $path): self
    {
        return new self(sprintf('Target directory already exists and is not empty: %s', $path));
    }
}
