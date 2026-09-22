<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * The command line was invoked without the arguments the scaffold requires.
 */
final class InvalidInvocation extends \InvalidArgumentException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function missingSlug(): self
    {
        return new self('A theme slug is required: mahout new <slug>');
    }

    public static function emptyFlag(string $name): self
    {
        return new self(sprintf('The --%s flag carries no value', $name));
    }

    public static function missingPath(): self
    {
        return new self('A path to a generated theme directory is required: mahout drift <theme-dir>');
    }

    public static function undeclaredIdentity(string $path): self
    {
        return new self(sprintf('%s declares no text domain in style.css; the drift gate cannot establish the theme identity', $path));
    }
}
