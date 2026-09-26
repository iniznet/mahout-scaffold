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

    public static function undeclaredIdentity(string $path, string $file): self
    {
        return new self(sprintf('%s declares no text domain in %s; the drift gate cannot establish the host identity', $path, $file));
    }

    /**
     * A theme always composes a mode; the default is the CLI's to supply, not the
     * layer tree's to invent.
     */
    public static function modeRequired(string $host): self
    {
        return new self(sprintf('--host=%s composes a template mode, so --mode must name one.', $host));
    }

    /**
     * The refusal exists because a flag that changes nothing is a flag that lies.
     */
    public static function modeWithoutTheme(string $host): self
    {
        return new self(sprintf('a template mode is a layer of the hierarchy a theme owns; --host=%s has no part in it, so drop --mode and render through a block or a route', $host));
    }
}
