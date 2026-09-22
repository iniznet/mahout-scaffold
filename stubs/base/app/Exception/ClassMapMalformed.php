<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

/**
 * The build's classmap is not the documented shape: an object of names to
 * class-name strings. A corrupt build artifact is a broken build, and the
 * theme refuses rather than guessing class names.
 */
final class ClassMapMalformed extends \RuntimeException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function because(string $reason): self
    {
        return new self(sprintf('The build classmap is malformed: %s', $reason));
    }

    public static function becauseJson(\JsonException $exception): self
    {
        return new self(sprintf('The build classmap is not valid JSON: %s', $exception->getMessage()));
    }
}
